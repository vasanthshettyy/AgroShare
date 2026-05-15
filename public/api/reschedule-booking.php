<?php
/**
 * api/reschedule-booking.php — Standardized endpoint for Module 15 (Rescheduling).
 * 
 * Contract:
 * - Method: POST
 * - Input: booking_id, new_start_datetime, new_end_datetime, csrf_token
 * - Output: JSON { success, message, data: { old_booking_id, new_booking_id, new_status, old_status } }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

// 1. Method Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// 3. CSRF Validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh the page.']);
    exit();
}

// 4. Input Extraction & Basic Validation
$bookingId = (int)($_POST['booking_id'] ?? 0);
$newStart  = $_POST['new_start_datetime'] ?? '';
$newEnd    = $_POST['new_end_datetime'] ?? '';
$userId    = (int)$_SESSION['user_id'];

if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking reference.']);
    exit();
}

if (empty($newStart) || empty($newEnd)) {
    echo json_encode(['success' => false, 'message' => 'New start and end dates are required.']);
    exit();
}

// 5. Date Logic Pre-check (Chronology)
$startTime = strtotime($newStart);
$endTime   = strtotime($newEnd);

if (!$startTime || !$endTime) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format provided.']);
    exit();
}

if ($endTime <= $startTime) {
    echo json_encode(['success' => false, 'message' => 'End date must be after start date.']);
    exit();
}

if ($startTime < time()) {
    echo json_encode(['success' => false, 'message' => 'Cannot reschedule to a past date.']);
    exit();
}

// 6. Process Reschedule via Controller
try {
    $result = rescheduleBooking($conn, $bookingId, $userId, $newStart, $newEnd);

    if ($result['success']) {
        // Success response matches requested contract
        echo json_encode([
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
        // (Actor forbidden, payment confirmed, or date conflicts)
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    logError('API reschedule-booking fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error. Our team has been notified.']);
}
