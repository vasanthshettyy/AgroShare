<?php
/**
 * initiate_booking.php — Module 12.C Full Business Logic for Escrow Flow.
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
    $bookingType = $_POST['booking_type'] ?? '';
    $startStr    = $_POST['start_datetime'] ?? '';
    $endStr      = $_POST['end_datetime'] ?? '';
    $renterId    = (int)$_SESSION['user_id'];

    // A) Basic Validation
    if ($eqId <= 0 || !in_array($bookingType, ['ESCROW', 'MANUAL'], true) || empty($startStr) || empty($endStr)) {
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

        // Generate Transaction ID
        $txnId = '';
        $isUnique = false;
        $retries = 0;
        while (!$isUnique && $retries < 5) {
            $txnId = 'TXN-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
            $checkTxn = $conn->prepare("SELECT transaction_id FROM transactions WHERE transaction_id = ?");
            $checkTxn->bind_param('s', $txnId);
            $checkTxn->execute();
            if ($checkTxn->get_result()->num_rows === 0) {
                $isUnique = true;
            }
            $checkTxn->close();
            $retries++;
        }

        if (!$isUnique) {
            throw new Exception('Failed to generate a unique transaction ID.');
        }

        // Insert into Transactions
        $txnStatus = ($bookingType === 'ESCROW') ? 'PENDING_PAYMENT' : 'MANUAL_DEAL_INITIATED';
        $txnStmt = $conn->prepare("INSERT INTO transactions (transaction_id, equipment_id, renter_id, owner_id, booking_type, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $txnStmt->bind_param('siiisds', $txnId, $eqId, $renterId, $ownerId, $bookingType, $amount, $txnStatus);
        $txnStmt->execute();
        $txnStmt->close();

        // Insert into Bookings
        $bookingStatus = ($bookingType === 'ESCROW') ? 'pending' : 'confirmed';
        $bookingStmt = $conn->prepare("INSERT INTO bookings (transaction_id, equipment_id, renter_id, owner_id, start_datetime, end_datetime, pricing_mode, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'daily', ?, ?)");
        $bookingStmt->bind_param('siiissds', $txnId, $eqId, $renterId, $ownerId, $startStr, $endStr, $amount, $bookingStatus);
        $bookingStmt->execute();
        $bookingStmt->close();

        $conn->commit();

        // G) Notification hooks
        if ($bookingType === 'ESCROW') {
            createNotification($conn, $ownerId, "New Escrow booking initiated for '$eqTitle'. Awaiting payment from renter.");
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking initiated. Proceed to lock funds.',
                'data' => [
                    'booking_type'   => 'ESCROW',
                    'transaction_id' => $txnId,
                    'amount'         => $amount,
                    'next_step'      => 'PROCESS_PAYMENT'
                ]
            ]);
        } else {
            createNotification($conn, $ownerId, "New Manual deal initiated for '$eqTitle'. Contact renter to coordinate.");

            // Fetch owner contact
            $ownerStmt = $conn->prepare("SELECT full_name, phone, email FROM users WHERE id = ?");
            $ownerStmt->bind_param('i', $ownerId);
            $ownerStmt->execute();
            $owner = $ownerStmt->get_result()->fetch_assoc();
            $ownerStmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Manual deal initiated. Contact owner directly.',
                'data' => [
                    'booking_type'   => 'MANUAL',
                    'transaction_id' => $txnId,
                    'amount'         => $amount,
                    'owner_contact'  => [
                        'name'  => $owner['full_name'],
                        'phone' => $owner['phone'],
                        'email' => $owner['email']
                    ],
                    'next_step'      => 'SHOW_OWNER_CONTACT'
                ]
            ]);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Server error during transaction.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
