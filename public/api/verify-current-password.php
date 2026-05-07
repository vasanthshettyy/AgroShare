<?php
/**
 * verify-current-password.php — Verifies user's current password for sensitive actions.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Helpers/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$userId = (int)$_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit();
    }

    // If user has no password (e.g. Google user), verification is skipped or handled differently
    // In this context, we check if the hash exists.
    if (empty($user['password_hash'])) {
        // For Google users setting a password for the first time
        echo json_encode(['success' => true, 'message' => 'Verified (Google account)']);
        exit();
    }

    if (password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => true, 'message' => 'Password verified.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error during verification.']);
}
