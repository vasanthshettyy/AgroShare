/**
 * theme-toggle.js — Handles switching between Light/Dark mode and persists it.
 * Works independently of dashboard.js to support auth and admin pages.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const themeBtn = document.getElementById('themeToggleBtn');

    if (!themeBtn) return;

    themeBtn.addEventListener('click', () => {
        const root = document.documentElement;
        const currentTheme = root.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Apply transition class for smooth overall blend
        root.classList.add('theme-transitioning');

        // Animate the button with a fluid spin
        themeBtn.style.transition = 'transform 0.5s var(--spring-ease)';
        themeBtn.style.transform = 'scale(0.85) rotate(180deg)';

        // Switch the theme attribute
        root.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        // Reset button and remove transition class after animation
        setTimeout(() => {
            themeBtn.style.transform = '';
            root.classList.remove('theme-transitioning');
        }, 500);

        // Dispatch event for other components (like charts) to react
        window.dispatchEvent(new Event('themeChanged'));
    });
});
