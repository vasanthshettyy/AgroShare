/**
 * theme-toggle.js — Handles switching between Light/Dark mode with modern transitions.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const themeBtn = document.getElementById('themeToggleBtn');
    if (!themeBtn) return;

    const toggleTheme = () => {
        const root = document.documentElement;
        const currentTheme = root.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        root.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Dispatch event for other components (like charts) to react
        window.dispatchEvent(new Event('themeChanged'));
    };

    themeBtn.addEventListener('click', (e) => {
        // Add a temporary class to enable CSS transitions for theme change
        document.documentElement.classList.add('theme-transitioning');

        // Check for View Transition API support (Chrome 111+)
        if (!document.startViewTransition) {
            toggleTheme();
            setTimeout(() => {
                document.documentElement.classList.remove('theme-transitioning');
            }, 500);
        } else {
            // Cinematic transition
            const transition = document.startViewTransition(() => toggleTheme());
            transition.finished.finally(() => {
                document.documentElement.classList.remove('theme-transitioning');
            });
        }
    });
});
