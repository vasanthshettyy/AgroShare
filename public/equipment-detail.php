<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/EquipmentController.php';
requireAuth();

// ── Common layout data ─────────────────────────────────────
$fullName  = trim($_SESSION['full_name'] ?? '');
$nameParts = explode(' ', $fullName);
$initials  = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : '?';
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

$needsTabCheck = isset($_SESSION['persist']) && $_SESSION['persist'] === false;

// ── Load equipment ─────────────────────────────────────────
$equipmentId = (int)($_GET['id'] ?? 0);
if ($equipmentId <= 0) {
    setFlash('error', 'Equipment not found.');
    header('Location: equipment-browse.php');
    exit();
}

$eq = getEquipmentById($conn, $equipmentId);
if (!$eq) {
    setFlash('error', 'Equipment not found.');
    header('Location: equipment-browse.php');
    exit();
}

$isOwner = (int)$eq['owner_id'] === (int)$_SESSION['user_id'];
$images  = $eq['images'] ? json_decode($eq['images'], true) : [];

// ── Handle delete ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid form submission.');
        header('Location: equipment-detail.php?id=' . $equipmentId);
        exit();
    }

    if ($_POST['action'] === 'delete' && $isOwner) {
        $deleted = deleteEquipment($conn, $equipmentId, (int)$_SESSION['user_id']);
        if ($deleted) {
            setFlash('success', 'Equipment deleted successfully.');
            header('Location: equipment-browse.php?mine=1');
            exit();
        } else {
            setFlash('error', 'Could not delete equipment.');
        }
    }

    header('Location: equipment-detail.php?id=' . $equipmentId);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($eq['title']) ?> — <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e($eq['title']) ?> — <?= e(ucfirst($eq['category'])) ?> available for rent on <?= e(APP_NAME) ?>.">

    <script>
        document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    <?php if ($needsTabCheck): ?>
    <script>if (!sessionStorage.getItem('agroshare_tab')) window.location.href = 'logout.php';</script>
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/equipment.css?v=<?= time() ?>">
</head>
<body>

