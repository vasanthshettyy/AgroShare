<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$logs = getAuditLogs($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .table-container { background: var(--surface-color); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        td { color: var(--text-main); }
        .meta-cell { font-family: monospace; font-size: 0.8rem; color: var(--text-subtle); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
            <a href="settings.php" class="nav-link">Settings</a>
            <a href="logs.php" class="nav-link active">Audit Logs</a>
            <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1>System Audit Logs</h1>
            <p style="color: var(--text-muted)">Latest 100 security and system events.</p>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Actor ID</th>
                        <th>Admin ID</th>
                        <th>Description</th>
                        <th>Metadata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= (int)$log['id'] ?></td>
                        <td><?= e($log['created_at']) ?></td>
                        <td><strong><?= e($log['action_type']) ?></strong></td>
                        <td><?= e($log['actor_user_id'] ?? '-') ?></td>
                        <td><?= e($log['admin_id'] ?? '-') ?></td>
                        <td><?= e($log['description']) ?></td>
                        <td class="meta-cell" title="<?= e($log['metadata_json'] ?? '') ?>"><?= e($log['metadata_json'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>