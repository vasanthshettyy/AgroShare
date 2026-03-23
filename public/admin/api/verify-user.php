<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../src/Helpers/audit.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php');
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token.');
    header('Location: ../users.php');
    exit();
}

$userId = (int)($_POST['user_id'] ?? 0);
$status = (int)($_POST['status'] ?? 0);

if ($userId > 0) {
    try {
        $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
        $stmt->bind_param('ii', $status, $userId);
        $stmt->execute();
        
        logAuditEvent($conn, 'admin_verify_user', $userId, "Admin updated is_verified to $status for user $userId", $_SESSION['user_id']);
        setFlash('success', "User verification status updated.");
    } catch (Exception $e) {
        setFlash('error', "Database error: " . $e->getMessage());
    }
}

header('Location: ../users.php');
exit();
