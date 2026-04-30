<!-- AgroPay Payment Modal -->
<div id="paymentModal" class="modal-overlay" style="display: none;">
    <div class="modal-content payment-modal-content" style="max-width: 440px; width: 92%; padding: 2.5rem; background: var(--glass-bg-heavy); border: 1px solid var(--glass-border); border-radius: 24px; box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.6); position: relative; animation: modalFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Premium Accent Bar -->
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #fbbf24, #d97706); border-radius: 24px 24px 0 0;"></div>

        <button type="button" class="modal-close" id="paymentModalCloseBtn" style="position: absolute; top: 1.25rem; right: 1.25rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-muted); cursor: pointer; padding: 0.5rem; border-radius: 10px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: rgba(251, 191, 36, 0.1); color: #fbbf24; border-radius: 16px; margin-bottom: 1.25rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem; letter-spacing: -0.02em;">Payment Details</h2>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">Complete your payment to the owner directly using UPI.</p>
        </div>

        <!-- Grand Total Display -->
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; text-align: center; margin-bottom: 1.5rem;">
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; display: block; margin-bottom: 0.5rem;">Payable Amount</span>
            <div style="font-size: 2.25rem; font-weight: 900; color: #fbbf24; letter-spacing: -0.01em;">
                <span style="font-size: 1.5rem; font-weight: 700; margin-right: 2px;">₹</span><span id="payment-grand-total">0</span>
            </div>
        </div>

        <!-- UPI ID Section -->
        <div style="margin-bottom: 1.5rem;">
            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.6rem; margin-left: 4px;">Owner's UPI ID</label>
            <div style="display: flex; gap: 0.75rem;">
                <div id="payment-upi-id" style="flex: 1; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.85rem 1rem; color: var(--text-main); font-family: monospace; font-size: 1rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Not Provided
                </div>
                <button type="button" id="copyUpiBtn" style="background: var(--primary-action); color: #fff; border: none; border-radius: 12px; padding: 0 1.25rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;" title="Copy UPI ID">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
            </div>
        </div>

        <!-- QR Code Display -->
        <div id="payment-qr-container" style="display: none; margin-bottom: 2rem;">
            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.6rem; margin-left: 4px;">Scan to Pay</label>
            <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 1.25rem; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <img id="payment-qr-img" src="" alt="UPI QR Code" style="max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
            </div>
        </div>

        <!-- Manual Instructions -->
        <div style="background: rgba(251, 191, 36, 0.05); border: 1px solid rgba(251, 191, 36, 0.15); border-radius: 16px; padding: 1.25rem; display: flex; gap: 1rem; align-items: flex-start;">
            <div style="color: #fbbf24; flex-shrink: 0; margin-top: 2px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </div>
            <p style="font-size: 0.82rem; line-height: 1.5; color: var(--text-main); margin: 0;">
                Scan to pay via <strong>GPay, PhonePe, or Paytm</strong>. Once paid, contact the owner to verify your booking and coordinate delivery.
            </p>
        </div>

        <div style="margin-top: 2rem;">
            <button type="button" class="btn-secondary" id="paymentModalCloseAction" style="width: 100%; padding: 0.85rem; border-radius: 14px; font-weight: 700;">Close</button>
        </div>
    </div>
</div>

<style>
@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
