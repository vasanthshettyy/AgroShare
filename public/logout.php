<?php
/**
 * logout.php — Destroys session and redirects to login.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Start a fresh session just to carry the flash message
session_start();
$_SESSION['flash'] = [
    'type'    => 'success',
    'message' => 'You have been logged out.'
];

header('Location: login.php');
exit();
