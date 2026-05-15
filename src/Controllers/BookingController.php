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
 * Check for booking overlaps while optionally excluding a specific booking ID (useful for rescheduling).
 */
function hasBookingConflict(mysqli $conn, int $equipmentId, string $start, string $end, ?int $excludeBookingId = null): bool 
{
    $sql = "SELECT id FROM bookings 
            WHERE equipment_id = ? 
            AND status IN ('pending', 'confirmed', 'active') 
            AND start_datetime < ? 
            AND end_datetime > ?";
    
    if ($excludeBookingId !== null) {
        $sql .= " AND id != ?";
    }
    
    $sql .= " LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($excludeBookingId !== null) {
        $stmt->bind_param('isssi', $equipmentId, $end, $start, $excludeBookingId);
    } else {
        $stmt->bind_param('iss', $equipmentId, $end, $start);
    }
    
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Reschedule an existing booking (Renter Only).
 * 
 * Rules:
 * 1. Actor must be the renter.
 * 2. Old status must be 'pending' or 'confirmed'.
 * 3. payment_status must be 'pending'.
 * 4. Creates a new 'pending' booking and marks the old one as 'rescheduled'.
 */
function rescheduleBooking(mysqli $conn, int $bookingId, int $userId, string $newStart, string $newEnd): array 
{
    // 1. Fetch and Lock the original booking for consistency
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();

        if (!$old) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        // 2. Strict Validations
        if ((int)$old['renter_id'] !== $userId) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Permission denied. Only renters can reschedule requests.'];
        }

        if (!in_array($old['status'], ['pending', 'confirmed'])) {
            $conn->rollback();
            return ['success' => false, 'message' => "Reschedule blocked: Booking is currently '{$old['status']}'."];
        }

        $paymentStatus = $old['payment_status'] ?? 'pending';
        $paymentRef = $old['payment_reference'] ?? '';
        if ($paymentStatus === 'confirmed' || !empty($paymentRef)) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Reschedule locked: Payment has already been initiated or confirmed.'];
        }

        // 3. Chronological Validation
        $startTime = strtotime($newStart);
        $endTime   = strtotime($newEnd);
        if (!$startTime || !$endTime || $endTime <= $startTime || $startTime < time()) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Invalid date range.'];
        }

        // 4. Conflict Check (Ignore current booking ID)
        if (hasBookingConflict($conn, (int)$old['equipment_id'], $newStart, $newEnd, $bookingId)) {
            $conn->rollback();
            return ['success' => false, 'message' => 'The selected dates conflict with an existing booking.'];
        }

        // 5. Calculate New Price
        $newPrice = calculateServerSidePrice($conn, (int)$old['equipment_id'], $newStart, $newEnd);

        // 6. Execute Pivot (Cancel Old + Create New)
        // This preserves audit logs of the original booking while initiating a fresh approval flow.
        $newStatus = 'pending';
        $newPaymentStatus = 'pending';
        $ins = $conn->prepare("INSERT INTO bookings (equipment_id, renter_id, owner_id, start_datetime, end_datetime, total_price, deposit_amount, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param('iiissddss', 
            $old['equipment_id'], $old['renter_id'], $old['owner_id'], 
            $newStart, $newEnd, $newPrice, $old['deposit_amount'], 
            $newStatus, $newPaymentStatus
        );
        $ins->execute();
        $newBookingId = $conn->insert_id;

        // B) Mark Old Booking as Rescheduled
        $upd = $conn->prepare("UPDATE bookings SET status = 'rescheduled' WHERE id = ?");
        $upd->bind_param('i', $bookingId);
        $upd->execute();

        // 7. Notification Logic
        $eqTitleStmt = $conn->prepare("SELECT title FROM equipment WHERE id = ?");
        $eqTitleStmt->bind_param('i', $old['equipment_id']);
        $eqTitleStmt->execute();
        $eqTitle = $eqTitleStmt->get_result()->fetch_column();

        createNotification($conn, (int)$old['owner_id'], "Renter has rescheduled booking for '$eqTitle'. Please review the new pending request.");

        $conn->commit();
        return [
            'success' => true, 
            'message' => 'Booking rescheduled successfully. New request created.',
            'new_booking_id' => $newBookingId
        ];

    } catch (Exception $e) {
        $conn->rollback();
        logError('rescheduleBooking error: ' . $e->getMessage(), ['booking_id' => $bookingId, 'user_id' => $userId]);
        return ['success' => false, 'message' => 'An internal error occurred while rescheduling.'];
    }
}

