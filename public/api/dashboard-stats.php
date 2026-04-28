<?php
/**
 * api/dashboard-stats.php — Returns KPI data for the dashboard as JSON.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

// Headers
header('Content-Type: application/json');

// Auth check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Fetch latest stats
$stats = [
    'totalEquipment' => getUserEquipmentCount($conn, $userId),
    'activeRentals'  => getUserActiveRentalsCount($conn, $userId),
    'poolCount'      => getUserPoolCount($conn, $userId),
    'trustScore'     => round(getUserTrustScore($conn, $userId), 1),
    'chartData'      => getMonthlyDashboardTrend($conn, $userId),
    'recentActivity' => getRecentDashboardActivity($conn, $userId)
];

echo json_encode(['success' => true, 'data' => $stats]);
exit;
