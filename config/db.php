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
require_once __DIR__ . '/../src/Helpers/audit.php';

// Apply global security headers (X-Frame-Options, X-Content-Type-Options)
applySecurityHeaders();

// Set application timezone (deterministic IST behavior)
date_default_timezone_set(APP_TIMEZONE);

// ── Session Configuration ──────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);   // JS cannot read session cookie
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);   // Reject uninitialized session IDs
    session_start();
}

// Enforce session idle timeout (1-hour check)
enforceSessionIdleTimeout();

// ── Database Connection (OO mysqli) ────────────────────────
// Enable strict error reporting BEFORE creating the connection
// This makes all DB errors throw mysqli_sql_exception (catchable)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4'); // Prevents charset-based injection

    // ── Maintenance Mode Check ─────────────────────────────
    // Part of Module 8.5: Site Reliability & Maintenance
    $res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
    if ($res) {
        $mMode = $res->fetch_column();
        if ($mMode === '1') {
            // Check if user is admin (to allow admin bypass)
            $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
            
            // Current script name
            $currentScript = basename($_SERVER['SCRIPT_NAME']);
            
            // Allow access ONLY to maintenance page, logout, and admin files for admins
            if (!$isAdmin && $currentScript !== 'maintenance.php' && $currentScript !== 'logout.php') {
                // If it's an API request, return JSON error
                if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'System is under maintenance.']);
                    exit();
                }
                
                // Redirect to maintenance page
                $mUrl = (strpos($_SERVER['REQUEST_URI'], '/public/') !== false) ? 'maintenance.php' : 'public/maintenance.php';
                
                // Safety check: if we are in public subfolder, just maintenance.php works
                // But we need a deterministic path.
                header('Location: ' . getBasePath() . '/public/maintenance.php');
                exit();
            }
        }
    }

} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
