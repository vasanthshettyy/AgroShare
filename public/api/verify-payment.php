<?php
/**
 * verify-payment.php — Owner verifies receipt of payment for a booking.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh the page.']);
    exit();
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking reference.']);
    exit();
}

$result = verifyOwnerPayment($conn, $bookingId, (int)$_SESSION['user_id']);
echo json_encode($result);
