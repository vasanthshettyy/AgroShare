<?php
/**
 * get-realtime-pooling.php — API endpoint for real-time community pooling updates.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/PoolingController.php';

$filters = [
    'district' => $_GET['district'] ?? '',
    'status'   => ($_GET['status'] ?? 'open' === 'all') ? '' : ($_GET['status'] ?? 'open')
];

try {
    $campaigns = getCampaigns($conn, $filters);

    echo json_encode([
        'success' => true,
        'data' => $campaigns
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
