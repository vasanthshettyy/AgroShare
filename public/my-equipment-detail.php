<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/EquipmentController.php';

requireAuth(); // Owner must be logged in

$userId = (int)$_SESSION['user_id'];

// —— Common layout data ─────────────────────────────────────
$fullName  = trim($_SESSION['full_name'] ?? '');
$nameParts = explode(' ', $fullName);
$initials  = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : '?';
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

$needsTabCheck = isset($_SESSION['persist']) && $_SESSION['persist'] === false;

// —— Load equipment ─────────────────────────────────────────
$equipmentId = (int)($_GET['id'] ?? 0);
if ($equipmentId <= 0) {
    setFlash('error', 'Equipment not found.');
    header('Location: equipment-browse.php?mine=1');
    exit();
}

$eq = getEquipmentById($conn, $equipmentId);
if (!$eq) {
    setFlash('error', 'Equipment not found.');
    header('Location: equipment-browse.php?mine=1');
    exit();
}

// Security: Only owner can view this page
if ((int)$eq['owner_id'] !== $userId) {
    header('Location: equipment-detail.php?id=' . $equipmentId);
    exit();
}

$images = $eq['images'] ? json_decode($eq['images'], true) : [];

// —— Handle delete ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid form submission.');
        header('Location: my-equipment-detail.php?id=' . $equipmentId);
        exit();
    }

    if ($_POST['action'] === 'delete') {
        $deleted = deleteEquipment($conn, $equipmentId, $userId);
        if ($deleted) {
            setFlash('success', 'Equipment deleted successfully.');
            header('Location: equipment-browse.php?mine=1');
            exit();
        } else {
            setFlash('error', 'Could not delete equipment.');
        }
    }

    header('Location: my-equipment-detail.php?id=' . $equipmentId);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage: <?= e($eq['title']) ?> — <?= e(APP_NAME) ?></title>

    <script>
        (function(){
            var t = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <?php if ($needsTabCheck): ?>
    <script>if (!sessionStorage.getItem('agroshare_tab')) window.location.href = 'logout.php';</script>
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/equipment.css?v=<?= time() ?>">
    <style>
        .owner-view-grid {
            display: grid;
            grid-template-columns: 450px 1fr;
            gap: 2.5rem;
            max-width: 1200px;
            margin: 2rem auto;
            align-items: start;
        }
        @media (max-width: 992px) {
            .owner-view-grid { grid-template-columns: 1fr; }
        }
        .owner-image-sidebar {
            position: sticky;
            top: 2rem;
        }
        .owner-details-main {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-sm);
        }
        .manage-actions-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        .btn-manage-large {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.5rem;
            border-radius: 16px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            cursor: pointer;
            background: var(--surface-color-alt);
            color: var(--text-color);
        }
        .btn-manage-large svg { width: 24px; height: 24px; }
        .btn-manage-large:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-action);
            background: var(--surface-color);
        }
        .btn-manage-large.is-active {
            border-color: var(--secondary-action);
            color: var(--secondary-action);
            background: rgba(76, 175, 120, 0.05);
        }
        .btn-manage-large.is-offline {
            border-color: var(--danger);
            color: var(--danger);
            background: rgba(225, 29, 72, 0.05);
        }
        .btn-manage-large.danger:hover {
            border-color: var(--danger);
            color: var(--danger);
        }
        .manage-specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .manage-spec-card {
            background: var(--surface-color-alt);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .manage-spec-label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .manage-spec-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }
    </style>
</head>
<body>

