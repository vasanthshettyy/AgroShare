<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$users = getUsersForAdmin($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .table-container { background: var(--surface-color); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        td { color: var(--text-main); font-size: 0.9rem; }
        .action-btn { background: var(--primary-10); color: var(--primary-action); border: 1px solid var(--border-color); padding: 4px 8px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
        .action-btn:hover { background: var(--primary-action); color: #fff; }
    </style>
</head>
<body data-theme="dark">

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><span class="brand-name"><?= e(APP_NAME) ?> Admin</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="users.php" class="nav-link active">Users</a>
            <a href="equipment.php" class="nav-link">Equipment</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="settings.php" class="nav-link">Settings</a>
            <a href="logs.php" class="nav-link">Audit Logs</a>
            <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1>Manage Users</h1>
        </header>

        <?= renderFlash() ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Verified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= e($u['full_name']) ?></td>
                        <td><?= e($u['phone']) ?></td>
                        <td><?= e($u['village']) ?>, <?= e($u['district']) ?></td>
                        <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <form method="POST" action="api/verify-user.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <input type="hidden" name="status" value="<?= $u['is_verified'] ? '0' : '1' ?>">
                                <button type="submit" class="action-btn">Toggle Verify</button>
                            </form>
                            <form method="POST" action="api/toggle-user-active.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <!-- Assuming status column will be handled safely if missing -->
                                <button type="submit" class="action-btn">Toggle Active</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>