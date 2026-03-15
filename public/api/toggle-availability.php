<?php
/**
 * toggle-availability.php — AJAX endpoint for Equipment availability toggle.
 *
 * Expects POST with: equipment_id
 * Returns JSON: { success: true/false, message: "...", is_available: 0|1 }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';

session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit();
}

// Validate input
$equipmentId = (int)($_POST['equipment_id'] ?? 0);
if ($equipmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID.']);
    exit();
}

$ownerId = (int)$_SESSION['user_id'];
$result  = toggleAvailability($conn, $equipmentId, $ownerId);

if ($result !== null) {
    echo json_encode([
        'success'      => true,
        'message'      => $result === 1 ? 'Equipment is now available.' : 'Equipment is now unavailable.',
        'is_available' => $result,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Could not update. You may not own this equipment.',
    ]);
}
