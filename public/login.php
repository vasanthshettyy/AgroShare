<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/GoogleAuthController.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $redirect = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
        ? getBasePath() . '/public/admin/dashboard.php'
        : 'dashboard.php';
    header('Location: ' . $redirect);
    exit();
}

$errors = [];
$old    = [];
$old_identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    if (isset($_POST['identifier'])) {
        // --- LOGIN LOGIC ---
        $identifier = trim($_POST['identifier'] ?? '');
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $identifier = strtolower($identifier);
        }
        $password   = $_POST['password'] ?? '';
        $remember   = isset($_POST['remember_me']);
        $old_identifier = $identifier;

        // ── CAPTCHA Validation ──────────────────────────
        $captcha_input  = strtoupper(trim($_POST['captcha_answer'] ?? ''));
        $captcha_stored = $_SESSION['captcha_code'] ?? null;
        if ($captcha_stored === null || $captcha_input === '' || $captcha_input !== $captcha_stored) {
            $errors['captcha'] = 'Incorrect code. Please enter the characters shown.';
        }

        if (empty($errors)) {
            if (empty($identifier)) {
                $errors['identifier'] = 'Phone number or email is required.';
            }
            if (empty($password)) {
                $errors['password'] = 'Password is required.';
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id, full_name, password_hash, role, is_active FROM users WHERE phone = ? OR email = ?");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password_hash'])) {
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    $errors['general'] = 'Your account has been deactivated. Please contact support.';
                    $maskedId = (strlen($identifier) >= 4) ? substr($identifier, 0, 2) . '***' . substr($identifier, -2) : '***';
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
                    logAuditEvent($conn, 'login_failed', $user['id'], "Failed login attempt (Account Deactivated) for: " . $maskedId . " from IP: " . $ip);
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['role']          = $user['role'];
                    $_SESSION['full_name']     = $user['full_name'];
                    $_SESSION['persist']       = $remember;
                    $_SESSION['last_activity'] = time();

                    logAuditEvent($conn, 'login_success', $user['id'], "User logged in successfully: " . $user['full_name']);

                    $redirect = ($user['role'] === 'admin')
                        ? getBasePath() . '/public/admin/dashboard.php'
                        : 'dashboard.php';

                    if (!$remember) {
                        echo '<!DOCTYPE html><html><head><script>';
                        echo 'sessionStorage.setItem("agroshare_tab","1");';
                        echo 'window.location.href="' . $redirect . '";';
                        echo '</script></head><body></body></html>';
                        exit();
                    }
                    header('Location: ' . $redirect);
                    exit();
                }
            } else {
                $errors['general'] = 'Invalid credentials.';
                $maskedId = (strlen($identifier) >= 4) ? substr($identifier, 0, 2) . '***' . substr($identifier, -2) : '***';
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
                logAuditEvent($conn, 'login_failed', null, "Failed login attempt for: " . $maskedId . " from IP: " . $ip);
            }
        }
    } else {
        // --- SIGNUP LOGIC ---
        $full_name        = trim($_POST['full_name'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $email            = strtolower(trim($_POST['email'] ?? ''));
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $city             = trim($_POST['city'] ?? '');
        $state            = trim($_POST['state'] ?? '');

        $old = compact('full_name', 'phone', 'email', 'city', 'state');

        if (empty($full_name)) {
            $errors['full_name'] = 'Full name is required.';
        } elseif (mb_strlen($full_name) > 120) {
            $errors['full_name'] = 'Max 120 characters.';
        }

        if (empty($phone)) {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errors['phone'] = 'Valid 10-digit Indian mobile required.';
        }

        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Min 8 characters required.';
        } elseif (!preg_match('/\d/', $password)) {
            $errors['password'] = 'Must contain at least one number.';
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (empty($city))  { $errors['city']  = 'City is required.';  }
        if (empty($state)) { $errors['state'] = 'State is required.'; }

        // Duplicate checks
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors['phone'] = 'This phone number is already registered.';
            }
            $stmt->close();

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors['email'] = 'This email address is already registered.';
            }
            $stmt->close();
        }

        if (empty($errors)) {
            $password_hash  = password_hash($password, PASSWORD_ARGON2ID);
            $email_value    = !empty($email) ? $email : null;
            $village_value  = $city;
            $district_value = $city;

            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, phone, email, password_hash, village, district, state)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssssss', $full_name, $phone, $email_value, $password_hash, $village_value, $district_value, $state);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Account created! Please log in to get started.');
            header('Location: login.php');
            exit();
        }
    }
}

