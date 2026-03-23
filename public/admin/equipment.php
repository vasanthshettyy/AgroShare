<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$equipmentList = getEquipmentForAdmin($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment — <?= e(APP_NAME) ?></title>
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
            <a href="users.php" class="nav-link">Users</a>
            <a href="equipment.php" class="nav-link active">Equipment</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="settings.php" class="nav-link">Settings</a>
            <a href="logs.php" class="nav-link">Audit Logs</a>
            <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1>Manage Equipment</h1>
        </header>

        <?= renderFlash() ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipmentList as $eq): ?>
                    <tr>
                        <td><?= (int)$eq['id'] ?></td>
                        <td><?= e($eq['title']) ?></td>
                        <td><?= e($eq['owner_name']) ?></td>
                        <td><?= e($eq['category']) ?></td>
                        <td><?= $eq['is_available'] ? 'Available' : 'Unavailable' ?></td>
                        <td>
                            <form method="POST" action="api/toggle-featured-equipment.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="equipment_id" value="<?= (int)$eq['id'] ?>">
                                <button type="submit" class="action-btn">Toggle Feature</button>
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