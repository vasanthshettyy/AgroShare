<?php
/**
 * create-equipment.php — AJAX endpoint for creating equipment.
 *
 * Expects: POST with FormData (multipart/form-data) including csrf_token.
 * Returns: JSON { success: bool, message: string, equipment_id?: int, card_html?: string }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

// CSRF check
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid form submission. Please refresh and try again.']);
    exit();
}

// Validate form data
$formData = $_POST;
$errors   = validateEquipmentData($formData);

// Process images
$imageResult = ['paths' => [], 'errors' => []];
if (!empty($_FILES['images']['name'][0])) {
    $imageResult = processImageUploads($_FILES['images']);
    if (!empty($imageResult['errors'])) {
        $errors['images'] = implode(' ', $imageResult['errors']);
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

// Create
$formData['owner_id'] = $_SESSION['user_id'];
$newId = createEquipment($conn, $formData, $imageResult['paths']);

if (!$newId) {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
    exit();
}

// Fetch the created equipment to build the card HTML for DOM insertion
$eq = getEquipmentById($conn, $newId);
if (!$eq) {
    echo json_encode(['success' => false, 'message' => 'Equipment created but could not be loaded.']);
    exit();
}
$images    = $eq['images'] ? json_decode($eq['images'], true) : [];
$thumbnail = !empty($images) ? htmlspecialchars($images[0], ENT_QUOTES, 'UTF-8') : '';

// Build card HTML string
$cardHtml = '<a href="equipment-detail.php?id=' . (int)$eq['id'] . '" class="eq-card" style="animation-delay:0s;">';
$cardHtml .= '<div class="eq-card-image">';
if ($thumbnail) {
    $cardHtml .= '<img src="' . $thumbnail . '" alt="' . htmlspecialchars($eq['title'], ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
} else {
    $cardHtml .= '<div class="eq-card-placeholder" aria-hidden="true">';
    $cardHtml .= '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>';
    $cardHtml .= '</div>';
}
$isAvailable = $eq['is_available'] ?? true;
$badgeClass = $isAvailable ? 'available' : 'unavailable';
$badgeText = $isAvailable ? 'Available' : 'Unavailable';
$cardHtml .= '<span class="eq-card-badge ' . $badgeClass . '">' . $badgeText . '</span>';
$cardHtml .= '</div>';

$cardHtml .= '<div class="eq-card-body">';
$cardHtml .= '<span class="eq-card-category">' . htmlspecialchars(ucfirst($eq['category']), ENT_QUOTES, 'UTF-8') . '</span>';
$cardHtml .= '<h3 class="eq-card-title">' . htmlspecialchars($eq['title'], ENT_QUOTES, 'UTF-8') . '</h3>';
$cardHtml .= '<p class="eq-card-location">';
$cardHtml .= '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
$cardHtml .= htmlspecialchars($eq['location_village'], ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($eq['location_district'], ENT_QUOTES, 'UTF-8');
$cardHtml .= '</p>';
$cardHtml .= '<div class="eq-card-pricing">';
$cardHtml .= '<span class="eq-card-price">₹' . number_format($eq['price_per_day'], 0) . '<small>/day</small></span>';
$cardHtml .= '<span class="eq-card-price-hourly">₹' . number_format($eq['price_per_hour'], 0) . '/hr</span>';
$cardHtml .= '</div></div>';

$cardHtml .= '<div class="eq-card-footer">';
$cardHtml .= '<div class="eq-card-owner"><span class="eq-card-owner-name">' . htmlspecialchars($eq['owner_name'], ENT_QUOTES, 'UTF-8') . '</span></div>';
if ($eq['includes_operator']) {
    $cardHtml .= '<span class="eq-card-operator-badge">+ Operator</span>';
}
$cardHtml .= '</div></a>';

// Generate a fresh CSRF token for the next submission
$newToken = generateCsrfToken();

echo json_encode([
    'success'      => true,
    'message'      => 'Equipment listed successfully!',
    'equipment_id' => $newId,
    'card_html'    => $cardHtml,
    'new_csrf'     => $newToken,
]);
