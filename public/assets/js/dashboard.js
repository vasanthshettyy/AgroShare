/**
 * AgroShare Dashboard — dashboard.js
 * Vanilla ES6+  |  No dependencies
 */
'use strict';

/* ── 1. Theme Toggle ──────────────────────────────────────────
   Reads saved pref from localStorage on load.
   Toggles   data-theme="dark"/"light" on <html>.
   ─────────────────────────────────────────────────────────── */
const html       = document.documentElement;
const themeBtn   = document.getElementById('theme-toggle');
const moonSvg    = document.getElementById('theme-moon');
const sunSvg     = document.getElementById('theme-sun');

function applyThemeIcons() {
    const isDark = html.getAttribute('data-theme') === 'dark' ||
                   (!html.hasAttribute('data-theme') &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (moonSvg) moonSvg.style.display = isDark ? 'none'  : 'block';
    if (sunSvg)  sunSvg.style.display  = isDark ? 'block' : 'none';
}

// Initialise icons on load
applyThemeIcons();

if (themeBtn) {
    themeBtn.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        applyThemeIcons();
    });
}

/* ── 2. Sidebar Toggle (mobile) ───────────────────────────────
   Hamburger opens the drawer.
   Clicking the overlay (backdrop) closes it.
   ─────────────────────────────────────────────────────────── */
const sidebar     = document.getElementById('sidebar');
const overlay     = document.getElementById('sidebarOverlay');
const hamburgerBtn = document.getElementById('hamburgerBtn');

function openSidebar() {
    if (!sidebar || !overlay) return;
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden'; // prevent body scroll
}
function closeSidebar() {
    if (!sidebar || !overlay) return;
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

if (hamburgerBtn) hamburgerBtn.addEventListener('click', openSidebar);
if (overlay)      overlay.addEventListener('click', closeSidebar);

// Close on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
});

/* ── 3. KPI Counter Animation ─────────────────────────────────
   Runs a smooth count-up from 0 to the target value
   (stored in data-target attribute).
   Uses IntersectionObserver — only fires when card is visible.
   ─────────────────────────────────────────────────────────── */
const kpiValues = document.querySelectorAll('.kpi-value[data-target]');

function animateCount(el) {
    const target   = parseFloat(el.dataset.target);
    const isFloat  = el.dataset.float === 'true';
    const prefix   = el.dataset.prefix  || '';
    const suffix   = el.dataset.suffix  || '';
    const duration = 1200; // ms
    const start    = performance.now();

    function step(ts) {
        const progress = Math.min((ts - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
        const current  = eased * target;
        el.textContent = prefix + (isFloat ? current.toFixed(1) : Math.floor(current)) + suffix;
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

if ('IntersectionObserver' in window && kpiValues.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    kpiValues.forEach(el => observer.observe(el));
} else {
    // Fallback: set immediately for browsers without IntersectionObserver
    kpiValues.forEach(el => {
        const target  = parseFloat(el.dataset.target);
        const prefix  = el.dataset.prefix  || '';
        const suffix  = el.dataset.suffix  || '';
        const isFloat = el.dataset.float === 'true';
        el.textContent = prefix + (isFloat ? target.toFixed(1) : target) + suffix;
    });
}

/* ── 4. Active Nav Link ────────────────────────────────────────
   Marks the link whose href matches the current page.
   ─────────────────────────────────────────────────────────── */
const navLinks  = document.querySelectorAll('.nav-link');
const pageName  = window.location.pathname.split('/').pop() || 'index';

navLinks.forEach(link => {
    const href = (link.getAttribute('href') || '').split('/').pop();
    if (href && href !== '#' && pageName.startsWith(href.replace('.php', ''))) {
        link.classList.add('active');
    }
});

/* ── 5. Inline SVG chart — monthly rentals line ──────────────
   Draws a smooth area chart using pure SVG path maths.
   Data is read from data-values attribute on the SVG wrapper.
   ─────────────────────────────────────────────────────────── */
function renderAreaChart(containerId) {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;

    const rawData = (wrap.dataset.values || '').split(',').map(Number);
    if (!rawData.length) return;

    const W = wrap.clientWidth  || 320;
    const H = wrap.clientHeight || 140;
    const PAD = { top: 12, right: 12, bottom: 20, left: 30 };
    const chartW = W - PAD.left - PAD.right;
    const chartH = H - PAD.top  - PAD.bottom;

    const maxVal  = Math.max(...rawData, 1);
    const step    = chartW / (rawData.length - 1);

    // Helper — map data point to (x, y)
    const px = (i) => PAD.left + i * step;
    const py = (v) => PAD.top + chartH - (v / maxVal) * chartH;

    // Build smooth cubic bezier path
    let dLine = `M ${px(0)},${py(rawData[0])}`;
    for (let i = 1; i < rawData.length; i++) {
        const cpX = px(i - 0.5);
        dLine += ` C ${cpX},${py(rawData[i-1])} ${cpX},${py(rawData[i])} ${px(i)},${py(rawData[i])}`;
    }

    // Area path (closed)
    const dArea = `${dLine} L ${px(rawData.length-1)},${PAD.top + chartH} L ${PAD.left},${PAD.top + chartH} Z`;

    // Y-axis grid lines
    const gridLines = [0.25, 0.5, 0.75, 1].map(t => {
        const yy = PAD.top + chartH - t * chartH;
        return `<line x1="${PAD.left}" y1="${yy}" x2="${PAD.left + chartW}" y2="${yy}"
                      stroke="var(--border-color)" stroke-width="1" stroke-dasharray="4 4"/>`;
    }).join('');

    // Y labels
    const yLabels = [0.25, 0.5, 0.75, 1].map(t => {
        const val = Math.round(t * maxVal);
        const yy  = PAD.top + chartH - t * chartH + 4;
        return `<text x="${PAD.left - 6}" y="${yy}" text-anchor="end"
                      font-size="9" fill="var(--text-subtle)">${val}</text>`;
    }).join('');

    // Dot points
    const dots = rawData.map((v, i) =>
        `<circle cx="${px(i)}" cy="${py(v)}" r="3.5"
                 fill="var(--primary-action)" stroke="var(--surface-color)" stroke-width="2"/>`
    ).join('');

    wrap.innerHTML = `
<svg viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" style="overflow:visible">
  <defs>
    <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%"   stop-color="var(--primary-action)" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="var(--primary-action)" stop-opacity="0"/>
    </linearGradient>
  </defs>
  ${gridLines}
  ${yLabels}
  <path d="${dArea}" fill="url(#areaGrad)"/>
  <path d="${dLine}"
        fill="none"
        stroke="var(--primary-action)"
        stroke-width="2.5"
        stroke-linejoin="round"
        stroke-linecap="round"/>
  ${dots}
</svg>`;
}

// Run on load and on resize
renderAreaChart('chart-area');
window.addEventListener('resize', () => renderAreaChart('chart-area'));
