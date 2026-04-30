<?php
/**
 * complete-google-profile.php — Collects missing profile data for new Google users.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tempUser = $_SESSION['temp_google_user'] ?? null;

// If no temp user data, redirect to login
if (!$tempUser) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    }

    $phone    = trim($_POST['phone'] ?? '');
    $village  = trim($_POST['village'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $state    = trim($_POST['state'] ?? '');

    $old = compact('phone', 'village', 'district', 'state');

    // Validation
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $errors['phone'] = 'Valid 10-digit Indian mobile required.';
    }

    if (empty($village))  { $errors['village']  = 'Village is required.'; }
    if (empty($district)) { $errors['district'] = 'District is required.'; }
    if (empty($state))    { $errors['state']    = 'State is required.'; }

    // Check if phone already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['phone'] = 'This phone number is already registered.';
        }
        $stmt->close();
    }

    // Final Creation
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, google_id, phone, village, district, state, password_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL)"
        );
        $stmt->bind_param(
            'sssssss', 
            $tempUser['name'], 
            $tempUser['email'], 
            $tempUser['google_id'], 
            $phone, 
            $village, 
            $district, 
            $state
        );
        
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $stmt->close();

            // Log them in
            $_SESSION['user_id']    = $newId;
            $_SESSION['user_email'] = $tempUser['email'];
            $_SESSION['full_name']  = $tempUser['name'];
            $_SESSION['role']       = 'user';
            $_SESSION['persist']    = true;
            $_SESSION['last_activity'] = time();
            
            unset($_SESSION['temp_google_user']);
            session_regenerate_id(true);
            logAuditEvent($conn, 'register_google', $newId, 'Account created via Google OAuth.');

            setFlash('success', 'Welcome to AgroShare! Your account is ready.');
            header('Location: ../dashboard.php');
            exit();
        } else {
            $errors['general'] = 'Failed to create account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <style>
        :root {
            --bg-color:            hsl(144, 28%, 6%);
            --surface-color:       hsl(150, 24%, 10%);
            --text-main:           hsl(90, 20%, 90%);
            --text-muted:          hsl(140, 14%, 60%);
            --border-color:        hsl(150, 20%, 16%);
            --primary-action:      hsl(150, 50%, 45%); 
            --accent-dark:         hsl(150, 50%, 30%);
            --danger:              #E11D48;
            --primary-10:          rgba(76, 175, 120, 0.12);
            --radius:              18px;
            --radius-sm:           12px;
            --font:                'Inter', sans-serif;
        }

        body {
            font-family: var(--font);
            background: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 480px; width: 100%;
            background: var(--surface-color);
            padding: 40px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .head { margin-bottom: 24px; text-align: center; }
        .head h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: 8px; }
        .head p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; }

        .form-group { margin-bottom: 16px; }
        .label { display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 6px; }
        .input {
            width: 100%; padding: 12px;
            background: var(--bg-color);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: #FFF; font-family: inherit;
        }
        .input:focus { outline: none; border-color: var(--primary-action); }
        .error { color: var(--danger); font-size: 0.75rem; font-weight: 600; margin-top: 4px; display: block; }

        .btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            border: none; border-radius: var(--radius-sm);
            color: #FFF; font-weight: 700; cursor: pointer;
            margin-top: 10px; transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }

        .google-info {
            background: var(--primary-10);
            padding: 12px; border-radius: var(--radius-sm);
            margin-bottom: 24px; display: flex; align-items: center; gap: 12px;
        }
        .google-info img { width: 32px; height: 32px; border-radius: 50%; }
        .google-info div { font-size: 0.8rem; }
        .google-info b { display: block; color: var(--primary-action); }
    </style>
</head>
<body>
    <div class="container">
        <div class="head">
            <h1>Almost there!</h1>
            <p>We've linked your Google account. Just provide a few more details to complete your AgroShare profile.</p>
        </div>

        <div class="google-info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>
            </svg>
            <div>
                <b>Signed in as <?= e($tempUser['name']) ?></b>
                <?= e($tempUser['email']) ?>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group">
                <label class="label">Phone Number</label>
                <input type="tel" name="phone" class="input" placeholder="10-digit mobile" value="<?= e($old['phone'] ?? '') ?>" required>
                <?php if(isset($errors['phone'])): ?><span class="error"><?= $errors['phone'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="label">City / Village</label>
                <input type="text" name="village" class="input" placeholder="Your village name" value="<?= e($old['village'] ?? '') ?>" required>
                <?php if(isset($errors['village'])): ?><span class="error"><?= $errors['village'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="label">District</label>
                <input type="text" name="district" class="input" placeholder="e.g. Belagavi" value="<?= e($old['district'] ?? '') ?>" required>
                <?php if(isset($errors['district'])): ?><span class="error"><?= $errors['district'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="label">State</label>
                <input type="text" name="state" class="input" placeholder="e.g. Karnataka" value="<?= e($old['state'] ?? '') ?>" required>
                <?php if(isset($errors['state'])): ?><span class="error"><?= $errors['state'] ?></span><?php endif; ?>
            </div>

            <button type="submit" class="btn">Finish Account Setup</button>
        </form>
    </div>
</body>
</html>
