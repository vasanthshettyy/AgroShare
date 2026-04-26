<?php
/**
 * get_user_public_profile.php — Fetches comprehensive public data for a user.
 */
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$targetUserId = (int)($_GET['user_id'] ?? 0);

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

try {
    // 1. Fetch User Base Info
    $userStmt = $conn->prepare("
        SELECT id, full_name, email, phone, village, district, trust_score, is_verified, created_at 
        FROM users 
        WHERE id = ?
    ");
    $userStmt->bind_param('i', $targetUserId);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // 2. Fetch Activity Stats (Total rentals/listings handled)
    $statsStmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE (owner_id = ? OR renter_id = ?) AND status = 'completed'");
    $statsStmt->bind_param('ii', $targetUserId, $targetUserId);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();

    // 3. Fetch 2 Most Recent Reviews
    $revStmt = $conn->prepare("
        SELECT r.rating, r.comment, r.created_at, u.full_name as reviewer_name
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewee_id = ?
        ORDER BY r.created_at DESC
        LIMIT 2
    ");
    $revStmt->bind_param('i', $targetUserId);
    $revStmt->execute();
    $reviewsResult = $revStmt->get_result();
    
    $recentReviews = [];
    while ($row = $reviewsResult->fetch_assoc()) {
        $recentReviews[] = [
            'name' => $row['reviewer_name'],
            'rating' => (float)$row['rating'],
            'comment' => $row['comment'],
            'date' => date('M Y', strtotime($row['created_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id'           => $user['id'],
            'name'         => $user['full_name'],
            'initials'     => strtoupper(substr($user['full_name'], 0, 1)),
            'location'     => $user['village'] . ', ' . $user['district'],
            'phone'        => $user['phone'],
            'email'        => $user['email'],
            'trust_score'  => (float)$user['trust_score'],
            'is_verified'  => (bool)$user['is_verified'],
            'joined'       => date('M Y', strtotime($user['created_at'])),
            'total_deals'  => $stats['total'],
            'recent_reviews' => $recentReviews
        ]
    ]);

} catch (Exception $e) {
    error_log('Public Profile API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
