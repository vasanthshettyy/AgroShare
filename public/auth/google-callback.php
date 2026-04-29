<?php
/**
 * google-callback.php — Handles the response from Google OAuth server.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/GoogleAuthController.php';
require_once __DIR__ . '/../../src/Helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$code  = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

// 1. Basic Validation
if (!$code || !$state) {
    setFlash('error', 'Authentication failed. Please try again.');
    header('Location: ../login.php');
    exit();
}

// 2. CSRF Check
if ($state !== ($_SESSION['google_oauth_state'] ?? '')) {
    setFlash('error', 'Invalid request state. CSRF protection triggered.');
    header('Location: ../login.php');
    exit();
}
unset($_SESSION['google_oauth_state']);

// 3. Exchange Code for Token
$tokenData = exchangeCodeForToken($code);
if (!$tokenData || !isset($tokenData['access_token'])) {
    setFlash('error', 'Failed to retrieve access token from Google.');
    header('Location: ../login.php');
    exit();
}

// 4. Get User Info
$googleUser = getGoogleUserInfo($tokenData['access_token']);
if (!$googleUser) {
    setFlash('error', 'Failed to retrieve user info from Google.');
    header('Location: ../login.php');
    exit();
}

// 5. Process User (Login or Register)
$result = handleGoogleUserSession($conn, $googleUser);

if ($result['status'] === 'success') {
    // Log them in
    $user = $result['user'];
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['role']       = $user['role'] ?? 'user';
    $_SESSION['persist']    = true; 
    $_SESSION['last_activity'] = time();
    
    session_regenerate_id(true);
    logAuditEvent($conn, 'login_google', $user['id'], 'User logged in via Google OAuth.');
    
    header('Location: ../dashboard.php');
    exit();
} 

if ($result['status'] === 'needs_profile') {
    // Redirect to profile completion page
    header('Location: complete-google-profile.php');
    exit();
}

// Fallback
setFlash('error', 'An unexpected error occurred during Google login.');
header('Location: ../login.php');
exit();