// ── Generate fresh CAPTCHA for the page (after any POST validation) ──
$captcha_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_code  = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_code .= $captcha_chars[random_int(0, strlen($captcha_chars) - 1)];
}
$_SESSION['captcha_code'] = $captcha_code;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In / Sign Up — <?= e(APP_NAME) ?></title>
    <meta name="description" content="Join <?= e(APP_NAME) ?> — India's farmer resource sharing platform.">
    <?php require_once __DIR__ . '/includes/theme-script.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"></noscript>

    <style>
        /* ── Tokens (AgroShare Dark Only) ──────────────── */
        :root {
            --bg-color:            hsl(144, 28%, 6%);
            --surface-color:       hsl(150, 24%, 10%);
            --text-main:           hsl(90, 20%, 90%);
            --text-muted:          hsl(140, 14%, 60%);
            --text-subtle:         hsl(150, 12%, 38%);
            --border-color:        hsl(150, 20%, 16%);
            --primary-action:      hsl(150, 50%, 45%); 
            --secondary-action:    hsl(171, 35%, 55%);
            --accent-dark:         hsl(150, 50%, 30%);
            --accent-soft:         hsl(150, 15%, 25%);
            --danger:              #E11D48;
            --primary-10:          rgba(76, 175, 120, 0.12);
            --secondary-10:        rgba(90, 180, 170, 0.10);
            --shadow-lg:           0 10px 25px rgba(0, 0, 0, 0.5);
            --radius:              18px;
            --radius-sm:           12px;
            --font:                'Inter', system-ui, -apple-system, sans-serif;
        }

        [data-theme="light"] {
            --bg-color: hsl(120, 10%, 95%);
            --surface-color: hsl(0, 0%, 100%);
            --text-main: hsl(210, 20%, 18%);
            --text-muted: hsl(210, 10%, 45%);
            --text-subtle: hsl(210, 8%, 60%);
            --border-color: hsl(210, 12%, 78%);
            --primary-action: hsl(150, 55%, 38%);
            --secondary-action: hsl(171, 40%, 42%);
            --accent-soft: hsl(150, 20%, 85%);
            --accent-dark: hsl(150, 45%, 32%);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .theme-transitioning, .theme-transitioning * {
            transition: background 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease !important;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }

        body {
            font-family: var(--font);
            background: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            -webkit-font-smoothing: antialiased;
        }

        /* -- Floating Theme Toggle -- */
        .theme-toggle-wrapper {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 1000;
        }
        .btn-icon.theme-toggle-btn {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 0;
            cursor: pointer;
        }
        .btn-icon.theme-toggle-btn:hover {
            border-color: var(--primary-action);
            color: var(--primary-action);
            transform: rotate(15deg) scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .theme-toggle-animated {
            position: relative;
            width: 20px;
            height: 20px;
        }
        #theme-icon-sun, #theme-icon-moon {
            position: absolute;
            top: 0;
            left: 0;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
        }
        #theme-icon-sun { opacity: 0; transform: rotate(-90deg) scale(0); }
        #theme-icon-moon { opacity: 1; transform: rotate(0) scale(1); }

        [data-theme="light"] #theme-icon-sun { opacity: 1; transform: rotate(0) scale(1); }
        [data-theme="light"] #theme-icon-moon { opacity: 0; transform: rotate(90deg) scale(0); }

        /* Basic unified panel styling for this step */
        .auth-slider-container {
            position: relative;
            max-width: 880px;
            width: 100%;
            min-height: 660px;
            background: var(--surface-color);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .login-pane-container,
        .signup-pane-container {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow-y: auto;
            scrollbar-width: none; /* Hide for Firefox */
            -ms-overflow-style: none; /* Hide for IE/Edge */
            /* Subtle 20px grid pattern */
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 20px 20px;
            -ms-overflow-style: none; /* Hide for IE/Edge */
            transition: opacity 0.6s ease-in-out, z-index 0.6s ease-in-out;
        }
        .login-pane-container::-webkit-scrollbar,
        .signup-pane-container::-webkit-scrollbar {
            display: none;
        }

        .login-pane-container {
            left: 0;
            opacity: 1;
            z-index: 2;
            pointer-events: auto;
        }

        .signup-pane-container {
            right: 0;
            opacity: 0;
            z-index: 1;
            pointer-events: none;
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(160deg, var(--primary-action) 0%, var(--accent-dark) 60%, #2B4A2D 100%);
            color: white;
            z-index: 10;
            transition: transform 0.6s ease-in-out;
        }

        /* ── Overlay Inner Graphics ──────────────────────── */
        .auth-panel {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .auth-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(180,207,191,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(64,161,144,0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        .auth-panel::after {
            content: '';
            position: absolute;
            bottom: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            border: 40px solid rgba(255,255,255,0.05);
            pointer-events: none;
        }

        .panel-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .panel-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1.5px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .panel-brand-mark svg { color: #FFF; }
        .panel-brand-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: #FFF;
            letter-spacing: -0.4px;
        }

        .panel-content {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: center;
        }
        .content-login, .content-signup {
            position: absolute;
            width: 100%;
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
        .content-login {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }
        .content-signup {
            opacity: 0;
            pointer-events: none;
            transform: translateX(20px);
        }
        .auth-slider-container.right-panel-active .content-login {
            opacity: 0;
            pointer-events: none;
            transform: translateX(-20px);
        }
        .auth-slider-container.right-panel-active .content-signup {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }

        .panel-content h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #FFF;
            line-height: 1.25;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }
        .panel-content p {
            font-size: 0.88rem;
            color: rgba(255,255,255,0.72);
            line-height: 1.65;
            margin-bottom: 28px;
        }

        /* Feature chips */
        .panel-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .feature-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 40px;
            padding: 8px 14px;
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(255,255,255,0.88);
        }
        .feature-chip svg { flex-shrink: 0; color: var(--accent-soft); }

        /* Progress step indicator (Signup) */
        .steps-indicator {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .step-num {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            border: 1.5px solid rgba(255,255,255,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 800;
            color: #FFF; flex-shrink: 0;
        }
        .step-num.done {
            background: var(--secondary-action);
            border-color: var(--secondary-action);
        }
        .step-text {
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(255,255,255,0.82);
        }

        .panel-footer {
            position: relative;
            z-index: 1;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.45);
        }

        /* ── Sliding Animation States ────────────────────── */
        .auth-slider-container.right-panel-active .login-pane-container {
            opacity: 0;
            z-index: 1;
            pointer-events: none;
        }

        .auth-slider-container.right-panel-active .signup-pane-container {
            opacity: 1;
            z-index: 5;
            pointer-events: auto;
        }

        .auth-slider-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .auth-form-panel {
            width: 100%;
        }

        .form-head { margin-bottom: 18px; }
        .form-head h1 {
            font-size: 1.45rem; font-weight: 800;
            color: var(--text-main); letter-spacing: -0.4px;
            margin-bottom: 3px;
        }
        .form-head p { font-size: 0.83rem; color: var(--text-muted); }

        .alert {
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 14px;
            font-size: 0.83rem; font-weight: 500;
        }
        .alert-error   { background: rgba(198,40,40,0.08);  color: var(--danger);          border: 1px solid rgba(198,40,40,0.2);  }
        .alert-success { background: rgba(19,83,44,0.08);   color: var(--primary-action);  border: 1px solid rgba(19,83,44,0.2);   }

        .form-group { margin-bottom: 10px; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .form-label {
            display: block;
            font-size: 0.76rem; font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px; letter-spacing: 0.1px;
        }
        .input-wrap { position: relative; }
        .form-input {
            width: 100%; height: 44px;
            padding: 0 12px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.875rem;
            color: var(--text-main);
            background: var(--bg-color);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-input.is-invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 4px rgba(198,40,40,0.12);
        }
        .form-input.has-icon { padding-left: 42px; }
        .form-input.has-suffix { padding-right: 44px; }
        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-subtle);
            pointer-events: none;
            display: flex;
        }
        .input-icon svg { width: 17px; height: 17px; }
        .pw-toggle {
            position: absolute;
            right: 11px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            padding: 4px;
            color: var(--text-subtle);
            cursor: pointer; display: flex;
            align-items: center;
        }
        .pw-toggle svg { width: 16px; height: 16px; }

        .error-msg {
            display: block;
            font-size: 0.72rem; font-weight: 600;
            color: var(--danger);
            margin-top: 3px;
        }
        .ajax-status {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 3px;
            min-height: 1rem;
        }
        .status-checking { color: var(--text-muted); }
        .status-available { color: var(--primary-action); }
        .status-error { color: var(--danger); }

        .pw-strength {
            height: 3px;
            border-radius: 4px;
            background: var(--border-color);
            margin-top: 6px;
            overflow: hidden;
        }
        .pw-strength-bar {
            height: 100%;
            border-radius: 4px;
            width: 0;
            transition: width 0.3s ease, background 0.3s ease;
        }
        /* ── CAPTCHA Row ─────────────────────────────── */
        .captcha-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 14px;
            background: rgba(76, 175, 120, 0.06);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
        }
        .captcha-group.is-invalid {
            border-color: var(--danger);
            background: rgba(198, 40, 40, 0.06);
        }
        .captcha-icon {
            color: var(--primary-action);
            flex-shrink: 0;
            display: flex;
        }
        .captcha-icon svg { width: 20px; height: 20px; }
        .captcha-code-display {
            position: relative;
            padding: 8px 18px;
            background: hsl(144, 10%, 15%);
            border: 2px solid hsl(150, 15%, 25%);
            border-radius: 4px;
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
        }
        .captcha-code-display::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(25deg, transparent 42%, hsl(150,20%,30%) 42.5%, hsl(150,20%,30%) 43%, transparent 43.5%),
                linear-gradient(-35deg, transparent 46%, hsl(0,0%,35%) 46.5%, hsl(0,0%,35%) 47%, transparent 47.5%),
                linear-gradient(65deg, transparent 30%, hsl(150,15%,28%) 30.5%, hsl(150,15%,28%) 31%, transparent 31.5%),
                linear-gradient(-15deg, transparent 55%, hsl(0,0%,32%) 55.5%, hsl(0,0%,32%) 56%, transparent 56.5%);
            pointer-events: none;
            z-index: 2;
        }
        .captcha-chars {
            display: flex;
            gap: 2px;
            font-family: 'Courier New', monospace;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 5px;
            color: hsl(90, 20%, 80%);
            position: relative;
            z-index: 1;
        }
        .captcha-chars span {
            display: inline-block;
        }
        .captcha-chars span:nth-child(1) { transform: rotate(-3deg); }
        .captcha-chars span:nth-child(2) { transform: rotate(4deg) translateY(2px); }
        .captcha-chars span:nth-child(3) { transform: rotate(-5deg) translateY(-1px); }
        .captcha-chars span:nth-child(4) { transform: rotate(3deg) translateY(1px); }
        .captcha-chars span:nth-child(5) { transform: rotate(-2deg) translateY(-2px); }
        .captcha-chars span:nth-child(6) { transform: rotate(5deg); }
        .captcha-input {
            width: 140px;
            height: 38px;
            text-align: center;
            font-size: 1rem;
            font-weight: 700;
            font-family: var(--font);
            color: var(--text-main);
            background: var(--bg-color);
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: border-color 0.2s;
        }
        .captcha-input:focus {
            outline: none;
            border-color: var(--primary-action);
        }
        .captcha-input.is-invalid {
            border-color: var(--danger);
        }
        .captcha-refresh {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            margin-left: auto;
            transition: color 0.2s, transform 0.3s;
        }
        .captcha-refresh:hover {
            color: var(--primary-action);
            transform: rotate(180deg);
        }
        .captcha-refresh svg { width: 16px; height: 16px; }

        .pw-hint {
            font-size: 0.7rem;
            color: var(--text-subtle);
            margin-top: 3px;
        }
        .btn-submit {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            color: #FFF; 
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center;
            justify-content: center; gap: 8px;
            margin-top: 6px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Glass shimmer effect */
        .btn-submit::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 40px;
            height: 200%;
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(30deg);
            transition: all 0s;
            filter: blur(10px);
            opacity: 0;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 175, 120, 0.35);
            border-color: rgba(255, 255, 255, 0.4);
            filter: brightness(1.05);
        }

        .btn-submit:hover::after {
            left: 140%;
            transition: all 0.7s cubic-bezier(0.19, 1, 0.22, 1);
            opacity: 1;
        }

        .btn-submit:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(76, 175, 120, 0.3);
            filter: brightness(0.95);
        }
        .btn-submit svg { width: 17px; height: 17px; }
        
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            margin-top: -2px;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 500;
        }
        .remember-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--primary-action);
            cursor: pointer;
        }
        .forgot-link {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--secondary-action);
        }

        /* Pane switch row (top in-form toggle controls) */
        .auth-switch-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
            width: 100%;
            flex-wrap: wrap;
        }
        .signup-pane-container .auth-switch-row,
        .signup-pane-container .pane-toggle-bar {
            justify-content: flex-start;
        }
        .pane-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.1px;
            white-space: nowrap;
            cursor: pointer;
            transition: transform 0.2s ease, color 0.2s ease, border-color 0.2s ease, background 0.2s ease;
        }
        .pane-toggle-btn:hover {
            color: var(--text-main);
            border-color: rgba(64,161,144,0.55);
            background: rgba(64,161,144,0.12);
        }
        .pane-toggle-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px var(--secondary-10);
            border-color: var(--secondary-action);
        }
        #switch-to-signup:hover {
            transform: translateX(3px);
        }
        #switch-to-login:hover {
            transform: translateX(-3px);
        }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 768px) {
            .auth-slider-container {
                min-height: auto;
                display: flex;
                flex-direction: column;
            }
            .login-pane-container,
            .signup-pane-container,
            .overlay-container {
                position: relative;
                width: 100%;
                left: 0 !important;
                right: 0 !important;
                transform: none !important;
                opacity: 1 !important;
                z-index: 1 !important;
                pointer-events: auto !important;
                transition: none !important;
            }
            .signup-pane-container {
                display: none;
            }
            .auth-slider-container.right-panel-active .login-pane-container {
                display: none;
            }
            .auth-slider-container.right-panel-active .signup-pane-container {
                display: flex;
            }
            .overlay-container {
                order: -1;
            }
            .panel-content p {
                display: none;
            }
            .auth-form-panel { padding: 24px 20px; }
            .auth-panel { padding: 32px 28px 28px; }
            .panel-content h2 { font-size: 1.35rem; }
        }

        /* -- New In-Form Switch Buttons -- */
        .auth-switch-row {
            display: flex;
            margin-bottom: 24px;
            position: relative;
            z-index: 5;
        }

        .auth-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--primary-10);
            border: 1px solid var(--border-color);
            border-radius: 100px;
            color: var(--primary-action);
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            cursor: pointer;
        }

        .auth-chip span {
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .auth-chip:hover {
            background: var(--accent-soft);
            border-color: var(--primary-action);
            color: var(--text-main);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .auth-chip.go-right:hover span { transform: translateX(4px); }
        .auth-chip.go-left:hover span { transform: translateX(-4px); }

        @media (max-width: 480px) {
            .auth-switch-row { margin-bottom: 20px; }
            .auth-chip { padding: 6px 12px; font-size: 0.8rem; }
        }

        /* -- Trust Badges -- */
        .trust-badges-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
        }
        .trust-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 500;
        }
        .trust-badge svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
            flex-shrink: 0;
        }

        /* -- Google Login Buttons (Emerald Harvest Theme) -- */
        .btn-google {
            width: 100%; height: 50px;
            background: var(--surface-color);
            color: var(--text-main); 
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.95rem; font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center;
            justify-content: center; gap: 14px;
            margin-top: 16px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-google:hover {
            background: var(--primary-10);
            border-color: var(--primary-action);
            color: var(--primary-action);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2), 0 0 15px rgba(76, 175, 120, 0.1);
        }
        .btn-google:active {
            transform: translateY(0) scale(0.98);
        }
        .btn-google svg { width: 20px; height: 20px; }

        .auth-divider {
            display: flex; align-items: center;
            gap: 16px; margin: 16px 0;
            color: var(--text-subtle); font-size: 0.75rem; font-weight: 600;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border-color);
        }
        </style></head>
