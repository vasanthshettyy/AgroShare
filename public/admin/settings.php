<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$settings = getSettings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Settings — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .form-container { background: var(--surface-color); padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); max-width: 600px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; color: var(--text-main); font-weight: 600; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 10px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-family: inherit; }
        .btn-submit { background: var(--primary-action); color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { background: var(--accent-dark); }
    </style>
</head>
<body data-theme="dark">

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><span class="brand-name"><?= e(APP_NAME) ?> Admin</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="equipment.php" class="nav-link">Equipment</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="settings.php" class="nav-link active">Settings</a>
            <a href="logs.php" class="nav-link">Audit Logs</a>
            <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1>Global Settings</h1>
        </header>

        <?= renderFlash() ?>

        <div class="form-container">
            <form method="POST" action="api/update-setting.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" class="form-input" value="<?= e($settings['site_name'] ?? APP_NAME) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="maintenance_mode">Maintenance Mode</label>
                    <select id="maintenance_mode" name="maintenance_mode" class="form-input">
                        <option value="0" <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '0') ? 'selected' : '' ?>>Disabled</option>
                        <option value="1" <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'selected' : '' ?>>Enabled</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Save Settings</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>