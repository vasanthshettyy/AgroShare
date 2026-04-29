<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../src/Helpers/audit.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../bookings.php');
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token.');
    header('Location: ../bookings.php');
    exit();
}

$bookingId = (int)($_POST['booking_id'] ?? 0);

if ($bookingId > 0) {
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'confirmed', 'active')");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Free up equipment
            $eqStmt = $conn->prepare("UPDATE equipment SET is_available = 1 WHERE id = (SELECT equipment_id FROM bookings WHERE id = ?)");
            $eqStmt->bind_param('i', $bookingId);
            $eqStmt->execute();

            logAuditEvent($conn, 'admin_cancel_booking', $bookingId, "Admin forcibly cancelled booking $bookingId", null, $_SESSION['user_id']);
            setFlash('success', "Booking forcibly cancelled.");
        } else {
            setFlash('error', "Could not cancel booking. It may already be completed or cancelled.");
        }
    } catch (Exception $e) {
        setFlash('error', "Database error: " . $e->getMessage());
    }
}

header('Location: ../bookings.php');
exit();
