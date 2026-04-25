<?php
/**
 * ReviewController.php — Handles submission and processing of user reviews.
 */

class ReviewController
{
    /**
     * Submit a review for a completed booking.
     */
    public static function submitReview(mysqli $conn, int $bookingId, int $reviewerId, float $rating, string $comment): array
    {
        // 1. Fetch booking details
        $stmt = $conn->prepare("SELECT renter_id, owner_id FROM bookings WHERE id = ? AND status = 'completed'");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found or not yet completed.'];
        }

        $renterId = (int)$booking['renter_id'];
        $ownerId  = (int)$booking['owner_id'];

        // 2. Identify reviewee and type
        if ($renterId === $reviewerId) {
            $revieweeId = $ownerId;
            $reviewType = 'renter_to_owner';
        } elseif ($ownerId === $reviewerId) {
            $revieweeId = $renterId;
            $reviewType = 'owner_to_renter';
        } else {
            return ['success' => false, 'message' => 'Unauthorized: You are not a party to this booking.'];
        }

        // 3. Check for existing review by this user for this booking
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE booking_id = ? AND reviewer_id = ?");
        $stmt->bind_param('ii', $bookingId, $reviewerId);
        $stmt->execute();
        $check = $stmt->get_result()->fetch_assoc();

        if ($check['cnt'] > 0) {
            return ['success' => false, 'message' => 'Review already submitted.'];
        }

        // 4. Insert review
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, comment, review_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iiiiss', $bookingId, $reviewerId, $revieweeId, $rating, $comment, $reviewType);
        $stmt->execute();

        // 5. Recalculate trust score for the reviewee
        self::recalculateTrustScore($conn, $revieweeId);

        return ['success' => true, 'message' => 'Review submitted!'];
    }

    /**
     * Recalculate and update the trust_score for a user based on their reviews.
     */
    public static function recalculateTrustScore(mysqli $conn, int $userId): void
    {
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE reviewee_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        $avg = (float)($res['avg_rating'] ?? 0.0);

        $updateStmt = $conn->prepare("UPDATE users SET trust_score = ROUND(?, 2) WHERE id = ?");
        $updateStmt->bind_param('di', $avg, $userId);
        $updateStmt->execute();
    }
}
