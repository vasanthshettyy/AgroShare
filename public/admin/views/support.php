<?php
if (!defined('BASE_PATH')) exit('No direct access allowed.');

$messages = getSupportMessages($conn);
?>
<div class="admin-view-header">
    <div class="header-content">
        <h2>Customer Support</h2>
        <p>User feedback and support requests.</p>
    </div>
</div>

<div class="admin-card" style="padding: 1.5rem;">
    <?php if (empty($messages)): ?>
        <div style="text-align: center; padding: 40px;">
            <p style="color: var(--admin-text-subtle);">No support messages yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <div style="background: var(--admin-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--admin-border); margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1rem; color: var(--admin-text);"><?= e($m['sender_name']) ?></h3>
                        <span style="font-size: 0.75rem; color: var(--admin-text-muted);">User ID: #<?= (int)$m['user_id'] ?></span>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-subtle);">
                        <?= date('M j, Y — g:i A', strtotime($m['created_at'])) ?>
                    </div>
                </div>
                <div style="color: var(--admin-text-muted); font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;">
                    <?= e($m['message']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
