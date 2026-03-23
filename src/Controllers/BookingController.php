<?php
/**
 * BookingController.php — Handles booking lifecycle, pricing, and conflict detection.
 */

/**
 * Recalculate the price on the server to prevent client-side manipulation.
 */
function calculateServerSidePrice(mysqli $conn, int $equipmentId, string $start, string $end): float 
{
    $stmt = $conn->prepare("SELECT price_per_hour, price_per_day FROM equipment WHERE id = ?");
    $stmt->bind_param('i', $equipmentId);
    $stmt->execute();
    $eq = $stmt->get_result()->fetch_assoc();

    if (!$eq) return 0.0;

    $startTime = strtotime($start);
    $endTime   = strtotime($end);
    $durationHours = ($endTime - $startTime) / 3600;

    $hourlyRate = (float)$eq['price_per_hour'];
    $dailyRate  = (float)$eq['price_per_day'];

    if ($durationHours < 8) {
        return ceil($durationHours) * $hourlyRate;
    } else {
        $hourlyTotal = ceil($durationHours) * $hourlyRate;
        $dayCount    = ceil($durationHours / 24);
        $dailyTotal  = $dayCount * $dailyRate;
        return min($hourlyTotal, $dailyTotal);
    }
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
 * Fetch bookings where the user is the Renter.
 */
function getRentalsForUser(mysqli $conn, int $userId): array 
{
    $sql = "SELECT b.*, e.title as equipment_title, u.full_name as owner_name, u.phone as owner_phone 
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users u ON b.owner_id = u.id
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
    $sql = "SELECT b.*, e.title as equipment_title, u.full_name as renter_name, u.phone as renter_phone
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users u ON b.renter_id = u.id
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

    // Fetch booking details to verify ownership/rentership
    $stmt = $conn->prepare("SELECT b.status, b.owner_id, b.renter_id, b.equipment_id, e.title as eq_title FROM bookings b JOIN equipment e ON b.equipment_id = e.id WHERE b.id = ?");
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
        // Owner or Renter can cancel a 'pending' or 'confirmed' booking.
        if ((!$isRenter && !$isOwner) || !in_array($current, ['pending', 'confirmed'])) return false;
    }

    if ($newStatus === 'completed') {
        // Either party can mark as completed if it was confirmed or active
        if ((!$isOwner && !$isRenter) || !in_array($current, ['confirmed', 'active'])) return false;
    }

    // Begin Transaction for atomicity (Status + Equipment availability + Notifications)
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $bookingId);
        $stmt->execute();

        // If completed or cancelled (after being confirmed), free up the equipment
        if ($newStatus === 'completed' || ($newStatus === 'cancelled' && $current === 'confirmed')) {
            $stmt = $conn->prepare("UPDATE equipment SET is_available = 1 WHERE id = ?");
            $stmt->bind_param('i', $booking['equipment_id']);
            $stmt->execute();
        }

        // --- Notifications ---
        $eqTitle = $booking['eq_title'];
        
        if ($newStatus === 'confirmed') {
            createNotification($conn, $booking['renter_id'], "Your booking request for '$eqTitle' was confirmed!");
        } elseif ($newStatus === 'cancelled') {
            $targetUserId = $isOwner ? $booking['renter_id'] : $booking['owner_id'];
            $actor = $isOwner ? "Owner" : "Renter";
            
            // Tailor message if owner declines a pending request
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
