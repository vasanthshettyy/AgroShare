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

if ($userId > 0) {
    try {
        // Optimistic query: assumes is_active column will be added if it doesn't exist
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT COALESCE(is_active, 1) WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            logAuditEvent($conn, 'admin_toggle_user', $userId, "Admin toggled active status for user $userId", $_SESSION['user_id']);
            setFlash('success', "User active status toggled.");
        } else {
            setFlash('error', "is_active column may not exist in schema yet.");
        }
    } catch (Exception $e) {
        setFlash('error', "Database error: " . $e->getMessage());
    }
}

header('Location: ../users.php');
exit();
