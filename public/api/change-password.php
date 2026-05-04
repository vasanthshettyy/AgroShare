<?php
/**
 * change-password.php — Handle password updates and "Set Password" for Google users.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Helpers/auth.php';
require_once __DIR__ . '/../../src/Helpers/logger.php';

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

$oldPassword = $_POST['old_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($newPassword) || strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
    exit();
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit();
}

try {
    // 1. Fetch current user data
    $stmt = $conn->prepare("SELECT password_hash, google_id FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit();
    }

    // 2. Logic: If user has a password_hash, they MUST verify old_password.
    // If password_hash is null (Google only users), they don't need old_password.
    if (!empty($user['password_hash'])) {
        if (empty($oldPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required to set a new one.']);
            exit();
        }
        if (!password_verify($oldPassword, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit();
        }
    }

    // 3. Update hash
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updateStmt->bind_param('si', $newHash, $userId);
    
    if ($updateStmt->execute()) {
        logError("User $userId successfully changed their password.");
        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    $updateStmt->close();

} catch (Exception $e) {
    logError('Change password error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred.']);
}
