<?php
/**
 * api/reschedule-booking.php — Standardized endpoint for Module 15 (Rescheduling).
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

// 1. Method Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

// 3. CSRF Validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    sendJsonResponse(['success' => false, 'message' => 'Security check failed. Please refresh the page.'], 403);
}

// 4. Input Extraction & Basic Validation
$bookingId = (int)($_POST['booking_id'] ?? 0);
$newStart  = $_POST['new_start_datetime'] ?? '';
$newEnd    = $_POST['new_end_datetime'] ?? '';
$userId    = (int)$_SESSION['user_id'];

if ($bookingId <= 0) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid booking reference.']);
}

if (empty($newStart) || empty($newEnd)) {
    sendJsonResponse(['success' => false, 'message' => 'New start and end dates are required.']);
}

// 5. Date Logic Pre-check (Chronology)
$startTime = strtotime($newStart);
$endTime   = strtotime($newEnd);

if (!$startTime || !$endTime) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid date format provided.']);
}

if ($endTime <= $startTime) {
    sendJsonResponse(['success' => false, 'message' => 'End date must be after start date.']);
}

if ($startTime < time()) {
    sendJsonResponse(['success' => false, 'message' => 'Cannot reschedule to a past date.']);
}

// 6. Process Reschedule via Controller
try {
    $result = rescheduleBooking($conn, $bookingId, $userId, $newStart, $newEnd);

    if ($result['success']) {
        // Success response matches requested contract
        sendJsonResponse([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'old_booking_id' => $bookingId,
                'new_booking_id' => $result['new_booking_id'],
                'old_status'     => 'rescheduled',
                'new_status'     => 'pending'
            ]
        ]);
    } else {
        // Handle specific business logic failures from controller
        sendJsonResponse([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    logError('API reschedule-booking fatal error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Internal server error. Our team has been notified.'], 500);
}
