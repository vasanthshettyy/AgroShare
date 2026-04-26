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

$result = closeCampaign($conn, $campaignId, $userId);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Campaign closed successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
exit;
