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
        
        /* Stateful Action Buttons */
        .btn-action { font-family: inherit; font-weight: 600; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; border: 1px solid transparent; transition: all 0.2s ease; outline-offset: 2px; }
        .btn-action:focus-visible { outline: 2px solid var(--primary-action); }
        
        /* Positive action (e.g., Verify, Activate) */
        .btn-positive { background: var(--primary-10); color: var(--primary-action); border-color: var(--primary-10); }
        .btn-positive:hover { background: var(--primary-action); color: #fff; border-color: var(--primary-action); }
        
        /* Warning/Neutral action (e.g., Revoke, Deactivate) */
        .btn-warning { background: rgba(244, 67, 54, 0.1); color: #e53935; border-color: rgba(244, 67, 54, 0.1); }
        .btn-warning:hover { background: #e53935; color: #fff; border-color: #e53935; }

        /* Status Chips */
        .chip { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .chip-success { background: rgba(76, 175, 80, 0.15); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.2); }
        .chip-neutral { background: rgba(158, 158, 158, 0.15); color: #9E9E9E; border: 1px solid rgba(158, 158, 158, 0.2); }
        .chip-danger { background: rgba(244, 67, 54, 0.15); color: #F44336; border: 1px solid rgba(244, 67, 54, 0.2); }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); pointer-events: none; opacity: 0; visibility: hidden; }
        .modal-overlay.active,
        .modal-overlay.show-modal { display: flex; animation: fadeIn 0.2s ease; pointer-events: auto; opacity: 1; visibility: visible; }
        .modal-content { background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; width: 100%; max-width: 480px; padding: 24px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.5); animation: slideUp 0.3s ease; }
        .modal-close { position: absolute; top: 16px; right: 16px; background: none; border: none; color: var(--text-muted); cursor: pointer; }
        .modal-close:hover { color: var(--text-main); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
        .form-input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-color); color: var(--text-main); font-family: inherit; }
        .form-input:focus { outline: none; border-color: var(--primary-action); }
        .btn-submit { background: var(--primary-action); color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; margin-top: 8px; }
        .btn-submit:hover { background: var(--accent-dark); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body data-theme="dark">

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?> Admin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Users</span>
            </a>
            <a href="equipment.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                <span>Equipment</span>
            </a>
            <a href="bookings.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span>Bookings</span>
            </a>
            <a href="settings.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>Settings</span>
            </a>
            <a href="logs.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>Audit Logs</span>
            </a>
            <a href="../logout.php" class="nav-link danger" style="margin-top: auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <h1>Manage Users</h1>
            <button type="button" id="openAdminModalBtn" class="action-btn" style="padding: 8px 16px; font-weight: bold; background: var(--primary-action); color: white; border: none;">+ Add Admin</button>
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
                        <th>Status</th>
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
                        <td>
                            <div style="display: flex; gap: 6px; flex-direction: column; align-items: flex-start;">
                                <?php if ($u['is_verified']): ?>
                                    <span class="chip chip-success">Verified</span>
                                <?php else: ?>
                                    <span class="chip chip-neutral">Unverified</span>
                                <?php endif; ?>
                                
                                <?php if (isset($u['is_active']) && $u['is_active'] == 0): ?>
                                    <span class="chip chip-danger">Inactive</span>
                                <?php else: ?>
                                    <span class="chip chip-success">Active</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <form method="POST" action="api/verify-user.php" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['is_verified'] ? '0' : '1' ?>">
                                    <?php if ($u['is_verified']): ?>
                                        <button type="submit" class="btn-action btn-warning" onclick="return confirm('Are you sure you want to revoke verification for this user?');">Revoke Verification</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-action btn-positive">Verify User</button>
                                    <?php endif; ?>
                                </form>
                                <form method="POST" action="api/toggle-user-active.php" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <?php if (isset($u['is_active']) && $u['is_active'] == 0): ?>
                                        <button type="submit" class="btn-action btn-positive">Activate User</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-action btn-warning" onclick="return confirm('Are you sure you want to deactivate this user? They will not be able to log in.');">Deactivate User</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Add Admin Modal -->
<div class="modal-overlay" id="addAdminModal">
    <div class="modal-content">
        <button class="modal-close" id="closeAdminModalBtn" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h2 style="margin-bottom: 20px;">Create New Admin</h2>
        <form method="POST" action="api/create-admin.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-input" required maxlength="120" autocomplete="name">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-input" required maxlength="10" pattern="[6-9][0-9]{9}" placeholder="10-digit mobile number" autocomplete="tel">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required minlength="8" placeholder="At least 8 characters" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-submit">Create Admin Account</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('addAdminModal');
    const openBtn = document.getElementById('openAdminModalBtn');
    const closeBtn = document.getElementById('closeAdminModalBtn');

    if (!modal || !openBtn || !closeBtn) return;

    openBtn.addEventListener('click', () => {
        modal.classList.add('active', 'show-modal');
    });

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active', 'show-modal');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active', 'show-modal');
        }
    });
});
</script>
</body>
</html>
