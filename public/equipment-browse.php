<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/EquipmentController.php';

$isLoggedIn = isset($_SESSION['user_id']);

if (isset($_GET['mine']) && $_GET['mine'] === '1' && !$isLoggedIn) {
    header('Location: login.php');
    exit();
}

// —— Common layout data ─────────────────────────────────────
$fullName  = trim($_SESSION['full_name'] ?? '');
$nameParts = explode(' ', $fullName);
$initials  = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : '?';
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

$needsTabCheck = isset($_SESSION['persist']) && $_SESSION['persist'] === false && $isLoggedIn;

// —— Filters from query string ──────────────────────────────
$filters = [];
if (!empty($_GET['category']))     $filters['category']     = $_GET['category'];
if (!empty($_GET['district']))     $filters['district']     = $_GET['district'];
if (!empty($_GET['max_price']))    $filters['max_price']    = $_GET['max_price'];
if (!empty($_GET['has_operator'])) $filters['has_operator'] = true;
if (isset($_GET['mine']) && $_GET['mine'] === '1') {
    $filters['owner_id'] = $_SESSION['user_id'];
    $filters['show_all'] = true; // Show unavailable too for the owner
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$results = browseEquipment($conn, $filters, $page);
$items   = $results['items'];
$isMyEquipment = isset($_GET['mine']) && $_GET['mine'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isMyEquipment ? 'My Equipment' : 'Browse Equipment' ?> — <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_NAME) ?> — find and rent agricultural equipment near you.">

    <script>
        (function(){
            var t = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <?php if ($needsTabCheck): ?>
    <script>
        if (!sessionStorage.getItem('agroshare_tab')) window.location.href = 'logout.php';
    </script>
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/equipment.css?v=<?= time() ?>">
</head>
<body>

<div class="app-layout">

    <!-- -- TOPBAR -------------------------------------------- -->
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation menu" aria-expanded="false"
                    aria-controls="sidebar">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6"  x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            <p class="topbar-greeting">
                <?= $isMyEquipment ? 'My Equipment' : 'Browse Equipment' ?>
            </p>
        </div>

        <label class="topbar-search" for="topbar-search-input" aria-label="Search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="search" id="topbar-search-input" placeholder="Search equipment…" autocomplete="off">
        </label>

        <div class="topbar-right" style="position: relative;">
            <?php if ($isLoggedIn): ?>
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

            <div class="avatar" id="avatar-btn" role="button" tabindex="0"
                 title="Profile — <?= e($fullName) ?>" aria-label="Open profile">
                <?= e($initials) ?>
            </div>
            <?php else: ?>
            <a href="login.php" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 8px; text-decoration: none;">Log In</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- -- SIDEBAR ------------------------------------------- -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>

        <nav class="sidebar-nav" aria-label="Site navigation">
            <span class="nav-section-label">Main</span>

            <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="equipment-browse.php?mine=1" class="nav-link <?= $isMyEquipment ? 'active' : '' ?>" <?= $isMyEquipment ? 'aria-current="page"' : '' ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                    <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                </svg>
                <span>My Equipment</span>
            </a>

            <a href="my-bookings.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>
                </svg>
                <span>My Bookings</span>
            </a>
            <?php endif; ?>

            <span class="nav-section-label">Community</span>
<a href="pooling-browse.php" class="nav-link">
    <!-- users icon -->
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>
    <span>Pooling</span>
</a>

            <a href="equipment-browse.php" class="nav-link <?= !$isMyEquipment ? 'active' : '' ?>" <?= !$isMyEquipment ? 'aria-current="page"' : '' ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <span>Browse</span>
            </a>

            <span class="nav-section-label">Account</span>

            <?php if ($isLoggedIn): ?>
            <a href="javascript:void(0)" class="nav-link" id="profile-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>
                </svg>
                <span>Profile</span>
            </a>
            <?php endif; ?>
        </nav>

        <?php if ($isLoggedIn): ?>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Log Out</span>
            </a>
        </div>
        <?php endif; ?>
    </aside>

    <!-- -- SIDEBAR OVERLAY (mobile backdrop) ---------------- -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- -- MAIN CONTENT -------------------------------------- -->
    <main class="main-content" role="main">

        <?= renderFlash() ?>

        <a href="dashboard.php" class="btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Dashboard
        </a>

        <!-- —— Page Header ────────────────────────────────────────── -->
        <div class="page-header">
            <div class="page-header-text">
                <h1><?= $isMyEquipment ? 'My Equipment' : 'Browse Equipment' ?></h1>
                <p><?= $isMyEquipment ? 'Manage your listed equipment below.' : 'Find tools and machinery available near you.' ?></p>
            </div>
            <?php if ($isMyEquipment): ?>
            <button type="button" class="btn-primary listEquipmentBtn" role="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                List Equipment
            </button>
            <?php endif; ?>
        </div>

        <!-- —— Filter Bar ────────────────────────────────────────── -->
        <?php if (!$isMyEquipment): ?>
        <form class="filter-bar" method="GET" action="equipment-browse.php" aria-label="Filter equipment">
            <div class="filter-group">
                <label for="filter-category" class="filter-label">Category</label>
                <div class="filter-input-wrapper">
                    <svg class="filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <select name="category" id="filter-category" class="form-input form-select filter-select">
                        <option value="">All Categories</option>
                        <?php foreach (['tractor','harvester','seeder','sprayer','plough','chain_saw','rotavator','cultivator','thresher','water_pump','earth_auger','baler','trolley','brush_cutter','power_tiller','chaff_cutter','other'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $cat)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-group">
                <label for="filter-district" class="filter-label">District</label>
                <div class="filter-input-wrapper">
                    <svg class="filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <input type="text" name="district" id="filter-district" class="form-input filter-input"
                           placeholder="e.g. Dharwad" value="<?= e($filters['district'] ?? '') ?>">
                </div>
            </div>

            <div class="filter-group">
                <label for="filter-price" class="filter-label">Max ₹/Day</label>
                <div class="filter-input-wrapper">
                    <svg class="filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 3h12"></path><path d="M6 8h12"></path><path d="m6 13 8.5 8"></path><path d="M6 13a6 6 0 0 0 0-10"></path>
                    </svg>
                    <input type="number" name="max_price" id="filter-price" class="form-input filter-input"
                           placeholder="5000" min="0" step="100" value="<?= e($filters['max_price'] ?? '') ?>">
                </div>
            </div>

            <div class="filter-group filter-toggle-group">
                <span class="filter-label">Includes</span>
                <label class="custom-toggle" for="filter-operator">
                    <input type="checkbox" name="has_operator" id="filter-operator" value="1" 
                           <?= !empty($filters['has_operator']) ? 'checked' : '' ?>>
                    <div class="toggle-slider"></div>
                    <span class="toggle-label">Operator</span>
                </label>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    Search
                </button>

                <?php if (!empty($filters)): ?>
                <a href="equipment-browse.php" class="btn-clear-filters" title="Reset Filters">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>

        <!-- —— Equipment Grid ────────────────────────────────────── -->
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--accent-soft)"
                 stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
            </svg>
            <h3><?= $isMyEquipment ? 'No equipment listed yet' : 'No equipment found' ?></h3>
            <p><?= $isMyEquipment
                ? 'Start sharing — list your first piece of equipment.'
                : 'Try adjusting your filters or search a different district.' ?></p>
            <?php if ($isMyEquipment): ?>
            <button type="button" class="btn-primary listEquipmentBtn" role="button" style="margin-top:1rem;">List Equipment</button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="equipment-grid" aria-label="Equipment listings">
            <?php foreach ($items as $index => $eq):
                $images    = $eq['images'] ? json_decode($eq['images'], true) : [];
                $thumbnail = !empty($images) ? e($images[0]) : '';
                $isOwner   = $isLoggedIn && (int)$eq['owner_id'] === (int)$_SESSION['user_id'];
                $detailPage = $isOwner ? 'my-equipment-detail.php' : 'equipment-detail.php';
            ?>
            <a href="<?= $detailPage ?>?id=<?= (int)$eq['id'] ?>" class="eq-card" style="animation-delay: <?= 0.06 * $index ?>s;">
                <div class="eq-card-image">
                    <?php if ($thumbnail): ?>
                    <img src="<?= $thumbnail ?>" alt="<?= e($eq['title']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="eq-card-placeholder" aria-hidden="true">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round">
                            <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                            <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                    <!-- Availability badge overlay -->
                    <?php if (!$eq['is_available']): ?>
                    <span class="eq-card-badge unavailable">Off-market</span>
                    <?php else: ?>
                    <span class="eq-card-badge available">Listed</span>
                    <?php endif; ?>
                </div>

                <div class="eq-card-body">
                    <span class="eq-card-category"><?= e(ucfirst($eq['category'])) ?></span>
                    <h3 class="eq-card-title"><?= e($eq['title']) ?></h3>
                    <p class="eq-card-location">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?= e($eq['location_village']) ?>, <?= e($eq['location_district']) ?>
                    </p>
                    <div class="eq-card-pricing">
                        <span class="eq-card-price">₹<?= number_format($eq['price_per_day'], 0) ?><small>/day</small></span>
                    </div>
                </div>

                <div class="eq-card-footer">
                    <div class="eq-card-owner">
                        <span class="eq-card-owner-name"><?= e($eq['owner_name']) ?></span>
                        <?php if ($eq['owner_trust'] > 0): ?>
                        <span class="eq-card-trust">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="var(--amber)" stroke="none" aria-hidden="true">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <?= number_format($eq['owner_trust'], 1) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($eq['includes_operator']): ?>
                    <span class="eq-card-operator-badge">+ Operator</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- —— Pagination ────────────────────────────────────────── -->
        <?php if ($results['totalPages'] > 1): ?>
        <nav class="pagination" aria-label="Page navigation">
            <?php
            $baseUrl = 'equipment-browse.php?' . http_build_query(array_diff_key($_GET, ['page' => '']));
            ?>
            <?php if ($results['page'] > 1): ?>
            <a href="<?= e($baseUrl . '&page=' . ($results['page'] - 1)) ?>" class="page-link" aria-label="Previous page">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $results['totalPages']; $p++): ?>
            <a href="<?= e($baseUrl . '&page=' . $p) ?>"
               class="page-link <?= $p === $results['page'] ? 'active' : '' ?>"
               <?= $p === $results['page'] ? 'aria-current="page"' : '' ?>>
                <?= $p ?>
            </a>
            <?php endfor; ?>
            <?php if ($results['page'] < $results['totalPages']): ?>
            <a href="<?= e($baseUrl . '&page=' . ($results['page'] + 1)) ?>" class="page-link" aria-label="Next page">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div><!-- /.app-layout -->
<div id="equipmentModal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Close modal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="modal-header">
            <h2>List New Equipment</h2>
            <p>Add a tool or machine to share with farmers nearby.</p>
        </div>
        <form id="equipmentForm" class="eq-form" method="POST" action="api/create-equipment.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?= generateCsrfToken() ?>">
            
            <!-- Equipment Details Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
                    Equipment Details
                </h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="eq-title" class="form-label">Equipment Title</label>
                        <input type="text" name="title" id="eq-title" class="form-input" placeholder="e.g. Mahindra 475 DI Tractor" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label for="eq-category" class="form-label">Category</label>
                        <select name="category" id="eq-category" class="form-input form-select" required>
                            <option value="">Select category…</option>
                            <option value="tractor">Tractor</option>
                            <option value="harvester">Harvester</option>
                            <option value="seeder">Seeder</option>
                            <option value="sprayer">Sprayer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="eq-condition" class="form-label">Condition</label>
                        <select name="condition" id="eq-condition" class="form-input form-select" required>
                            <option value="">Select condition…</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="eq-description" class="form-label">Description</label>
                        <textarea name="description" id="eq-description" class="form-input form-textarea" rows="4" placeholder="Describe your equipment, its features, attachments, and usage history…" required></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Pricing
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="eq-price-day" class="form-label">Price per Day (₹)</label>
                        <input type="number" name="price_per_day" id="eq-price-day" class="form-input" placeholder="3000" min="0" step="100" required>
                    </div>
                    <div class="form-group">
                        <label for="eq-safety-deposit" class="form-label">Safety Deposit (₹)</label>
                        <input type="number" name="safety_deposit" id="eq-safety-deposit" class="form-input" placeholder="0" min="0" step="100">
                    </div>
                    <div class="form-group full-width" style="padding-bottom: 0.8rem;">
                        <label class="form-checkbox-label" style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" name="includes_operator" value="1" class="form-checkbox" style="display: none;">
                            <span class="checkbox-visual" style="margin: 0;"></span>
                            <span>Includes Operator</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Location Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Location
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="eq-village" class="form-label">Village</label>
                        <input type="text" name="location_village" id="eq-village" class="form-input" placeholder="e.g. Kundgol" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="eq-district" class="form-label">District</label>
                        <input type="text" name="location_district" id="eq-district" class="form-input" placeholder="e.g. Dharwad" maxlength="100" required>
                    </div>
                </div>
            </div>
            
            <!-- Photos Section -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Photos (up to 5)
                </h2>
                <div class="image-upload-zone" id="imageUploadZone">
                    <input type="file" name="images[]" id="eq-images" accept="image/jpeg,image/png,image/webp" multiple class="image-upload-input" style="display: none;">
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                            <path d="m21 11-3-3a2 2 0 0 0-2.828 0l-8.086 8.086"/>
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                        </svg>
                        <p>Drag & drop images here, or <strong>click to browse</strong></p>
                        <span>JPEG, PNG, WebP — max 2MB each</span>
                    </div>
                    <div class="image-preview-grid" id="imagePreviewGrid"></div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions" style="margin-top: 2rem; border: none; padding: 0; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true" style="margin-right: 4px;">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    List Equipment
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($isLoggedIn): ?>
    <?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
    <?php require_once __DIR__ . '/includes/viewer-reviews-modal.php'; ?>
    <?php require_once __DIR__ . '/includes/user-public-profile-modal.php'; ?>
<?php endif; ?>
<script src="assets/js/dashboard.js" defer></script>
<script src="assets/js/equipment.js?v=<?= time() ?>" defer></script>
</body>
</html>
