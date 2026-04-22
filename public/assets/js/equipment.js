/**
 * equipment.js — AgroShare Equipment Module (Vanilla ES6)
 *
 * Handles:
 *   1. Image upload preview (drag-and-drop + click-to-browse)
 *   2. Equipment detail gallery (thumbnail switching)
 *   3. AJAX availability toggle (no page reload)
 */

'use strict';

// ── 1. Image Upload Preview ────────────────────────────────
(function initImageUpload() {
    const zone        = document.getElementById('imageUploadZone');
    const input       = document.getElementById('eq-images');
    const previewGrid = document.getElementById('imagePreviewGrid');
    const placeholder = document.getElementById('uploadPlaceholder');

    if (!zone || !input) return;

    // Drag-and-drop visual feedback
    zone.addEventListener('dragenter', (e) => {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', () => {
        zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        // Assign dropped files to the input
        if (e.dataTransfer.files.length > 0) {
            input.files = e.dataTransfer.files;
            renderPreviews(input.files);
        }
    });

    // File input change handler
    input.addEventListener('change', () => {
        renderPreviews(input.files);
    });

    function renderPreviews(files) {
        if (!previewGrid) return;
        previewGrid.innerHTML = '';

        if (files.length === 0) {
            if (placeholder) placeholder.style.display = '';
            return;
        }
        if (placeholder) placeholder.style.display = 'none';

        Array.from(files).forEach((file, i) => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.style.animationDelay = `${i * 0.08}s`;

                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;

                item.appendChild(img);
                previewGrid.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    }
})();


// ── 2. Equipment Detail Gallery ────────────────────────────
(function initGallery() {
    const mainImg = document.getElementById('galleryMainImg');
    const thumbs  = document.querySelectorAll('.gallery-thumb');

    if (!mainImg || thumbs.length === 0) return;

    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            const newSrc = thumb.dataset.src;
            if (!newSrc) return;
            
            const currentPath = new URL(mainImg.src, window.location.href).pathname;
            const newPath = new URL(newSrc, window.location.href).pathname;
            if (currentPath === newPath) return;

            // Smooth fade transition
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = newSrc;
                mainImg.style.opacity = '1';
            }, 200);

            // Update active state
            thumbs.forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        });
    });
})();


