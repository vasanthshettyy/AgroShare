<?php
/**
 * get-realtime-dashboard.php — API endpoint for real-time dashboard updates.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';
require_once __DIR__ . '/../../src/Controllers/BookingController.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$userId = (int)$_SESSION['user_id'];

try {
    // 1. KPI Data
    $totalEquipment = getUserEquipmentCount($conn, $userId);
    $activeRentals  = getUserActiveRentalsCount($conn, $userId);
    $poolCount      = getUserPoolCount($conn, $userId); // Currently 0 placeholder
    $trustScore     = getUserTrustScore($conn, $userId);

    // 2. Calculate Total Earnings (completed bookings where user is owner)
    $stmt = $conn->prepare("SELECT SUM(total_price) as earnings FROM bookings WHERE owner_id = ? AND status = 'completed'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $earningsRow = $stmt->get_result()->fetch_assoc();
    $totalEarnings = (float)($earningsRow['earnings'] ?? 0.0);
    $stmt->close();

    // 3. Trend & Activity
    $monthlyTrend   = getMonthlyDashboardTrend($conn, $userId);
    $recentActivity = getRecentDashboardActivity($conn, $userId, 5);

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total_equipment' => $totalEquipment,
            'active_rentals'  => $activeRentals,
            'total_earnings'  => $totalEarnings,
            'pool_count'      => $poolCount,
            'trust_score'     => $trustScore
        ],
        'trend' => $monthlyTrend,
        'recent_activity' => $recentActivity
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
