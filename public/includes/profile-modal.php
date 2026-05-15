<!-- Profile Modal -->
<div id="profileModal" class="modal-overlay">
    <div class="modal-content profile-modal-content">
        <button type="button" class="modal-close-premium" id="profileModalCloseBtn" aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="modal-header">
            <h2>User Profile</h2>
            <p>Update your personal information and profile picture.</p>
        </div>
        
        <form id="profileForm" class="eq-form" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <!-- Avatar Section -->
            <div class="profile-avatar-section">
                <img src="assets/img/default-avatar.png" alt="Profile" id="prof-photo-preview" class="profile-avatar-preview">
                <div id="prof-badges" style="display:flex; gap:10px;"></div>
                <label class="btn-secondary btn-upload-avatar">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Change Photo
                    <input type="file" name="profile_photo" id="prof-photo-input" accept="image/jpeg,image/png,image/webp">
                </label>
            </div>

            <!-- Identity Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Personal Details
                </h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="prof-name" class="form-label">Full Name <span style="color: var(--status-rejected);">*</span></label>
                        <input type="text" name="full_name" id="prof-name" class="form-input" required autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="prof-phone" class="form-label">Phone Number <span style="color: var(--status-rejected);">*</span></label>
                        <input type="text" name="phone" id="prof-phone" class="form-input" required pattern="[0-9]{10}" autocomplete="tel">
                    </div>
                    <div class="form-group">
                        <label for="prof-email" class="form-label">Email Address <span style="color: var(--status-rejected);">*</span></label>
                        <input type="email" name="email" id="prof-email" class="form-input" required autocomplete="email">
                    </div>
                </div>
            </div>

            <!-- Location Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Location Info
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="prof-village" class="form-label">Village</label>
                        <input type="text" name="village" id="prof-village" class="form-input" required autocomplete="address-level3">
                    </div>
                    <div class="form-group">
                        <label for="prof-district" class="form-label">District</label>
                        <input type="text" name="district" id="prof-district" class="form-input" required autocomplete="address-level2">
                    </div>
                    <div class="form-group full-width">
                        <label for="prof-state" class="form-label">State</label>
                        <input type="text" name="state" id="prof-state" class="form-input" required autocomplete="address-level1">
                    </div>
                </div>
            </div>

            <!-- Payment Settings Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Payment Settings
                </h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="prof-upi-id" class="form-label">UPI ID</label>
                        <input type="text" name="upi_id" id="prof-upi-id" class="form-input" placeholder="e.g. username@bankname">
                    </div>
                    <div class="form-group full-width">
                        <label for="prof-upi-qr-input" class="form-label">UPI QR Scanner Image</label>
                        <label class="btn-secondary btn-upload-avatar" style="width: 100%; display: flex; justify-content: center; margin-top: 5px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Upload QR Image
                            <input type="file" name="upi_qr_image" id="prof-upi-qr-input" accept="image/jpeg,image/png,image/webp" style="display: none;">
                        </label>
                        <div id="qr-preview-container" style="margin-top: 10px; text-align: center; display: none; padding: 10px; background: var(--bg-color-alt); border: 1.5px dashed var(--border-color); border-radius: 12px;">
                            <p style="font-size: 0.7rem; color: var(--text-subtle); margin-bottom: 8px;">QR Preview</p>
                            <img id="prof-qr-preview" src="" alt="QR Preview" style="max-width: 150px; border-radius: 8px; box-shadow: var(--shadow-sm);">
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 5px; display: block;">
                            Upload your UPI QR code to receive payments directly from other farmers.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Security Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Security
                </h2>
                <div class="form-grid">
                    <!-- Read-Only View (Perfect Flex Alignment) -->
                    <div class="form-group full-width" id="readonly-password-group" style="flex-direction: row; align-items: flex-end; gap: 1rem;">
                        <div style="flex: 1;">
                            <label for="prof-readonly-pass" class="form-label">Current Password</label>
                            <input type="password" id="prof-readonly-pass" value="••••••••••••" class="form-input" readonly disabled style="color: var(--text-muted); background: var(--bg-color); letter-spacing: 2px;">
                        </div>
                        <button type="button" class="btn-secondary" id="btn-reveal-password-change" style="height: 40px; white-space: nowrap; padding: 0 1.5rem;">Change Password</button>
                    </div>

                    <!-- Step 1: Verification (Perfect Flex Alignment) -->
                    <div class="form-group full-width" id="password-verify-step" style="display: none; flex-direction: row; align-items: flex-end; gap: 1rem;">
                        <div style="flex: 1;">
                            <label for="prof-verify-current" class="form-label">Verify Current Password</label>
                            <input type="password" id="prof-verify-current" class="form-input" placeholder="Enter current password to continue" autocomplete="current-password">
                        </div>
                        <button type="button" class="btn-primary" id="btn-verify-password" style="height: 40px; white-space: nowrap; padding: 0 1.5rem;">Verify</button>
                    </div>

                    <!-- Step 2: Hidden Change Password Form -->
                    <div id="change-password-fields-wrap" style="display: none; grid-column: 1 / -1; width: 100%;">
                        <div class="form-grid" style="gap: 1.25rem;">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <div style="padding: 10px; background: var(--primary-10); border: 1px solid var(--primary-action); border-radius: 8px; color: var(--primary-action); font-size: 0.85rem; display: flex; align-items: center; gap: 10px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    Password verified. You can now set a new one.
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="prof-new-password" class="form-label">New Password</label>
                                <input type="password" name="new_password" id="prof-new-password" class="form-input" placeholder="Min 8 chars, mixed case, number & symbol" autocomplete="new-password">
                                <div class="pw-strength" style="height: 3px; border-radius: 4px; background: var(--border-color); margin-top: 6px; overflow: hidden;"><div id="prof-pw-bar" style="height: 100%; width: 0; transition: all 0.3s ease;"></div></div>
                                <span class="pw-hint" style="font-size: 0.7rem; color: var(--text-subtle);">Min 8 chars, mixed case, number & symbol</span>
                            </div>
                            <div class="form-group">
                                <label for="prof-confirm-password" class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="prof-confirm-password" class="form-input" placeholder="Repeat new password" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    <div class="form-group full-width" style="margin-top: 0.5rem;">
                        <small style="color: var(--text-muted); font-size: 0.75rem;">
                            Forgot your password? <a href="forgot-password.php" style="color: var(--primary-action);">Reset it here</a>.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Reviews Shortcut -->
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); text-align: center;">
                <button type="button" class="btn-secondary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 0.85rem;" onclick="showUserReviews(<?= (int)$_SESSION['user_id'] ?>)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    View My Reviews
                </button>
            </div>

            <div class="modal-footer-premium">
                <a href="logout.php" class="btn-logout-modal" style="width: auto; margin: 0;" title="Log out of your account">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Log Out
                </a>
                <div style="display:flex; gap:12px; flex: 1; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" id="profileCancelBtn" style="height: 48px; border-radius: 14px;">Cancel</button>
                    <button type="submit" class="premium-btn-brand" id="profileSubmitBtn" style="width: auto; min-width: 160px;">Update Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>
