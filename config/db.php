<?php
/**
 * db.php — Database connection and session initialization.
 * 
 * Include this file at the top of every page that needs:
 *   - Database access ($conn)
 *   - Session support ($_SESSION)
 *   - Auth/CSRF/Flash helper functions
 * 
 * Usage: require_once __DIR__ . '/../config/db.php';
 *        (adjust path based on file location)
 */

// Load credentials
require_once __DIR__ . '/constants.php';

// Load helper functions (auth, CSRF, flash messages)
require_once __DIR__ . '/../src/Helpers/auth.php';

// ── Session Configuration ──────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);   // JS cannot read session cookie
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);   // Reject uninitialized session IDs
    session_start();
}

// ── Database Connection (OO mysqli) ────────────────────────
// Enable strict error reporting BEFORE creating the connection
// This makes all DB errors throw mysqli_sql_exception (catchable)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4'); // Prevents charset-based injection
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