<div class="app-layout">

    <!-- -- TOPBAR -------------------------------------------- -->
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation menu" aria-expanded="false" aria-controls="sidebar"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <p class="topbar-greeting">Listing Management</p>
        </div>
        <div class="topbar-right" style="position: relative;">
            <button class="btn-icon" id="notifBtn" aria-label="Notifications" title="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notif-dot" id="notifDot" aria-hidden="true" style="display: none;"></span>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">Notifications</div>
                <div class="notif-list" id="notifList"><div class="notif-empty">Loading...</div></div>
            </div>
            <div class="avatar" id="avatar-btn" role="button" tabindex="0" title="Profile — <?= e($_SESSION['full_name']) ?>" aria-label="Open profile"><?= e($initials) ?></div>
        </div>
    </header>

    <!-- -- SIDEBAR ------------------------------------------- -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>

        <nav class="sidebar-nav" aria-label="Site navigation">
            <span class="nav-section-label">Main</span>
            <a href="dashboard.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="equipment-browse.php?mine=1" class="nav-link active" aria-current="page">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                </svg>
                <span>My Equipment</span>
            </a>
            <a href="my-bookings.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>
                </svg>
                <span>My Bookings</span>
            </a>

            <span class="nav-section-label">Community</span>
            <span class="nav-link is-disabled" title="Coming soon" aria-disabled="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Pooling</span>
            </span>
            <a href="equipment-browse.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <span>Browse</span>
            </a>
            <span class="nav-link is-disabled" title="Coming soon" aria-disabled="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Reviews</span>
            </span>

            <span class="nav-section-label">Account</span>
            <a href="javascript:void(0)" class="nav-link" id="profile-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>
                </svg>
                <span>Profile</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- -- MAIN CONTENT -------------------------------------- -->
    <main class="main-content" role="main">
        <?= renderFlash() ?>

        <div class="product-header">
            <a href="equipment-browse.php?mine=1" class="btn-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to My Listings
            </a>
        </div>

        <div class="owner-view-grid">
            <!-- Left: E-com style image gallery -->
            <div class="owner-image-sidebar">
                <div class="detail-gallery">
                    <?php if (!empty($images)): ?>
                    <div class="gallery-main" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-color);">
                        <img src="<?= e($images[0]) ?>" alt="<?= e($eq['title']) ?>" id="galleryMainImg" class="gallery-main-img" style="aspect-ratio: 4/3; object-fit: cover;">
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs" style="margin-top: 1rem; justify-content: center;">
                        <?php foreach ($images as $i => $img): ?>
                        <button class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                                data-src="<?= e($img) ?>" aria-label="View photo <?= $i+1 ?>" style="width: 70px; height: 70px;">
                            <img src="<?= e($img) ?>" alt="Photo <?= $i+1 ?>" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="gallery-empty" style="aspect-ratio: 4/3; border-radius: 16px;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--accent-soft)" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p>No photos uploaded</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="manage-specs-grid" style="margin-top: 2rem;">
                    <div class="manage-spec-card">
                        <span class="manage-spec-label">Availability</span>
                        <span class="manage-spec-value" id="manageAvailStatus" style="color: <?= $eq['is_available'] ? 'var(--secondary-action)' : 'var(--danger)' ?>;">
                            <?= $eq['is_available'] ? 'Visible & Active' : 'Hidden from Browse' ?>
                        </span>
                    </div>
                    <div class="manage-spec-card">
                        <span class="manage-spec-label">Daily Rate</span>
                        <span class="manage-spec-value">₹<?= number_format($eq['price_per_day'], 0) ?></span>
                    </div>
                    <div class="manage-spec-card">
                        <span class="manage-spec-label">Operator</span>
                        <span class="manage-spec-value"><?= $eq['includes_operator'] ? 'Included' : 'Not Included' ?></span>
                    </div>
                </div>
            </div>

            <!-- Right: Details and Actions -->
            <div class="owner-details-main">
                <div class="manage-actions-bar">
                    <button class="btn-manage-large <?= $eq['is_available'] ? 'is-active' : '' ?>" id="toggleAvailBtn" data-id="<?= $equipmentId ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span><?= $eq['is_available'] ? 'Active' : 'Offline' ?></span>
                    </button>
                    
                    <button class="btn-manage-large" id="editEquipmentBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>Edit Details</span>
                    </button>

                    <form method="POST" action="my-equipment-detail.php?id=<?= $equipmentId ?>" id="deleteEquipmentForm" style="display:contents;">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="button" class="btn-manage-large danger" id="deleteListingBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>Delete</span>
                        </button>
                    </form>
                </div>

                <h1 style="font-size: 2.25rem; margin-bottom: 0.5rem;"><?= e($eq['title']) ?></h1>
                <p style="color: var(--text-muted); margin-bottom: 2rem;"><?= e(ucfirst(str_replace('_', ' ', $eq['category']))) ?> • <?= e($eq['location_village']) ?>, <?= e($eq['location_district']) ?></p>

                <div class="premium-section">
                    <span class="section-label">Listing Description</span>
                    <p class="description-text" style="font-size: 1.1rem; line-height: 1.7; color: var(--text-color);"><?= nl2br(e($eq['description'])) ?></p>
                </div>

                <div class="premium-section" style="border-bottom: none;">
                    <span class="section-label">Full Specifications</span>
                    <table class="specs-table" style="width: 100%;">
                        <tr><th>Structural Condition</th><td><?= e(ucfirst($eq['condition'])) ?></td></tr>
                        <tr><th>Operator Included</th><td><?= $eq['includes_operator'] ? 'Yes' : 'No' ?></td></tr>
                        <tr><th>Base Price (Daily)</th><td>₹<?= number_format($eq['price_per_day'], 0) ?></td></tr>
                        <?php if ((float)$eq['safety_deposit'] > 0): ?>
                        <tr><th>Safety Deposit (Refundable)</th><td>₹<?= number_format($eq['safety_deposit'], 0) ?></td></tr>
                        <?php endif; ?>
                        <tr><th>Created On</th><td><?= date('d M Y', strtotime($eq['created_at'])) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Edit Equipment Modal -->
