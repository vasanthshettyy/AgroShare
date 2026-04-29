/* ── Review Modal ─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reviewModal');
    const bookingIdInput = document.getElementById('review-booking-id');
    const cancelBtn = document.getElementById('reviewCancelBtn');
    const closeXBtn = document.getElementById('reviewModalCloseBtn');
    const submitBtn = document.getElementById('reviewSubmitBtn');
    
    // Liquid Star Gradient Elements
    const starsWrapper = document.getElementById('liquid-stars-container');
    const activeStop = document.getElementById('grad-stop-active');
    const ghostStop = document.getElementById('grad-stop-ghost');
    
    const ratingInput = document.getElementById('review-rating');
    const manualRatingInput = document.getElementById('manual-rating-input');
    const trustTextDisplay = document.getElementById('trust-text-display');
    const trustPreviewBox = document.getElementById('trust-preview');
    
    const commentTextarea = document.getElementById('review-comment');
    const charCountDisplay = document.getElementById('char-count');
    const tagBtns = document.querySelectorAll('.review-tag');

    let isLocked = false;
    let lockTimeout = null;

    if (!modal) {
        console.error('Review modal not found in DOM.');
        return;
    }

    // --- Liquid Vector Fill Engine ---
    function updateFill(rating) {
        if (!activeStop || !ghostStop) return;
        const percent = (rating / 5) * 100;
        activeStop.setAttribute('offset', `${percent}%`);
        ghostStop.setAttribute('offset', `${percent}%`);
    }

    function updateTrustPreview(rating, updateInput = true) {
        const r = parseFloat(rating) || 0;
        
        if (updateInput && document.activeElement !== manualRatingInput) {
            manualRatingInput.value = r > 0 ? r.toFixed(1) : '';
        }
        
        let label = 'Rate It';
        if (r >= 4.5) label = 'Excellent';
        else if (r >= 3.5) label = 'Great';
        else if (r >= 2.5) label = 'Good';
        else if (r >= 1.5) label = 'Fair';
        else if (r > 0) label = 'Poor';
        
        trustTextDisplay.textContent = label;
        
        if (r > 0) {
            trustPreviewBox.classList.add('highlight');
        } else {
            trustPreviewBox.classList.remove('highlight');
        }
    }

    starsWrapper.addEventListener('mousemove', (e) => {
        if (isLocked) return;
        const rect = starsWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const width = rect.width;
        let rating = (x / width) * 5;
        rating = Math.max(0, Math.min(5, rating));
        updateFill(rating);
        updateTrustPreview(rating);
    });

    starsWrapper.addEventListener('mouseleave', () => {
        isLocked = false;
        if (lockTimeout) clearTimeout(lockTimeout);
        const savedRating = parseFloat(ratingInput.value || 0);
        updateFill(savedRating);
        updateTrustPreview(savedRating);
    });

    starsWrapper.addEventListener('click', (e) => {
        const rect = starsWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const width = rect.width;
        let rating = (x / width) * 5;
        rating = Math.round(rating * 2) / 2;
        rating = Math.max(0.5, Math.min(5, rating));
        ratingInput.value = rating;
        updateFill(rating);
        updateTrustPreview(rating);
        isLocked = true;
        if (lockTimeout) clearTimeout(lockTimeout);
        lockTimeout = setTimeout(() => { isLocked = false; }, 800);
        starsWrapper.classList.remove('star-pop-anim');
        void starsWrapper.offsetWidth; 
        starsWrapper.classList.add('star-pop-anim');
    });

    manualRatingInput.addEventListener('input', () => {
        let val = parseFloat(manualRatingInput.value);
        if (isNaN(val)) val = 0;
        if (val > 5) { val = 5; manualRatingInput.value = 5; }
        if (val < 0) { val = 0; manualRatingInput.value = 0; }
        updateFill(val);
        updateTrustPreview(val, false);
        ratingInput.value = val;
    });

    manualRatingInput.addEventListener('blur', () => {
        let val = parseFloat(manualRatingInput.value);
        if (isNaN(val) || val <= 0) {
            resetModalForm();
            return;
        }
        val = Math.round(val * 2) / 2;
        val = Math.max(0.5, Math.min(5, val));
        manualRatingInput.value = val.toFixed(1);
        ratingInput.value = val;
        updateFill(val);
        updateTrustPreview(val);
    });

    if (commentTextarea) {
        commentTextarea.addEventListener('input', () => {
            const len = commentTextarea.value.length;
            charCountDisplay.textContent = len;
            charCountDisplay.style.color = len >= 500 ? '#ef4444' : 'var(--text-subtle)';
        });
    }

    tagBtns.forEach(tag => {
        tag.addEventListener('click', () => {
            tag.classList.toggle('active');
            const tagName = tag.textContent.trim();
            let currentComment = commentTextarea.value;
            if (tag.classList.contains('active')) {
                if (!currentComment.toLowerCase().includes(tagName.toLowerCase())) {
                    const separator = currentComment.length > 0 ? (currentComment.trim().endsWith('.') ? ' ' : '. ') : '';
                    commentTextarea.value = currentComment.trim() + separator + tagName + ". ";
                }
            } else {
                const regex = new RegExp(`,?\\s*${tagName}\\.?\\s*`, 'gi');
                commentTextarea.value = currentComment.replace(regex, ' ').trim();
            }
            commentTextarea.dispatchEvent(new Event('input'));
        });
    });

    const resetModalForm = () => {
        if (ratingInput) ratingInput.value = '0';
        if (manualRatingInput) manualRatingInput.value = '';
        if (commentTextarea) commentTextarea.value = '';
        if (charCountDisplay) charCountDisplay.textContent = '0';
        updateFill(0);
        updateTrustPreview(0);
        isLocked = false;
        if (lockTimeout) clearTimeout(lockTimeout);
        tagBtns.forEach(t => t.classList.remove('active'));
        starsWrapper.classList.remove('star-pop-anim');
    };

    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-review-booking]');
        if (btn) {
            e.preventDefault();
            bookingIdInput.value = btn.dataset.reviewBooking;
            resetModalForm();
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show-modal'), 10);
            document.body.style.overflow = 'hidden';
        }
    });

    const closeModal = () => {
        modal.classList.remove('show-modal');
        setTimeout(() => modal.style.display = 'none', 400);
        document.body.style.overflow = '';
    };

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (closeXBtn) closeXBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const rating = parseFloat(ratingInput.value);
            const comment = commentTextarea.value.trim();
            const bookingId = bookingIdInput.value;
            if (rating <= 0) { alert('Please select a star rating.'); return; }
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('rating', rating);
            formData.append('comment', comment);
            const csrfEl = document.getElementById('csrf_token') || document.querySelector('input[name="csrf_token"]');
            if (csrfEl) formData.append('csrf_token', csrfEl.value);
            try {
                const res = await fetch('api/submit-review.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeModal();
                    alert(data.message || 'Review submitted successfully!');
                    window.location.reload();
                } else { alert(data.message || 'Error submitting review.'); }
            } catch (err) { console.error('Submission error:', err); alert('Network error.'); }
            finally { submitBtn.disabled = false; submitBtn.textContent = originalText; }
        });
    }
});

/* ── Public Profile Modal Logic ───────────────────────────── */