<body>

<div class="theme-toggle-wrapper">
    <?php include __DIR__ . '/includes/theme-toggle-btn.php'; ?>
</div>

<div id="auth-slider-container" class="auth-slider-container">

    <div class="login-pane-container">
        <div class="auth-form-panel">

        <div class="auth-switch-row">
            <button type="button" id="switch-to-signup" class="auth-chip go-right">Sign Up <span>&rarr;</span></button>
        </div>
        
        <div class="form-head">
            <h1>Log In</h1>
            <p>Enter your phone number or email and password to continue.</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        <?= renderFlash() ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Identifier (Phone or Email) -->
            <div class="form-group">
                <label class="form-label" for="identifier">Phone Number or Email</label>
                <div class="input-wrap">
                    <input type="text" id="identifier" name="identifier"
                           value="<?= e($old_identifier) ?>"
                           placeholder="Enter phone or email"
                           class="form-input has-icon<?= isset($errors['identifier']) ? ' is-invalid' : '' ?>"
                           required autofocus>
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                </div>
                <span id="login-identifier-status" class="ajax-status" aria-live="polite"></span>
                <?php if (isset($errors['identifier'])): ?>
                    <span class="error-msg" role="alert"><?= e($errors['identifier']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password"
                           class="form-input has-icon has-suffix<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                           required disabled>
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <button type="button" class="pw-toggle" id="pw-toggle-login"
                            aria-label="Show password" title="Show/hide password" disabled>
                        <!-- Eye open (default — password hidden) -->
                        <svg id="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <!-- Eye closed (shown when password visible) -->
                        <svg id="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="error-msg" role="alert"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Options row -->
            <div class="options-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember_me"> Stay logged in
                </label>
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <!-- CAPTCHA -->
            <div class="captcha-group<?= isset($errors['captcha']) ? ' is-invalid' : '' ?>">
                <span class="captcha-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </span>
                <div class="captcha-code-display" aria-label="CAPTCHA code">
                    <span class="captcha-chars"><?php foreach (str_split($captcha_code) as $ch): ?><span><?= $ch ?></span><?php endforeach; ?></span>
                </div>
                <input type="text" name="captcha_answer" class="captcha-input<?= isset($errors['captcha']) ? ' is-invalid' : '' ?>" placeholder="Type code" maxlength="6" required autocomplete="off" spellcheck="false">
                <button type="button" class="captcha-refresh" title="New code" id="captcha-refresh-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                </button>
            </div>
            <?php if (isset($errors['captcha'])): ?>
                <span class="error-msg" style="margin-top:-8px;margin-bottom:10px;display:block" role="alert"><?= e($errors['captcha']) ?></span>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Log In
            </button>
        </form>

        <div class="auth-divider">OR</div>

        <a href="<?= getGoogleAuthUrl() ?>" class="btn-google">
            <svg viewBox="0 0 48 48" width="18" height="18">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Continue with Google
        </a>


        
    
        </div>
    </div>

    <div class="signup-pane-container">
        <div class="auth-form-panel">

        <div class="auth-switch-row">
            <button type="button" id="switch-to-login" class="auth-chip go-left"><span>&larr;</span> Log In</button>
        </div>
        
        <div class="form-head">
            <h1>Create Account</h1>
            <p>Fill in your details to get started — it's completely free.</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        <?= renderFlash() ?>

        <form method="POST" action="" novalidate id="signup-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Full Name -->
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= e($old['full_name'] ?? '') ?>"
                       placeholder="e.g. Rajesh Kumar"
                       class="form-input<?= isset($errors['full_name']) ? ' is-invalid' : '' ?>"
                       required maxlength="120" autofocus autocomplete="name">
                <?php if (isset($errors['full_name'])): ?>
                    <span class="error-msg" role="alert"><?= e($errors['full_name']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Phone / Email row -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= e($old['phone'] ?? '') ?>"
                           placeholder="e.g. 9876543210"
                           class="form-input<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                           required maxlength="10" inputmode="tel" autocomplete="tel">
                    <span id="phone-status" class="ajax-status" aria-live="polite"></span>
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" id="email" name="email"
                           value="<?= e($old['email'] ?? '') ?>"
                           placeholder="e.g. rajesh@email.com"
                           class="form-input<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                           required autocomplete="email">
                    <span id="email-status" class="ajax-status" aria-live="polite"></span>
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['email']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Password / Confirm row -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Min 8 chars, 1 number"
                               class="form-input has-suffix<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                               required minlength="8" autocomplete="new-password">
                        <button type="button" class="pw-toggle" data-target="password"
                                aria-label="Show password">
                            <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Strength meter -->
                    <div class="pw-strength"><div class="pw-strength-bar" id="pw-bar"></div></div>
                    <span class="pw-hint" id="pw-hint">At least 8 characters &amp; 1 number</span>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['password']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-wrap">
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Re-enter password"
                               class="form-input has-suffix<?= isset($errors['confirm_password']) ? ' is-invalid' : '' ?>"
                               required autocomplete="new-password">
                        <button type="button" class="pw-toggle" data-target="confirm_password"
                                aria-label="Show password">
                            <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['confirm_password']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- City / State row -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="city">City / Village</label>
                    <input type="text" id="city" name="city"
                           value="<?= e($old['city'] ?? '') ?>"
                           placeholder="e.g. Dharwad"
                           class="form-input<?= isset($errors['city']) ? ' is-invalid' : '' ?>"
                           required autocomplete="address-level2">
                    <?php if (isset($errors['city'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['city']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="state">State</label>
                    <input type="text" id="state" name="state"
                           value="<?= e($old['state'] ?? '') ?>"
                           placeholder="e.g. Karnataka"
                           class="form-input<?= isset($errors['state']) ? ' is-invalid' : '' ?>"
                           required autocomplete="address-level1">
                    <?php if (isset($errors['state'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['state']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
                Create Free Account
            </button>
        </form>

        <div class="auth-divider">OR</div>

        <a href="<?= getGoogleAuthUrl() ?>" class="btn-google">
            <svg viewBox="0 0 48 48" width="18" height="18">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Continue with Google
        </a>

        
    
        </div>
    </div>

    <div class="overlay-container">
        <div class="auth-panel" aria-hidden="true">

        <div class="panel-brand">
            <div class="panel-brand-mark">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="panel-brand-name"><?= e(APP_NAME) ?></span>
        </div>

        <div class="panel-content">
            <div class="content-login">
                <h2>Welcome back, farmer.</h2>
                <p>Access your equipment listings, manage bookings, and join community pooling campaigns — all in one place.</p>
                <div class="panel-features">
                    <div class="feature-chip">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                            <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                        </svg>
                        Share &amp; rent farm equipment
                    </div>
                    <div class="feature-chip">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Bulk-buy pooling campaigns
                    </div>
                    <div class="feature-chip">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        Verified farmer trust scores
                    </div>
                </div>
            </div>

            <div class="content-signup">
                <h2>Join the AgroShare network.</h2>
                <p>Thousands of Indian farmers already rent, share, and bulk-buy together. It takes less than 2 minutes to get started.</p>

                <div class="steps-indicator">
                    <div class="step-item">
                        <div class="step-num done">✓</div>
                        <span class="step-text">Visit AgroShare</span>
                    </div>
                    <div class="step-item">
                        <div class="step-num" style="background:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.5);">2</div>
                        <span class="step-text">Create your account <em style="color:rgba(255,255,255,0.5);font-style:normal;">(you are here)</em></span>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <span class="step-text">List or rent equipment</span>
                    </div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <span class="step-text">Join community pooling</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?>. P2P farmer network.</div>
        </div>
    </div>

</div>

<script>
'use strict';

// ── Panel Toggle Logic ────────────────────────────────────
const container         = document.getElementById('auth-slider-container');
const switchToSignupBtn = document.getElementById('switch-to-signup');
const switchToLoginBtn  = document.getElementById('switch-to-login');

if (container) {
    if (switchToSignupBtn) {
        switchToSignupBtn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.add('right-panel-active');
        });
    }
    if (switchToLoginBtn) {
        switchToLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove('right-panel-active');
        });
    }
}

// ── Password visibility toggles ───────────────────────────
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        let input;
        let showSvg, hideSvg;
        
        if (btn.id === 'pw-toggle-login') {
            input = document.querySelector('.login-pane-container #password');
            showSvg = document.querySelector('.login-pane-container #eye-open');
            hideSvg = document.querySelector('.login-pane-container #eye-closed');
        } else {
            const targetId = btn.dataset.target;
            input = document.querySelector(`.signup-pane-container #${targetId}`);
            showSvg = btn.querySelector('.eye-show');
            hideSvg = btn.querySelector('.eye-hide');
        }

        if (!input || !showSvg || !hideSvg) return;

        const isHidden = input.type === 'password';
        input.type             = isHidden ? 'text'  : 'password';
        showSvg.style.display  = isHidden ? 'none'  : 'block';
        hideSvg.style.display  = isHidden ? 'block' : 'none';
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
});

// ── Password strength meter (Signup) ──────────────────────
const signupPwInput = document.querySelector('.signup-pane-container #password');
const pwBar   = document.querySelector('.signup-pane-container #pw-bar');
const pwHint  = document.querySelector('.signup-pane-container #pw-hint');

const strengthLevels = [
    { label: 'Too short',  color: '#C62828', w: '20%'  },
    { label: 'Weak',       color: '#E87020', w: '40%'  },
    { label: 'Fair',       color: '#E8A011', w: '65%'  },
    { label: 'Good',       color: '#40A190', w: '85%'  },
    { label: 'Strong ✓',   color: '#13532C', w: '100%' },
];

function calcStrength(pw) {
    if (pw.length === 0) return -1;
    let score = 0;
    if (pw.length >= 8)  score++;
    if (pw.length >= 12) score++;
    if (/\d/.test(pw))   score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(score, 4);
}

if (signupPwInput && pwBar && pwHint) {
    signupPwInput.addEventListener('input', () => {
        const level = calcStrength(signupPwInput.value);
        if (level < 0) {
            pwBar.style.width      = '0';
            pwHint.textContent     = 'At least 8 characters & 1 number';
            pwHint.style.color     = 'var(--text-subtle)';
        } else {
            const l = strengthLevels[level];
            pwBar.style.width      = l.w;
            pwBar.style.background = l.color;
            pwHint.textContent     = l.label;
            pwHint.style.color     = l.color;
        }
    });
}

// ── Prevent Enter from submitting, move to next input ─────
// Signup Form
const signupForm = document.getElementById('signup-form');
if (signupForm) {
    const signupFormInputs = Array.from(signupForm.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"])'));
    signupFormInputs.forEach((input, index) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = signupFormInputs[index + 1];
                if (next) next.focus();
            }
        });
    });
}

