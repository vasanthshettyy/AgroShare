<?php
/**
 * process_escrow_payment.php — Module 12.D Escrow payment business logic.
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
    $transactionId = $_POST['transaction_id'] ?? '';
    $userId = (int)$_SESSION['user_id'];

    // A) Validate transaction_id format
    if (empty($transactionId)) {
        echo json_encode(['success' => false, 'message' => 'Transaction ID is required.']);
        exit();
    }

    if (!str_starts_with($transactionId, 'TXN-')) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction reference.']);
        exit();
    }

    // B) Load transaction row with lock
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE transaction_id = ? FOR UPDATE");
        $stmt->bind_param('s', $transactionId);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();

        if (!$txn) {
            throw new Exception('Transaction not found.');
        }

        if ((int)$txn['renter_id'] !== $userId) {
            throw new Exception('Unauthorized. Only the renter can lock funds for this transaction.');
        }

        if ($txn['booking_type'] !== 'ESCROW') {
            throw new Exception('Invalid booking type for escrow payment.');
        }

        if ($txn['status'] !== 'PENDING_PAYMENT') {
            throw new Exception('Invalid state transition. Payment is only allowed for PENDING_PAYMENT status.');
        }

        // C) Simulate payment success & generate handover_otp
        $handoverOtp = random_int(1000, 9999);

        // D) Atomic update
        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'FUNDS_LOCKED', handover_otp = ?, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?");
        $updateTxn->bind_param('is', $handoverOtp, $transactionId);
        $updateTxn->execute();

        // Sync with Bookings: set to confirmed
        $updateBooking = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE transaction_id = ?");
        $updateBooking->bind_param('s', $transactionId);
        $updateBooking->execute();

        // Mark equipment as unavailable (rental confirmed)
        $updateEq = $conn->prepare("UPDATE equipment SET is_available = 0 WHERE id = ?");
        $updateEq->bind_param('i', $txn['equipment_id']);
        $updateEq->execute();

        // Optional: Get equipment title for notifications
        $eqStmt = $conn->prepare("SELECT title FROM equipment WHERE id = ?");
        $eqStmt->bind_param('i', $txn['equipment_id']);
        $eqStmt->execute();
        $eqTitle = $eqStmt->get_result()->fetch_column() ?: 'Equipment';

        // Notifications
        createNotification($conn, $txn['renter_id'], "Funds locked successfully for transaction $transactionId ($eqTitle).");
        createNotification($conn, $txn['owner_id'], "Escrow booking for '$eqTitle' is funded and ready for handover scheduling. Transaction: $transactionId.");

        $conn->commit();

        // E) RESPONSE PAYLOAD
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful. Funds are locked in escrow.',
            'data' => [
                'transaction_id'    => $transactionId,
                'status'            => 'FUNDS_LOCKED',
                'next_step'         => 'VERIFY_HANDOVER',
                'handover_otp_hint' => 'Generated successfully',
                'demo_handover_otp' => (string)$handoverOtp // Included for development/demo convenience
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
