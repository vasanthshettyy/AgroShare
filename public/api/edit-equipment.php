<?php
/**
 * edit-equipment.php — AJAX endpoint for editing equipment.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

// Equipment ID
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID.']);
    exit();
}

// CSRF check
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid form submission. Please refresh and try again.']);
    exit();
}

// Load equipment to check ownership
$eq = getEquipmentById($conn, $id);
if (!$eq || (int)$eq['owner_id'] !== (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or not found.']);
    exit();
}

$existingImages = $eq['images'] ? json_decode($eq['images'], true) : [];

// Validate form data
$formData = $_POST;
$errors   = validateEquipmentData($formData);

// Handle image removals
$removedImages = $_POST['remove_images'] ?? [];
$keptImages    = array_values(array_diff($existingImages, $removedImages));

// Process new uploads
$newImagePaths = [];
if (!empty($_FILES['images']['name'][0])) {
    $totalAfter = count($keptImages) + count(array_filter($_FILES['images']['name']));
    if ($totalAfter > MAX_IMAGES) {
        $errors['images'] = 'Maximum ' . MAX_IMAGES . ' images allowed total.';
    } else {
        $imageResult = processImageUploads($_FILES['images']);
        if (!empty($imageResult['errors'])) {
            $errors['images'] = implode(' ', $imageResult['errors']);
        }
        $newImagePaths = $imageResult['paths'];
    }
}

// Return validation errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fix the errors below.',
        'errors'  => $errors,
    ]);
    exit();
}

// Merge kept + new images
$finalImages = array_merge($keptImages, $newImagePaths);
$imagesJson  = !empty($finalImages) ? json_encode($finalImages) : null;

// Delete removed images from disk
if (!empty($removedImages)) {
    deleteEquipmentImages($removedImages);
}

// Update
$updated = updateEquipment($conn, $id, (int)$_SESSION['user_id'], $formData, $imagesJson);

if (!$updated) {
    echo json_encode(['success' => false, 'message' => 'Could not update equipment.']);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Equipment updated successfully!',
    'new_csrf' => generateCsrfToken(),
]);
