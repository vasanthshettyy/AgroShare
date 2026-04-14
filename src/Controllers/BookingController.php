<?php
/**
 * BookingController.php — Handles booking lifecycle, pricing, and conflict detection.
 */

/**
 * Recalculate the price on the server to prevent client-side manipulation.
 */
function calculateServerSidePrice(mysqli $conn, int $equipmentId, string $start, string $end): float 
{
    $stmt = $conn->prepare("SELECT price_per_day FROM equipment WHERE id = ?");
    $stmt->bind_param('i', $equipmentId);
    $stmt->execute();
    $eq = $stmt->get_result()->fetch_assoc();

    if (!$eq) return 0.0;

    $startTime = strtotime($start);
    $endTime   = strtotime($end);
    $durationHours = ($endTime - $startTime) / 3600;
    $dayCount = max(1, (int)ceil($durationHours / 24));
    $dailyRate = (float)$eq['price_per_day'];

    return $dayCount * $dailyRate;
}

/**
 * Check for booking overlaps using the specified algorithm.
 */
function hasBookingConflict(mysqli $conn, int $equipmentId, string $start, string $end): bool 
{
    // Exact logic requested: SELECT id FROM bookings WHERE equipment_id = ? 
    // AND status IN ('pending', 'confirmed', 'active') AND start_datetime < ? AND end_datetime > ? LIMIT 1
    $sql = "SELECT id FROM bookings 
            WHERE equipment_id = ? 
            AND status IN ('pending', 'confirmed', 'active') 
            AND start_datetime < ? 
            AND end_datetime > ? 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    // Note: To check overlap of (S, E) against (bS, bE), we pass E and S to the query
    $stmt->bind_param('iss', $equipmentId, $end, $start);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Automatically promote booking statuses based on current time.
 */
function autoPromoteBookings(mysqli $conn, int $userId): void
{
    $now = date('Y-m-d H:i:s');
    
    // confirmed -> active (skip ESCROW bookings — they use PIN-gated handover)
    $sql1 = "UPDATE bookings b
             LEFT JOIN transactions t ON b.transaction_id = t.transaction_id
             SET b.status = 'active' 
             WHERE (b.renter_id = ? OR b.owner_id = ?) 
             AND b.status = 'confirmed' 
             AND b.start_datetime <= ? 
             AND b.end_datetime > ?
             AND (t.booking_type IS NULL OR t.booking_type != 'ESCROW')";
    $stmt1 = $conn->prepare($sql1);
    if ($stmt1) {
        $stmt1->bind_param('iiss', $userId, $userId, $now, $now);
        $stmt1->execute();
        $stmt1->close();
    }

    // active -> completed (skip ESCROW bookings — they use PIN-gated return)
    $sql2 = "UPDATE bookings b
             LEFT JOIN transactions t ON b.transaction_id = t.transaction_id
             SET b.status = 'completed' 
             WHERE (b.renter_id = ? OR b.owner_id = ?) 
             AND b.status = 'active' 
             AND b.end_datetime <= ?
             AND (t.booking_type IS NULL OR t.booking_type != 'ESCROW')";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param('iis', $userId, $userId, $now);
        $stmt2->execute();
        $stmt2->close();
    }
}

/**
 * Fetch bookings where the user is the Renter.
 */
