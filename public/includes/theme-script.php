<?php
/**
 * theme-script.php — Initial theme application and maintenance monitor.
 * Included in the <head> of pages to ensure theme persistence and real-time status.
 */
require_once __DIR__ . '/../../src/Helpers/auth.php'; // For getBasePath()
?>
<script>
    (function(){
        // ── 1. Theme Initialization ──
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        if (!localStorage.getItem('theme')) {
            const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const setTheme = (e) => document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            darkQuery.addEventListener('change', setTheme);
        }

        // ── 2. Real-time Maintenance Monitor ──
        // Checks status every 60 seconds and redirects active users if maintenance is enabled.
        const basePath = '<?= getBasePath() ?>';
        const isMaintenancePage = window.location.pathname.includes('maintenance.php');
        const isAdminPage = window.location.pathname.includes('/admin/');

        if (!isMaintenancePage && !isAdminPage) {
            const checkMaintenance = async () => {
                try {
                    const res = await fetch(basePath + '/public/api/maintenance-check.php');
                    const data = await res.json();
                    if (data.maintenance === true) {
                        window.location.href = basePath + '/public/maintenance.php';
                    }
                } catch (e) { /* Fail silently */ }
            };
            
            // Poll every 60 seconds (starting 10s after load)
            setTimeout(() => {
                checkMaintenance();
                setInterval(checkMaintenance, 60000);
            }, 10000);
        }
    })();
</script>
