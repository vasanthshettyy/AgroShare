<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Helpers/mail.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Always reset verified state when a new recovery attempt starts.
    unset($_SESSION['reset_verified']);

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $old_email = $email;

    if (empty($errors)) {
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
    }

    if (empty($errors)) {
        // Rate limit: Max 3 requests per 15 mins for this email
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $rateLimit = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        if ($rateLimit >= 3) {
            $errors['general'] = 'Too many requests. Please try again after 15 minutes.';
        } else {
            $otpDispatched = false;

            // Find user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Invalidate old tokens
                $stmt = $conn->prepare("UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->close();

                // Generate 6-digit OTP
                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                // Store OTP
                $stmt = $conn->prepare("INSERT INTO password_resets (user_id, phone, email, otp, expires_at) VALUES (?, '', ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
                $stmt->bind_param('iss', $user['id'], $email, $otp);
                $stmt->execute();
                $stmt->close();

                // Send Email
                $otpDispatched = sendOtpEmail($email, $otp);
                if (!$otpDispatched) {
                    error_log('OTP email dispatch failed for password reset: ' . $email);
                }
            }

            // Generic flow and message to avoid account/email enumeration.
            $_SESSION['reset_email'] = $email;
            setFlash('success', "If this email is registered, an OTP has been sent. Please check your inbox/spam folder.");
            header('Location: verify-otp.php');
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
    <title>Forgot Password — <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color:            hsl(144, 28%, 6%);
            --surface-color:       hsl(150, 24%, 10%);
            --text-main:           hsl(90, 20%, 90%);
            --text-muted:          hsl(140, 14%, 60%);
            --border-color:        hsl(150, 20%, 16%);
            --primary-action:      hsl(150, 50%, 45%); 
            --secondary-action:    hsl(171, 35%, 55%);
            --danger:              #E11D48;
            --primary-10:          rgba(76, 175, 120, 0.12);
            --shadow-lg:           0 10px 25px rgba(0, 0, 0, 0.5);
            --radius:              18px;
            --radius-sm:           12px;
            --font:                'Inter', system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a { color: inherit; text-decoration: none; }
        body { font-family: var(--font); background: var(--bg-color); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .auth-wrapper { max-width: 460px; width: 100%; background: var(--surface-color); border-radius: var(--radius); box-shadow: var(--shadow-lg); padding: 40px; }
        h1 { font-size: 1.75rem; margin-bottom: 0.5rem; }
        p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem; line-height: 1.5; }
        .form-group { margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .input-wrap { position: relative; }
        .form-input { width: 100%; background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px 16px 12px 40px; color: var(--text-main); font-size: 0.95rem; outline: none; transition: border-color 0.2s ease; }
        .form-input:focus { border-color: var(--primary-action); }
        .form-input.is-invalid { border-color: var(--danger); }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 18px; height: 18px; }
        .btn-submit { width: 100%; background: var(--primary-action); color: #fff; border: none; padding: 12px; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer; transition: filter 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 1rem; }
        .btn-submit:hover { filter: brightness(1.1); }
        .error-msg { color: var(--danger); font-size: 0.8rem; font-weight: 500; }
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500; margin-bottom: 1rem; }
        .alert-error { background: rgba(225, 29, 72, 0.1); border: 1px solid rgba(225, 29, 72, 0.2); color: #ff4c4c; }
        .alert-success { background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.2); color: #4caf50; }
        .auth-footer { text-align: center; margin-top: 1.5rem; }
        .auth-footer a { color: var(--primary-action); font-weight: 600; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; background: var(--primary-10); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--primary-action); font-size: 0.8rem; font-weight: 700; transition: all 0.2s ease; margin-bottom: 1.5rem; width: fit-content; }
        .btn-back:hover { background: var(--primary-action); color: #fff; }
        .btn-back svg { width: 14px; height: 14px; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <a href="login.php" class="btn-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Login
    </a>
    <h1>Forgot Password</h1>
    <p>Enter your registered email address. We will send you an OTP to reset your password.</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>
    <?= renderFlash() ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <div class="input-wrap">
                <input type="email" id="email" name="email" value="<?= e($old_email) ?>" placeholder="e.g. farmer@example.com" class="form-input has-icon<?= isset($errors['email']) ? ' is-invalid' : '' ?>" required autofocus>
                <span class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </span>
            </div>
            <?php if (isset($errors['email'])): ?>
                <span class="error-msg"><?= e($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">Send OTP</button>
    </form>
</div>
</body>
</html>