function getRentalsForUser(mysqli $conn, int $userId): array 
{
    autoPromoteBookings($conn, $userId);
    
    $sql = "SELECT b.*, e.title as equipment_title, u.full_name as owner_name, u.phone as owner_phone,
                   t.booking_type, t.status as escrow_status, t.handover_otp
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users u ON b.owner_id = u.id
            LEFT JOIN transactions t ON b.transaction_id = t.transaction_id
            WHERE b.renter_id = ?
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch bookings where the user is the Owner.
 */
function getRequestsForOwner(mysqli $conn, int $userId): array 
{
    autoPromoteBookings($conn, $userId);
    
    $sql = "SELECT b.*, e.title as equipment_title, 
                   u.full_name as renter_name, u.phone as renter_phone, u.email as renter_email,
                   u.village as renter_village, u.district as renter_district,
                   u.trust_score as renter_trust, u.is_verified as renter_verified,
                   t.booking_type, t.status as escrow_status, t.return_otp
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users u ON b.renter_id = u.id
            LEFT JOIN transactions t ON b.transaction_id = t.transaction_id
            WHERE b.owner_id = ?
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Helper: Create a notification.
 */
function createNotification(mysqli $conn, int $userId, string $message): void 
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param('is', $userId, $message);
    $stmt->execute();
}

/**
 * Update booking status with state machine enforcement.
 */
function updateBookingStatus(mysqli $conn, int $bookingId, int $userId, string $newStatus): bool 
{
    $validStatuses = ['confirmed', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) return false;

    // Fetch booking details with transaction info
    $stmt = $conn->prepare(
        "SELECT b.status, b.owner_id, b.renter_id, b.equipment_id, b.transaction_id,
                e.title as eq_title, t.booking_type, t.status as txn_status
         FROM bookings b
         JOIN equipment e ON b.equipment_id = e.id
         LEFT JOIN transactions t ON b.transaction_id = t.transaction_id
         WHERE b.id = ?"
    );
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) return false;

    $isOwner  = (int)$booking['owner_id'] === $userId;
    $isRenter = (int)$booking['renter_id'] === $userId;
    $current  = $booking['status'];
    $isEscrow = ($booking['booking_type'] === 'ESCROW');

    // State Machine & Permission Enforcement
    if ($newStatus === 'confirmed') {
        if (!$isOwner || $current !== 'pending') return false;
    }
    
    if ($newStatus === 'cancelled') {
        if ((!$isRenter && !$isOwner) || !in_array($current, ['pending', 'confirmed'])) return false;
    }

    if ($newStatus === 'completed') {
        // Block manual completion for ESCROW bookings — must go through PIN verification
        if ($isEscrow && $booking['txn_status'] !== 'COMPLETED') return false;
        if ((!$isOwner && !$isRenter) || !in_array($current, ['confirmed', 'active'])) return false;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $bookingId);
        $stmt->execute();

        if ($newStatus === 'confirmed') {
            $stmt = $conn->prepare("UPDATE equipment SET is_available = 0 WHERE id = ?");
            $stmt->bind_param('i', $booking['equipment_id']);
            $stmt->execute();
        }

        if ($newStatus === 'completed' || ($newStatus === 'cancelled' && $current === 'confirmed')) {
            $stmt = $conn->prepare("UPDATE equipment SET is_available = 1 WHERE id = ?");
            $stmt->bind_param('i', $booking['equipment_id']);
            $stmt->execute();
        }

        // Sync ESCROW transaction on cancel
        if ($newStatus === 'cancelled' && $isEscrow && !empty($booking['transaction_id'])) {
            cancelEscrowTransaction($conn, $booking['transaction_id']);
        }

        // --- Notifications ---
        $eqTitle = $booking['eq_title'];
        
        if ($newStatus === 'confirmed') {
            createNotification($conn, $booking['renter_id'], "Your booking request for '$eqTitle' was confirmed!");
        } elseif ($newStatus === 'cancelled') {
            $targetUserId = $isOwner ? $booking['renter_id'] : $booking['owner_id'];
            $actor = $isOwner ? "Owner" : "Renter";
            
            if ($isOwner && $current === 'pending') {
                createNotification($conn, $booking['renter_id'], "Your booking request for '$eqTitle' was declined by the owner.");
            } else {
                createNotification($conn, $targetUserId, "The booking for '$eqTitle' was cancelled by the $actor.");
            }
        } elseif ($newStatus === 'completed') {
            $targetUserId = $isOwner ? $booking['renter_id'] : $booking['owner_id'];
            $actor = $isOwner ? "Owner" : "Renter";
            createNotification($conn, $targetUserId, "The booking for '$eqTitle' was marked as completed by the $actor.");
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Cancel an ESCROW transaction and release equipment.
 */
function cancelEscrowTransaction(mysqli $conn, string $transactionId): void
{
    $stmt = $conn->prepare(
        "UPDATE transactions SET status = 'CANCELLED', handover_otp = NULL, return_otp = NULL, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ? AND status IN ('PENDING_PAYMENT', 'FUNDS_LOCKED', 'ACTIVE_RENTAL')"
    );
    $stmt->bind_param('s', $transactionId);
    $stmt->execute();
}