/**
 * Automatically promote booking statuses based on current time.
 */
function autoPromoteBookings(mysqli $conn, int $userId): void
{
    $now = date('Y-m-d H:i:s');
    
    // confirmed -> active
    $sql1 = "UPDATE bookings b
             SET b.status = 'active' 
             WHERE (b.renter_id = ? OR b.owner_id = ?) 
             AND b.status = 'confirmed' 
             AND b.start_datetime <= ? 
             AND b.end_datetime > ?";
    $stmt1 = $conn->prepare($sql1);
    if ($stmt1) {
        $stmt1->bind_param('iiss', $userId, $userId, $now, $now);
        $stmt1->execute();
        $stmt1->close();
    }

    // active -> completed
    $sql2 = "UPDATE bookings b
             SET b.status = 'completed' 
             WHERE (b.renter_id = ? OR b.owner_id = ?) 
             AND b.status = 'active' 
             AND b.end_datetime <= ?";
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
    
    $sql = "SELECT b.*, e.title as equipment_title, e.images as equipment_images, 
                   u.full_name as owner_name, u.phone as owner_phone, u.trust_score as owner_trust,
                   u.upi_id as owner_upi_id, u.upi_qr_path as owner_upi_qr_path,
                   u.profile_photo AS owner_photo,
                   r.id AS review_id
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users u ON b.owner_id = u.id
            LEFT JOIN reviews r ON r.booking_id = b.id AND r.reviewer_id = ?
            WHERE b.renter_id = ?
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch bookings where the user is the Owner.
 */
function getRequestsForOwner(mysqli $conn, int $userId): array 
{
    autoPromoteBookings($conn, $userId);
    
    $sql = "SELECT b.*, e.title as equipment_title, e.images as equipment_images,
                   u.full_name as renter_name, u.phone as renter_phone, u.email as renter_email,
                   u.village as renter_village, u.district as renter_district,
                   u.trust_score as renter_trust, u.is_verified as renter_verified,
                   u.profile_photo AS renter_photo,
                   r.id AS review_id
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users u ON b.renter_id = u.id
            LEFT JOIN reviews r ON r.booking_id = b.id AND r.reviewer_id = ?
            WHERE b.owner_id = ?
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $userId, $userId);
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
 * Mark booking payment as confirmed (Renter only).
 * Once confirmed, rescheduling should be blocked by rescheduleBooking rules.
 */
function confirmBookingPayment(mysqli $conn, int $bookingId, int $userId, string $reference = ''): array
{
    try {
        $stmt = $conn->prepare("SELECT id, renter_id, owner_id, status, payment_status, equipment_id FROM bookings WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }
        if ((int)$booking['renter_id'] !== $userId) {
            return ['success' => false, 'message' => 'Only the renter can confirm payment.'];
        }
        if ($booking['status'] !== 'confirmed') {
            return ['success' => false, 'message' => 'Payment can only be confirmed for confirmed bookings.'];
        }
        if (($booking['payment_status'] ?? 'pending') === 'confirmed') {
            return ['success' => true, 'message' => 'Payment is already confirmed.'];
        }

        $upd = $conn->prepare("UPDATE bookings SET payment_status = 'confirmed', payment_reference = ? WHERE id = ? AND renter_id = ?");
        $upd->bind_param('sii', $reference, $bookingId, $userId);
        $upd->execute();
        $upd->close();

        $eqStmt = $conn->prepare("SELECT title FROM equipment WHERE id = ?");
        $eqStmt->bind_param('i', $booking['equipment_id']);
        $eqStmt->execute();
        $eqTitle = $eqStmt->get_result()->fetch_column() ?: 'your equipment';
        $eqStmt->close();

        createNotification($conn, (int)$booking['owner_id'], "Renter marked payment as completed for '$eqTitle'. Reference: " . ($reference ?: 'None'));

        return ['success' => true, 'message' => 'Payment confirmed. Rescheduling is now disabled for this booking.'];
    } catch (Exception $e) {
        logError('confirmBookingPayment error: ' . $e->getMessage(), ['booking_id' => $bookingId, 'user_id' => $userId]);
        return ['success' => false, 'message' => 'Could not confirm payment at this time.'];
    }
}

/**
 * Verify payment received (Owner only).
 */
function verifyOwnerPayment(mysqli $conn, int $bookingId, int $userId): array
{
    try {
        $stmt = $conn->prepare("SELECT id, owner_id, payment_status, equipment_id FROM bookings WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }
        if ((int)$booking['owner_id'] !== $userId) {
            return ['success' => false, 'message' => 'Only the owner can verify payment.'];
        }

        $now = date('Y-m-d H:i:s');
        $upd = $conn->prepare("UPDATE bookings SET payment_verified_at = ? WHERE id = ?");
        $upd->bind_param('si', $now, $bookingId);
        $upd->execute();
        $upd->close();

        return ['success' => true, 'message' => 'Payment verification recorded.'];
    } catch (Exception $e) {
        logError('verifyOwnerPayment error: ' . $e->getMessage(), ['booking_id' => $bookingId, 'user_id' => $userId]);
        return ['success' => false, 'message' => 'Could not verify payment at this time.'];
    }
}

/**
 * Update booking status with state machine enforcement.
 */
function updateBookingStatus(mysqli $conn, int $bookingId, int $userId, string $newStatus): bool 
{
    $validStatuses = ['confirmed', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) return false;

    // Fetch booking details
    $stmt = $conn->prepare(
        "SELECT b.status, b.owner_id, b.renter_id, b.equipment_id,
                e.title as eq_title
         FROM bookings b
         JOIN equipment e ON b.equipment_id = e.id
         WHERE b.id = ?"
    );
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) return false;

    $isOwner  = (int)$booking['owner_id'] === $userId;
    $isRenter = (int)$booking['renter_id'] === $userId;
    $current  = $booking['status'];

    // State Machine & Permission Enforcement
    if ($newStatus === 'confirmed') {
        if (!$isOwner || $current !== 'pending') return false;
    }
    
    if ($newStatus === 'cancelled') {
        if ((!$isRenter && !$isOwner) || !in_array($current, ['pending', 'confirmed'])) return false;
    }

    if ($newStatus === 'completed') {
        if ((!$isOwner && !$isRenter) || !in_array($current, ['confirmed', 'active'])) return false;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $bookingId);
        $stmt->execute();

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
        logError('updateBookingStatus error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Fetch the latest N activities for the user dashboard.
 */
function getRecentDashboardActivity(mysqli $conn, int $userId, int $limit = 5): array
{
    $sql = "SELECT b.id, b.status, b.created_at, e.title as equipment_title,
                   IF(b.renter_id = ?, 'Rental', 'Request') as activity_type
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            WHERE b.renter_id = ? OR b.owner_id = ?
            ORDER BY b.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $userId, $userId, $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch monthly rental trends (counts) for the last 7 months.
 */
function getMonthlyDashboardTrend(mysqli $conn, int $userId): array
{
    $counts = [];
    for ($i = 6; $i >= 0; $i--) {
        $monthStr = date('Y-m', strtotime("-$i months"));
        $sql = "SELECT COUNT(*) as total 
                FROM bookings 
                WHERE (renter_id = ? OR owner_id = ?) 
                AND DATE_FORMAT(created_at, '%Y-%m') = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $userId, $userId, $monthStr);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $counts[] = (int)($row['total'] ?? 0);
        $stmt->close();
    }
    return $counts;
}

/**
 * Fetch all upcoming confirmed or active dates for a piece of equipment.
 */
function getBlockedDatesForEquipment(mysqli $conn, int $equipmentId): array 
{
    $now = date('Y-m-d H:i:s');
    $sql = "SELECT start_datetime, end_datetime 
            FROM bookings 
            WHERE equipment_id = ? 
            AND status IN ('pending', 'confirmed', 'active')
            AND end_datetime > ?
            ORDER BY start_datetime ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $equipmentId, $now);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
