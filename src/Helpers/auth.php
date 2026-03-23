<?php
/**
 * auth.php — Authentication, CSRF, and Flash Message helpers.
 * 
 * These functions are auto-loaded via config/db.php.
 * They establish patterns reused across every module.
 */

// ── Authentication Guards ──────────────────────────────────

/**
 * Require an authenticated session. Redirects to login if not logged in.
 * Call at the top of every protected page.
 */
function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        setFlash('error', 'Please log in to access this page.');
        header('Location: ' . getBasePath() . '/public/login.php');
        exit();
    }
}

/**
 * Require a specific role. Redirects to dashboard if role doesn't match.
 * Example: requireRole('admin');
 */
function requireRole(string $role): void
{
    requireAuth(); // Must be logged in first

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        setFlash('error', 'You do not have permission to access this page.');
        header('Location: ' . getBasePath() . '/public/dashboard.php');
        exit();
    }
}

/**
 * Enforce session idle timeout based on SESSION_LIFETIME.
 * Redirects to login if the user has been inactive for too long.
 */
function enforceSessionIdleTimeout(): void
{
    // If not logged in, nothing to enforce
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        // Safe logout sequence:
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        // Start fresh session just to show the error
        session_start();
        setFlash('error', 'Session expired due to inactivity. Please log in again.');
        header('Location: ' . getBasePath() . '/public/login.php');
        exit();
    }

    // Otherwise update activity time
    $_SESSION['last_activity'] = $now;
}

// ── CSRF Protection ────────────────────────────────────────

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token string for embedding in forms.
 * 
 * Usage in forms:
 *   <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the submitted CSRF token against the session token.
 * Returns true if valid, false if invalid or missing.
 * 
 * Usage at top of POST handlers:
 *   if (!validateCsrfToken($_POST['csrf_token'] ?? '')) { ... }
 */
function validateCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// ── Flash Messages ─────────────────────────────────────────

/**
 * Set a flash message in the session.
 * Type should be: 'success', 'error', 'info', 'warning'.
 * 
 * Usage: setFlash('success', 'Registration complete!');
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the flash message from the session.
 * Returns null if no flash message exists.
 * 
 * Usage in templates:
 *   $flash = getFlash();
 *   if ($flash) { echo "<div class='alert alert-{$flash['type']}'>{$flash['message']}</div>"; }
 */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Render a flash message as HTML if one exists.
 * Call this in the page body where you want messages to appear.
 */
function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) {
        return '';
    }

    $type    = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    return "<div class=\"alert alert-{$type}\" role=\"alert\">{$message}</div>";
}

// ── Utility ────────────────────────────────────────────────

/**
 * Apply global security headers to protect against clickjacking and MIME sniffing.
 */
function applySecurityHeaders(): void
{
    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
    }
}

/**
 * Get the base path for URL redirects.
 * Adjust this if your project is in a subdirectory of localhost.
 */
function getBasePath(): string
{
    return '/agroshare';
}

/**
 * Sanitize output to prevent XSS.
 * Use this on all user-generated content rendered to HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
