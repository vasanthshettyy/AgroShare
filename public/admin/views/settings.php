<?php
/**
 * settings.php — Admin Platform Settings View
 */
$settings = getSettings($conn);
?>
<div class="admin-settings">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">Platform Configuration</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 4px 0 0 0;">Manage global application variables and security policies.</p>
        </div>
    </div>

    <div class="admin-grid">
        <div class="admin-card">
            <h4 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; font-weight: 800;">General Settings</h4>
            <form id="settingsForm" class="admin-form">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Platform Name</label>
                    <input type="text" value="AgroShare" class="form-input" style="background: rgba(255,255,255,0.05); border: 1px solid var(--admin-border); color: var(--text-main); width: 100%;" readonly>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">System Currency</label>
                    <input type="text" value="INR (₹)" class="form-input" style="background: rgba(255,255,255,0.05); border: 1px solid var(--admin-border); color: var(--text-main); width: 100%;" readonly>
                </div>
                <div style="margin-top: 2rem; padding: 1rem; background: var(--admin-accent-soft); border-radius: 12px; border: 1px dashed var(--admin-accent);">
                    <p style="margin: 0; font-size: 0.8rem; color: var(--admin-accent); font-weight: 700;">Read Only</p>
                    <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: var(--text-subtle);">Direct database access required to modify core platform variables for security.</p>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h4 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; font-weight: 800;">Security Policies</h4>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; font-weight: 700;">Two-Factor Authentication</p>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted);">Require 2FA for all admin accounts.</p>
                    </div>
                    <span class="admin-status-pill" style="background: rgba(74, 222, 128, 0.1); color: #4ade80;">Active</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="margin: 0; font-weight: 700;">Registration Approval</p>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted);">Manually verify new farmer accounts.</p>
                    </div>
                    <span class="admin-status-pill" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24;">Pending</span>
                </div>
            </div>
        </div>
    </div>
</div>
