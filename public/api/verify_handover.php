<?php
/**
 * verify_handover.php — Module 12.E-1 Escrow handover verification business logic.
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
    $submittedOtp  = $_POST['submitted_otp'] ?? '';
    $userId        = (int)$_SESSION['user_id'];

    // A) Validate inputs
    if (empty($transactionId) || !str_starts_with($transactionId, 'TXN-')) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction reference.']);
        exit();
    }

    if (!preg_match('/^\d{4}$/', $submittedOtp)) {
        echo json_encode(['success' => false, 'message' => 'Invalid handover PIN format. Must be 4 digits.']);
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

        if ($txn['booking_type'] !== 'ESCROW') {
            throw new Exception('Invalid booking type for escrow handover.');
        }

        // Check if user is part of the transaction
        if ((int)$txn['renter_id'] !== $userId && (int)$txn['owner_id'] !== $userId) {
            throw new Exception('Unauthorized access to this transaction.');
        }

        if ($txn['status'] !== 'FUNDS_LOCKED') {
            throw new Exception('Invalid state transition. Handover is only allowed when funds are locked.');
        }

        if ($txn['handover_otp'] === null) {
            throw new Exception('Handover PIN was not generated for this transaction.');
        }

        // C) OTP verification
        if ((int)$submittedOtp !== (int)$txn['handover_otp']) {
            throw new Exception('Invalid handover PIN.');
        }

        // D) On successful OTP: generate return_otp and update status
        $returnOtp = random_int(1000, 9999);

        // Update Transactions
        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'ACTIVE_RENTAL', return_otp = ?, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?");
        $updateTxn->bind_param('is', $returnOtp, $transactionId);
        $updateTxn->execute();

        // Sync with Bookings
        $updateBooking = $conn->prepare("UPDATE bookings SET status = 'active' WHERE transaction_id = ?");
        $updateBooking->bind_param('s', $transactionId);
        $updateBooking->execute();

        // Notifications
        $eqStmt = $conn->prepare("SELECT title FROM equipment WHERE id = ?");
        $eqStmt->bind_param('i', $txn['equipment_id']);
        $eqStmt->execute();
        $eqTitle = $eqStmt->get_result()->fetch_column() ?: 'Equipment';

        $notifMsg = "Handover verified for '$eqTitle'. Rental is now active. Transaction: $transactionId";
        createNotification($conn, $txn['renter_id'], $notifMsg);
        createNotification($conn, $txn['owner_id'], $notifMsg);

        $conn->commit();

        // E) RESPONSE PAYLOAD
        echo json_encode([
            'success' => true,
            'message' => 'Handover verified. Rental is now active.',
            'data' => [
                'transaction_id'  => $transactionId,
                'status'          => 'ACTIVE_RENTAL',
                'next_step'       => 'VERIFY_RETURN',
                'return_otp_hint' => 'Generated successfully',
                'demo_return_otp' => (string)$returnOtp // Included for development/demo convenience
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
