<?php
/**
 * submit-review.php — API endpoint for submitting user reviews.
 */

header('Content-Type: application/json; charset=utf-8');

// Load database and controller
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/ReviewController.php';

// session_start() and helper includes are handled in config/db.php

// 1. Check Authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
    exit();
}

// 2. Validate CSRF (Uses the function from src/Helpers/auth.php)
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Invalid security token.']);
    exit();
}

// 3. Sanitize Inputs
$bookingId = (int)($_POST['booking_id'] ?? 0);
$rating    = (float)($_POST['rating'] ?? 0);
$comment   = trim($_POST['comment'] ?? '');

// 4. Validate Logic
if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit();
}

// 5. Process Review
$result = ReviewController::submitReview($conn, $bookingId, (int)$_SESSION['user_id'], (float)$rating, $comment);

// 6. Return Response
echo json_encode($result);