<div id="editEquipmentModal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" id="editModalCloseBtn" aria-label="Close modal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="modal-header">
            <h2>Edit Equipment</h2>
            <p>Update details for your <?= e($eq['title']) ?>.</p>
        </div>
        <form id="editEquipmentForm" class="eq-form" method="POST" action="api/edit-equipment.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" id="editCsrfToken" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="id" value="<?= (int)$eq['id'] ?>">
            
            <div class="form-section">
                <h2 class="form-section-title">Equipment Details</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="edit-eq-title" class="form-label">Equipment Title</label>
                        <input type="text" name="title" id="edit-eq-title" class="form-input" value="<?= e($eq['title']) ?>" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-eq-category" class="form-label">Category</label>
                        <select name="category" id="edit-eq-category" class="form-input form-select" required>
                            <?php foreach (['tractor','harvester','seeder','sprayer','plough','chain_saw','rotavator','cultivator','thresher','water_pump','earth_auger','baler','trolley','brush_cutter','power_tiller','chaff_cutter','other'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $eq['category'] === $cat ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $cat)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-eq-condition" class="form-label">Condition</label>
                        <select name="condition" id="edit-eq-condition" class="form-input form-select" required>
                            <?php foreach (['excellent'=>'Excellent','good'=>'Good','fair'=>'Fair'] as $val=>$label): ?>
                            <option value="<?= $val ?>" <?= $eq['condition'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="edit-eq-description" class="form-label">Description</label>
                        <textarea name="description" id="edit-eq-description" class="form-input form-textarea" rows="4" required><?= e($eq['description']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="form-section-title">Pricing & Location</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-eq-price-day" class="form-label">Price per Day (₹)</label>
                        <input type="number" name="price_per_day" id="edit-eq-price-day" class="form-input" value="<?= (float)$eq['price_per_day'] ?>" min="0" step="100" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-eq-safety-deposit" class="form-label">Safety Deposit (₹)</label>
                        <input type="number" name="safety_deposit" id="edit-eq-safety-deposit" class="form-input" value="<?= (float)($eq['safety_deposit'] ?? 0) ?>" min="0" step="100">
                    </div>
                    <div class="form-group full-width" style="padding-bottom: 0.8rem;">
                        <label class="form-checkbox-label" style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" name="includes_operator" value="1" class="form-checkbox" <?= $eq['includes_operator'] ? 'checked' : '' ?> style="display: none;">
                            <span class="checkbox-visual" style="margin: 0;"></span>
                            <span>Includes Operator</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="edit-eq-village" class="form-label">Village</label>
                        <input type="text" name="location_village" id="edit-eq-village" class="form-input" value="<?= e($eq['location_village']) ?>" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-eq-district" class="form-label">District</label>
                        <input type="text" name="location_district" id="edit-eq-district" class="form-input" value="<?= e($eq['location_district']) ?>" maxlength="100" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="form-section-title">Photos</h2>
                <?php if (!empty($images)): ?>
                <div class="existing-images-grid" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));">
                    <?php foreach ($images as $img): ?>
                    <div class="existing-image-item">
                        <img src="<?= e($img) ?>" alt="Equipment photo">
                        <label class="remove-image-label">
                            <input type="checkbox" name="remove_images[]" value="<?= e($img) ?>">
                            <span class="remove-badge">✕ Remove</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="image-upload-zone" id="editImageUploadZone">
                    <input type="file" name="images[]" id="edit-eq-images" accept="image/jpeg,image/png,image/webp" multiple class="image-upload-input" style="display: none;">
                    <div class="upload-placeholder">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><path d="m21 11-3-3a2 2 0 0 0-2.828 0l-8.086 8.086"/><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg>
                        <p style="font-size:0.8rem;">Add more photos</p>
                    </div>
                    <div class="image-preview-grid" id="editImagePreviewGrid"></div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 2rem; border: none; padding: 0; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn-secondary" id="editCancelBtn" style="min-height: auto; padding: 0.7rem 1.5rem; font-size: 0.85rem; border-radius: 40px;">Cancel</button>
                <button type="submit" class="btn-primary" id="editSubmitBtn" style="min-height: auto; padding: 0.7rem 1.5rem; font-size: 0.85rem; border-radius: 40px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 450px; text-align: center; padding: 3rem 2rem;">
        <div style="background: rgba(225, 29, 72, 0.1); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--danger);">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </div>
        <h2 style="margin-bottom: 0.5rem; font-size: 1.5rem;">Delete Listing?</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem; line-height: 1.5;">Are you sure you want to permanently delete this equipment? You might want to <strong>Edit</strong> it instead if some details have changed.</p>
        
        <div class="form-actions" style="margin-top: 1.5rem; border: none; padding: 0; justify-content: center; gap: 1rem;">
            <button type="button" class="btn-secondary" id="switchToDeleteEditBtn" style="min-height: auto; padding: 0.7rem 1.5rem; font-size: 0.85rem; border-radius: 40px;">Edit Listing</button>
            <button type="button" class="btn-primary" id="confirmDeleteBtn" style="background: var(--danger); border-color: var(--danger); min-height: auto; padding: 0.7rem 1.5rem; font-size: 0.85rem; border-radius: 40px;">Delete Permanently</button>
        </div>
        <div style="margin-top: 1.5rem;">
            <button type="button" class="btn-clear-filters" id="cancelDeleteBtn" style="font-size: 0.85rem; background: none; border: none; cursor: pointer;">Nevermind, go back</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<script src="assets/js/dashboard.js" defer></script>
<script src="assets/js/equipment.js?v=<?= time() ?>" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteBtn = document.getElementById('deleteListingBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const switchToEditBtn = document.getElementById('switchToDeleteEditBtn');
    const deleteForm = document.getElementById('deleteEquipmentForm');

    if (!deleteModal || !deleteBtn) return;

    const openDeleteModal = () => {
        deleteModal.classList.add('show-modal');
        document.body.style.overflow = 'hidden';
    };

    const closeDeleteModal = () => {
        deleteModal.classList.remove('show-modal');
        document.body.style.overflow = '';
    };

    deleteBtn.addEventListener('click', openDeleteModal);
    cancelDeleteBtn.addEventListener('click', closeDeleteModal);

    confirmDeleteBtn.addEventListener('click', () => {
        deleteForm.submit();
    });

    switchToEditBtn.addEventListener('click', () => {
        closeDeleteModal();
        // Trigger the Edit modal (initEditModal in equipment.js handles this)
        const editBtn = document.getElementById('editEquipmentBtn');
        if (editBtn) editBtn.click();
    });

    // Close on overlay click
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeDeleteModal();
    });
});
</script>
</body>
</html>
