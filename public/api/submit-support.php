<?php
/**
 * submit-support.php — AJAX endpoint for submitting customer feedback/support messages.
 *
 * Expects: POST with 'message' and 'csrf_token'.
 * Returns: JSON { success: bool, message: string }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

// 2. CSRF Validation
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid session. Please refresh the page.']);
    exit();
}

// 3. Input Validation
$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a message.']);
    exit();
}

if (mb_strlen($message) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long (max 2000 characters).']);
    exit();
}

// 4. Database Insertion
try {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO support_messages (user_id, message) VALUES (?, ?)");
    $stmt->bind_param('is', $userId, $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your feedback! Our team will review it shortly.'
        ]);
    } else {
        throw new Exception("Database execution failed.");
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Support submission error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save your message. Please try again later.']);
}
