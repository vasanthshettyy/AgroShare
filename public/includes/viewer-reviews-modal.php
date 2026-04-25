<!-- Viewer Reviews Modal -->
<div id="userReviewsModal" class="modal-overlay" style="display: none;">
    <div class="modal-content profile-modal-content" style="max-width: 600px; width: 90%; padding: 2.5rem;">
        <button id="closeReviewsModal" class="modal-close-x" aria-label="Close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div class="modal-header-section" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 class="premium-title" id="reviewsModalTitle">User Reviews</h2>
                    <p class="premium-subtitle" id="reviewsModalSubtitle">History of ratings and feedback from the community.</p>
                </div>
                <div id="modalTrustScoreContainer" class="trust-score-badge" style="background: var(--primary-10); padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid var(--primary-action);">
                    <span style="display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Trust Score</span>
                    <span id="modalTrustScoreValue" style="font-size: 1.25rem; font-weight: 800; color: var(--primary-action);">0.0</span>
                </div>
            </div>
        </div>

        <div id="reviewsListContainer" class="custom-scrollbar" style="max-height: 450px; overflow-y: auto; padding-right: 10px;">
            <!-- Reviews will be injected here via AJAX -->
            <div class="notif-empty">Loading reviews...</div>
        </div>
    </div>
</div>

<style>
    .trust-score-badge {
        text-align: center;
        min-width: 100px;
    }
    
    .review-card-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: transform 0.2s;
    }
    
    .review-card-item:hover {
        transform: translateX(4px);
        background: rgba(255, 255, 255, 0.05);
    }

    .review-card-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .reviewer-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .reviewer-name {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-main);
    }

    .review-date {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .review-rating-stars {
        color: #fbbf24;
        display: flex;
        gap: 2px;
    }

    .review-comment-text {
        font-size: 0.875rem;
        line-height: 1.5;
        color: var(--text-muted);
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: var(--text-subtle);
    }
</style>
