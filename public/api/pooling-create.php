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

$result = createCampaign($conn, $_POST, $userId);

if ($result['success']) {
    echo json_encode(['success' => true, 'id' => $result['id']]);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
exit;
