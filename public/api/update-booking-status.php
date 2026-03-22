<?php
/**
 * update-booking-status.php — AJAX endpoint for managing booking states.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$bookingId = (int)($_POST['id'] ?? 0);
$newStatus = $_POST['status'] ?? '';
$csrf      = $_POST['csrf_token'] ?? '';

if (!validateCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security check failed.']);
    exit();
}

if (updateBookingStatus($conn, $bookingId, (int)$_SESSION['user_id'], $newStatus)) {
    echo json_encode(['success' => true, 'message' => "Booking " . ucfirst($newStatus) . " successfully."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid transition or permission denied.']);
}
