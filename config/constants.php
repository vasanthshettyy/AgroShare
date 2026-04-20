<?php
/**
 * constants.php — Database credentials and application constants.
 * 
 * SECURITY: This file must NEVER be placed in the public web root.
 * It is included by db.php via require_once.
 * 
 * Update these values to match your WAMP/MySQL setup.
 */

// Optional local secrets override (git-ignored)
$localSecretsFile = __DIR__ . '/local.secrets.php';
if (is_file($localSecretsFile)) {
    require_once $localSecretsFile;
}

// Database credentials
if (!defined('DB_HOST')) { define('DB_HOST', 'localhost'); }
if (!defined('DB_USER')) { define('DB_USER', 'root'); }
if (!defined('DB_PASS')) { define('DB_PASS', ''); }          // WAMP default: no password
if (!defined('DB_NAME')) { define('DB_NAME', 'agroshare'); }

// Application settings
if (!defined('APP_NAME')) { define('APP_NAME', 'AgroShare'); }
if (!defined('APP_URL')) { define('APP_URL', 'http://localhost/agroshare3'); }
if (!defined('APP_TIMEZONE')) { define('APP_TIMEZONE', 'Asia/Kolkata'); }

// Session settings
if (!defined('SESSION_LIFETIME')) { define('SESSION_LIFETIME', 3600); } // 1 hour in seconds

// SMTP Configuration (PHPMailer)
if (!defined('SMTP_HOST')) { define('SMTP_HOST', 'smtp.gmail.com'); }
if (!defined('SMTP_PORT')) { define('SMTP_PORT', 587); }
if (!defined('SMTP_USER')) { define('SMTP_USER', 'your_email@gmail.com'); }
if (!defined('SMTP_PASS')) { define('SMTP_PASS', 'your_app_password_without_spaces'); }
if (!defined('SMTP_SECURE')) { define('SMTP_SECURE', 'tls'); } // 'tls' or 'ssl'
if (!defined('SMTP_FROM_EMAIL')) { define('SMTP_FROM_EMAIL', 'your_email@gmail.com'); }
if (!defined('SMTP_FROM_NAME')) { define('SMTP_FROM_NAME', 'AgroShare Support'); }
