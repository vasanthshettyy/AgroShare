/**
 * realtime.js — Global AJAX Polling Engine for AgroShare.
 * This centralized engine handles background fetching for the dashboard and equipment browsing.
 */

const RealtimeEngine = (function() {
    let pollingInterval = null;
    let isFetching = false;
    const POLLING_RATE = 5000; // 5 seconds

    /**
     * Silent Fetcher: Calls the API and triggers the callback on success.
     * Silently ignores errors to avoid polluting the user console or UI.
     */
    async function fetchData(endpoint, callback) {
        if (isFetching || document.hidden) return;
        
        isFetching = true;
        try {
            const response = await fetch(endpoint, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const json = await response.json();
            if (json.success && typeof callback === 'function') {
                callback(json);
            }
        } catch (error) {
            // Silently catch errors as per requirement
        } finally {
            isFetching = false;
        }
    }

    /**
     * DOM Manipulation Logic for Dashboard KPIs and Activity.
     * (Phase 3 Target: Surgical updates)
     */
    function updateDashboardDOM(data) {
        if (!data.kpis) return;

        // 1. Update KPI numbers surgically
        const mapping = {
            'kpi-total-equipment': data.kpis.total_equipment,
            'kpi-active-rentals': data.kpis.active_rentals,
            'kpi-total-earnings': '₹' + parseInt(data.kpis.total_earnings).toLocaleString(),
            'kpi-trust-score': parseFloat(data.kpis.trust_score).toFixed(1)
        };

        for (const [id, value] of Object.entries(mapping)) {
            const el = document.getElementById(id);
            if (el) {
                const newValue = String(value);
                if (el.textContent.trim() !== newValue) {
                    el.classList.remove('stat-update-pulse');
                    void el.offsetWidth; // Trigger reflow for animation
                    el.classList.add('stat-update-pulse');
                    el.textContent = newValue;
                }
            }
        }

        // 2. Update Recent Activity Table
        const tbody = document.getElementById('dashboard-activity-body');
        if (tbody && data.recent_activity) {
            if (data.recent_activity.length === 0) return;

            let html = '';
            data.recent_activity.forEach(act => {
                const date = new Date(act.created_at);
                const month = date.toLocaleString('default', { month: 'short' });
                const day = date.getDate();
                
                html += `
                    <tr>
                        <td><strong>${escapeHtml(act.equipment_title)}</strong></td>
                        <td><span class="activity-badge ${act.activity_type.toLowerCase()}">${act.activity_type}</span></td>
                        <td style="font-size: 0.8rem; color: var(--text-muted);">${month} ${day}</td>
                        <td><span class="status-pill ${act.status.toLowerCase()}">${act.status.charAt(0).toUpperCase() + act.status.slice(1)}</span></td>
                    </tr>
                `;
            });
            
            if (tbody.innerHTML.trim() !== html.trim()) {
                tbody.innerHTML = html;
            }
        }

        // 3. Update Chart Data Attribute
        const chartArea = document.getElementById('chart-area');
        if (chartArea && data.trend) {
            const trendStr = data.trend.join(',');
            if (chartArea.getAttribute('data-values') !== trendStr) {
                chartArea.setAttribute('data-values', trendStr);
                // Hook for re-rendering if chart library is present
                if (typeof window.renderDashboardChart === 'function') window.renderDashboardChart();
                else if (typeof window.renderAreaChart === 'function') window.renderAreaChart('chart-area');
            }
        }
    }

    /**
     * DOM Manipulation Logic for Equipment Grid.
     * (Phase 3 Target)
     */
    function updateEquipmentGrid(data) {
        const grid = document.getElementById('equipment-grid');
        if (!grid || !data.data) return;

        let html = '';
        data.data.forEach((eq, index) => {
            const images = eq.images ? JSON.parse(eq.images) : [];
            const thumbnail = images.length > 0 ? escapeHtml(images[0]) : '';
            const isOwner = eq.owner_id == window.currentUserId;
            const detailPage = isOwner ? 'my-equipment-detail.php' : 'equipment-detail.php';
            const availabilityClass = eq.is_available == 1 ? 'available' : 'unavailable';
            const availabilityText = eq.is_available == 1 ? 'Listed' : 'Off-market';

            html += `
                <a href="${detailPage}?id=${eq.id}" class="eq-card" style="animation-delay: ${0.06 * index}s;">
                    <div class="eq-card-image">
                        ${thumbnail ? `<img src="${thumbnail}" alt="${escapeHtml(eq.title)}" loading="lazy">` : `
                        <div class="eq-card-placeholder">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                                <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                            </svg>
                        </div>`}
                        <span class="eq-card-badge ${availabilityClass}">${availabilityText}</span>
                    </div>
                    <div class="eq-card-body">
                        <span class="eq-card-category">${escapeHtml(eq.category.charAt(0).toUpperCase() + eq.category.slice(1))}</span>
                        <h3 class="eq-card-title">${escapeHtml(eq.title)}</h3>
                        <p class="eq-card-location">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            ${escapeHtml(eq.location_village)}, ${escapeHtml(eq.location_district)}
                        </p>
                        <div class="eq-card-pricing">
                            <span class="eq-card-price">₹${parseInt(eq.price_per_day).toLocaleString()}<small>/day</small></span>
                        </div>
                    </div>
                    <div class="eq-card-footer">
                        <div class="eq-card-owner">
                            ${eq.owner_photo ? `<img src="${escapeHtml(eq.owner_photo)}" alt="${escapeHtml(eq.owner_name)}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);">` : `
                            <div class="owner-initial-small" style="width: 24px; height: 24px; border-radius: 50%; background: var(--primary-10); color: var(--primary-action); display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; border: 1px solid var(--border-color);">
                                ${eq.owner_name.charAt(0).toUpperCase()}
                            </div>`}
                            <span class="eq-card-owner-name">${escapeHtml(eq.owner_name)}</span>
                        </div>
                        <div class="eq-card-footer-right">
                            ${eq.owner_trust > 0 ? `<span class="eq-card-trust">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                ${parseFloat(eq.owner_trust).toFixed(1)}
                            </span>` : ''}
                            ${eq.includes_operator == 1 ? `<span class="eq-card-operator-badge">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                                Operator
                            </span>` : ''}
                        </div>
                    </div>
                </a>
            `;
        });

        if (grid.innerHTML.trim() !== html.trim()) {
            grid.innerHTML = html;
        }
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    return {
        /**
         * Initialize polling for a specific page type.
         */
        init: function(pageType) {
            if (pollingInterval) clearInterval(pollingInterval);
            
            let endpoint = '';
            let callback = null;

            if (pageType === 'dashboard') {
                endpoint = 'api/get-realtime-dashboard.php';
                callback = updateDashboardDOM;
            } else if (pageType === 'equipment') {
                const params = new URLSearchParams(window.location.search);
                endpoint = `api/get-realtime-equipment.php?${params.toString()}`;
                callback = updateEquipmentGrid;
            }

            if (endpoint && callback) {
                // Initial fetch
                fetchData(endpoint, callback);
                // Set interval
                pollingInterval = setInterval(() => fetchData(endpoint, callback), POLLING_RATE);
            }
        },

        /**
         * Force an immediate refresh of data.
         */
        triggerUpdate: function(pageType) {
            let endpoint = '';
            let callback = null;

            if (pageType === 'dashboard') {
                endpoint = 'api/get-realtime-dashboard.php';
                callback = updateDashboardDOM;
            } else if (pageType === 'equipment') {
                const params = new URLSearchParams(window.location.search);
                endpoint = `api/get-realtime-equipment.php?${params.toString()}`;
                callback = updateEquipmentGrid;
            }

            if (endpoint && callback) {
                fetchData(endpoint, callback);
            }
        }
    };
})();

/**
 * Auto-initialize engine on page load.
 */
document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;
    if (path.includes('dashboard.php')) {
        RealtimeEngine.init('dashboard');
    } else if (path.includes('equipment-browse.php')) {
        RealtimeEngine.init('equipment');
    }
});
