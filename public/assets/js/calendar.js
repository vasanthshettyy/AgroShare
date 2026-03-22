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
        this.today.setHours(0,0,0,0);
        
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
        const originalContent = btnBook.innerHTML;
        btnBook.disabled = true;
        btnBook.innerHTML = '<span class="loading-spinner"></span> Processing...';

        try {
            const formData = new FormData();
            formData.append('equipment_id', this.equipmentId);
            formData.append('start_datetime', document.getElementById('est-start').value);
            formData.append('end_datetime', document.getElementById('est-end').value);
            
            // Get CSRF token from the delete form or any other place it exists
            const csrf = document.querySelector('input[name="csrf_token"]')?.value;
            formData.append('csrf_token', csrf);

            const res = await fetch('api/create-booking.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                if (window.showToast) window.showToast('success', data.message);
                // Clear selection
                this.selectedStart = null;
                this.selectedEnd = null;
                this.updateInputs();
                await this.fetchBookedSlots(); // Refresh booked slots
                this.render();
            } else {
                if (window.showToast) window.showToast('error', data.message);
            }
        } catch (err) {
            console.error('Booking error:', err);
            if (window.showToast) window.showToast('error', 'Network error. Please try again.');
        } finally {
            btnBook.disabled = false;
            btnBook.innerHTML = originalContent;
        }
    }

    changeMonth(delta) {
        this.viewDate.setMonth(this.viewDate.getMonth() + delta);
        this.render();
    }

    isDateBooked(date) {
        return this.bookedRanges.some(range => {
            const start = new Date(range.start);
            start.setHours(0,0,0,0);
            const end = new Date(range.end);
            end.setHours(23,59,59,999);
            return date >= start && date <= end;
        });
    }

    hasOverlap(start, end) {
        return this.bookedRanges.some(range => {
            const bStart = new Date(range.start);
            bStart.setHours(0,0,0,0);
            const bEnd = new Date(range.end);
            bEnd.setHours(23,59,59,999);
            
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
            date.setHours(0,0,0,0);

            const dayEl = document.createElement('div');
            dayEl.className = 'calendar-day';
            dayEl.textContent = day;

            if (date < this.today) {
                dayEl.classList.add('disabled');
            } else if (this.isDateBooked(date)) {
                dayEl.classList.add('booked');
            } else {
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
            } else if (date.getTime() === this.selectedStart.getTime()) {
                this.selectedStart = null; // Toggle off if clicked same date
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

        if (this.selectedStart && this.selectedEnd) {
            // Range selected
            startInput.value = this.selectedStart.toISOString().split('T')[0] + 'T09:00';
            endInput.value   = this.selectedEnd.toISOString().split('T')[0] + 'T18:00';
            btnBook?.removeAttribute('disabled');
            
            // Availability hint
            const diffDays = Math.ceil((this.selectedEnd - this.selectedStart) / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('calHint').style.display = 'block';
            document.getElementById('calHintText').innerHTML = `Selected <span class="avail-highlight">${diffDays} days</span>.`;
        } else if (this.selectedStart) {
            // Only start selected
            startInput.value = this.selectedStart.toISOString().split('T')[0] + 'T09:00';
            endInput.value   = '';
            btnBook?.setAttribute('disabled', 'true');
            
            const nextBooked = this.getNextBookedDate(this.selectedStart);
            const avail = nextBooked ? Math.floor((nextBooked - this.selectedStart) / (86400000)) : 30;
            
            document.getElementById('calHint').style.display = 'block';
            document.getElementById('calHintText').innerHTML = `Available for up to <span class="avail-highlight">${avail} consecutive days</span>.`;
        } else {
            startInput.value = '';
            endInput.value = '';
            btnBook?.setAttribute('disabled', 'true');
            document.getElementById('calHint').style.display = 'none';
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

// Global initialization
document.addEventListener('DOMContentLoaded', () => {
    const eqId = new URLSearchParams(window.location.search).get('id');
    if (eqId && document.getElementById('calGrid')) {
        window.bookingCal = new BookingCalendar('calGrid', eqId);
    }
});
