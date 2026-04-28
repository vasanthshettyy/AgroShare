<?php
/**
 * overview.php — Admin Overview View
 */
$stats = getAdminDashboardStats($conn);
?>
<div class="admin-overview">
    <div class="admin-grid">
        <!-- KPI Cards -->
        <div class="admin-card kpi-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Total Revenue</p>
                    <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0;">₹<?= number_format($stats['total_revenue'], 0) ?></h3>
                </div>
                <div style="background: var(--primary-10); color: var(--admin-accent); padding: 10px; border-radius: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
            <p style="font-size: 0.75rem; color: #4ade80; margin-top: 12px; font-weight: 600;">+12% from last month</p>
        </div>

        <div class="admin-card kpi-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Total Farmers</p>
                    <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0;"><?= e($stats['users_count']) ?></h3>
                </div>
                <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 10px; border-radius: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
            </div>
            <?php if ($stats['unverified_users'] > 0): ?>
                <p style="font-size: 0.75rem; color: var(--warning-color); margin-top: 12px; font-weight: 600;"><?= e($stats['unverified_users']) ?> pending verification</p>
            <?php else: ?>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px; font-weight: 600;">All users verified</p>
            <?php endif; ?>
        </div>

        <div class="admin-card kpi-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Active Disputes</p>
                    <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0;"><?= e($stats['active_disputes']) ?></h3>
                </div>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px; border-radius: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px; font-weight: 600;">Requires attention</p>
        </div>
    </div>

    <div style="margin-top: 2rem; display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Recent Logs Section -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800;">Recent Audit Logs</h4>
                <button class="nav-link" data-view="logs" style="font-size: 0.8rem; padding: 4px 12px; background: rgba(255,255,255,0.05); border-radius: 6px; border: 1px solid var(--admin-border); cursor: pointer;">View All</button>
            </div>
            <div class="table-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--admin-border);">
                            <th style="padding: 12px 8px; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Time</th>
                            <th style="padding: 12px 8px; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Action</th>
                            <th style="padding: 12px 8px; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_logs'] as $log): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                            <td style="padding: 14px 8px; font-size: 0.85rem; color: var(--text-subtle);"><?= date('H:i, d M', strtotime($log['created_at'])) ?></td>
                            <td style="padding: 14px 8px;">
                                <span class="admin-status-pill" style="background: rgba(255,255,255,0.05); color: var(--text-main);"><?= e($log['action_type']) ?></span>
                            </td>
                            <td style="padding: 14px 8px; font-size: 0.85rem;"><?= e($log['description']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Quick Actions -->
        <div class="admin-card">
            <h4 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; font-weight: 800;">Quick Actions</h4>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button class="admin-nav-item" style="border: 1px solid var(--admin-border); justify-content: flex-start; color: var(--text-main);" onclick="alert('Maintenance mode coming soon')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    Maintenance Mode
                </button>
                <div style="padding: 1rem; background: var(--admin-accent-soft); border-radius: 12px; border: 1px dashed var(--admin-accent);">
                    <p style="margin: 0; font-size: 0.8rem; color: var(--admin-accent); font-weight: 700;">System Tip</p>
                    <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: var(--text-subtle);">There are <?= e($stats['pending_equipment']) ?> equipment listings waiting for moderation.</p>
                </div>
            </div>
        </div>
    </div>
</div>
