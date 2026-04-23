<?php
/**
 * raise_dispute.php — AJAX endpoint for renters to dispute a completed booking.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security check failed.']);
    exit();
}

try {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $renterId  = (int)$_SESSION['user_id'];

    if ($bookingId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
        exit();
    }

    // Only allow renters to dispute completed bookings
    $stmt = $conn->prepare("UPDATE bookings SET status = 'disputed' WHERE id = ? AND renter_id = ? AND status = 'completed'");
    $stmt->bind_param('ii', $bookingId, $renterId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Dispute raised successfully. An admin will review it.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not raise dispute. Ensure the booking is completed.']);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
