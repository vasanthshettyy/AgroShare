<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$identifier = trim($input['identifier'] ?? '');
$csrf_token = $input['csrf_token'] ?? '';

if (!validateCsrfToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session.']);
    exit;
}

if (empty($identifier)) {
    echo json_encode(['success' => false, 'message' => 'Please enter phone or email.']);
    exit;
}

// 1. Check Format
$is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
$is_phone = preg_match('/^[6-9]\d{9}$/', $identifier);

if (!$is_email && !$is_phone) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or phone format.']);
    exit;
}

// 2. Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? OR email = ?");
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();
$exists = $result->num_rows > 0;
$stmt->close();

if (!$exists) {
    echo json_encode(['success' => false, 'message' => 'Account not found. Please sign up.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Account recognized.',
    'type' => $is_email ? 'email' : 'phone'
]);
