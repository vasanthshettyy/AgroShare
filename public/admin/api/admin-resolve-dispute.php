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
        // Resolve dispute by moving status back to completed, noting admin override
        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed', admin_override = 1, admin_override_reason = 'Dispute Resolved manually' WHERE id = ? AND status = 'disputed'");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            logAuditEvent($conn, 'admin_resolve_dispute', $bookingId, "Admin marked disputed booking $bookingId as resolved/completed", null, $_SESSION['user_id']);
            setFlash('success', "Dispute resolved successfully.");
        } else {
            setFlash('error', "Could not resolve dispute. Ensure the booking is currently disputed.");
        }
        $stmt->close();
    } catch (Exception $e) {
        setFlash('error', "Database error: " . $e->getMessage());
    }
}

header('Location: ../bookings.php');
exit();
