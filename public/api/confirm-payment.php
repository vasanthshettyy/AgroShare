<?php
/**
 * confirm-payment.php — Renter confirms payment completion for a booking.
 */

// Use output buffering to catch any accidental output/warnings
ob_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

// Helper to send JSON and exit
function sendJsonResponse(array $data, int $statusCode = 200) {
    ob_clean(); // Discard any buffered output
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    sendJsonResponse(['success' => false, 'message' => 'Security check failed. Please refresh the page.'], 403);
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$reference = trim($_POST['payment_reference'] ?? '');

if ($bookingId <= 0) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid booking reference.']);
}

try {
    $result = confirmBookingPayment($conn, $bookingId, (int)$_SESSION['user_id'], $reference);
    sendJsonResponse($result);
} catch (Exception $e) {
    logError('confirm-payment API error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An internal error occurred.'], 500);
}