// Login Form
const loginForm = document.querySelector('.login-pane-container form');
const loginIdentifierInput = document.getElementById('identifier');
const loginIdentifierStatus = document.getElementById('login-identifier-status');
const loginPasswordInput = document.getElementById('password');
const loginPwToggleBtn = document.getElementById('pw-toggle-login');
const loginSubmitBtn = loginForm ? loginForm.querySelector('.btn-submit') : null;
const loginCsrfToken = loginForm ? loginForm.querySelector('input[name="csrf_token"]').value : '';

async function validateLoginIdentifier() {
    const value = loginIdentifierInput.value.trim();
    if (!value) {
        loginIdentifierStatus.textContent = '';
        loginIdentifierStatus.className = 'ajax-status';
        loginPasswordInput.disabled = true;
        loginPwToggleBtn.disabled = true;
        return;
    }

    loginIdentifierStatus.textContent = 'Verifying account...';
    loginIdentifierStatus.className = 'ajax-status status-checking';

    try {
        const response = await fetch('api/validate-login-identifier.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier: value, csrf_token: loginCsrfToken })
        });
        const data = await response.json();

        if (data.success) {
            loginIdentifierStatus.textContent = data.message;
            loginIdentifierStatus.className = 'ajax-status status-available';
            loginPasswordInput.disabled = false;
            loginPwToggleBtn.disabled = false;
            if (loginSubmitBtn) loginSubmitBtn.disabled = false;
        } else {
            loginIdentifierStatus.textContent = data.message;
            loginIdentifierStatus.className = 'ajax-status status-error';
            loginPasswordInput.disabled = true;
            loginPwToggleBtn.disabled = true;
            if (loginSubmitBtn) loginSubmitBtn.disabled = true;
        }
    } catch (e) {
        loginIdentifierStatus.textContent = 'Connection error.';
        loginIdentifierStatus.className = 'ajax-status status-error';
    }
}

