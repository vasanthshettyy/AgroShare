<?php
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if (!isset($_SESSION['reset_phone']) || !isset($_SESSION['reset_verified'])) {
    header('Location: forgot-password.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($errors)) {
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    if (empty($errors)) {
        $phone = $_SESSION['reset_phone'];
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE phone = ?");
        $stmt->bind_param('ss', $hash, $phone);
        
        if ($stmt->execute()) {
            unset($_SESSION['reset_phone'], $_SESSION['reset_verified']);
            setFlash('success', 'Your password has been reset successfully. Please log in.');
            header('Location: login.php');
            exit();
        } else {
            $errors['general'] = 'Failed to reset password. Please try again later.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= e(APP_NAME) ?></title>
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
            --danger:              #E11D48;
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
        .btn-submit { width: 100%; background: var(--primary-action); color: #fff; border: none; padding: 12px; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer; transition: filter 0.2s ease; margin-top: 1rem; }
        .btn-submit:hover { filter: brightness(1.1); }
        .error-msg { color: var(--danger); font-size: 0.8rem; font-weight: 500; }
        .alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500; margin-bottom: 1rem; }
        .alert-error { background: rgba(225, 29, 72, 0.1); border: 1px solid rgba(225, 29, 72, 0.2); color: #ff4c4c; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <h1>Set New Password</h1>
    <p>Choose a strong password with at least 8 characters.</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" placeholder="At least 8 characters" class="form-input has-icon<?= isset($errors['password']) ? ' is-invalid' : '' ?>" required minlength="8" autofocus>
                <span class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
            </div>
            <?php if (isset($errors['password'])): ?>
                <span class="error-msg"><?= e($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm New Password</label>
            <div class="input-wrap">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" class="form-input has-icon<?= isset($errors['confirm_password']) ? ' is-invalid' : '' ?>" required minlength="8">
                <span class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
            </div>
            <?php if (isset($errors['confirm_password'])): ?>
                <span class="error-msg"><?= e($errors['confirm_password']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">Reset Password</button>
    </form>
</div>
</body>
</html>