<?php
/**
 * create-booking.php — Refined AJAX endpoint for Module 5.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh.']);
    exit();
}

$eqId  = (int)($_POST['equipment_id'] ?? 0);
$start = $_POST['start_datetime'] ?? '';
$end   = $_POST['end_datetime'] ?? '';
$userId = (int)$_SESSION['user_id'];

if ($eqId <= 0 || !$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'Missing details.']);
    exit();
}

// 1. Conflict Check (Server-side)
if (hasBookingConflict($conn, $eqId, $start, $end)) {
    echo json_encode(['success' => false, 'message' => 'Dates unavailable. Someone else booked these slots.']);
    exit();
}

// 2. Pricing (Server-side calculation only)
$totalPrice = calculateServerSidePrice($conn, $eqId, $start, $end);

// 3. Get Owner ID
$stmt = $conn->prepare("SELECT owner_id FROM equipment WHERE id = ?");
$stmt->bind_param('i', $eqId);
$stmt->execute();
$ownerId = $stmt->get_result()->fetch_column();

if ($ownerId == $userId) {
    echo json_encode(['success' => false, 'message' => 'You cannot book your own equipment.']);
    exit();
}

// 4. Insert
$sql = "INSERT INTO bookings (equipment_id, renter_id, owner_id, start_datetime, end_datetime, total_price, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiisss', $eqId, $userId, $ownerId, $start, $end, $totalPrice);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Booking request sent successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