if (loginIdentifierInput) {
    loginIdentifierInput.addEventListener('input', debounce(validateLoginIdentifier, 500));
    loginIdentifierInput.addEventListener('blur', validateLoginIdentifier);
    
    // Also handle existing value on load (if any)
    if (loginIdentifierInput.value.trim()) {
        validateLoginIdentifier();
    }
}

if (loginForm) {
    const loginFormInputs = Array.from(loginForm.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"])'));
    loginFormInputs.forEach((input, index) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                // If it's the identifier input and it's invalid, block enter
                if (input.id === 'identifier' && loginPasswordInput.disabled) {
                    e.preventDefault();
                    return;
                }
                
                e.preventDefault();
                const next = loginFormInputs[index + 1];
                if (next && !next.disabled) next.focus();
                else if (!loginPasswordInput.disabled) loginForm.submit();
            }
        });
    });
}

// ── AJAX Availability Checks (Signup) ─────────────────────
const csrfInput = document.querySelector('.signup-pane-container input[name="csrf_token"]');
const csrfToken = csrfInput ? csrfInput.value : '';

function debounce(func, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}

async function checkAvailability(field, inputElement, statusElement) {
    const value = inputElement.value.trim();
    if (!value) {
        statusElement.textContent = '';
        statusElement.className = 'ajax-status';
        return;
    }

    // Basic format checks before hitting API
    if (field === 'phone' && !/^[6-9]\d{9}$/.test(value)) {
        statusElement.textContent = 'Invalid format';
        statusElement.className = 'ajax-status status-error';
        return;
    }
    if (field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        statusElement.textContent = 'Invalid format';
        statusElement.className = 'ajax-status status-error';
        return;
    }

    statusElement.textContent = 'Checking...';
    statusElement.className = 'ajax-status status-checking';

    try {
        const response = await fetch('api/check-signup-availability.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ field, value, csrf_token: csrfToken })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            statusElement.textContent = data.message;
            statusElement.className = 'ajax-status status-error';
        } else if (data.exists) {
            statusElement.textContent = data.message;
            statusElement.className = 'ajax-status status-error';
        } else {
            statusElement.textContent = data.message;
            statusElement.className = 'ajax-status status-available';
        }
    } catch (e) {
        statusElement.textContent = 'Error checking availability';
        statusElement.className = 'ajax-status status-error';
    }
}

