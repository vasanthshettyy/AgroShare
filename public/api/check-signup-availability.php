<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

$field      = $input['field'] ?? '';
$value      = trim($input['value'] ?? '');
$csrf_token = $input['csrf_token'] ?? '';

// Validate CSRF
if (!validateCsrfToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch.']);
    exit;
}

// Validate Field
if (!in_array($field, ['phone', 'email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid validation field.']);
    exit;
}

if (empty($value)) {
    echo json_encode(['success' => false, 'field' => $field, 'message' => 'Value is required.']);
    exit;
}

$exists = false;
$msg    = '';

if ($field === 'phone') {
    if (!preg_match('/^[6-9]\d{9}$/', $value)) {
        echo json_encode(['success' => false, 'field' => $field, 'message' => 'Invalid phone format.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    $msg = $exists ? 'Phone already registered.' : 'Available';
} else {
    // Email normalization
    $value = strtolower($value);
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'field' => $field, 'message' => 'Invalid email format.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    $msg = $exists ? 'Email already registered.' : 'Available';
}

echo json_encode([
    'success' => true,
    'field'   => $field,
    'exists'  => $exists,
    'message' => $msg
]);
