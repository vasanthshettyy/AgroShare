<?php
/**
 * equipment.php — Admin Equipment Management View
 */
$equipmentList = getEquipmentForAdmin($conn);
?>
<div class="admin-equipment">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">Equipment Moderation</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Review listings and manage featured content.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Owner</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipmentList as $eq): ?>
                    <tr data-eq-id="<?= $eq['id'] ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 48px; height: 32px; background: rgba(255,255,255,0.05); border-radius: 6px; overflow: hidden; border: 1px solid var(--admin-border);">
                                    <?php 
                                        $imgs = json_decode($eq['images'] ?? '[]', true);
                                        if(!empty($imgs)): 
                                    ?>
                                        <img src="<?= e($imgs[0]) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p style="font-weight: 700; margin: 0;"><?= e($eq['title']) ?></p>
                                    <?php if($eq['is_featured']): ?>
                                        <span style="font-size: 0.65rem; color: var(--admin-accent); font-weight: 800; text-transform: uppercase;">★ Featured</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p style="margin: 0; font-size: 0.85rem;"><?= e($eq['owner_name']) ?></p>
                        </td>
                        <td>
                            <span class="admin-status-pill" style="background: rgba(255,255,255,0.05); color: var(--text-muted);"><?= e(ucfirst($eq['category'])) ?></span>
                        </td>
                        <td>
                            <p style="margin: 0; font-weight: 700;">₹<?= number_format($eq['price_per_day'], 0) ?></p>
                        </td>
                        <td>
                            <?php if ($eq['is_available']): ?>
                                <span class="admin-status-pill" style="background: rgba(74, 222, 128, 0.1); color: #4ade80;">Listed</span>
                            <?php else: ?>
                                <span class="admin-status-pill" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24;">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <button class="btn-icon eq-action-btn" data-action="toggle-featured" data-id="<?= $eq['id'] ?>" title="<?= $eq['is_featured'] ? 'Unfeature' : 'Feature' ?>" style="color: <?= $eq['is_featured'] ? 'var(--admin-accent)' : 'inherit' ?>;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $eq['is_featured'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </button>
                                <button class="btn-icon eq-action-btn" data-action="view-details" data-id="<?= $eq['id'] ?>" title="Review Details">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
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
