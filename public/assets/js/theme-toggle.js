/**
 * theme-toggle.js — Handles switching between Light/Dark mode and persists it.
 * Works independently of dashboard.js to support auth and admin pages.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const themeBtn = document.getElementById('themeToggleBtn');

    if (!themeBtn) return;

    themeBtn.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Animate the button with a spin
        themeBtn.style.transform = 'scale(0.8) rotate(180deg)';
        setTimeout(() => {
            themeBtn.style.transform = '';
        }, 400);

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        // Dispatch event for other components (like charts) to react
        window.dispatchEvent(new Event('themeChanged'));
    });
});
