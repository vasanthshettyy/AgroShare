<!-- Public User Profile Modal -->
<div id="userPublicProfileModal" class="modal-overlay" style="display: none;">
    <div class="modal-content profile-modal-content premium-public-profile" style="max-width: 550px; width: 95%; padding: 0; overflow: hidden;">
        <!-- Cover/Header Section -->
        <div class="profile-header-card">
            <button id="closePublicProfile" class="modal-close-x" style="background: rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.1); color: #fff; top: 1rem; right: 1rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            
            <div class="profile-main-meta">
                <div class="profile-avatar-large" id="pub-avatar">?</div>
                <div class="profile-identity">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h2 id="pub-name">User Name</h2>
                        <span id="pub-verified-badge" class="verified-badge-premium" style="display: none;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1.707-6.293l6.364-6.364-1.414-1.414-4.95 4.95-2.121-2.122-1.414 1.414 3.535 3.536z"/></svg>
                        </span>
                    </div>
                    <p class="pub-location"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> <span id="pub-location-text">Village, District</span></p>
                </div>
            </div>

            <div class="profile-stats-row">
                <div class="pub-stat">
                    <span class="val" id="pub-trust-val">0.0</span>
                    <span class="lab">Trust Score</span>
                </div>
                <div class="pub-divider"></div>
                <div class="pub-stat">
                    <span class="val" id="pub-deals-val">0</span>
                    <span class="lab">Completed</span>
                </div>
                <div class="pub-divider"></div>
                <div class="pub-stat">
                    <span class="val" id="pub-joined-val">Jan 2026</span>
                    <span class="lab">Member Since</span>
                </div>
            </div>
        </div>

        <div class="profile-content-scroll custom-scrollbar" style="padding: 2rem; max-height: 400px; overflow-y: auto;">
            <!-- Contact Quick-Links -->
            <div class="pub-section">
                <h4 class="pub-section-label">Contact Information</h4>
                <div class="pub-contact-grid">
                    <a id="pub-call-btn" href="#" class="pub-contact-link">
                        <div class="icon-box"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                        <div class="text">
                            <span>Phone</span>
                            <strong id="pub-phone-text">+91 00000 00000</strong>
                        </div>
                    </a>
                    <a id="pub-email-btn" href="#" class="pub-contact-link">
                        <div class="icon-box"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                        <div class="text">
                            <span>Email Address</span>
                            <strong id="pub-email-text">user@example.com</strong>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Feedback -->
            <div class="pub-section" style="margin-top: 2rem;">
                <h4 class="pub-section-label">Recent Community Feedback</h4>
                <div id="pub-reviews-container">
                    <!-- Reviews injected here -->
                    <div class="notif-empty">No reviews yet.</div>
                </div>
            </div>
        </div>

        <div class="modal-footer-premium" style="padding: 1.5rem 2rem; background: rgba(0,0,0,0.1);">
            <button type="button" class="premium-btn-secondary" style="width: 100%;" onclick="closePublicProfile()">Close Profile</button>
        </div>
    </div>
</div>

<style>
    .premium-public-profile {
        background: var(--bg-color) !important;
        border: 1px solid var(--border-color) !important;
    }

    .profile-header-card {
        background: linear-gradient(135deg, var(--primary-10) 0%, var(--surface-color) 100%);
        padding: 3rem 2rem 2rem;
        border-bottom: 1px solid var(--border-color);
        position: relative;
    }

    .profile-main-meta {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .profile-avatar-large {
        width: 80px; height: 80px;
        background: var(--primary-action);
        color: #fff;
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.5rem; font-weight: 800;
        box-shadow: 0 10px 30px rgba(76, 175, 120, 0.3);
    }

    .profile-identity h2 { font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0; }
    .pub-location { font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; display: flex; align-items: center; gap: 6px; }

    .verified-badge-premium { color: var(--secondary-action); display: flex; align-items: center; }

    .profile-stats-row {
        display: flex;
        justify-content: space-between;
        background: rgba(0,0,0,0.2);
        padding: 1rem 1.5rem;
        border-radius: 14px;
        border: 1px solid var(--border-color);
    }
    .pub-stat { text-align: center; flex: 1; }
    .pub-stat .val { display: block; font-size: 1.1rem; font-weight: 800; color: var(--text-main); }
    .pub-stat .lab { display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
    .pub-divider { width: 1px; background: var(--border-color); margin: 0 10px; }

    .pub-section-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-subtle);
        margin-bottom: 1rem;
    }

    .pub-contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 500px) { .pub-contact-grid { grid-template-columns: 1fr; } }

    .pub-contact-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.75rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .pub-contact-link:hover { border-color: var(--primary-action); background: var(--primary-10); }
    .pub-contact-link .icon-box { 
        width: 32px; height: 32px; border-radius: 8px; background: var(--surface-color); 
        display: flex; align-items: center; justify-content: center; color: var(--primary-action);
    }
    .pub-contact-link .text span { display: block; font-size: 0.65rem; color: var(--text-muted); }
    .pub-contact-link .text strong { display: block; font-size: 0.8rem; color: var(--text-main); }

    /* Small review cards for the profile view */
    .pub-review-mini {
        padding: 1rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    .pub-review-mini:last-child { border-bottom: none; }
    .prm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .prm-name { font-weight: 700; font-size: 0.85rem; color: var(--text-main); }
    .prm-date { font-size: 0.7rem; color: var(--text-subtle); }
    .prm-stars { color: #fbbf24; display: flex; gap: 2px; }
    .prm-comment { font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; }
</style>