const phoneInput = document.querySelector('.signup-pane-container #phone');
const phoneStatus = document.querySelector('.signup-pane-container #phone-status');
const emailInput = document.querySelector('.signup-pane-container #email');
const emailStatus = document.querySelector('.signup-pane-container #email-status');

if (phoneInput && phoneStatus) {
    const checkPhone = () => checkAvailability('phone', phoneInput, phoneStatus);
    phoneInput.addEventListener('input', debounce(checkPhone, 400));
    phoneInput.addEventListener('blur', checkPhone);
}

if (emailInput && emailStatus) {
    const checkEmail = () => checkAvailability('email', emailInput, emailStatus);
    emailInput.addEventListener('input', debounce(checkEmail, 400));
    emailInput.addEventListener('blur', checkEmail);
}

// ── Local Captcha Refresh ──
const captchaRefreshBtn = document.getElementById('captcha-refresh-btn');
const captchaCharsContainer = document.querySelector('.captcha-chars');

if (captchaRefreshBtn && captchaCharsContainer) {
    captchaRefreshBtn.addEventListener('click', async () => {
        captchaRefreshBtn.style.pointerEvents = 'none';
        captchaRefreshBtn.style.opacity = '0.5';
        
        try {
            const response = await fetch('api/refresh-captcha.php');
            const data = await response.json();
            if (data.success) {
                captchaCharsContainer.innerHTML = data.captcha.split('').map(ch => `<span>${ch}</span>`).join('');
            }
        } catch (e) {
            console.error('Offline captcha refresh failed.');
        } finally {
            captchaRefreshBtn.style.pointerEvents = 'auto';
            captchaRefreshBtn.style.opacity = '1';
        }
    });
}
</script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
