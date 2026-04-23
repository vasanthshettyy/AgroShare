<?php
/**
 * initiate_booking.php — Standard Booking Initiation Flow.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

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
    $eqId        = (int)($_POST['equipment_id'] ?? 0);
    $startStr    = $_POST['start_datetime'] ?? '';
    $endStr      = $_POST['end_datetime'] ?? '';
    $renterId    = (int)$_SESSION['user_id'];

    // A) Basic Validation
    if ($eqId <= 0 || empty($startStr) || empty($endStr)) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid details.']);
        exit();
    }

    $startTime = strtotime($startStr);
    $endTime   = strtotime($endStr);

    if ($startTime === false || $endTime === false || $endTime <= $startTime) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates. End date must be after start date.']);
        exit();
    }

    // B) Load Equipment & Ownership Check
    $stmt = $conn->prepare("SELECT owner_id, price_per_day, is_available, title FROM equipment WHERE id = ?");
    $stmt->bind_param('i', $eqId);
    $stmt->execute();
    $eq = $stmt->get_result()->fetch_assoc();

    if (!$eq) {
        echo json_encode(['success' => false, 'message' => 'Equipment not found.']);
        exit();
    }

    if ((int)$eq['owner_id'] === $renterId) {
        echo json_encode(['success' => false, 'message' => 'You cannot book your own equipment.']);
        exit();
    }

    if ((int)$eq['is_available'] !== 1) {
        echo json_encode(['success' => false, 'message' => 'Equipment is currently unavailable.']);
        exit();
    }

    $ownerId = (int)$eq['owner_id'];
    $pricePerDay = (float)$eq['price_per_day'];
    $eqTitle = $eq['title'];

    // C) Overlap Prevention (Pre-check)
    if (hasBookingConflict($conn, $eqId, $startStr, $endStr)) {
        echo json_encode(['success' => false, 'message' => 'Dates unavailable. Someone else booked these slots.']);
        exit();
    }

    // D) Calculate Amount
    $durationHours = ($endTime - $startTime) / 3600;
    $dayCount = max(1, (int)ceil($durationHours / 24));
    $amount = $dayCount * $pricePerDay;

    // E) Atomic DB Transaction
    $conn->begin_transaction();

    try {
        // Re-check overlap inside transaction for race protection
        $conflictStmt = $conn->prepare("SELECT id FROM bookings WHERE equipment_id = ? AND status IN ('pending', 'confirmed', 'active') AND start_datetime < ? AND end_datetime > ? FOR UPDATE");
        $conflictStmt->bind_param('iss', $eqId, $endStr, $startStr);
        $conflictStmt->execute();
        if ($conflictStmt->get_result()->num_rows > 0) {
            throw new Exception('Dates unavailable (concurrent booking).');
        }
        $conflictStmt->close();

        // Insert into Bookings
        $bookingStatus = 'pending'; // Default to pending until owner approves
        $bookingStmt = $conn->prepare("INSERT INTO bookings (equipment_id, renter_id, owner_id, start_datetime, end_datetime, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $bookingStmt->bind_param('iiissds', $eqId, $renterId, $ownerId, $startStr, $endStr, $amount, $bookingStatus);
        $bookingStmt->execute();
        $bookingStmt->close();

        $conn->commit();

        // G) Notification hooks
        createNotification($conn, $ownerId, "New booking request for '$eqTitle' from renter. Please coordinate.");

        // Fetch owner contact
        $ownerStmt = $conn->prepare("SELECT full_name, phone, email FROM users WHERE id = ?");
        $ownerStmt->bind_param('i', $ownerId);
        $ownerStmt->execute();
        $owner = $ownerStmt->get_result()->fetch_assoc();
        $ownerStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Booking request initiated. Contact owner directly.',
            'data' => [
                'amount'         => $amount,
                'owner_contact'  => [
                    'name'  => $owner['full_name'],
                    'phone' => $owner['phone'],
                    'email' => $owner['email']
                ],
                'next_step'      => 'SHOW_OWNER_CONTACT'
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Server error during transaction.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
