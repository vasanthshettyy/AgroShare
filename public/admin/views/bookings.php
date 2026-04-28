<?php
/**
 * bookings.php — Admin Bookings Management View
 */
$bookings = getBookingsForAdmin($conn);
?>
<div class="admin-bookings">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">Transaction Log</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Monitor rentals and resolve disputes.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Equipment</th>
                        <th>Participants</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th style="text-align: right;">Resolutions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr data-booking-id="<?= $b['id'] ?>">
                        <td>
                            <p style="font-weight: 800; margin: 0; color: var(--text-muted);">#BK-<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></p>
                            <p style="font-size: 0.7rem; margin: 0;"><?= date('d M Y', strtotime($b['created_at'])) ?></p>
                        </td>
                        <td>
                            <p style="font-weight: 700; margin: 0;"><?= e($b['equipment_title']) ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;"><?= date('M d', strtotime($b['start_datetime'])) ?> - <?= date('M d', strtotime($b['end_datetime'])) ?></p>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem;">
                                <p style="margin: 0;"><span style="color: var(--text-muted);">Renter:</span> <?= e($b['renter_name']) ?></p>
                                <p style="margin: 0;"><span style="color: var(--text-muted);">Owner:</span> <?= e($b['owner_name']) ?></p>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $statusClass = [
                                    'disputed' => 'background: rgba(239, 68, 68, 0.1); color: #ef4444;',
                                    'completed' => 'background: rgba(74, 222, 128, 0.1); color: #4ade80;',
                                    'cancelled' => 'background: rgba(255,255,255,0.05); color: var(--text-muted);',
                                    'confirmed' => 'background: rgba(59, 130, 246, 0.1); color: #3b82f6;',
                                    'active' => 'background: var(--primary-10); color: var(--admin-accent);'
                                ][$b['status']] ?? 'background: rgba(255,255,255,0.05); color: var(--text-main);';
                            ?>
                            <span class="admin-status-pill" style="<?= $statusClass ?>"><?= e(ucfirst($b['status'])) ?></span>
                        </td>
                        <td>
                            <p style="margin: 0; font-weight: 700;">₹<?= number_format($b['total_price'], 0) ?></p>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 0;">Dep: ₹<?= number_format($b['deposit_amount'], 0) ?></p>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <?php if (in_array($b['status'], ['pending', 'confirmed', 'active'])): ?>
                                    <button class="btn-icon booking-action-btn" data-action="force-cancel" data-id="<?= $b['id'] ?>" title="Force Cancel" style="color: #ef4444;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    </button>
                                <?php elseif ($b['status'] === 'disputed'): ?>
                                    <button class="btn-icon booking-action-btn" data-action="resolve-dispute" data-id="<?= $b['id'] ?>" title="Resolve Dispute" style="color: #e67e22;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                                    </button>
                                <?php endif; ?>
                                <button class="btn-icon" title="View Details">
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
