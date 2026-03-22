<?php
/**
 * mark-notification-read.php — Mark a specific notification as read.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$notifId = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if ($notifId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    exit();
}

$sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $notifId, $userId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Notification not found or already read.']);
}
