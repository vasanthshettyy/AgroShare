<?php
/**
 * constants.php — Database credentials and application constants.
 * 
 * SECURITY: This file must NEVER be placed in the public web root.
 * It is included by db.php via require_once.
 * 
 * Update these values to match your WAMP/MySQL setup.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // WAMP default: no password
define('DB_NAME', 'agroshare');

// Application settings
define('APP_NAME', 'AgroShare');
define('APP_URL',  'http://localhost/agroshare');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