/**
 * showUserReviews — Now acts as the Public Profile trigger.
 */
async function showUserReviews(userId) {
    const modal = document.getElementById('userPublicProfileModal');
    if (!modal) return;

    // Reset/Loading state
    const reviewsContainer = document.getElementById('pub-reviews-container');
    reviewsContainer.innerHTML = '<div class="notif-empty"><div class="loading-spinner" style="border-top-color:var(--primary-action);"></div><p style="margin-top:10px;">Loading profile...</p></div>';
    document.getElementById('pub-verified-badge').style.display = 'none';

    // Show modal
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show-modal'), 10);
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`api/get_user_public_profile.php?user_id=${userId}`);
        const result = await response.json();

        if (!result.success) {
            reviewsContainer.innerHTML = `<div class="notif-empty">${result.message}</div>`;
            return;
        }

        const user = result.data;

        // Populate Static Fields
        document.getElementById('pub-avatar').textContent = user.initials;
        document.getElementById('pub-name').textContent = user.name;
        document.getElementById('pub-location-text').textContent = user.location;
        document.getElementById('pub-trust-val').textContent = user.trust_score.toFixed(1);
        document.getElementById('pub-deals-val').textContent = user.total_deals;
        document.getElementById('pub-joined-val').textContent = user.joined;
        
        document.getElementById('pub-phone-text').textContent = user.phone;
        const pubCallBtn = document.getElementById('pub-call-btn');
        if (pubCallBtn) {
            pubCallBtn.href = `tel:${user.phone}`;
            pubCallBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> ${user.phone}`;
        }
        document.getElementById('pub-email-text').textContent = user.email;
        document.getElementById('pub-email-btn').href = `mailto:${user.email}`;

        if (user.is_verified) {
            document.getElementById('pub-verified-badge').style.display = 'block';
        }

        // Render Recent Reviews
        if (user.recent_reviews.length === 0) {
            reviewsContainer.innerHTML = '<div class="notif-empty">No community feedback yet.</div>';
        } else {
            let revHtml = '';
            user.recent_reviews.forEach(rev => {
                revHtml += `
                    <div class="pub-review-mini">
                        <div class="prm-header">
                            <span class="prm-name">${rev.name}</span>
                            <span class="prm-date">${rev.date}</span>
                        </div>
                        <div class="prm-stars" style="margin-bottom:8px;">
                            ${renderStarRating(rev.rating)}
                        </div>
                        <p class="prm-comment">${rev.comment || '<em>No comment provided.</em>'}</p>
                    </div>
                `;
            });
            reviewsContainer.innerHTML = revHtml;
        }

    } catch (err) {
        console.error('Profile Load Error:', err);
        reviewsContainer.innerHTML = '<div class="notif-empty">Error loading profile.</div>';
    }
}

function closePublicProfile() {
    const modal = document.getElementById('userPublicProfileModal');
    if (!modal) return;
    modal.classList.remove('show-modal');
    setTimeout(() => modal.style.display = 'none', 400);
    document.body.style.overflow = '';
}

document.getElementById('closePublicProfile')?.addEventListener('click', closePublicProfile);

/* ── Booking Detail Modal Logic ───────────────────────────── */

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-booking-details');
    if (!btn) return;
    const modal = document.getElementById('bookingDetailModal');
    const data = JSON.parse(btn.dataset.booking);
    if (!modal) return;
    document.getElementById('bd-title').textContent = data.title;
    document.getElementById('bd-id-tag').textContent = `Order #AS-${data.id.toString().padStart(5, '0')}`;
    document.getElementById('bd-hero-img').src = data.image;
    document.getElementById('bd-start-date').textContent = data.start;
    document.getElementById('bd-end-date').textContent = data.end;
    document.getElementById('bd-rental-fee').textContent = `₹${new Intl.NumberFormat('en-IN').format(data.rental_fee)}`;
    document.getElementById('bd-deposit').textContent = `₹${new Intl.NumberFormat('en-IN').format(data.deposit)}`;
    document.getElementById('bd-total-price').textContent = `₹${new Intl.NumberFormat('en-IN').format(data.total)}`;
    document.getElementById('bd-party-type').textContent = `${data.party_type} Info`;
    document.getElementById('bd-party-name').textContent = data.party_name;
    document.getElementById('bd-party-avatar').textContent = data.party_name.charAt(0).toUpperCase();
    document.getElementById('bd-party-trust').textContent = parseFloat(data.party_trust).toFixed(1);
    const callBtn = document.getElementById('bd-call-btn');
    if (callBtn) {
        callBtn.href = `tel:${data.party_phone}`;
        callBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> ${data.party_phone}`;
    }
    const statusEl = document.getElementById('bd-status-badge');
    statusEl.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
    statusEl.className = `status-badge status-${data.status}`;
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show-modal'), 10);
    document.body.style.overflow = 'hidden';
});

function closeDetailModal() {
    const modal = document.getElementById('bookingDetailModal');
    if (!modal) return;
    modal.classList.remove('show-modal');
    setTimeout(() => modal.style.display = 'none', 400);
    document.body.style.overflow = '';
}
document.getElementById('closeBookingModal')?.addEventListener('click', closeDetailModal);

/**
 * renderStarRating — Helper to generate star HTML based on numeric rating.
 */
function renderStarRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (rating >= i) {
            stars += '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        } else if (rating >= i - 0.5) {
            stars += '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4V6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';
        } else {
            stars += '<svg width="14" height="14" viewBox="0 0 24 24" fill="rgba(255,255,255,0.1)"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4V6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z\"/></svg>';
        }
    }
    return stars;
}