<div class="app-layout">

    <!-- -- TOPBAR -------------------------------------------- -->
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation menu" aria-expanded="false" aria-controls="sidebar"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <p class="topbar-greeting">Equipment Details</p>
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
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Loading...</div>
                </div>
            </div>
            
            <div class="avatar" id="avatar-btn" role="button" tabindex="0" title="Profile — <?= e($_SESSION['full_name']) ?>" aria-label="Open profile"><?= e($initials) ?></div>
        </div>
    </header>

    <!-- -- SIDEBAR ------------------------------------------- -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/><path d="M6 22c0-4 2-7 6-9"/></svg></div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>
        <nav class="sidebar-nav" aria-label="Site navigation">
            <span class="nav-section-label">Main</span>
            <a href="dashboard.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span>Dashboard</span></a>
            <a href="equipment-browse.php?mine=1" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg><span>My Equipment</span></a>
            <a href="my-bookings.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg><span>My Bookings</span></a>
            <span class="nav-section-label">Community</span>
            <a href="#" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Pooling</span></a>
            <a href="equipment-browse.php" class="nav-link active" aria-current="page"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><span>Browse</span></a>
            <a href="#" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span>Reviews</span></a>
            <span class="nav-section-label">Account</span>
            <a href="#" class="nav-link" id="profile-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg><span>Profile</span></a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="nav-link danger"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Log Out</span></a></div>
    </aside>

    <!-- -- SIDEBAR OVERLAY (mobile backdrop) ---------------- -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- -- MAIN CONTENT -------------------------------------- -->
    <main class="main-content" role="main">

        <?= renderFlash() ?>

        <div class="page-header">
            <div class="page-header-text">
                <a href="equipment-browse.php" class="btn-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Browse
            </a>
                <h1><?= e($eq['title']) ?></h1>
            </div>
            <?php if ($isOwner): ?>
            <div class="owner-actions">
                <button class="btn-toggle-avail <?= $eq['is_available'] ? 'is-available' : 'is-unavailable' ?>"
                        id="toggleAvailBtn" data-id="<?= $equipmentId ?>"
                        aria-label="Toggle availability">
                    <span class="toggle-dot"></span>
                    <span class="toggle-label"><?= $eq['is_available'] ? 'Available' : 'Unavailable' ?></span>
                </button>
                <button type="button" class="btn-secondary" id="editEquipmentBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </button>
                <form method="POST" action="equipment-detail.php?id=<?= $equipmentId ?>" class="inline-form"
                      onsubmit="return confirm('Are you sure you want to delete this equipment? This action cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn-danger">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Delete
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-layout-grid">

            <!-- ── LEFT COLUMN: Main Info ────────────────────────── -->
            <div class="detail-main-col">
                <!-- Image Gallery -->
                <div class="detail-gallery glass-card">
                    <?php if (!empty($images)): ?>
                    <div class="gallery-main">
                        <img src="<?= e($images[0]) ?>" alt="<?= e($eq['title']) ?>" id="galleryMainImg" class="gallery-main-img">
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($images as $i => $img): ?>
                        <button class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                                data-src="<?= e($img) ?>" aria-label="View photo <?= $i+1 ?>">
                            <img src="<?= e($img) ?>" alt="Photo <?= $i+1 ?>" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="gallery-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--accent-soft)" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p>No photos uploaded</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Specs -->
                <div class="detail-section glass-card">
                    <h2 class="detail-section-title">Specifications</h2>
                    <table class="specs-table">
                        <tr><th>Category</th><td><?= e(ucfirst(str_replace('_', ' ', $eq['category']))) ?></td></tr>
                        <tr><th>Condition</th><td><span class="condition-badge condition-<?= e($eq['condition']) ?>"><?= e(ucfirst($eq['condition'])) ?></span></td></tr>
                        <tr><th>Operator</th><td><?= $eq['includes_operator'] ? '✅ Included' : '—' ?></td></tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge <?= $eq['is_available'] ? 'badge-active' : 'badge-cancelled' ?>" id="statusBadge">
                                    <?= $eq['is_available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </td>
                        </tr>
                        <tr><th>Location</th><td><?= e($eq['location_village']) ?>, <?= e($eq['location_district']) ?></td></tr>
                        <tr><th>Listed</th><td><?= date('d M Y', strtotime($eq['created_at'])) ?></td></tr>
                    </table>
                </div>

                <!-- Description -->
                <div class="detail-section glass-card">
                    <h2 class="detail-section-title">Description</h2>
                    <p class="detail-description"><?= nl2br(e($eq['description'])) ?></p>
                </div>

                <!-- Owner Info -->
                <div class="detail-section glass-card">
                    <h2 class="detail-section-title">Owner</h2>
                    <div class="owner-info-card">
                        <div class="owner-avatar">
                            <?= strtoupper(substr(trim($eq['owner_name'] ?? ''), 0, 1)) ?: '?' ?>
                        </div>
                        <div class="owner-details">
                            <strong><?= e($eq['owner_name']) ?></strong>
                            <?php if ($eq['owner_verified']): ?>
                            <span class="verified-badge" title="Verified farmer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="var(--secondary-action)" stroke="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Verified
                            </span>
                            <?php endif; ?>
                            <span class="owner-location"><?= e($eq['owner_village']) ?>, <?= e($eq['owner_district']) ?></span>
                            <?php if ($eq['owner_trust'] > 0): ?>
                            <span class="owner-trust">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="var(--amber)" stroke="none" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <?= number_format($eq['owner_trust'], 1) ?> Trust Score
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT COLUMN: Booking Sidebar ────────────────── -->
            <div class="detail-side-col">
                
                <!-- Pricing Card -->
                <div class="detail-section glass-card">
                    <h2 class="detail-section-title">Pricing</h2>
                    <div class="pricing-grid">
                        <div class="pricing-card">
                            <span class="pricing-label">Per Hour</span>
                            <span class="pricing-value">₹<?= number_format($eq['price_per_hour'], 0) ?></span>
                        </div>
                        <div class="pricing-card pricing-card-highlight">
                            <span class="pricing-label">Per Day</span>
                            <span class="pricing-value">₹<?= number_format($eq['price_per_day'], 0) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Interactive Booking Calendar -->
                <div class="calendar-widget glass-card">
                    <div class="calendar-header">
                        <span class="calendar-month-year" id="calMonthYear">March 2026</span>
                        <div class="calendar-nav">
                            <button class="calendar-nav-btn" id="calPrev" title="Previous Month">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <button class="calendar-nav-btn" id="calNext" title="Next Month">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="calendar-grid" id="calGrid">
                        <!-- Labels -->
                        <div class="calendar-day-label">Mo</div>
                        <div class="calendar-day-label">Tu</div>
                        <div class="calendar-day-label">We</div>
                        <div class="calendar-day-label">Th</div>
                        <div class="calendar-day-label">Fr</div>
                        <div class="calendar-day-label">Sa</div>
                        <div class="calendar-day-label">Su</div>
                        <!-- Days injected via JS -->
                    </div>

                    <div id="calHint" class="calendar-availability-hint" style="display: none;">
                        ✨ <span id="calHintText">Available for up to 5 consecutive days</span>
                    </div>

                    <div id="est-result" class="est-result" style="display: none; margin-top: 1.25rem; padding: 1.25rem; background: var(--primary-10); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; color: var(--text-muted);">Estimated Total</span>
                            <strong id="est-total" style="font-size: 1.35rem; color: var(--primary-action);">₹0</strong>
                        </div>
                        <p id="est-breakdown" style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 4px;"></p>
                    </div>

                    <input type="hidden" id="est-start" value="">
                    <input type="hidden" id="est-end" value="">

                    <?php if (!$isOwner && $eq['is_available']): ?>
                    <button class="btn-primary btn-book-cta" id="btnBookNow" disabled title="Select dates to book">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        Book Now
                    </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </main>
</div><!-- /.app-layout -->

<!-- Edit Equipment Modal -->
<div id="editEquipmentModal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" id="editModalCloseBtn" aria-label="Close modal">&times;</button>
        <div class="modal-header">
            <h2>Edit Equipment</h2>
            <p>Update details for your <?= e($eq['title']) ?>.</p>
        </div>
        <form id="editEquipmentForm" class="eq-form" method="POST" action="api/edit-equipment.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" id="editCsrfToken" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="id" value="<?= (int)$eq['id'] ?>">
            
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
                    Equipment Details
                </h2>
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
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Pricing
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-eq-price-hour" class="form-label">Price per Hour (₹)</label>
                        <input type="number" name="price_per_hour" id="edit-eq-price-hour" class="form-input" value="<?= (float)$eq['price_per_hour'] ?>" min="0" step="50" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-eq-price-day" class="form-label">Price per Day (₹)</label>
                        <input type="number" name="price_per_day" id="edit-eq-price-day" class="form-input" value="<?= (float)$eq['price_per_day'] ?>" min="0" step="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="includes_operator" value="1" class="form-checkbox" <?= $eq['includes_operator'] ? 'checked' : '' ?>>
                            <span class="checkbox-visual"></span>
                            <span>Includes Operator</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Location
                </h2>
                <div class="form-grid">
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
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Photos
                </h2>
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
                    <input type="file" name="images[]" id="edit-eq-images" accept="image/jpeg,image/png,image/webp" multiple class="image-upload-input">
                    <div class="upload-placeholder">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><path d="m21 11-3-3a2 2 0 0 0-2.828 0l-8.086 8.086"/><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg>
                        <p style="font-size:0.8rem;">Add more photos</p>
                    </div>
                    <div class="image-preview-grid" id="editImagePreviewGrid"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" id="editCancelBtn">Cancel</button>
                <button type="submit" class="btn-primary" id="editSubmitBtn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<script src="assets/js/dashboard.js" defer></script>
<script src="assets/js/equipment.js?v=<?= time() ?>" defer></script>
<script src="assets/js/calendar.js?v=<?= time() ?>" defer></script>
</body>
</html>
