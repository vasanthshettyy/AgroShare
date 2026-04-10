<?php
/**
 * verify_return.php — Module 12.E-2 Escrow return verification and completion.
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
        echo json_encode(['success' => false, 'message' => 'Invalid return PIN format. Must be 4 digits.']);
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
            throw new Exception('Invalid booking type for escrow return.');
        }

        // Check if user is part of the transaction (renter or owner)
        if ((int)$txn['renter_id'] !== $userId && (int)$txn['owner_id'] !== $userId) {
            throw new Exception('Unauthorized access to this transaction.');
        }

        if ($txn['status'] !== 'ACTIVE_RENTAL') {
            throw new Exception('Invalid state transition. Return is only allowed for active rentals.');
        }

        if ($txn['return_otp'] === null) {
            throw new Exception('Return PIN was not generated for this transaction.');
        }

        // C) Verify OTP
        if ((int)$submittedOtp !== (int)$txn['return_otp']) {
            throw new Exception('Invalid return PIN.');
        }

        // D) On OTP match: Completion Side Effects
        
        // Update Transaction: status = COMPLETED, clear OTPs
        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'COMPLETED', handover_otp = NULL, return_otp = NULL, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?");
        $updateTxn->bind_param('s', $transactionId);
        $updateTxn->execute();

        // Update linked booking: status = completed
        $updateBooking = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE transaction_id = ?");
        $updateBooking->bind_param('s', $transactionId);
        $updateBooking->execute();

        // Equipment availability release: set is_available = 1
        $updateEq = $conn->prepare("UPDATE equipment SET is_available = 1 WHERE id = ?");
        $updateEq->bind_param('i', $txn['equipment_id']);
        $updateEq->execute();

        // Optional Audit Log
        logAuditEvent($conn, 'escrow_completed', (int)$txn['renter_id'], "Escrow transaction $transactionId completed successfully.");

        // Notifications
        $eqStmt = $conn->prepare("SELECT title FROM equipment WHERE id = ?");
        $eqStmt->bind_param('i', $txn['equipment_id']);
        $eqStmt->execute();
        $eqTitle = $eqStmt->get_result()->fetch_column() ?: 'Equipment';

        $notifMsg = "Rental return verified for '$eqTitle'. Transaction $transactionId is now completed and funds are released to the owner.";
        createNotification($conn, $txn['renter_id'], $notifMsg);
        createNotification($conn, $txn['owner_id'], $notifMsg);

        $conn->commit();

        // E) RESPONSE PAYLOAD
        echo json_encode([
            'success' => true,
            'message' => 'Return verified. Escrow transaction completed.',
            'data' => [
                'transaction_id' => $transactionId,
                'status'         => 'COMPLETED',
                'fund_release'   => 'SIMULATED_RELEASE_TO_OWNER',
                'next_step'      => 'NONE'
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
