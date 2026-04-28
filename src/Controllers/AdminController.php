<?php
/**
 * AdminController.php — Handles all admin-specific logic.
 */

function getAdminDashboardStats(mysqli $conn): array
{
    $stats = [
        'users_count' => 0,
        'unverified_users' => 0,
        'equipment_count' => 0,
        'pending_equipment' => 0,
        'bookings_count' => 0,
        'active_disputes' => 0,
        'total_revenue' => 0,
        'recent_logs' => []
    ];

    $res = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'");
    if ($res) $stats['users_count'] = (int)$res->fetch_column();

    $res = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer' AND is_verified = 0");
    if ($res) $stats['unverified_users'] = (int)$res->fetch_column();

    $res = $conn->query("SELECT COUNT(*) FROM equipment");
    if ($res) $stats['equipment_count'] = (int)$res->fetch_column();

    $res = $conn->query("SELECT COUNT(*) FROM equipment WHERE is_available = 0");
    if ($res) $stats['pending_equipment'] = (int)$res->fetch_column();

    $res = $conn->query("SELECT COUNT(*) FROM bookings");
    if ($res) $stats['bookings_count'] = (int)$res->fetch_column();

    $res = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'disputed'");
    if ($res) $stats['active_disputes'] = (int)$res->fetch_column();

    $res = $conn->query("SELECT SUM(total_price) FROM bookings WHERE status != 'cancelled'");
    if ($res) $stats['total_revenue'] = (float)$res->fetch_column();

    try {
        $res = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 8");
        if ($res) $stats['recent_logs'] = $res->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {} catch (Error $e) {}

    return $stats;
}

function getUsersForAdmin(mysqli $conn): array
{
    $res = $conn->query("SELECT * FROM users WHERE role = 'farmer' ORDER BY created_at DESC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getEquipmentForAdmin(mysqli $conn): array
{
    $sql = "SELECT e.*, u.full_name as owner_name 
            FROM equipment e 
            JOIN users u ON e.owner_id = u.id 
            ORDER BY e.created_at DESC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getBookingsForAdmin(mysqli $conn): array
{
    $sql = "SELECT b.*, e.title as equipment_title, r.full_name as renter_name, o.full_name as owner_name 
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users r ON b.renter_id = r.id
            JOIN users o ON b.owner_id = o.id
            ORDER BY b.created_at DESC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getSettings(mysqli $conn): array
{
    // Placeholder for settings if there's no settings table yet
    // Try to fetch if table exists, otherwise return empty
    try {
        $res = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($res) {
            $arr = [];
            while ($row = $res->fetch_assoc()) {
                $arr[$row['setting_key']] = $row['setting_value'];
            }
            return $arr;
        }
    } catch (Exception $e) {} catch (Error $e) {}
    
    return [];
}

function getAuditLogs(mysqli $conn): array
{
    try {
        $res = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 100");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {} catch (Error $e) {}
    
    return [];
}

function getPoolingCampaignsForAdmin(mysqli $conn): array
{
    $sql = "SELECT c.*, u.full_name as creator_name,
            (SELECT COUNT(*) FROM pledges WHERE campaign_id = c.id) as pledge_count,
            (SELECT SUM(quantity) FROM pledges WHERE campaign_id = c.id) as current_quantity
            FROM campaigns c
            JOIN users u ON c.creator_id = u.id
            ORDER BY c.created_at DESC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
