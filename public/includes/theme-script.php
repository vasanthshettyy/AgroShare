<?php
/**
 * theme-script.php — Initial theme application and maintenance monitor.
 * Included in the <head> of pages to ensure theme persistence and real-time status.
 */
require_once __DIR__ . '/../../src/Helpers/auth.php'; // For getBasePath()
?>
<script>
    (function(){
        // ── 0. Global Configuration ──
        window.AgroShare = {
            apiUrl: '<?= SITE_URL ?>/public/api',
            adminApiUrl: '<?= SITE_URL ?>/public/admin/api'
        };

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

        // ── 3. Global Back To Top Component ──
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.createElement('button');
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>';
            btn.setAttribute('aria-label', 'Back to top');
            btn.title = 'Back to top';
            Object.assign(btn.style, {
                position: 'fixed',
                bottom: '30px',
                right: '30px',
                width: '45px',
                height: '45px',
                borderRadius: '50%',
                backgroundColor: '#10b981', // AgroShare primary action approx
                color: '#fff',
                border: 'none',
                boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                opacity: '0',
                visibility: 'hidden',
                transform: 'translateY(15px)',
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                zIndex: '9999'
            });

            document.body.appendChild(btn);

            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    btn.style.opacity = '1';
                    btn.style.visibility = 'visible';
                    btn.style.transform = 'translateY(0)';
                } else {
                    btn.style.opacity = '0';
                    btn.style.visibility = 'hidden';
                    btn.style.transform = 'translateY(15px)';
                }
            });

            btn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-3px)';
                btn.style.boxShadow = '0 6px 16px rgba(0,0,0,0.4)';
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translateY(0)';
                btn.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
            });
        });

    })();
</script>
<script src="<?= getBasePath() ?>/public/assets/js/validator.js" defer></script>
