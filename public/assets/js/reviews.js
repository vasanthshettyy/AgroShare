/* ── Review Modal ─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reviewModal');
    const bookingIdInput = document.getElementById('review-booking-id');
    const cancelBtn = document.getElementById('reviewCancelBtn');
    const closeXBtn = document.getElementById('reviewModalCloseBtn');
    const submitBtn = document.getElementById('reviewSubmitBtn');
    
    // Liquid Stars Elements
    const starsWrapper = document.getElementById('liquid-stars-container');
    const ratingInput = document.getElementById('selected-rating');
    
    const commentTextarea = document.getElementById('review-comment');
    const tagBtns = document.querySelectorAll('.review-tag');

    let isLocked = false;

    if (!modal) {
        console.error('Review modal not found in DOM.');
        return;
    }

    // --- Magnetic Hover & Half-Star Locking Logic ---
    function updateFill(percent) {
        starsWrapper.style.setProperty('--star-fill-width', `${percent}%`);
    }

    starsWrapper.addEventListener('mousemove', (e) => {
        // If user just clicked, don't let the cursor "drag" the fill until they move out/in
        if (isLocked) return;

        const rect = starsWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const width = rect.width;
        let percent = (x / width) * 100;
        percent = Math.max(0, Math.min(100, percent));
        updateFill(percent);
    });

    starsWrapper.addEventListener('mouseleave', () => {
        isLocked = false; // Reset lock on leave so they can re-preview when they come back
        const savedRating = parseFloat(ratingInput.value || 0);
        const percent = (savedRating / 5) * 100;
        updateFill(percent);
    });

    starsWrapper.addEventListener('mouseenter', () => {
        // Just in case, ensure lock is off when entering
        isLocked = false;
    });

    starsWrapper.addEventListener('click', (e) => {
        const rect = starsWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const width = rect.width;
        let rating = (x / width) * 5;
        
        // Round to nearest 0.5 (Half-Star Locking)
        rating = Math.round(rating * 2) / 2;
        rating = Math.max(0.5, Math.min(5, rating));
        
        ratingInput.value = rating;
        const finalPercent = (rating / 5) * 100;
        updateFill(finalPercent);
        
        // Lock the visual state so it doesn't follow the cursor anymore
        isLocked = true;
        
        // Pop & Glow Animation
        starsWrapper.classList.remove('star-pop-anim');
        void starsWrapper.offsetWidth; // Trigger reflow
        starsWrapper.classList.add('star-pop-anim');
        
        console.log('Rating locked at:', rating);
    });

    // --- Tag Selection Logic ---
    tagBtns.forEach(tag => {
        tag.addEventListener('click', () => {
            tag.classList.toggle('active');
            
            const tagName = tag.textContent.trim();
            let currentComment = commentTextarea.value;
            
            if (tag.classList.contains('active')) {
                if (!currentComment.toLowerCase().includes(tagName.toLowerCase())) {
                    commentTextarea.value = currentComment + (currentComment ? ', ' : '') + tagName;
                }
            } else {
                const regex = new RegExp(`,?\\s*${tagName}`, 'gi');
                commentTextarea.value = currentComment.replace(regex, '').replace(/^,\s*/, '').trim();
            }
        });
    });

    // --- Modal Control Logic ---
    const resetModalForm = () => {
        ratingInput.value = '0';
        commentTextarea.value = '';
        updateFill(0);
        isLocked = false;
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
