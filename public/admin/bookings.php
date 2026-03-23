<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$bookings = getBookingsForAdmin($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .table-container { background: var(--surface-color); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        td { color: var(--text-main); font-size: 0.9rem; }
        .action-btn { background: var(--danger); color: #fff; border: 1px solid var(--border-color); padding: 4px 8px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
        .action-btn:hover { background: #b71c1c; }
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
            <a href="bookings.php" class="nav-link active">Bookings</a>
            <a href="settings.php" class="nav-link">Settings</a>
            <a href="logs.php" class="nav-link">Audit Logs</a>
            <a href="../logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1>Manage Bookings</h1>
        </header>

        <?= renderFlash() ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Equipment</th>
                        <th>Renter</th>
                        <th>Owner</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= (int)$b['id'] ?></td>
                        <td><?= e($b['equipment_title']) ?></td>
                        <td><?= e($b['renter_name']) ?></td>
                        <td><?= e($b['owner_name']) ?></td>
                        <td><?= date('M d', strtotime($b['start_datetime'])) ?> - <?= date('M d', strtotime($b['end_datetime'])) ?></td>
                        <td><?= e(ucfirst($b['status'])) ?></td>
                        <td>
                            <?php if (in_array($b['status'], ['pending', 'confirmed', 'active'])): ?>
                            <form method="POST" action="api/admin-booking-override.php" style="display:inline;" onsubmit="return confirm('Force cancel this booking?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                <button type="submit" class="action-btn">Force Cancel</button>
                            </form>
                            <?php endif; ?>
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