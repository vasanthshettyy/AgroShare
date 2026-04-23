/**
 * calendar.js — Interactive Booking Calendar (Vanilla ES6)
 * 
 * Logic:
 * 1. First click sets selectedStart.
 * 2. Second click sets selectedEnd (if no overlap with booked dates).
 * 3. Range is visually highlighted.
 */
'use strict';

class BookingCalendar {
    constructor(containerId, equipmentId) {
        this.container = document.getElementById(containerId);
        this.equipmentId = equipmentId;
        this.viewDate = new Date(); // Viewing month
        this.today = new Date();
        this.today.setHours(0, 0, 0, 0);

        this.selectedStart = null;
        this.selectedEnd = null;
        this.bookedRanges = []; // Array of {start, end} Date objects

        this.monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];

        this.init();
    }

    async init() {
        await this.fetchBookedSlots();
        this.render();
        this.bindEvents();
    }

    async fetchBookedSlots() {
        try {
            const res = await fetch(`api/get-booked-slots.php?id=${this.equipmentId}`);
            const data = await res.json();
            if (data.success) {
                this.bookedRanges = data.booked_ranges.map(r => ({
                    start: new Date(r.start),
                    end: new Date(r.end)
                }));
            }
        } catch (err) {
            console.error('Failed to fetch booked slots:', err);
        }
    }

    bindEvents() {
        document.getElementById('calPrev').addEventListener('click', () => this.changeMonth(-1));
        document.getElementById('calNext').addEventListener('click', () => this.changeMonth(1));

        const btnBook = document.getElementById('btnBookNow');
        if (btnBook) {
            btnBook.addEventListener('click', () => this.submitBooking());
        }
    }

    async submitBooking() {
        if (!this.selectedStart || !this.selectedEnd) return;
        
        const btnBook = document.getElementById('btnBookNow');
        const originalHtml = btnBook.innerHTML;
        
        const confirmed = await this.showConfirmModal(
            this.selectedStart.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }),
            this.selectedEnd.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }),
            document.getElementById('est-total').textContent
        );
        
        if (!confirmed) return;

        btnBook.disabled = true;
        btnBook.innerHTML = '<span class="loading-spinner"></span> Sending...';

        try {
            const formData = new FormData();
            formData.append('equipment_id', this.equipmentId);
            formData.append('start_datetime', document.getElementById('est-start').value);
            formData.append('end_datetime', document.getElementById('est-end').value);
            formData.append('csrf_token', document.getElementById('global-csrf-token').value);

            const res = await fetch('api/initiate_booking.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                this.showBookingSuccess({
                    equipment_title: document.querySelector('.detail-title').textContent,
                    start_date: document.getElementById('est-start').value,
                    end_date: document.getElementById('est-end').value,
                    total_price: data.data.amount,
                    owner_name: data.data.owner_contact.name,
                    owner_phone: data.data.owner_contact.phone
                });
                
                // Clear selection
                this.selectedStart = null;
                this.selectedEnd = null;
                this.updateInputs();
                await this.fetchBookedSlots();
                this.render();
            } else {
                if (window.showToast) window.showToast('error', data.message);
                btnBook.disabled = false;
                btnBook.innerHTML = originalHtml;
            }
        } catch (err) {
            console.error('Booking error:', err);
            if (window.showToast) window.showToast('error', 'Network error. Please try again.');
            btnBook.disabled = false;
            btnBook.innerHTML = originalHtml;
        }
    }

    showConfirmModal(startDate, endDate, totalPrice) {
        return new Promise((resolve) => {
            // Remove any existing confirm modal
            document.getElementById('bookingConfirmOverlay')?.remove();

            const overlay = document.createElement('div');
            overlay.id = 'bookingConfirmOverlay';
            overlay.className = 'booking-success-overlay';
            overlay.innerHTML = `
                <div class="booking-success-card" style="max-width:400px;">
                    <div class="success-icon-wrap">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--primary-action, #2e7d32)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                            <path d="m9 16 2 2 4-4"/>
                        </svg>
                    </div>
                    <h2 class="success-title" style="font-size:1.3rem;">Confirm Booking?</h2>
                    <div class="success-details" style="margin-top:1rem;">
                        <div class="success-detail-row">
                            <span class="detail-label">📅 Dates</span>
                            <span class="detail-value">${startDate} — ${endDate}</span>
                        </div>
                        <div class="success-detail-row">
                            <span class="detail-label">💰 Total</span>
                            <span class="detail-value detail-price">${totalPrice}</span>
                        </div>
                    </div>
                    <p class="success-note" style="margin-top:1rem;">A booking request will be sent to the equipment owner for approval.</p>
                    <div class="success-actions">
                        <button type="button" class="btn-primary success-btn" id="confirmBookingYes">Yes, Book Now</button>
                        <button type="button" class="btn-secondary success-btn" id="confirmBookingNo">Cancel</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add('visible'));

            overlay.querySelector('#confirmBookingYes').addEventListener('click', () => {
                overlay.classList.remove('visible');
                setTimeout(() => overlay.remove(), 300);
                resolve(true);
            });

            overlay.querySelector('#confirmBookingNo').addEventListener('click', () => {
                overlay.classList.remove('visible');
                setTimeout(() => overlay.remove(), 300);
                resolve(false);
            });
        });
    }

    showBookingSuccess(data) {
        // Remove any existing overlay
        document.getElementById('bookingSuccessOverlay')?.remove();

        const formatDate = (dateStr) => {
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
        };

        const overlay = document.createElement('div');
        overlay.id = 'bookingSuccessOverlay';
        overlay.className = 'booking-success-overlay';
        overlay.innerHTML = `
            <div class="booking-success-card">
                <div class="success-icon-wrap">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary-action)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h2 class="success-title">Booking Request Sent!</h2>
                <p class="success-subtitle">Your request for <strong>${data.equipment_title || 'this equipment'}</strong> has been sent to the owner.</p>
                
                <div class="success-details">
                    <div class="success-detail-row">
                        <span class="detail-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            Dates
                        </span>
                        <span class="detail-value">${formatDate(data.start_date)} — ${formatDate(data.end_date)}</span>
                    </div>
                    <div class="success-detail-row">
                        <span class="detail-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            Total
                        </span>
                        <span class="detail-value detail-price">₹${Number(data.total_price).toLocaleString('en-IN')}</span>
                    </div>
                </div>
                
                <div class="success-owner-card">
                    <div class="owner-badge">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="owner-info">
                        <span class="owner-label">Equipment Owner</span>
                        <strong class="owner-name-display">${data.owner_name || 'Owner'}</strong>
                    </div>
                    ${data.owner_phone ? `
                    <a href="tel:${data.owner_phone}" class="owner-phone-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        ${data.owner_phone}
                    </a>` : ''}
                </div>

                <p class="success-note">The owner will be notified. You can track your booking status on the My Bookings page.</p>

                <div class="success-actions">
                    <a href="my-bookings.php" class="btn-primary success-btn">Go to My Bookings</a>
                    <button type="button" class="btn-secondary success-btn" id="successCloseBtn">Continue Browsing</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Animate in
        requestAnimationFrame(() => overlay.classList.add('visible'));

        // Close button
        overlay.querySelector('#successCloseBtn').addEventListener('click', () => {
            overlay.classList.remove('visible');
            setTimeout(() => overlay.remove(), 300);
        });
    }

    changeMonth(delta) {
        this.viewDate.setMonth(this.viewDate.getMonth() + delta);
        this.render();
    }

    isDateBooked(date) {
        return this.bookedRanges.some(range => {
            const start = new Date(range.start);
            start.setHours(0, 0, 0, 0);
            const end = new Date(range.end);
            end.setHours(23, 59, 59, 999);
            return date >= start && date <= end;
        });
    }

    hasOverlap(start, end) {
        return this.bookedRanges.some(range => {
            const bStart = new Date(range.start);
            bStart.setHours(0, 0, 0, 0);
            const bEnd = new Date(range.end);
            bEnd.setHours(23, 59, 59, 999);

            // Overlap condition: start <= bEnd && end >= bStart
            return start <= bEnd && end >= bStart;
        });
    }

    render() {
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();

        document.getElementById('calMonthYear').textContent = `${this.monthNames[month]} ${year}`;

        const grid = this.container;
        const labels = grid.querySelectorAll('.calendar-day-label');
        grid.innerHTML = '';
        labels.forEach(l => grid.appendChild(l));

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        let offset = firstDay === 0 ? 6 : firstDay - 1;

        for (let i = 0; i < offset; i++) {
            grid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            date.setHours(0, 0, 0, 0);

            const dayEl = document.createElement('div');
            dayEl.className = 'calendar-day';
            dayEl.textContent = day;

            if (date < this.today) {
                dayEl.classList.add('disabled');
            } else if (this.isDateBooked(date)) {
                dayEl.classList.add('booked');
            } else {
                dayEl.classList.add('available');
                if (date.getTime() === this.today.getTime()) dayEl.classList.add('today');
                dayEl.addEventListener('click', () => this.handleDateClick(date));

                // Add hover effect if only start is selected
                if (this.selectedStart && !this.selectedEnd && date > this.selectedStart) {
                    dayEl.addEventListener('mouseenter', () => this.previewRange(date));
                }
            }

            // Apply range classes
            if (this.selectedStart && date.getTime() === this.selectedStart.getTime()) {
                dayEl.classList.add('selected', 'range-start');
                if (this.selectedEnd) dayEl.classList.add('has-range');
            } else if (this.selectedEnd && date.getTime() === this.selectedEnd.getTime()) {
                dayEl.classList.add('selected', 'range-end');
            } else if (this.selectedStart && this.selectedEnd && date > this.selectedStart && date < this.selectedEnd) {
                dayEl.classList.add('range-mid');
            }

            grid.appendChild(dayEl);
        }
    }

    handleDateClick(date) {
        if (!this.selectedStart || (this.selectedStart && this.selectedEnd)) {
            // Start a new selection
            this.selectedStart = date;
            this.selectedEnd = null;
        } else {
            // Selecting an end date
            if (date < this.selectedStart) {
                this.selectedStart = date; // Reset start if clicked before current start
            } else {
                // Check for booked overlaps in between
                if (this.hasOverlap(this.selectedStart, date)) {
                    if (window.showToast) window.showToast('error', 'Selected range overlaps with existing bookings.');
                    this.selectedStart = date; // Reset start to the new click
                } else {
                    this.selectedEnd = date;
                }
            }
        }

        this.updateInputs();
        this.render();
    }

    updateInputs() {
        const startInput = document.getElementById('est-start');
        const endInput = document.getElementById('est-end');
        const btnBook = document.getElementById('btnBookNow');

        // Helper to get local ISO date string (YYYY-MM-DD)
        const toLocalDateString = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        if (this.selectedStart && this.selectedEnd) {
            // Range selected (can be same day)
            startInput.value = toLocalDateString(this.selectedStart) + 'T09:00';
            endInput.value = toLocalDateString(this.selectedEnd) + 'T18:00';
            if (btnBook) {
                btnBook.disabled = false;
                btnBook.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> Book Now`;
            }

            // Availability hint
            const diffDays = Math.ceil((this.selectedEnd - this.selectedStart) / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('calHintText').innerHTML = `Selected <span class="avail-highlight">${diffDays} day${diffDays > 1 ? 's' : ''}</span>.`;
        } else if (this.selectedStart) {
            // Only start selected
            startInput.value = toLocalDateString(this.selectedStart) + 'T09:00';
            endInput.value = '';
            if (btnBook) {
                btnBook.disabled = true;
                btnBook.textContent = 'Select end date';
            }

            const nextBooked = this.getNextBookedDate(this.selectedStart);
            const avail = nextBooked ? Math.floor((nextBooked - this.selectedStart) / (86400000)) : 30;

            document.getElementById('calHintText').innerHTML = `Selected start. <span class="avail-highlight">Click again</span> for a 1-day rental, or pick an end date.`;
        } else {
            startInput.value = '';
            endInput.value = '';
            if (btnBook) {
                btnBook.disabled = true;
                btnBook.textContent = 'Select dates to book';
            }
            document.getElementById('calHintText').innerHTML = `Tip: Click twice on the same date for a single-day rental.`;
        }

        if (window.calculatePricing) window.calculatePricing();
    }

    getNextBookedDate(startDate) {
        const next = this.bookedRanges
            .map(r => new Date(r.start))
            .filter(d => d > startDate)
            .sort((a, b) => a - b)[0];
        return next || null;
    }

    previewRange(date) {
        // Optional: Implement visual range preview on hover
    }
}

/* ══════════════════════════════════════════════════════════
   STICKY BOOKING BAR LOGIC (Intersection Observer + Proxy)
   ══════════════════════════════════════════════════════════ */
(function initStickyBookingBar() {
    console.log("AgroShare: Sticky Booking Bar Initialized");
    const stickyBar = document.getElementById('stickyBookingBar');
    const stickyBtn = document.getElementById('stickyBookBtn');
    const mainBtn = document.getElementById('btnBookNow');
    const stickyPriceText = document.getElementById('sticky-est-total');
    const mainPriceText = document.getElementById('est-total');

    // If the main Book Now button doesn't exist (e.g. user is owner or item is unavailable),
    // remove the sticky bar entirely.
    if (!mainBtn || !stickyBar) {
        stickyBar?.remove();
        return;
    }

    // 1. Intersection Observer: Docking Logic
    // Watches the INLINE button. If it's not visible, show the sticky bar.
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Show sticky bar only if the main button is NOT in the viewport
            stickyBar.classList.toggle('is-visible', !entry.isIntersecting);
        });
    }, { 
        threshold: 0,
        rootMargin: '0px 0px -10px 0px' // Offset slightly to ensure smooth transition
    });

    observer.observe(mainBtn);

    // 2. Click Proxy
    stickyBtn?.addEventListener('click', () => {
        mainBtn.click();
    });

    // 3. State Syncing
    const syncStates = () => {
        if (mainBtn && stickyBtn) {
            stickyBtn.disabled = mainBtn.disabled;
            if (mainBtn.innerHTML.includes('loading-spinner')) {
                stickyBtn.innerHTML = mainBtn.innerHTML;
            } else {
                // If main button has an SVG (Book Now state), we just want the text "Book Now" for sticky
                // or we can just sync the textContent
                stickyBtn.textContent = mainBtn.textContent;
            }
        }
        if (mainPriceText && stickyPriceText) {
            stickyPriceText.textContent = mainPriceText.textContent;
        }
    };

    const syncObserver = new MutationObserver(syncStates);
    syncObserver.observe(mainBtn, { attributes: true, attributeFilter: ['disabled', 'class'], childList: true });
    if (mainPriceText) syncObserver.observe(mainPriceText, { childList: true, characterData: true, subtree: true });

    syncStates();
})();

// Global initialization
document.addEventListener('DOMContentLoaded', () => {
    const eqId = new URLSearchParams(window.location.search).get('id');
    if (eqId && document.getElementById('calGrid')) {
        window.bookingCal = new BookingCalendar('calGrid', eqId);
    }
});
