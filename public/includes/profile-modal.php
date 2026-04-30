<!-- Profile Modal -->
<div id="profileModal" class="modal-overlay">
    <div class="modal-content profile-modal-content">
        <button type="button" class="modal-close" id="profileModalCloseBtn" aria-label="Close modal">&times;</button>
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
                        <label for="prof-name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="prof-name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="prof-phone" class="form-label">Phone Number</label>
                        <input type="text" id="prof-phone" class="form-input" disabled title="Phone number cannot be changed.">
                    </div>
                    <div class="form-group">
                        <label for="prof-email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="prof-email" class="form-input">
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
                        <input type="text" name="village" id="prof-village" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="prof-district" class="form-label">District</label>
                        <input type="text" name="district" id="prof-district" class="form-input" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="prof-state" class="form-label">State</label>
                        <input type="text" name="state" id="prof-state" class="form-input" required>
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
                        <label for="prof-upi-qr" class="form-label">UPI QR Scanner Image</label>
                        <input type="file" name="upi_qr_image" id="prof-upi-qr" class="form-input" accept="image/jpeg,image/png,image/webp">
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 5px; display: block;">
                            Upload your UPI QR code to receive payments directly from other farmers.
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

            <div class="modal-footer-actions">
                <a href="logout.php" class="btn-logout-modal" title="Log out of your account">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Log Out
                </a>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-secondary" id="profileCancelBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="profileSubmitBtn">Update Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>
