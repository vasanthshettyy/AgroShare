/**
 * AgroShare Dashboard — dashboard.js
 * Vanilla ES6+  |  No dependencies
 */
'use strict';

/* ── 1. Theme Persistence ──────────────────────────────────────
   Force dark mode as the default theme.
   ─────────────────────────────────────────────────────────── */
document.documentElement.setAttribute('data-theme', 'dark');
localStorage.setItem('theme', 'dark');

/* ── 2. Sidebar Toggle (mobile) ───────────────────────────────
   Hamburger opens the drawer.
   Clicking the overlay (backdrop) closes it.
   ─────────────────────────────────────────────────────────── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
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
if (overlay) overlay.addEventListener('click', closeSidebar);

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
    const target = parseFloat(el.dataset.target);
    const isFloat = el.dataset.float === 'true';
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    const duration = 1200; // ms
    const start = performance.now();

    function step(ts) {
        const progress = Math.min((ts - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
        const current = eased * target;
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
        const target = parseFloat(el.dataset.target);
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        const isFloat = el.dataset.float === 'true';
        el.textContent = prefix + (isFloat ? target.toFixed(1) : target) + suffix;
    });
}

/* ── 4. Active Nav Link ────────────────────────────────────────
   (Removed — active states are now handled server-side via PHP)
   ─────────────────────────────────────────────────────────── */

/* ── 5. Inline SVG chart — monthly rentals line ──────────────
   Draws a smooth area chart using pure SVG path maths.
   Data is read from data-values attribute on the SVG wrapper.
   ─────────────────────────────────────────────────────────── */
