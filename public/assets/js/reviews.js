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
    /**
     * updateFill — Updates the SVG gradient offsets using percentages.
     * Even with userSpaceOnUse, 'offset' must be 0-1 or 0-100%.
     */
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
        
        // Round to nearest 0.5
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

    // --- Manual Input Sync Logic ---
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

    // --- Char Count Logic ---
    if (commentTextarea) {
        commentTextarea.addEventListener('input', () => {
            const len = commentTextarea.value.length;
            charCountDisplay.textContent = len;
            charCountDisplay.style.color = len >= 500 ? '#ef4444' : 'var(--text-subtle)';
        });
    }

    // --- Tag Selection Logic ---
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

    // --- Modal Control Logic ---
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
            setTimeout(() => {
                modal.classList.add('show-modal');
            }, 10);
            
            document.body.style.overflow = 'hidden';
        }
    });

    const closeModal = () => {
        modal.classList.remove('show-modal');
        setTimeout(() => {
            modal.style.display = 'none';
            resetModalForm();
        }, 400);
        document.body.style.overflow = '';
    };

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (closeXBtn) closeXBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // --- Submit Logic ---
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const rating = parseFloat(ratingInput.value);
            const comment = commentTextarea.value.trim();
            const bookingId = bookingIdInput.value;

            if (rating <= 0) { 
                alert('Please select a star rating.'); 
                return; 
            }

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
                } else {
                    alert(data.message || 'Error submitting review.');
                }
            } catch (err) {
                console.error('Submission error:', err);
                alert('Network error. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

/* ── Review Viewer Modal Logic ───────────────────────────── */

async function showUserReviews(userId) {
    const modal = document.getElementById('userReviewsModal');
    const container = document.getElementById('reviewsListContainer');
    const trustScoreVal = document.getElementById('modalTrustScoreValue');
    const modalTitle = document.getElementById('reviewsModalTitle');
    const closeBtn = document.getElementById('closeReviewsModal');

    if (!modal || !container) return;

    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show-modal'), 10);
    document.body.style.overflow = 'hidden';
    container.innerHTML = '<div class="notif-empty"><div class="loading-spinner" style="border-top-color: var(--primary-action);"></div><p style="margin-top:10px;">Fetching reviews...</p></div>';

    const closeModal = () => {
        modal.classList.remove('show-modal');
        setTimeout(() => modal.style.display = 'none', 400);
        document.body.style.overflow = '';
    };
    closeBtn.onclick = closeModal;
    modal.onclick = (e) => { if (e.target === modal) closeModal(); };

    try {
        const response = await fetch(`api/get_user_reviews.php?user_id=${userId}`);
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = `<div class="notif-empty">${data.message || 'Could not load reviews.'}</div>`;
            return;
        }

        modalTitle.textContent = `${data.user_name}'s Reviews`;
        trustScoreVal.textContent = data.trust_score.toFixed(1);

        if (data.reviews.length === 0) {
            container.innerHTML = '<div class="notif-empty">No reviews yet for this user.</div>';
            return;
        }

        let html = '';
        data.reviews.forEach(rev => {
            html += `
                <div class="review-card-item">
                    <div class="review-card-header">
                        <div class="reviewer-info">
                            <div class="reviewer-name">${rev.reviewer_name}</div>
                            <div class="review-date">${rev.date}</div>
                        </div>
                        <div class="review-rating-stars">
                            ${renderStarRating(rev.rating)}
                        </div>
                    </div>
                    <div class="review-comment-text">
                        ${rev.comment ? rev.comment : '<em style="color:var(--text-subtle);">No comment provided.</em>'}
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;

    } catch (err) {
        console.error('Error fetching reviews:', err);
        container.innerHTML = '<div class="notif-empty">Network error. Please try again.</div>';
    }
}

function renderStarRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (rating >= i) {
            stars += '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        } else if (rating >= i - 0.5) {
            stars += '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4V6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';
        } else {
            stars += '<svg width="14" height="14" viewBox="0 0 24 24" fill="rgba(255,255,255,0.1)"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4V6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>';
        }
    }
    return stars;
}
