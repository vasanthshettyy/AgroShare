<?php
/**
 * get-profile.php — Fetch current user details for the profile modal.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Helpers/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name, phone, email, village, district, state, profile_photo, trust_score, is_verified FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Ensure profile_photo has a default if null
        if (empty($user['profile_photo'])) {
            $user['profile_photo'] = 'assets/img/default-avatar.png';
        }
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
