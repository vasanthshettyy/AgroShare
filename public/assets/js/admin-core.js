/**
 * admin-core.js — Handles module switching and AJAX view loading.
 */

document.addEventListener('DOMContentLoaded', () => {
    const navItems = document.querySelectorAll('.admin-nav-item[data-view]');
    const viewContainer = document.getElementById('main-view-content');
    const viewTitle = document.getElementById('view-title');

    // Function to load a view
    const loadView = async (viewName) => {
        const url = new URL(window.location);
        url.searchParams.set('view', viewName);
        window.history.pushState({}, '', url);

        navItems.forEach(item => {
            item.classList.toggle('active', item.dataset.view === viewName);
        });

        viewContainer.classList.add('loading');
        viewContainer.innerHTML = '<div class="admin-skeleton" style="height: 400px; width: 100%;"></div>';

        try {
            const response = await fetch(`api/view-loader.php?view=${viewName}`);
            if (!response.ok) throw new Error('Failed to load view');

            const html = await response.text();
            viewContainer.innerHTML = html;
            viewContainer.classList.remove('loading');

            initViewListeners(viewName);
        } catch (error) {
            console.error(error);
            viewContainer.innerHTML = `<div class="admin-card"><p style="color: var(--error-color);">Error loading ${viewName}. Please try again.</p></div>`;
        }
    };

    // Generic Action Handler
    const handleAction = async (endpoint, data, callback) => {
        try {
            const formData = new FormData();
            for (const key in data) formData.append(key, data[key]);

            // Add CSRF token if available in a global meta tag or hidden input
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf) formData.append('csrf_token', csrf);

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                if (callback) callback(result);
                // Refresh current view to show changes
                const params = new URLSearchParams(window.location.search);
                loadView(params.get('view') || 'overview');
            } else {
                alert(result.message || 'Action failed');
            }
        } catch (error) {
            console.error('Action error:', error);
            // If it redirected (HTML response), just reload
            window.location.reload();
        }
    };

    // Global Event Delegation for dynamic content
    viewContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;

        // User Actions
        if (btn.classList.contains('user-action-btn')) {
            const action = btn.dataset.action;
            const id = btn.dataset.id;
            if (action === 'toggle-verify') {
                handleAction('api/verify-user.php', { user_id: id, status: btn.title === 'Verify' ? 1 : 0 });
            } else if (action === 'toggle-active') {
                handleAction('api/toggle-user-active.php', { user_id: id });
            }
        }

        // Equipment Actions
        if (btn.classList.contains('eq-action-btn')) {
            const action = btn.dataset.action;
            const id = btn.dataset.id;
            if (action === 'toggle-featured') {
                handleAction('api/toggle-featured-equipment.php', { equipment_id: id });
            }
        }
    });

    // Nav click events
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const view = item.dataset.view;
            if (view) loadView(view);
        });
    });

    // Helper for module-specific JS
    const initViewListeners = (viewName) => {
        // e.g., setup chart listeners if overview, etc.
        console.log(`Initialized module: ${viewName}`);
    };

    // Initial load from URL
    const params = new URLSearchParams(window.location.search);
    const initialView = params.get('view') || 'overview';
    loadView(initialView);
});
