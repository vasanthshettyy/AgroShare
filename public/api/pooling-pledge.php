<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/PoolingController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

requireAuth();
$userId = (int)$_SESSION['user_id'];

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$campaignId = (int)($_POST['campaign_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1.']);
    exit;
}

$result = addPledge($conn, $campaignId, $userId, $quantity);

if ($result['success']) {
    $camp = getCampaignById($conn, $campaignId);
    $progress_pct = 0;
    if ($camp) {
        $progress_pct = min(100, round(($camp['current_quantity'] / $camp['target_quantity']) * 100));
    }
    
    echo json_encode([
        'success' => true, 
        'new_quantity' => $result['new_quantity'], 
        'progress_pct' => $progress_pct,
        'status' => $result['status'],
        'message' => 'Pledge added successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
exit;
