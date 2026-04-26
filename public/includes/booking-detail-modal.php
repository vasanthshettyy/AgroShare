<!-- Booking Detail Modal -->
<div id="bookingDetailModal" class="modal-overlay" style="display: none;">
    <div class="modal-content profile-modal-content booking-premium-modal" style="max-width: 700px; width: 95%; padding: 0; overflow: hidden;">
        <!-- Header Image & Overlay -->
        <div class="booking-modal-hero">
            <img id="bd-hero-img" src="assets/img/placeholder.png" alt="Equipment">
            <div class="hero-overlay"></div>
            <button id="closeBookingModal" class="modal-close-x" style="background: rgba(0,0,0,0.5); border-color: rgba(255,255,255,0.1); color: #fff;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div class="hero-content">
                <span class="status-badge" id="bd-status-badge">Pending</span>
                <h2 id="bd-title">Equipment Name</h2>
                <p id="bd-id-tag" style="font-family: monospace; font-size: 0.8rem; opacity: 0.8; margin-top: 4px;">Order #AS-12345</p>
            </div>
        </div>

        <div class="booking-modal-body" style="padding: 2.5rem;">
            <div class="bd-grid">
                <!-- Left Column: Agreement & Timeline -->
                <div class="bd-col-main">
                    <div class="bd-section">
                        <h4 class="bd-label">Rental Timeline</h4>
                        <div class="timeline-visual">
                            <div class="time-node">
                                <span class="time-point"></span>
                                <div class="time-data">
                                    <span class="label">Pickup</span>
                                    <strong id="bd-start-date">01 Jan 2026</strong>
                                </div>
                            </div>
                            <div class="time-connector"></div>
                            <div class="time-node">
                                <span class="time-point end"></span>
                                <div class="time-data">
                                    <span class="label">Return</span>
                                    <strong id="bd-end-date">05 Jan 2026</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bd-section">
                        <h4 class="bd-label">Transaction Details</h4>
                        <div class="pricing-card">
                            <div class="price-row">
                                <span>Rental Fee (Total)</span>
                                <span id="bd-rental-fee">₹0</span>
                            </div>
                            <div class="price-row">
                                <span>Safety Deposit (Refundable)</span>
                                <span id="bd-deposit">₹0</span>
                            </div>
                            <div class="price-row total">
                                <span>Total Amount</span>
                                <span id="bd-total-price">₹0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Counterparty -->
                <div class="bd-col-side">
                    <div class="counterparty-card">
                        <div id="bd-party-type" class="bd-label" style="margin-bottom: 1rem;">Owner Info</div>
                        <div class="party-info">
                            <div class="party-avatar" id="bd-party-avatar">?</div>
                            <div class="party-text">
                                <strong id="bd-party-name">User Name</strong>
                                <div class="party-trust">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    <span id="bd-party-trust">0.0</span>
                                </div>
                            </div>
                        </div>
                        <div class="party-actions" style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                            <a id="bd-call-btn" href="#" class="btn-sm btn-primary" style="width: 100%; text-align: center; justify-content: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Call Now
                            </a>
                            <button type="button" class="btn-sm btn-secondary" style="width: 100%;" onclick="closeDetailModal()">Close View</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .booking-premium-modal {
        background: var(--bg-color) !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.6) !important;
    }

    .booking-modal-hero {
        height: 200px;
        position: relative;
        overflow: hidden;
    }
    .booking-modal-hero img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, var(--bg-color) 0%, transparent 100%);
    }
    .hero-content {
        position: absolute;
        bottom: 1.5rem;
        left: 2.5rem;
        z-index: 5;
    }
    .hero-content h2 { font-size: 1.75rem; font-weight: 800; color: #fff; margin: 0; }

    .bd-grid { display: grid; grid-template-columns: 1fr 220px; gap: 2.5rem; }
    @media (max-width: 600px) { .bd-grid { grid-template-columns: 1fr; } }

    .bd-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 1.25rem;
    }

    .timeline-visual {
        display: flex;
        align-items: center;
        background: rgba(255,255,255,0.03);
        padding: 1.25rem;
        border-radius: 14px;
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
    }
    .time-point {
        width: 12px; height: 12px;
        background: var(--primary-action);
        border-radius: 50%;
        box-shadow: 0 0 10px var(--primary-action);
    }
    .time-point.end { background: var(--secondary-action); box-shadow: 0 0 10px var(--secondary-action); }
    .time-connector { flex: 1; height: 2px; background: var(--border-color); margin: 0 10px; position: relative; }
    .time-connector::after {
        content: '→';
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 10px; color: var(--text-subtle);
    }
    .time-data { display: flex; flex-direction: column; gap: 2px; }
    .time-data .label { font-size: 0.65rem; color: var(--text-subtle); }
    .time-data strong { font-size: 0.9rem; color: var(--text-main); }

    .pricing-card {
        background: rgba(76, 175, 120, 0.05);
        border: 1px solid rgba(76, 175, 120, 0.15);
        border-radius: 14px;
        padding: 1.25rem;
    }
    .price-row { display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.75rem; }
    .price-row.total { 
        margin-top: 1rem; padding-top: 1rem; 
        border-top: 1px dashed var(--border-color); 
        color: var(--text-main); font-weight: 800; font-size: 1.1rem;
    }
    .price-row.total span:last-child { color: var(--primary-action); }

    .counterparty-card {
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem;
    }
    .party-info { display: flex; align-items: center; gap: 12px; }
    .party-avatar {
        width: 44px; height: 44px;
        background: var(--primary-10);
        color: var(--primary-action);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem;
    }
    .party-trust { display: flex; align-items: center; gap: 4px; font-size: 0.8rem; color: #fbbf24; font-weight: 700; margin-top: 2px; }
</style>
