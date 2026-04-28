<?php
/**
 * pooling.php — Admin Pooling Management View
 */
$campaigns = getPoolingCampaignsForAdmin($conn);
?>
<div class="admin-pooling">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">Community Pooling</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Monitor bulk-buy campaigns and pledges.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Creator</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c): ?>
                    <?php 
                        $percent = $c['target_quantity'] > 0 ? min(100, ($c['current_quantity'] / $c['target_quantity']) * 100) : 0;
                        $isExpired = strtotime($c['end_date']) < time();
                    ?>
                    <tr data-campaign-id="<?= $c['id'] ?>">
                        <td>
                            <p style="font-weight: 700; margin: 0;"><?= e($c['title']) ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;"><?= e($c['item_name']) ?></p>
                        </td>
                        <td>
                            <p style="margin: 0; font-size: 0.85rem;"><?= e($c['creator_name']) ?></p>
                        </td>
                        <td>
                            <div style="width: 120px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.7rem; margin-bottom: 4px;">
                                    <span><?= (int)$c['current_quantity'] ?>/<?= (int)$c['target_quantity'] ?></span>
                                    <span><?= round($percent) ?>%</span>
                                </div>
                                <div style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                                    <div style="height: 100%; width: <?= $percent ?>%; background: var(--admin-accent);"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $status = $c['status'];
                                if ($isExpired && $status === 'open') $status = 'expired';
                                
                                $statusClass = [
                                    'open' => 'background: rgba(59, 130, 246, 0.1); color: #3b82f6;',
                                    'threshold_met' => 'background: rgba(74, 222, 128, 0.1); color: #4ade80;',
                                    'completed' => 'background: var(--primary-10); color: var(--admin-accent);',
                                    'expired' => 'background: rgba(239, 68, 68, 0.1); color: #ef4444;',
                                    'failed' => 'background: rgba(255,255,255,0.05); color: var(--text-muted);'
                                ][$status] ?? 'background: rgba(255,255,255,0.05); color: var(--text-main);';
                            ?>
                            <span class="admin-status-pill" style="<?= $statusClass ?>"><?= e(ucfirst(str_replace('_', ' ', $status))) ?></span>
                        </td>
                        <td>
                            <p style="margin: 0; font-size: 0.85rem; color: <?= $isExpired ? '#ef4444' : 'inherit' ?>;">
                                <?= date('d M Y', strtotime($c['end_date'])) ?>
                            </p>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <button class="btn-icon campaign-action-btn" data-action="toggle-status" data-id="<?= $c['id'] ?>" title="Manage Status">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                </button>
                                <button class="btn-icon" title="View Pledges">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
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
