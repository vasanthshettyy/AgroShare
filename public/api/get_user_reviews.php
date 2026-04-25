<?php
/**
 * get_user_reviews.php — Fetches all reviews for a specific user and returns their trust score.
 */
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$revieweeId = (int)($_GET['user_id'] ?? 0);

if ($revieweeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

try {
    // 1. Fetch the user's trust score and name
    $userStmt = $conn->prepare("SELECT full_name, trust_score FROM users WHERE id = ?");
    $userStmt->bind_param('i', $revieweeId);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();

    if (!$userData) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // 2. Fetch all reviews for this user
    $reviewsStmt = $conn->prepare("
        SELECT r.*, u.full_name AS reviewer_name 
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewee_id = ?
        ORDER BY r.created_at DESC
    ");
    $reviewsStmt->bind_param('i', $revieweeId);
    $reviewsStmt->execute();
    $result = $reviewsStmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = [
            'reviewer_name' => $row['reviewer_name'],
            'rating'        => (float)$row['rating'],
            'comment'       => $row['comment'],
            'date'          => date('d M Y', strtotime($row['created_at']))
        ];
    }

    echo json_encode([
        'success'     => true,
        'user_name'   => $userData['full_name'],
        'trust_score' => (float)$userData['trust_score'],
        'reviews'     => $reviews
    ]);

} catch (Exception $e) {
    error_log('API Error (get_user_reviews): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