function renderAreaChart(containerId) {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;

    const rawData = (wrap.dataset.values || '').split(',').map(Number);
    if (!rawData.length) return;

    const W = wrap.clientWidth || 320;
    const H = wrap.clientHeight || 140;
    const PAD = { top: 12, right: 12, bottom: 20, left: 30 };
    const chartW = W - PAD.left - PAD.right;
    const chartH = H - PAD.top - PAD.bottom;

    const maxVal = Math.max(...rawData, 1);
    const step = chartW / (rawData.length - 1);

    // Helper — map data point to (x, y)
    const px = (i) => PAD.left + i * step;
    const py = (v) => PAD.top + chartH - (v / maxVal) * chartH;

    // Build smooth cubic bezier path
    let dLine = `M ${px(0)},${py(rawData[0])}`;
    for (let i = 1; i < rawData.length; i++) {
        const cpX = px(i - 0.5);
        dLine += ` C ${cpX},${py(rawData[i - 1])} ${cpX},${py(rawData[i])} ${px(i)},${py(rawData[i])}`;
    }

    // Area path (closed)
    const dArea = `${dLine} L ${px(rawData.length - 1)},${PAD.top + chartH} L ${PAD.left},${PAD.top + chartH} Z`;

    // Y-axis grid lines
    const gridLines = [0.25, 0.5, 0.75, 1].map(t => {
        const yy = PAD.top + chartH - t * chartH;
        return `<line x1="${PAD.left}" y1="${yy}" x2="${PAD.left + chartW}" y2="${yy}"
                      stroke="var(--border-color)" stroke-width="1" stroke-dasharray="4 4"/>`;
    }).join('');

    // Y labels
    const yLabels = [0.25, 0.5, 0.75, 1].map(t => {
        const val = Math.round(t * maxVal);
        const yy = PAD.top + chartH - t * chartH + 4;
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

/* ── 6. Equipment Modal ───────────────────────────────────────
   Modal for listing new equipment without page redirect.
   ─────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {
    // Modal elements
    const equipmentModal = document.getElementById('equipmentModal');
    const listEquipmentBtns = document.querySelectorAll('.listEquipmentBtn');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const equipmentForm = document.getElementById('equipmentForm');

    // Open modal
    listEquipmentBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            
            if (equipmentModal) {
                equipmentModal.classList.add('show-modal');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
            } else {
                console.warn('Modal element #equipmentModal not found, redirecting...');
                window.location.href = 'equipment-create.php';
            }
        });
    });

    // Close modal function
    function closeModal() {
        if (!equipmentModal) return;
        equipmentModal.classList.remove('show-modal');
        document.body.style.overflow = ''; // Restore scroll
        if (equipmentForm) {
            equipmentForm.reset();
            if (typeof window.clearImagePreviews === 'function') window.clearImagePreviews();
        }
    }

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Close on overlay click
    if (equipmentModal) {
        equipmentModal.addEventListener('click', (e) => {
            if (e.target === equipmentModal) {
                closeModal();
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && equipmentModal && equipmentModal.classList.contains('show-modal')) {
            closeModal();
        }
    });

    // Form submission
    if (equipmentForm) {
        equipmentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Listing...</span>';
            }
            
            // Clear previous errors
            document.querySelectorAll('.form-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
            
            try {
                const formData = new FormData(equipmentForm);
                
                const basePath = document.documentElement.dataset.basePath || '';
                const response = await fetch(`${basePath}/api/create-equipment.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.new_csrf) {
                        const csrfInput = document.getElementById('csrfToken');
                        if (csrfInput) csrfInput.value = result.new_csrf;
                    }
                    alert(result.message);
                    closeModal();
                    window.location.reload();
                } else if (result.errors) {
                    for (const [field, msg] of Object.entries(result.errors)) {
                        const input = document.getElementsByName(field)[0];
                        if (input && input.parentNode) {
                            input.classList.add('has-error');
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'form-error-msg';
                            errorDiv.style.color = 'var(--danger)';
                            errorDiv.style.fontSize = '0.75rem';
                            errorDiv.style.marginTop = '4px';
                            errorDiv.textContent = msg;
                            input.parentNode.appendChild(errorDiv);
                        }
                    }
                } else {
                    alert(result.message || 'Something went wrong.');
                }
            } catch (error) {
                console.error('Submission error:', error);
                alert('Network error. Please try again.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });
    }

    // ── Profile Modal Logic ──────────────────────────────────
    const profileModal = document.getElementById('profileModal');
    const profileForm = document.getElementById('profileForm');

    const openProfileModal = async (e) => {
        if (e) e.preventDefault();
        
        // Find modal again in case DOM was updated
        const modal = document.getElementById('profileModal');
        if (!modal) {
            console.error('Profile modal not found in DOM.');
            return;
        }

        try {
            // Use absolute-style relative path from public root
            const response = await fetch('api/get-profile.php');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const result = await response.json();

            if (result.success) {
                const user = result.data;
                
                // Safe population helper
                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };

                setVal('prof-name', user.full_name);
                setVal('prof-phone', user.phone);
                setVal('prof-email', user.email);
                setVal('prof-village', user.village);
                setVal('prof-district', user.district);
                setVal('prof-state', user.state);
                
                // Avatar preview
                const preview = document.getElementById('prof-photo-preview');
                if (preview && user.profile_photo) {
                    preview.src = user.profile_photo;
                }
                
                // Badges
                const badgeContainer = document.getElementById('prof-badges');
                if (badgeContainer) {
                    badgeContainer.innerHTML = '';
                    if (parseInt(user.is_verified)) {
                        const vBadge = document.createElement('span');
                        vBadge.className = 'profile-trust-badge verified';
                        vBadge.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Verified Farmer`;
                        badgeContainer.appendChild(vBadge);
                    }
                    const tBadge = document.createElement('span');
                    tBadge.className = 'profile-trust-badge';
                    tBadge.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> ${user.trust_score} Trust`;
                    badgeContainer.appendChild(tBadge);
                }

                modal.classList.add('show-modal');
                document.body.style.overflow = 'hidden';
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Profile fetch error:', error);
            alert('Could not load profile. Please check your connection.');
        }
    };

    // Use event delegation or multiple listeners for triggers
    document.addEventListener('click', (e) => {
        const target = e.target.closest('#profile-btn, #avatar-btn, #edit-profile-quick-action');
        if (target) {
            openProfileModal(e);
        }
    });
    
    const closeProfileModal = () => {
        const modal = document.getElementById('profileModal');
        if (modal) {
            modal.classList.remove('show-modal');
            document.body.style.overflow = '';
        }
    };

    // Close listeners
    document.addEventListener('click', (e) => {
        if (e.target.matches('#profileModalCloseBtn, #profileCancelBtn') || e.target.matches('#profileModal')) {
            closeProfileModal();
        }
    });

    // Photo preview change
    document.addEventListener('change', (e) => {
        if (e.target.id === 'prof-photo-input') {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    const preview = document.getElementById('prof-photo-preview');
                    if (preview) preview.src = ev.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    });

    // Profile form submission
    document.addEventListener('submit', async (e) => {
        if (e.target.id === 'profileForm') {
            e.preventDefault();
            const form = e.target;
            const submitBtn = document.getElementById('profileSubmitBtn');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Update Profile';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Updating...';
            }

            try {
                const formData = new FormData(form);
                const response = await fetch('api/update-profile.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    const greetings = document.querySelectorAll('.topbar-greeting strong');
                    greetings.forEach(el => el.textContent = result.full_name);
                    closeProfileModal();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Profile update error:', error);
                alert('Update failed. Check your connection.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        }
    });
});

// Image upload preview (adapted from equipment.js)
const imageUploadZone = document.getElementById('imageUploadZone');
const imagePreviewGrid = document.getElementById('imagePreviewGrid');
const imageInput = document.getElementById('eq-images');

function clearImagePreviews() {
    if (imagePreviewGrid) imagePreviewGrid.innerHTML = '';
    if (imageUploadZone) imageUploadZone.classList.remove('dragover');
}
window.clearImagePreviews = clearImagePreviews;

if (imageUploadZone && imageInput) {
    imageUploadZone.addEventListener('click', () => imageInput.click());
    
    imageUploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        imageUploadZone.classList.add('dragover');
    });
    
    imageUploadZone.addEventListener('dragleave', () => {
        imageUploadZone.classList.remove('dragover');
    });
    
    imageUploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        imageUploadZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const dt = new DataTransfer();
            for (let i = 0; i < files.length; i++) {
                dt.items.add(files[i]);
            }
            imageInput.files = dt.files;
            // Trigger change event to update previews
            imageInput.dispatchEvent(new Event('change'));
        }
    });
    
    imageInput.addEventListener('change', (e) => {
        const files = e.target.files;
        if (files.length > 5) {
            alert('You can upload a maximum of 5 images.');
            imageInput.value = '';
            return;
        }
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')) {
                alert('Only image files are allowed.');
                imageInput.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert(`File ${file.name} exceeds 2MB limit.`);
                imageInput.value = '';
                return;
            }
        }
        
        // Clear previous previews
        clearImagePreviews();
        
        // Create previews
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '60px';
                    img.style.height = '60px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.style.margin = '4px';
                    imagePreviewGrid.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    });
}
