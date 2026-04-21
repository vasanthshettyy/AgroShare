<?php
require_once __DIR__ . '/../config/db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In / Sign Up — <?= e(APP_NAME) ?></title>
    <meta name="description" content="Join <?= e(APP_NAME) ?> — India's farmer resource sharing platform.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

        /* Basic unified panel styling for this step */
        .auth-slider-container {
            position: relative;
            max-width: 1000px;
            width: 100%;
            min-height: 650px;
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
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            /* Subtle 20px grid pattern */
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 20px 20px;
            /* Synchronize pane fade with overlay slide */
            transition: opacity 0.6s ease-in-out, z-index 0.6s ease-in-out;
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
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(160deg, var(--primary-action) 0%, var(--accent-dark) 60%, #2B4A2D 100%);
            color: white;
            z-index: 10;
            transition: transform 0.6s ease-in-out;
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

        .form-head { margin-bottom: 22px; }
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

        .form-group { margin-bottom: 12px; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
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
        .pw-hint {
            font-size: 0.7rem;
            color: var(--text-subtle);
            margin-top: 3px;
        }
        .btn-submit {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            color: #FFF; border: none;
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.95rem; font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center;
            justify-content: center; gap: 8px;
            margin-top: 6px;
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

        /* Overlay buttons */
        .overlay-toggle-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.4);
            color: #FFF;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 20px;
            cursor: pointer;
        }

    </style>
</head>
<body>

<div id="auth-slider-container" class="auth-slider-container">

    <div class="login-pane-container">
        <div class="auth-form-panel">

        
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
                           required>
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <button type="button" class="pw-toggle" id="pw-toggle-login"
                            aria-label="Show password" title="Show/hide password">
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

        
    
        </div>
    </div>

    <div class="signup-pane-container">
        <div class="auth-form-panel">

        
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

        <div class="panel-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?>. P2P farmer network.</div>
    
            <div style="margin-top: 30px;">
                <button type="button" id="show-signup-panel" class="overlay-toggle-btn">Sign Up</button>
                <button type="button" id="show-login-panel" class="overlay-toggle-btn">Log In</button>
            </div>
        </div>
    </div>

</div>

<script>
'use strict';

// ── Panel Toggle Logic ────────────────────────────────────
const container = document.getElementById('auth-slider-container');
const showSignupBtn = document.getElementById('show-signup-panel');
const showLoginBtn = document.getElementById('show-login-panel');

if (container && showSignupBtn && showLoginBtn) {
    if (showSignupBtn.type === 'button' && showLoginBtn.type === 'button') {
        showSignupBtn.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        showLoginBtn.addEventListener('click', () => {
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
            showSvg = document.getElementById('eye-open');
            hideSvg = document.getElementById('eye-closed');
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
if (loginForm) {
    const loginFormInputs = Array.from(loginForm.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"])'));
    loginFormInputs.forEach((input, index) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = loginFormInputs[index + 1];
                if (next) next.focus();
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
</script>
</body>
</html>
