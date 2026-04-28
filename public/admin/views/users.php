<?php
/**
 * users.php — Admin Users Management View
 */
$users = getUsersForAdmin($conn);
?>
<div class="admin-users">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">Farmer Management</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Manage and verify platform participants.</p>
        </div>
        <button class="btn-primary" id="openAddAdminBtn" style="padding: 10px 20px; font-weight: 700;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
            Add Admin
        </button>
    </div>

    <div class="admin-card">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Farmer</th>
                        <th>Location</th>
                        <th>Trust Score</th>
                        <th>Verification</th>
                        <th>Account</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr data-user-id="<?= $u['id'] ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div class="avatar" style="width: 36px; height: 36px; background: var(--admin-accent-soft); color: var(--admin-accent);">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p style="font-weight: 700; margin: 0;"><?= e($u['full_name']) ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;"><?= e($u['phone']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p style="margin: 0;"><?= e($u['village']) ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;"><?= e($u['district']) ?></p>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 4px; color: var(--amber);">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <span style="font-weight: 700;"><?= number_format($u['trust_score'] ?? 0, 1) ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if ($u['is_verified']): ?>
                                <span class="admin-status-pill" style="background: rgba(74, 222, 128, 0.1); color: #4ade80;">Verified</span>
                            <?php else: ?>
                                <span class="admin-status-pill" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($u['is_active']) && $u['is_active'] == 0): ?>
                                <span class="admin-status-pill" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">Suspended</span>
                            <?php else: ?>
                                <span class="admin-status-pill" style="background: rgba(76, 175, 120, 0.1); color: var(--admin-accent);">Active</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <button class="btn-icon user-action-btn" data-action="toggle-verify" data-id="<?= $u['id'] ?>" title="<?= $u['is_verified'] ? 'Revoke' : 'Verify' ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                                </button>
                                <button class="btn-icon user-action-btn" data-action="toggle-active" data-id="<?= $u['id'] ?>" title="<?= ($u['is_active'] ?? 1) ? 'Suspend' : 'Activate' ?>" style="color: <?= ($u['is_active'] ?? 1) ? '#ef4444' : '#4ade80' ?>;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Drawer and Modals will be handled globally in index.php or dynamically injected here -->
