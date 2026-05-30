<!-- Reschedule Booking Modal -->
<div id="rescheduleModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); z-index:10000; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:var(--surface-color, #1a2e1a); border:1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius:24px; width:100%; max-width:560px; padding:2rem; position:relative; box-shadow:0 24px 64px rgba(0,0,0,0.5);">
        <button type="button" id="closeReschedule" aria-label="Close Reschedule Modal" style="position:absolute; top:1.5rem; right:1.5rem; background:none; border:none; color:var(--text-muted); cursor:pointer;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <h2 style="font-size:1.5rem; font-weight:800; color:var(--text-main); margin-bottom:0.5rem; display:flex; align-items:center; gap:0.75rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-action)" stroke-width="2.5"><path d="M16 2v4M8 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M12 14v4M10 16h4"/></svg>
            Reschedule Booking
        </h2>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.2rem;">Pick new dates from the full calendar. Blocked dates cannot be selected, and your old booked range stays editable.</p>

        <form id="rescheduleForm" method="POST" action="api/reschedule-booking.php" novalidate>
            <input type="hidden" name="booking_id" id="res_booking_id">
            <input type="hidden" id="res_equipment_id">
            <input type="hidden" name="csrf_token" id="res_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="new_start_datetime" id="res_start_datetime_hidden">
            <input type="hidden" name="new_end_datetime" id="res_end_datetime_hidden">

            <div style="margin-bottom:1rem; padding:0.8rem 0.9rem; border:1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius:12px; background:rgba(255,255,255,0.02);">
                <div style="font-size:0.72rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.4rem;">Old Booking Dates</div>
                <div id="res_old_range_text" style="font-size:0.9rem; font-weight:700; color:var(--text-main);">—</div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="input-group-sleek">
                    <label for="res_start_display" style="display:block; font-size:0.75rem; font-weight:700; color:var(--primary-action); text-transform:uppercase; margin-bottom:0.5rem;">Start Date</label>
                    <input type="text" id="res_start_display" readonly style="width:100%; background:var(--bg-color, #111); border:1px solid var(--border-color); border-radius:12px; padding:0.75rem; color:var(--text-main);">
                </div>
                <div class="input-group-sleek">
                    <label for="res_end_display" style="display:block; font-size:0.75rem; font-weight:700; color:var(--primary-action); text-transform:uppercase; margin-bottom:0.5rem;">End Date</label>
                    <input type="text" id="res_end_display" readonly style="width:100%; background:var(--bg-color, #111); border:1px solid var(--border-color); border-radius:12px; padding:0.75rem; color:var(--text-main);">
                </div>
            </div>

            <div class="res-cal-wrap">
                <div class="res-cal-header">
                    <button type="button" id="resCalPrev" class="res-cal-nav" aria-label="Previous month">‹</button>
                    <div id="resCalMonthYear" class="res-cal-month">Loading…</div>
                    <button type="button" id="resCalNext" class="res-cal-nav" aria-label="Next month">›</button>
                </div>
                <div id="resCalGrid" class="res-cal-grid">
                    <div class="res-cal-day-label">Mo</div>
                    <div class="res-cal-day-label">Tu</div>
                    <div class="res-cal-day-label">We</div>
                    <div class="res-cal-day-label">Th</div>
                    <div class="res-cal-day-label">Fr</div>
                    <div class="res-cal-day-label">Sa</div>
                    <div class="res-cal-day-label">Su</div>
                </div>
                <div id="resCalHint" class="res-cal-hint">Select start and end dates. Blocked dates are disabled. Double-click the same date for a single-day booking.</div>
            </div>

            <div style="margin-bottom:1rem; padding:0.75rem 0.9rem; border:1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius:12px; background:rgba(255,255,255,0.015);">
                <div style="font-size:0.72rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.5rem;">Calendar Legend</div>
                <div style="display:flex; flex-wrap:wrap; gap:0.55rem 0.9rem; font-size:0.76rem; color:var(--text-muted);">
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <span style="width:12px; height:12px; border-radius:3px; background:rgba(198,40,40,0.16); border:1px solid rgba(198,40,40,0.45);"></span>
                        Red = already booked by others (blocked)
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <span style="width:12px; height:12px; border-radius:3px; background:var(--primary-action);"></span>
                        Green = your new selected dates
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <span style="width:12px; height:12px; border-radius:3px; background:rgba(255,255,255,0.03); outline:1px dashed rgba(76,175,120,0.7);"></span>
                        Dashed = your old booking range (editable)
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:6px;">
                        <span style="width:12px; height:12px; border-radius:3px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.25);"></span>
                        Tip: double-click the same date for a single-day booking
                    </span>
                </div>
            </div>

            <div id="rescheduleStatus" role="status" aria-live="polite" style="padding:1rem; border-radius:12px; margin-bottom:1.2rem; display:none; font-size:0.85rem;"></div>

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
    const startDisplay = document.getElementById('res_start_display');
    const endDisplay = document.getElementById('res_end_display');
    const oldRangeText = document.getElementById('res_old_range_text');
    const startHidden = document.getElementById('res_start_datetime_hidden');
    const endHidden = document.getElementById('res_end_datetime_hidden');
    const bookingIdEl = document.getElementById('res_booking_id');
    const equipmentIdEl = document.getElementById('res_equipment_id');
    const calGrid = document.getElementById('resCalGrid');
    const calMonthYear = document.getElementById('resCalMonthYear');
    const calHint = document.getElementById('resCalHint');

    if (!modal) return;

    const state = {
        viewDate: new Date(),
        today: (() => {
            const d = new Date();
            d.setHours(0, 0, 0, 0);
            return d;
        })(),
        minDate: (() => {
            const d = new Date();
            d.setHours(0, 0, 0, 0);
            d.setMonth(d.getMonth() - 2);
            return d;
        })(),
        maxDate: (() => {
            const d = new Date();
            d.setHours(0, 0, 0, 0);
            d.setMonth(d.getMonth() + 2);
            return d;
        })(),
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        blockedRanges: [],
        ownStart: null,
        ownEnd: null,
        selectedStart: null,
        selectedEnd: null
    };

    function normalizeDate(d) {
        const n = new Date(d);
        n.setHours(0, 0, 0, 0);
        return n;
    }

    function toYmd(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function toDisplay(d) {
        return d.toLocaleDateString('en-GB');
    }

    function isInOwnRange(date) {
        return !!(state.ownStart && state.ownEnd && date >= state.ownStart && date <= state.ownEnd);
    }

    function isBlockedByOthers(date) {
        return state.blockedRanges.some(r => {
            const s = normalizeDate(r.start);
            const e = normalizeDate(r.end);
            if (state.ownStart && state.ownEnd && s.getTime() === state.ownStart.getTime() && e.getTime() === state.ownEnd.getTime()) {
                return false;
            }
            return date >= s && date <= e;
        });
    }

    function rangeOverlapsOthers(start, end) {
        return state.blockedRanges.some(r => {
            const s = normalizeDate(r.start);
            const e = normalizeDate(r.end);
            if (state.ownStart && state.ownEnd && s.getTime() === state.ownStart.getTime() && e.getTime() === state.ownEnd.getTime()) {
                return false;
            }
            return start <= e && end >= s;
        });
    }

    function setStatus(message, isError) {
        statusDiv.style.display = 'block';
        statusDiv.style.background = isError ? 'rgba(198, 40, 40, 0.1)' : 'rgba(46, 125, 50, 0.1)';
        statusDiv.style.color = isError ? '#c62828' : '#2e7d32';
        statusDiv.style.border = isError ? '1px solid rgba(198, 40, 40, 0.2)' : '1px solid rgba(46, 125, 50, 0.2)';
        statusDiv.textContent = message;
    }

    function clearStatus() {
        statusDiv.style.display = 'none';
        statusDiv.textContent = '';
    }

    function updateSelectionUI() {
        if (state.selectedStart) {
            startDisplay.value = toDisplay(state.selectedStart);
            startHidden.value = `${toYmd(state.selectedStart)} 09:00:00`;
        } else {
            startDisplay.value = '';
            startHidden.value = '';
        }

        if (state.selectedEnd) {
            endDisplay.value = toDisplay(state.selectedEnd);
            endHidden.value = `${toYmd(state.selectedEnd)} 18:00:00`;
        } else {
            endDisplay.value = '';
            endHidden.value = '';
        }

        if (state.selectedStart && state.selectedEnd) {
            calHint.textContent = `Selected ${toDisplay(state.selectedStart)} → ${toDisplay(state.selectedEnd)}.`;
        } else {
            calHint.textContent = 'Select start and end dates. Blocked dates are disabled. Double-click the same date for a single-day booking.';
        }

        if (oldRangeText && state.ownStart && state.ownEnd) {
            oldRangeText.textContent = `${toDisplay(state.ownStart)} → ${toDisplay(state.ownEnd)}`;
        }
    }

    function renderCalendar() {
        const year = state.viewDate.getFullYear();
        const month = state.viewDate.getMonth();
        calMonthYear.textContent = `${state.monthNames[month]} ${year}`;

        const labels = Array.from(calGrid.querySelectorAll('.res-cal-day-label'));
        calGrid.innerHTML = '';
        labels.forEach(l => calGrid.appendChild(l));

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const offset = firstDay === 0 ? 6 : firstDay - 1;

        for (let i = 0; i < offset; i++) {
            calGrid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = normalizeDate(new Date(year, month, day));
            const dayBtn = document.createElement('button');
            dayBtn.type = 'button';
            dayBtn.className = 'res-cal-day';
            dayBtn.textContent = String(day);

            const isPast = date < state.today;
            const isOutOfRange = date < state.minDate || date > state.maxDate;
            const isOwn = isInOwnRange(date);
            const blockedByOthers = isBlockedByOthers(date);

            if (blockedByOthers) {
                dayBtn.classList.add('blocked');
                dayBtn.disabled = true;
            } else if ((isPast && !isOwn) || isOutOfRange) {
                dayBtn.classList.add('disabled');
                dayBtn.disabled = true;
            } else {
                dayBtn.classList.add('available');
                dayBtn.addEventListener('click', () => onDateClick(date));
            }

            if (isOwn) dayBtn.classList.add('own-old-range');
            if (state.selectedStart && date.getTime() === state.selectedStart.getTime()) dayBtn.classList.add('selected', 'range-start');
            if (state.selectedEnd && date.getTime() === state.selectedEnd.getTime()) dayBtn.classList.add('selected', 'range-end');
            if (state.selectedStart && state.selectedEnd && date > state.selectedStart && date < state.selectedEnd) dayBtn.classList.add('range-mid');

            calGrid.appendChild(dayBtn);
        }
    }

    function onDateClick(date) {
        clearStatus();

        if (!state.selectedStart || (state.selectedStart && state.selectedEnd)) {
            state.selectedStart = date;
            state.selectedEnd = null;
            updateSelectionUI();
            renderCalendar();
            return;
        }

        if (date < state.selectedStart) {
            state.selectedStart = date;
            state.selectedEnd = null;
            updateSelectionUI();
            renderCalendar();
            return;
        }

        if (rangeOverlapsOthers(state.selectedStart, date)) {
            setStatus('Selected range overlaps blocked dates. Please choose available days only.', true);
            state.selectedStart = date;
            state.selectedEnd = null;
            updateSelectionUI();
            renderCalendar();
            return;
        }

        state.selectedEnd = date;
        updateSelectionUI();
        renderCalendar();
    }

    async function fetchBlockedRanges(equipmentId) {
        if (!equipmentId) {
            state.blockedRanges = [];
            return;
        }

        try {
            const res = await fetch(`api/get-booked-slots.php?id=${encodeURIComponent(equipmentId)}`);
            const data = await res.json();
            state.blockedRanges = data.success && Array.isArray(data.booked_ranges) ? data.booked_ranges : [];
        } catch (err) {
            console.error('Failed to fetch blocked ranges:', err);
            state.blockedRanges = [];
        }
    }

    window.initRescheduleModal = async function(booking) {
        try {
            if (!booking || !booking.id) {
                setStatus('Invalid booking details.', true);
                return;
            }

            bookingIdEl.value = booking.id;
            equipmentIdEl.value = booking.equipment_id || '';

            const startVal = String(booking.start_datetime || '').split(' ')[0];
            const endVal = String(booking.end_datetime || '').split(' ')[0];

            state.ownStart = normalizeDate(new Date(startVal));
            state.ownEnd = normalizeDate(new Date(endVal));
            state.selectedStart = state.ownStart;
            state.selectedEnd = state.ownEnd;
            state.viewDate = new Date(state.ownStart);

            await fetchBlockedRanges(equipmentIdEl.value);

            updateSelectionUI();
            renderCalendar();
            clearStatus();

            modal.style.display = 'flex';
            modal.classList.add('show-modal');
        } catch (err) {
            console.error('initRescheduleModal fatal error:', err);
            setStatus('Could not initialize calendar. Please try again.', true);
        }
    };

    function closeModal() {
        modal.classList.remove('show-modal');
        modal.style.display = 'none';
        clearStatus();
    }

    document.getElementById('closeReschedule')?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    document.getElementById('resCalPrev')?.addEventListener('click', () => {
        const nextView = new Date(state.viewDate);
        nextView.setMonth(state.viewDate.getMonth() - 1);
        const minCompare = new Date(state.minDate.getFullYear(), state.minDate.getMonth(), 1);
        if (nextView < minCompare) return;

        state.viewDate = nextView;
        renderCalendar();
    });

    document.getElementById('resCalNext')?.addEventListener('click', () => {
        const nextView = new Date(state.viewDate);
        nextView.setMonth(state.viewDate.getMonth() + 1);
        const maxCompare = new Date(state.maxDate.getFullYear(), state.maxDate.getMonth(), 1);
        if (nextView > maxCompare) return;

        state.viewDate = nextView;
        renderCalendar();
    });

    submitBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        if (!startHidden.value || !endHidden.value) {
            setStatus('Please select both start and end dates.', true);
            return;
        }

        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loader-small"></span> Processing...';
        clearStatus();

        const formData = new FormData(form);
        formData.set('new_start_datetime', startHidden.value);
        formData.set('new_end_datetime', endHidden.value);

        try {
            const res = await fetch('api/reschedule-booking.php', {
                method: 'POST',
                body: formData
            });

            const raw = await res.text();
            let data = null;
            try {
                data = JSON.parse(raw);
            } catch (parseErr) {
                console.error('Reschedule non-JSON response:', raw);
                setStatus('Server returned an unexpected response. Please refresh and try again.', true);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            if (data.success) {
                setStatus(`Success! ${data.message}`, false);
                setTimeout(() => window.location.reload(), 1400);
            } else {
                setStatus(data.message || 'Failed to reschedule booking.', true);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (err) {
            console.error('Reschedule submit error:', err);
            setStatus('Network error. Please try again.', true);
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

.res-cal-wrap {
    border: 1px solid var(--border-color, rgba(255,255,255,0.1));
    border-radius: 14px;
    padding: 0.9rem;
    margin-bottom: 1.2rem;
    background: rgba(255,255,255,0.02);
}
.res-cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.7rem;
}
.res-cal-month {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-main);
}
.res-cal-nav {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: rgba(255,255,255,0.03);
    color: var(--text-main);
    cursor: pointer;
}
.res-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 6px;
}
.res-cal-day-label {
    text-align: center;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--text-subtle);
    padding: 4px 0;
}
.res-cal-day {
    border: 1px solid transparent;
    border-radius: 8px;
    background: rgba(255,255,255,0.03);
    color: var(--text-main);
    min-height: 30px;
    font-size: 0.78rem;
    cursor: pointer;
}
.res-cal-day.available:hover {
    border-color: var(--primary-action);
}
.res-cal-day.blocked {
    background: rgba(198, 40, 40, 0.16);
    color: #ef9a9a;
    cursor: not-allowed;
}
.res-cal-day.disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.res-cal-day.own-old-range {
    outline: 1px dashed rgba(76,175,120,0.7);
}
.res-cal-day.selected,
.res-cal-day.range-start,
.res-cal-day.range-end {
    background: var(--primary-action);
    color: #fff;
}
.res-cal-day.range-mid {
    background: rgba(76,175,120,0.24);
}
.res-cal-hint {
    margin-top: 0.7rem;
    font-size: 0.74rem;
    color: var(--text-muted);
}
</style>
