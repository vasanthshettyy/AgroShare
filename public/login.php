<?php
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors    = [];
$old_phone = '';

// ── Handle POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);
    $old_phone = $phone;

    if (empty($errors)) {
        if (empty($phone)) {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errors['phone'] = 'Valid 10-digit Indian mobile required.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['persist']       = $remember;
            $_SESSION['last_activity'] = time();

            // Log successful login
            logAuditEvent($conn, [
                'actor_user_id' => $user['id'],
                'action_type'   => 'login_success',
                'description'   => "User logged in successfully: " . $user['full_name']
            ]);

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
        } else {
            $errors['general'] = 'Invalid phone number or password.';

            // Log failed login attempt
            $maskedPhone = (strlen($phone) >= 2) ? str_repeat('*', strlen($phone)-2) . substr($phone, -2) : $phone;
            logAuditEvent($conn, [
                'action_type'   => 'login_failed',
                'description'   => "Failed login attempt for phone: " . $maskedPhone,
                'metadata'      => [
                    'attempted_phone' => $maskedPhone,
                    'reason' => ($user) ? 'invalid_password' : 'unknown_user'
                ]
            ]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In — <?= e(APP_NAME) ?></title>
    <meta name="description" content="Log in to your <?= e(APP_NAME) ?> account to manage equipment and bookings.">

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

        /* ── Reset ────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }

        /* ── Page Shell ───────────────────────────────────── */
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

        /* ── Auth wrapper — split panel ───────────────────── */
        .auth-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 860px;
            width: 100%;
            background: var(--surface-color);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            animation: slideUp 0.45s ease forwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0);    }
        }

        /* ── Left Panel — branding ─────────────────────────
           Dark green art panel with pattern overlay            */
        .auth-panel {
            background: linear-gradient(160deg, var(--primary-action) 0%, var(--accent-dark) 60%, #2B4A2D 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Soft SVG pattern overlay */
        .auth-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(180,207,191,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(64,161,144,0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Decorative circles */
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

        .panel-footer {
            position: relative;
            z-index: 1;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.45);
        }

        /* ── Right Panel — form ─────────────────────────── */
        .auth-form-panel {
            padding: 44px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-head {
            margin-bottom: 28px;
        }
        .form-head h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.4px;
            margin-bottom: 4px;
        }
        .form-head p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Alert */
        .alert {
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 0.83rem;
            font-weight: 500;
            animation: slideUp 0.3s ease;
        }
        .alert-error   { background: rgba(198,40,40,0.08);  color: var(--danger);          border: 1px solid rgba(198,40,40,0.2);  }
        .alert-success { background: rgba(19,83,44,0.08);   color: var(--primary-action);  border: 1px solid rgba(19,83,44,0.2);   }
        .alert-info    { background: rgba(64,161,144,0.10); color: var(--secondary-action); border: 1px solid rgba(64,161,144,0.25); }

        /* Form groups */
        .form-group { margin-bottom: 14px; }
        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
            letter-spacing: 0.1px;
        }

        .input-wrap { position: relative; }

        .form-input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.9rem;
            color: var(--text-main);
            background: var(--bg-color);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary-action);
            box-shadow: 0 0 0 4px var(--primary-10);
            background: var(--bg-color);
            color: var(--text-main);
        }

        /* ── Autofill Hardening ────────────────────────── */
        .form-input:-webkit-autofill,
        .form-input:-webkit-autofill:hover, 
        .form-input:-webkit-autofill:focus, 
        .form-input:-webkit-autofill:active,
        .form-input:autofill {
            -webkit-text-fill-color: var(--text-main) !important;
            -webkit-box-shadow: 0 0 0 1000px var(--bg-color) inset !important;
            box-shadow: 0 0 0 1000px var(--bg-color) inset !important;
            background-color: var(--bg-color) !important;
            border-color: var(--border-color);
            caret-color: var(--text-main);
            transition: background-color 5000s ease-in-out 0s;
        }

        .form-input:-webkit-autofill:focus,
        .form-input:autofill:focus {
            border-color: var(--primary-action) !important;
            -webkit-box-shadow: 0 0 0 1000px var(--bg-color) inset, 0 0 0 4px var(--primary-10) !important;
            box-shadow: 0 0 0 1000px var(--bg-color) inset, 0 0 0 4px var(--primary-10) !important;
        }

        .form-input.is-invalid {

        /* Input icon (left) */
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
        .form-input:focus ~ .input-icon { color: var(--secondary-action); }

        /* Password toggle (right) */
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 4px;
            color: var(--text-subtle);
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .pw-toggle:hover { color: var(--primary-action); }
        .pw-toggle svg  { width: 17px; height: 17px; }

        .error-msg {
            display: block;
            font-size: 0.73rem;
            font-weight: 600;
            color: var(--danger);
            margin-top: 4px;
        }

        /* Options row */
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
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--primary-action); }

        /* Submit button */
        .btn-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            color: #FFF;
            border: none;
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.1px;
            cursor: pointer;
            transition: all 0.22s ease;
            box-shadow: 0 4px 16px rgba(19,83,44,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(19,83,44,0.38);
            background: linear-gradient(135deg, var(--accent-dark), #243525);
        }
        .btn-submit:active { transform: scale(0.98); }
        .btn-submit svg { width: 17px; height: 17px; }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: var(--text-subtle);
            font-size: 0.78rem;
            font-weight: 500;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        /* Footer link */
        .auth-footer {
            text-align: center;
            font-size: 0.84rem;
            color: var(--text-muted);
            margin-top: 20px;
        }
        .auth-footer a {
            font-weight: 700;
            color: var(--primary-action);
            transition: color 0.2s;
        }
        .auth-footer a:hover { color: var(--secondary-action); }

        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--primary-10);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--primary-action);
            font-size: 0.8rem;
            font-weight: 700;
            transition: all 0.2s ease;
            margin-bottom: 20px;
            width: fit-content;
        }
        .btn-back:hover {
            background: var(--primary-action);
            color: #FFF;
            transform: translateX(-4px);
        }
        .btn-back svg { width: 14px; height: 14px; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 680px) {
            .auth-wrapper { grid-template-columns: 1fr; max-width: 420px; }
            .auth-panel   { padding: 32px 28px 28px; }
            .panel-content h2 { font-size: 1.35rem; }
            .panel-content p  { display: none; }
            .auth-form-panel  { padding: 32px 28px; }
        }
        @media (max-width: 400px) {
            .auth-form-panel { padding: 24px 20px; }
            .auth-panel      { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <!-- ══ LEFT — Brand Panel ═══════════════════════════════ -->
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
    </div>

    <!-- ══ RIGHT — Login Form ════════════════════════════════ -->
    <div class="auth-form-panel">
        <a href="signup.php" class="btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Signup
        </a>
        <div class="form-head">
            <h1>Log In</h1>
            <p>Enter your phone number and password to continue.</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        <?= renderFlash() ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <div class="input-wrap">
                    <input type="tel" id="phone" name="phone"
                           value="<?= e($old_phone) ?>"
                           placeholder="10-digit mobile (e.g. 9876543210)"
                           class="form-input has-icon<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                           required maxlength="10" autofocus inputmode="tel">
                    <span class="input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/>
                        </svg>
                    </span>
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error-msg" role="alert"><?= e($errors['phone']) ?></span>
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

        <p class="auth-footer">
            New to <?= e(APP_NAME) ?>? <a href="signup.php">Create a free account</a>
        </p>
    </div>
</div>

<script>
'use strict';
// Password visibility toggle
const pwInput   = document.getElementById('password');
const toggleBtn = document.getElementById('pw-toggle-login');
const eyeOpen   = document.getElementById('eye-open');
const eyeClosed = document.getElementById('eye-closed');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        const isHidden = pwInput.type === 'password';
        pwInput.type            = isHidden ? 'text' : 'password';
        eyeOpen.style.display   = isHidden ? 'none'  : 'block';
        eyeClosed.style.display = isHidden ? 'block' : 'none';
        toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
}
</script>
</body>
</html>
