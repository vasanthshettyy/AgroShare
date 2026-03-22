<?php
/**
 * delete-equipment.php — AJAX endpoint for deleting equipment.
 *
 * Expects: POST with id and csrf_token.
 * Returns: JSON { success: bool, message: string }
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

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID.']);
    exit();
}

$ownerId = (int)$_SESSION['user_id'];

// deleteEquipment handles:
// 1. Ownership check
// 2. Fetching image paths from DB
// 3. Deleting image files from disk using unlink()
// 4. Deleting the database row
$deleted = deleteEquipment($conn, $id, $ownerId);

if ($deleted) {
    echo json_encode(['success' => true, 'message' => 'Equipment and associated images deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not delete equipment. You may not be the owner.']);
}
