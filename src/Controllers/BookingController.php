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
    $sql = "SELECT b.*, e.title as equipment_title, u.full_name as owner_name 
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
 * Update booking status with state machine enforcement.
 */
function updateBookingStatus(mysqli $conn, int $bookingId, int $ownerId, string $newStatus): bool 
{
    $validStatuses = ['confirmed', 'rejected', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) return false;

    // Fetch current status and verify ownership
    $stmt = $conn->prepare("SELECT status, owner_id, renter_id FROM bookings WHERE id = ?");
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking || (int)$booking['owner_id'] !== $ownerId) return false;

    // State machine transitions
    $current = $booking['status'];
    if ($current === 'pending' && !in_array($newStatus, ['confirmed', 'rejected'])) return false;
    if ($current === 'confirmed' && !in_array($newStatus, ['completed', 'cancelled'])) return false;
    if (in_array($current, ['rejected', 'completed', 'cancelled'])) return false; // Terminal states

    $stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $bookingId);
    return $stmt->execute();
}
