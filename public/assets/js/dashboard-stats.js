/**
 * dashboard-stats.js — Periodically fetches KPI stats and updates the UI.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const statsConfig = [
        { id: 'kpi-total-equipment', key: 'totalEquipment' },
        { id: 'kpi-active-rentals', key: 'activeRentals' },
        { id: 'kpi-pool-count', key: 'poolCount' },
        { id: 'kpi-trust-score', key: 'trustScore' }
    ];

    /**
     * Fetch latest stats and update the DOM
     */
    async function updateStats() {
        try {
            const apiBase = (window.AgroShare && window.AgroShare.apiUrl) ? window.AgroShare.apiUrl : 'api';
            const response = await fetch(`${apiBase}/dashboard-stats.php`);
            const result = await response.json();

            if (result.success && result.data) {
                // 1. Update KPI numbers
                statsConfig.forEach(stat => {
                    const el = document.getElementById(stat.id);
                    if (!el) return;

                    const newValue = result.data[stat.key];
                    const currentValue = el.textContent.trim();

                    if (String(newValue) !== currentValue) {
                        el.classList.remove('stat-update-pulse');
                        void el.offsetWidth;
                        el.classList.add('stat-update-pulse');
                        el.textContent = newValue;
                    }
                });

                // 2. Update Chart if trend changed
                const chartWrap = document.getElementById('chart-area');
                if (chartWrap && result.data.chartData) {
                    const newTrend = result.data.chartData.join(',');
                    if (chartWrap.dataset.values !== newTrend) {
                        chartWrap.dataset.values = newTrend;
                        if (typeof window.renderAreaChart === 'function') {
                            window.renderAreaChart('chart-area');
                        }
                    }
                }

                // 3. Update Recent Activity Table
                const activityBody = document.getElementById('dashboard-activity-body');
                if (activityBody && result.data.recentActivity) {
                    const newActivity = result.data.recentActivity;
                    // Simple check: compare first item ID or length
                    const firstItem = activityBody.querySelector('tr strong');
                    const currentFirstTitle = firstItem ? firstItem.textContent.trim() : '';

                    if (newActivity.length > 0 && newActivity[0].equipment_title !== currentFirstTitle) {
                        renderActivityRows(activityBody, newActivity);
                    }
                }
            }
        } catch (error) {
            console.error('Failed to fetch dashboard stats:', error);
        } finally {
            // Update every 30 seconds, but ONLY after the previous one finishes
            setTimeout(updateStats, 30000);
        }
    }

    /**
     * Helper to escape HTML to prevent XSS
     */
    function escapeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Helper to render activity rows dynamically
     */
    function renderActivityRows(container, activities) {
        if (!activities || activities.length === 0) return;

        container.innerHTML = '';
        activities.forEach(act => {
            const row = document.createElement('tr');
            const date = new Date(act.created_at);
            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

            // Create title cell safely
            const titleTd = document.createElement('td');
            const titleStrong = document.createElement('strong');
            titleStrong.textContent = act.equipment_title;
            titleTd.appendChild(titleStrong);
            row.appendChild(titleTd);

            // Other cells can use innerHTML for simple badges/spans as they are status/type strings
            row.innerHTML += `
                <td><span class="activity-badge ${act.activity_type.toLowerCase()}">${act.activity_type}</span></td>
                <td style="font-size: 0.8rem; color: var(--text-muted);">${dateStr}</td>
                <td><span class="status-pill ${act.status.toLowerCase()}">${act.status.charAt(0).toUpperCase() + act.status.slice(1)}</span></td>
            `;
            container.appendChild(row);
        });
    }

    // Initial call to start the recursive loop
    updateStats();
});
