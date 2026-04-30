<?php
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    $otp = trim($_POST['otp'] ?? '');

    if (empty($errors)) {
        if (empty($otp)) {
            $errors['otp'] = 'OTP is required.';
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            $errors['otp'] = 'Valid 6-digit OTP required.';
        }
    }

    if (empty($errors)) {
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND is_used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('ss', $email, $otp);
        $stmt->execute();
        $resetRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($resetRow) {
            $stmt = $conn->prepare("UPDATE password_resets SET is_used = 1 WHERE id = ?");
            $stmt->bind_param('i', $resetRow['id']);
            $stmt->execute();
            $stmt->close();

            $_SESSION['reset_verified'] = true;
            header('Location: reset-password.php');
            exit();
        } else {
            $errors['general'] = 'Invalid or expired OTP.';
            unset($_SESSION['reset_verified']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/fonts.css">
    <style>
        :root {
            --bg-color:            hsl(144, 28%, 6%);
            --surface-color:       hsl(150, 24%, 10%);
            --text-main:           hsl(90, 20%, 90%);
            --text-muted:          hsl(140, 14%, 60%);
            --border-color:        hsl(150, 20%, 16%);
            --primary-action:      hsl(150, 50%, 45%); 
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
        .form-input { width: 100%; background: var(--bg-color); border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px 16px 12px 40px; color: var(--text-main); font-size: 0.95rem; outline: none; transition: border-color 0.2s ease; letter-spacing: 0.2em; font-weight: 700; }
        .form-input:focus { border-color: var(--primary-action); }
        .form-input.is-invalid { border-color: var(--danger); }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 18px; height: 18px; }
        .btn-submit { width: 100%; background: var(--primary-action); color: #fff; border: none; padding: 12px; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer; transition: filter 0.2s ease; margin-top: 1rem; }
        .btn-submit:hover { filter: brightness(1.1); }
        .error-msg { color: var(--danger); font-size: 0.8rem; font-weight: 500; }
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500; margin-bottom: 1rem; }
        .alert-error { background: rgba(225, 29, 72, 0.1); border: 1px solid rgba(225, 29, 72, 0.2); color: #ff4c4c; }
        .alert-success { background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.2); color: #4caf50; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; background: var(--primary-10); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--primary-action); font-size: 0.8rem; font-weight: 700; transition: all 0.2s ease; margin-bottom: 1.5rem; width: fit-content; }
        .btn-back:hover { background: var(--primary-action); color: #fff; }
        .btn-back svg { width: 14px; height: 14px; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <a href="forgot-password.php" class="btn-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back
    </a>
    <h1>Verify OTP</h1>
    <p>Enter the 6-digit OTP sent to <?= e($_SESSION['reset_email']) ?>.</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>
    <?= renderFlash() ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <div class="form-group">
            <label class="form-label" for="otp">One-Time Password</label>
            <div class="input-wrap">
                <input type="text" id="otp" name="otp" placeholder="000000" class="form-input has-icon<?= isset($errors['otp']) ? ' is-invalid' : '' ?>" required maxlength="6" autofocus inputmode="numeric">
                <span class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
            </div>
            <?php if (isset($errors['otp'])): ?>
                <span class="error-msg"><?= e($errors['otp']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">Verify & Continue</button>
    </form>
</div>
</body>
</html>
