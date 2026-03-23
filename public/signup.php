<?php
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$old    = [];

// ── Handle POST (Registration) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    $full_name        = trim($_POST['full_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
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

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

    // Duplicate phone check
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['phone'] = 'This phone number is already registered.';
        }
        $stmt->close();
    }

    // Create user
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — <?= e(APP_NAME) ?></title>
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

        /* ── Auth wrapper — split panel ───────────────────── */
        .auth-wrapper {
            display: grid;
            /* Signup: form is wider — 55% form, 45% art */
            grid-template-columns: 1fr 1.2fr;
            max-width: 940px;
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

        /* ── Left Panel — branding ─────────────────────────*/
        .auth-panel {
            background: linear-gradient(160deg, var(--primary-action) 0%, var(--accent-dark) 60%, #2B4A2D 100%);
            padding: 48px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 15% 85%, rgba(180,207,191,0.15) 0%, transparent 50%),
                radial-gradient(circle at 85% 15%, rgba(64,161,144,0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        .auth-panel::after {
            content: '';
            position: absolute;
            bottom: -70px;
            right: -70px;
            width: 260px; height: 260px;
            border-radius: 50%;
            border: 45px solid rgba(255,255,255,0.05);
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
            width: 42px; height: 42px;
            border-radius: 13px;
            background: rgba(255,255,255,0.15);
            border: 1.5px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .panel-brand-mark svg { color: #FFF; }
        .panel-brand-name {
            font-size: 1.2rem; font-weight: 800;
            color: #FFF; letter-spacing: -0.4px;
        }

        .panel-content { position: relative; z-index: 1; }
        .panel-content h2 {
            font-size: 1.65rem; font-weight: 800;
            color: #FFF; line-height: 1.25;
            margin-bottom: 12px; letter-spacing: -0.5px;
        }
        .panel-content p {
            font-size: 0.86rem;
            color: rgba(255,255,255,0.70);
            line-height: 1.65;
            margin-bottom: 24px;
        }

        /* Progress step indicator */
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
            position: relative; z-index: 1;
            font-size: 0.73rem;
            color: rgba(255,255,255,0.40);
        }

        /* ── Right Panel — form ─────────────────────────── */
        .auth-form-panel {
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-head { margin-bottom: 22px; }
        .form-head h1 {
            font-size: 1.45rem; font-weight: 800;
            color: var(--text-main); letter-spacing: -0.4px;
            margin-bottom: 3px;
        }
        .form-head p { font-size: 0.83rem; color: var(--text-muted); }

        /* Alert */
        .alert {
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 14px;
            font-size: 0.83rem; font-weight: 500;
            animation: slideUp 0.3s ease;
        }
        .alert-error   { background: rgba(198,40,40,0.08);  color: var(--danger);          border: 1px solid rgba(198,40,40,0.2);  }
        .alert-success { background: rgba(19,83,44,0.08);   color: var(--primary-action);  border: 1px solid rgba(19,83,44,0.2);   }

        /* Form groups */
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
        .form-label .opt {
            font-weight: 400;
            color: var(--text-subtle);
            font-size: 0.72rem;
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
        .form-input.has-suffix { padding-right: 44px; }

        /* Override Chrome Autofill White Background */
        .form-input:-webkit-autofill,
        .form-input:-webkit-autofill:hover, 
        .form-input:-webkit-autofill:focus, 
        .form-input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px var(--bg-color) inset !important;
            -webkit-text-fill-color: var(--text-main) !important;
            transition: background-color 5000s ease-in-out 0s;
            border-color: var(--border-color);
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 11px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            padding: 4px;
            color: var(--text-subtle);
            cursor: pointer; display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .pw-toggle:hover { color: var(--primary-action); }
        .pw-toggle svg { width: 16px; height: 16px; }

        .error-msg {
            display: block;
            font-size: 0.72rem; font-weight: 600;
            color: var(--danger);
            margin-top: 3px;
        }

        /* Password strength meter */
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

        /* Submit */
        .btn-submit {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            color: #FFF; border: none;
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 0.95rem; font-weight: 700;
            letter-spacing: 0.1px;
            cursor: pointer;
            transition: all 0.22s ease;
            box-shadow: 0 4px 16px rgba(19,83,44,0.28);
            display: flex; align-items: center;
            justify-content: center; gap: 8px;
            margin-top: 6px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(19,83,44,0.38);
            background: linear-gradient(135deg, var(--accent-dark), #243525);
        }
        .btn-submit:active { transform: scale(0.98); }
        .btn-submit svg { width: 17px; height: 17px; }

        /* Footer link */
        .auth-footer {
            text-align: center;
            font-size: 0.83rem;
            color: var(--text-muted);
            margin-top: 18px;
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
        @media (max-width: 720px) {
            .auth-wrapper { grid-template-columns: 1fr; max-width: 460px; }
            .auth-panel   { padding: 28px 28px 24px; }
            .panel-content h2 { font-size: 1.3rem; }
            .panel-content p  { display: none; }
            .auth-form-panel  { padding: 28px 28px; }
        }
        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .auth-form-panel { padding: 22px 18px; }
            .auth-panel { padding: 22px 18px; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <!-- ══ LEFT — Brand Panel ════════════════════════════════ -->
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

        <div class="panel-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?>. Free for all Indian farmers.</div>
    </div>

    <!-- ══ RIGHT — Signup Form ═══════════════════════════════ -->
    <div class="auth-form-panel">
        <a href="login.php" class="btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Login
        </a>
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
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error-msg" role="alert"><?= e($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="opt">(optional)</span></label>
                    <input type="email" id="email" name="email"
                           value="<?= e($old['email'] ?? '') ?>"
                           placeholder="e.g. rajesh@email.com"
                           class="form-input<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                           autocomplete="email">
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

        <p class="auth-footer">
            Already have an account? <a href="login.php">Log In</a>
        </p>
    </div>
</div>

<script>
'use strict';

// ── Password visibility toggles ───────────────────────────
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.dataset.target;
        const input    = document.getElementById(targetId);
        const showSvg  = btn.querySelector('.eye-show');
        const hideSvg  = btn.querySelector('.eye-hide');
        const isHidden = input.type === 'password';

        input.type             = isHidden ? 'text'  : 'password';
        showSvg.style.display  = isHidden ? 'none'  : 'block';
        hideSvg.style.display  = isHidden ? 'block' : 'none';
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
});

// ── Password strength meter ───────────────────────────────
const pwInput = document.getElementById('password');
const pwBar   = document.getElementById('pw-bar');
const pwHint  = document.getElementById('pw-hint');

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

if (pwInput) {
    pwInput.addEventListener('input', () => {
        const level = calcStrength(pwInput.value);
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
</script>
</body>
</html>
