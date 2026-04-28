<?php
/**
 * theme-script.php — Initial theme application to prevent FOUC.
 * Included in the <head> of every page.
 */
?>
<script>
    (function(){
        // Check for saved theme in localStorage, default to 'dark'
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Listen for system theme changes if no user preference
        if (!localStorage.getItem('theme')) {
            const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const setTheme = (e) => document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            darkQuery.addEventListener('change', setTheme);
        }
    })();
</script>
