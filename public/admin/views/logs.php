<?php
/**
 * logs.php — Admin Audit Logs View
 */
$logs = getAuditLogs($conn);
?>
<div class="admin-logs">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">System Audit Logs</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Traceability for all administrative and automated actions.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Admin / User</th>
                        <th>Action Type</th>
                        <th>Entity ID</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No audit logs recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.85rem;">
                                <?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--admin-accent-soft); color: var(--admin-accent); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800;">
                                        <?= (int)($log['user_id'] ?? 0) ?>
                                    </div>
                                    <span style="font-size: 0.85rem;"><?= (int)($log['user_id'] ?? 0) == 0 ? 'System' : 'Admin #' . $log['user_id'] ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="admin-status-pill" style="background: rgba(255,255,255,0.05); color: var(--text-main); font-weight: 700;"><?= e($log['action_type']) ?></span>
                            </td>
                            <td>
                                <span style="font-family: monospace; color: var(--text-muted);">#<?= e($log['entity_id'] ?? 'N/A') ?></span>
                            </td>
                            <td style="max-width: 300px; font-size: 0.85rem; line-height: 1.4;">
                                <?= e($log['description']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