// ── 3. AJAX Availability Toggle ────────────────────────────
(function initToggleAvailability() {
    const btn = document.getElementById('toggleAvailBtn');
    if (!btn) return;

    const equipmentId = btn.dataset.id;
    const labelEl     = btn.querySelector('.toggle-label'); // Detail page
    const spanEl      = btn.querySelector('span');         // Management page
    const statusBadge = document.getElementById('statusBadge');
    const manageStatus = document.getElementById('manageAvailStatus');

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.style.opacity = '0.6';

        try {
            const formData = new FormData();
            formData.append('equipment_id', equipmentId);
            
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || 
                            document.getElementById('global-csrf-token')?.value || '';
            formData.append('csrf_token', csrfToken);

            const res  = await fetch('api/toggle-availability.php', {
                method: 'POST',
                body: formData,
            });
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            
            const data = await res.json();

            if (data.success) {
                const isNowAvailable = Boolean(data.is_available === 1 || data.is_available === true || data.is_available === '1');

                // 1. Update the button itself
                btn.classList.toggle('is-available', isNowAvailable);
                btn.classList.toggle('is-unavailable', !isNowAvailable);
                btn.classList.toggle('is-active', isNowAvailable); 
                btn.classList.toggle('is-offline', !isNowAvailable); 

                // 2. Update button text
                if (labelEl) {
                    labelEl.textContent = isNowAvailable ? 'Available for Booking' : 'Unavailable';
                } else if (spanEl) {
                    spanEl.textContent = isNowAvailable ? 'Active' : 'Offline';
                }

                // 3. Update status displays outside button
                if (statusBadge) {
                    statusBadge.textContent = isNowAvailable ? 'Available' : 'Unavailable';
                    statusBadge.className = 'badge ' + (isNowAvailable ? 'badge-active' : 'badge-cancelled');
                }
                
                if (manageStatus) {
                    manageStatus.textContent = isNowAvailable ? 'Visible & Active' : 'Hidden from Browse';
                    manageStatus.style.color = isNowAvailable ? 'var(--secondary-action)' : 'var(--danger)';
                }

                showToast('success', isNowAvailable ? 'Listing is now active.' : 'Listing is now offline.');
            } else {
                showToast('error', data.message || 'Could not update availability.');
            }
        } catch (err) {
            console.error('Toggle error:', err);
            showToast('error', 'Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    });
})();


// ── 4. Edit Equipment Modal & AJAX ──────────────────────────
(function initEditModal() {
    const editModal = document.getElementById('editEquipmentModal');
    const editBtn = document.getElementById('editEquipmentBtn');
    const closeBtn = document.getElementById('editModalCloseBtn');
    const cancelBtn = document.getElementById('editCancelBtn');
    const editForm = document.getElementById('editEquipmentForm');

    if (!editModal || !editBtn) return;

    const openModal = () => {
        editModal.classList.add('show-modal');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        editModal.classList.remove('show-modal');
        document.body.style.overflow = '';
        if (editForm) {
            // We don't necessarily want to reset since it's an edit form, 
            // but we should clear the new image previews
            const previewGrid = document.getElementById('editImagePreviewGrid');
            if (previewGrid) previewGrid.innerHTML = '';
        }
    };

    editBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Close on overlay click
    editModal.addEventListener('click', (e) => {
        if (e.target === editModal) closeModal();
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && editModal.classList.contains('show-modal')) {
            closeModal();
        }
    });

    // Form submission
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = document.getElementById('editSubmitBtn');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Saving...</span>';
            }
            
            // Clear previous errors
            document.querySelectorAll('.form-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
            
            try {
                const formData = new FormData(editForm);
                
                const response = await fetch('api/edit-equipment.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();
                
                if (result.success) {
                    if (result.new_csrf) {
                        const csrfInput = document.getElementById('editCsrfToken');
                        if (csrfInput) csrfInput.value = result.new_csrf;
                    }
                    showToast('success', result.message);
                    closeModal();
                    // Reload to show updated data
                    setTimeout(() => window.location.reload(), 1000);
                } else if (result.errors) {
                    for (const [field, msg] of Object.entries(result.errors)) {
                        const input = document.getElementsByName(field)[0];
                        if (input) {
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
                    showToast('error', result.message || 'Something went wrong.');
                }
            } catch (error) {
                console.error('Submission error:', error);
                showToast('error', 'Network error. Please try again.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });
    }

    // New images preview for edit form
    const editInput = document.getElementById('edit-eq-images');
    const editPreviewGrid = document.getElementById('editImagePreviewGrid');
    const editZone = document.getElementById('editImageUploadZone');

    if (editZone && editInput) {
        editZone.addEventListener('click', () => editInput.click());
        
        editZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            editZone.classList.add('drag-over');
        });
        
        editZone.addEventListener('dragleave', () => {
            editZone.classList.remove('drag-over');
        });
        
        editZone.addEventListener('drop', (e) => {
            e.preventDefault();
            editZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                editInput.files = e.dataTransfer.files;
                renderEditPreviews(editInput.files);
            }
        });
        
        editInput.addEventListener('change', () => {
            renderEditPreviews(editInput.files);
        });
        
        function renderEditPreviews(files) {
            if (!editPreviewGrid) return;
            editPreviewGrid.innerHTML = '';
            
            Array.from(files).forEach((file, i) => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '60px';
                    img.style.height = '60px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.style.margin = '4px';
                    editPreviewGrid.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    }
})();

// ── 5. Price Estimator Logic ────────────────────────────────
window.calculatePricing = async () => {
    const startInput = document.getElementById('est-start');
    const endInput   = document.getElementById('est-end');
    const resultDiv  = document.getElementById('est-result');
    const totalEl    = document.getElementById('est-total');
    const breakEl    = document.getElementById('est-breakdown');
    
    const eqId = document.getElementById('toggleAvailBtn')?.dataset.id || 
                 new URLSearchParams(window.location.search).get('id');

    if (!startInput || !endInput || !eqId) return;

    const start = startInput.value;
    const end   = endInput.value;

    if (!start || !end) {
        resultDiv.style.display = 'none';
        return;
    }

    try {
        const res = await fetch(`api/calculate-price.php?equipment_id=${eqId}&start_datetime=${encodeURIComponent(start)}&end_datetime=${encodeURIComponent(end)}`);
        const data = await res.json();

        if (data.success) {
            totalEl.textContent = `₹${new Intl.NumberFormat('en-IN').format(data.total_price)}`;
            breakEl.textContent = data.breakdown;
            resultDiv.style.display = 'block';
        } else {
            resultDiv.style.display = 'none';
        }
    } catch (err) {
        console.error('Pricing error:', err);
    }
};

(function initPriceEstimator() {
    const startInput = document.getElementById('est-start');
    const endInput   = document.getElementById('est-end');
    
    if (!startInput || !endInput) return;

    startInput.addEventListener('change', window.calculatePricing);
    endInput.addEventListener('change', window.calculatePricing);
})();

// ── 6. Auto-open Modal from URL ──────────────────────────────
(function initAutoOpen() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'list') {
        const modal = document.getElementById('equipmentModal');
        if (modal) {
            modal.classList.add('show-modal');
            document.body.style.overflow = 'hidden';
        }
    }
})();

// ── Toast helper ───────────────────────────────────────────
function showToast(type, message) {
    // Remove existing toast
    const old = document.querySelector('.toast');
    if (old) old.remove();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
