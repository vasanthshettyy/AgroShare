<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$stats = getAdminDashboardStats($conn);
$greeting = "Welcome, Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--surface-color); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); }
        .stat-card h3 { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 8px; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: var(--text-main); }
    </style>
</head>
<body data-theme="dark">

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-name"><?= e(APP_NAME) ?> Admin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="equipment.php" class="nav-link">Equipment</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="settings.php" class="nav-link">Settings</a>
            <a href="logs.php" class="nav-link">Audit Logs</a>
            <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1><?= e($greeting) ?></h1>
            <p style="color: var(--text-muted)">System overview and KPI metrics.</p>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Farmers</h3>
                <div class="value"><?= e($stats['users_count']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Equipment</h3>
                <div class="value"><?= e($stats['equipment_count']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="value"><?= e($stats['bookings_count']) ?></div>
            </div>
        </div>

        <h2>Recent Audit Logs</h2>
        <div class="table-container" style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-top: 16px;">
            <?php if (empty($stats['recent_logs'])): ?>
                <p>No recent activity.</p>
            <?php else: ?>
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 8px;">Time</th>
                            <th style="padding: 8px;">Action</th>
                            <th style="padding: 8px;">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_logs'] as $log): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px;"><?= e($log['created_at']) ?></td>
                            <td style="padding: 8px;"><?= e($log['action_type']) ?></td>
                            <td style="padding: 8px;"><?= e($log['description']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>