<!-- Reschedule Booking Modal -->
<div id="rescheduleModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); z-index:10000; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s ease;">
    <div class="modal-content" style="background:var(--surface-color, #1a2e1a); border:1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius:24px; width:100%; max-width:480px; padding:2rem; position:relative; box-shadow:0 24px 64px rgba(0,0,0,0.5);">
        <button type="button" id="closeReschedule" aria-label="Close Reschedule Modal" style="position:absolute; top:1.5rem; right:1.5rem; background:none; border:none; color:var(--text-muted); cursor:pointer;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <h2 style="font-size:1.5rem; font-weight:800; color:var(--text-main); margin-bottom:0.5rem; display:flex; align-items:center; gap:0.75rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-action)" stroke-width="2.5"><path d="M16 2v4M8 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M12 14v4M10 16h4"/></svg>
            Reschedule Booking
        </h2>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Choose new dates for your equipment rental. Price will be updated automatically.</p>

        <form id="rescheduleForm" method="POST" action="api/reschedule-booking.php" novalidate>
            <input type="hidden" name="booking_id" id="res_booking_id">
            <input type="hidden" name="csrf_token" id="res_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div class="input-group-sleek">
                    <label for="res_start_date" style="display:block; font-size:0.75rem; font-weight:700; color:var(--primary-action); text-transform:uppercase; margin-bottom:0.5rem;">Start Date</label>
                    <input type="date" name="start_date" id="res_start_date" autocomplete="off" required min="<?= date('Y-m-d') ?>" style="width:100%; background:var(--bg-color, #111); border:1px solid var(--border-color); border-radius:12px; padding:0.75rem; color:var(--text-main);">
                </div>
                <div class="input-group-sleek">
                    <label for="res_end_date" style="display:block; font-size:0.75rem; font-weight:700; color:var(--primary-action); text-transform:uppercase; margin-bottom:0.5rem;">End Date</label>
                    <input type="date" name="end_date" id="res_end_date" autocomplete="off" required min="<?= date('Y-m-d') ?>" style="width:100%; background:var(--bg-color, #111); border:1px solid var(--border-color); border-radius:12px; padding:0.75rem; color:var(--text-main);">
                </div>
            </div>

            <div id="rescheduleStatus" role="status" aria-live="polite" style="padding:1rem; border-radius:12px; margin-bottom:1.5rem; display:none; font-size:0.85rem;"></div>

            <button type="button" id="rescheduleSubmit" aria-label="Confirm Reschedule" style="width:100%; background:var(--primary-action, #2e7d32); color:#fff; border:none; padding:1rem; border-radius:12px; font-weight:700; cursor:pointer; transition:all 0.3s ease; display:flex; align-items:center; justify-content:center; gap:0.75rem;">
                Update Booking
            </button>
        </form>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('rescheduleModal');
    const form = document.getElementById('rescheduleForm');
    const statusDiv = document.getElementById('rescheduleStatus');
    const submitBtn = document.getElementById('rescheduleSubmit');

    /**
     * Exposed globally to be called from my-bookings.php event delegation
     */
    window.initRescheduleModal = function(booking) {
        try {
            if (!booking || !booking.id) {
                console.error('Reschedule Error: Booking data is empty or invalid.');
                return;
            }

            const idEl = document.getElementById('res_booking_id');
            const startEl = document.getElementById('res_start_date');
            const endEl = document.getElementById('res_end_date');

            if (!idEl || !startEl || !endEl) {
                console.error('Reschedule Error: Modal internal inputs missing.');
                return;
            }

            idEl.value = booking.id;
            
            const startVal = (booking.start_datetime || '').toString();
            const endVal = (booking.end_datetime || '').toString();

            const startDate = startVal.includes(' ') ? startVal.split(' ')[0] : startVal;
            const endDate = endVal.includes(' ') ? endVal.split(' ')[0] : endVal;

            startEl.value = startDate;
            endEl.value = endDate;

            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
        } catch (err) {
            console.error('initRescheduleModal Fatal Error:', err);
        }
    };

    function closeModal() {
        if (modal) {
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
            setTimeout(() => {
                modal.style.display = 'none';
                statusDiv.style.display = 'none';
            }, 300);
        }
    }

    document.getElementById('closeReschedule')?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    submitBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        
        const startDate = document.getElementById('res_start_date').value;
        const endDate = document.getElementById('res_end_date').value;

        if (!startDate || !endDate) {
            statusDiv.style.background = 'rgba(198, 40, 40, 0.1)';
            statusDiv.style.color = '#c62828';
            statusDiv.style.border = '1px solid rgba(198, 40, 40, 0.2)';
            statusDiv.textContent = 'Please select both start and end dates.';
            statusDiv.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loader-small"></span> Processing...';
        statusDiv.style.display = 'none';

        const formData = new FormData(form);
        formData.set('new_start_datetime', startDate + ' 09:00:00');
        formData.set('new_end_datetime', endDate + ' 18:00:00');

        try {
            const res = await fetch('api/reschedule-booking.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                statusDiv.style.background = 'rgba(46, 125, 50, 0.1)';
                statusDiv.style.color = '#2e7d32';
                statusDiv.style.border = '1px solid rgba(46, 125, 50, 0.2)';
                statusDiv.innerHTML = 'Success! ' + data.message + '<br><small>Updating view...</small>';
                statusDiv.style.display = 'block';
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                statusDiv.style.background = 'rgba(198, 40, 40, 0.1)';
                statusDiv.style.color = '#c62828';
                statusDiv.style.border = '1px solid rgba(198, 40, 40, 0.2)';
                statusDiv.textContent = data.message;
                statusDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (err) {
            console.error('Reschedule Submit Error:', err);
            statusDiv.textContent = 'Network error. Please try again.';
            statusDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
})();
</script>

<style>
.loader-small {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
