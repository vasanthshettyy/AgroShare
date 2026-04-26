<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/EquipmentController.php';
requireAuth();

$userId = (int)$_SESSION['user_id'];

// Fetch KPI data
$totalEquipment = getUserEquipmentCount($conn, $userId);
$activeRentals  = getUserActiveRentalsCount($conn, $userId);
$poolCount      = getUserPoolCount($conn, $userId);
$trustScore     = getUserTrustScore($conn, $userId);

// Derive initials from full name for the avatar
$nameParts = explode(' ', $_SESSION['full_name']);
$initials   = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

// —— Greeting based on time of day ──────────────────────────
$hour     = (int) date('G');
$greeting = match(true) {
    $hour < 12  => 'Good Morning',
    $hour < 17  => 'Good Afternoon',
    default     => 'Good Evening',
};

// —— Persist-tab check injection flag ───────────────────────
$needsTabCheck = isset($_SESSION['persist']) && $_SESSION['persist'] === false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_NAME) ?> farmer dashboard — manage your equipment, bookings and pooling campaigns.">

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
</head>
<body>

<div class="app-layout">

    <!-- -- TOPBAR -- -->
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <!-- Hamburger (mobile only) -->
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
                <?= e($greeting) ?>, <strong><?= e($_SESSION['full_name']) ?></strong>
            </p>
        </div>

        <!-- Search bar -->
        <label class="topbar-search" for="topbar-search-input" aria-label="Search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="search" id="topbar-search-input" placeholder="Search anything…" autocomplete="off">
        </label>

        <div class="topbar-right" style="position: relative;">
            <!-- Notifications -->
            <button class="btn-icon" id="notifBtn" aria-label="Notifications" title="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notif-dot" id="notifDot" aria-hidden="true" style="display: none;"></span>
            </button>

            <!-- Notifications Dropdown -->
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">Notifications</div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Loading...</div>
                </div>
            </div>

            <!-- Avatar -->
            <div class="avatar" id="avatar-btn" role="button" tabindex="0"
                 title="Profile — <?= e($_SESSION['full_name']) ?>" aria-label="Open profile">
                <?= e($initials) ?>
            </div>
        </div>
    </header>

    <!-- -- SIDEBAR -- -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">

        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true">
                <!-- Leaf / seedling icon (inline SVG) -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>

        <!-- Nav links -->
        <nav class="sidebar-nav" aria-label="Site navigation">
            <span class="nav-section-label">Main</span>

            <a href="dashboard.php" class="nav-link active" aria-current="page">
                <!-- layout-dashboard icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="equipment-browse.php?mine=1" class="nav-link">
                <!-- tractor icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                    <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                </svg>
                <span>My Equipment</span>
            </a>

            <a href="my-bookings.php" class="nav-link">
                <!-- calendar-check icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>
                </svg>
                <span>My Bookings</span>
            </a>

            <span class="nav-section-label">Community</span>

            <span class="nav-link is-disabled" title="Coming soon" aria-disabled="true">
                <!-- users icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Pooling</span>
            </span>

            <a href="equipment-browse.php" class="nav-link">
                <!-- search icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <span>Browse</span>
            </a>

            <span class="nav-section-label">Account</span>

            <a href="javascript:void(0)" class="nav-link" id="profile-btn">
                <!-- user-circle icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>
                </svg>
                <span>Profile</span>
            </a>
        </nav>

        <!-- Logout -->
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link danger">
                <!-- log-out icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <!-- -- SIDEBAR OVERLAY -- -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- -- MAIN CONTENT -- -->
    <main class="main-content" role="main">

        <?= renderFlash() ?>

        <!-- —— Page Header ────────────────────────────────────────── -->
        <div class="page-header">
            <div class="page-header-text">
                <h1>Dashboard</h1>
                <p>Your farm activity at a glance, <?= e($nameParts[0]) ?>.</p>
            </div>
            <a href="equipment-browse.php?mine=1&action=list" class="btn-primary listEquipmentBtn" role="button">
                <!-- plus icon -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                List Equipment
            </a>
        </div>

        <!-- —— ROW 1: KPI Cards (4 columns) ──────────────────────── -->
        <section class="kpi-grid" aria-label="Key performance indicators">

            <!-- KPI 1 — Hero card (dark green) -->
            <a href="equipment-browse.php?mine=1" class="kpi-card kpi-hero" style="text-decoration: none; display: block; cursor: pointer;">
                <div class="kpi-header">
                    <span class="kpi-label">Total Equipment</span>
                    <div class="kpi-header-link" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"/>
                            <polyline points="7 7 17 7 17 17"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-value" data-target="<?= e($totalEquipment) ?>"><?= e($totalEquipment) ?></div>
                <div class="kpi-trend neutral">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Total listed
                </div>
                <!-- Decorative icon -->
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/>
                        <circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/>
                    </svg>
                </div>
            </a>

            <!-- KPI 2 — Active Rentals -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">Active Rentals</span>
                    <a href="my-bookings.php" class="kpi-header-link" aria-label="View rentals">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"/>
                            <polyline points="7 7 17 7 17 17"/>
                        </svg>
                    </a>
                </div>
                <div class="kpi-value" data-target="<?= e($activeRentals) ?>"><?= e($activeRentals) ?></div>
                <div class="kpi-trend neutral">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Ongoing
                </div>
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>
                    </svg>
                </div>
            </div>

            <!-- KPI 3 — Pool Campaigns -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">Pool Campaigns</span>
                    <span class="kpi-header-link is-disabled" title="Coming soon" aria-disabled="true" aria-label="View campaigns">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"/>
                            <polyline points="7 7 17 7 17 17"/>
                        </svg>
                    </span>
                </div>
                <div class="kpi-value" data-target="<?= e($poolCount) ?>"><?= e($poolCount) ?></div>
                <div class="kpi-trend neutral">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Joined
                </div>
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>

            <!-- KPI 4 — Trust Score -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <span class="kpi-label">Trust Score</span>
                    <span class="kpi-header-link is-disabled" title="Coming soon" aria-disabled="true" aria-label="View reviews">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"/>
                            <polyline points="7 7 17 7 17 17"/>
                        </svg>
                    </span>
                </div>
                <div class="kpi-value" data-target="<?= e(number_format($trustScore, 1)) ?>"><?= e(number_format($trustScore, 1)) ?></div>
                <div class="kpi-trend neutral">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    No reviews yet
                </div>
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
            </div>
        </section>

        <!-- —— ROW 2: Recent Activity + Chart ─────────────────── -->
        <div class="bento-row bento-row-2">

            <!-- Recent Activity Table -->
            <article class="bento-card" aria-label="Recent booking activity">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        Recent Activity
                    </h2>
                    <a href="my-bookings.php" class="card-action-link" aria-label="View all bookings">
                        View All
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>
                </div>

                <div class="activity-table-wrap">
                    <table class="activity-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Equipment</th>
                                <th scope="col">Type</th>
                                <th scope="col">Date</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" style="text-align:center;padding:36px 20px;">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                                         stroke="var(--accent-soft)" stroke-width="1.5"
                                         stroke-linecap="round" style="margin:0 auto 10px;"
                                         aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                    <p style="color:var(--text-subtle);font-size:0.82rem;font-weight:500;">No activity yet — bookings and rentals will appear here.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <!-- Rental Trend Chart -->
            <article class="bento-card" aria-label="Monthly rental trend chart">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                        Rental Activity
                    </h2>
                    <span style="font-size:0.75rem;color:var(--text-subtle);font-weight:500;">Last 7 months</span>
                </div>

                <div class="chart-container">
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <span class="legend-dot" style="background:var(--primary-action)"></span>
                            Bookings
                        </div>
                        <div class="chart-legend-item">
                            <span class="legend-dot" style="background:var(--secondary-action)"></span>
                            Earnings (₹00s)
                        </div>
                    </div>

                    <!-- JS will render an SVG area chart here -->
                    <!-- data-values = comma-separated monthly booking counts (placeholder) -->
                    <div class="chart-svg-wrap" id="chart-area"
                         data-values="0,0,0,0,0,0,0"
                         aria-label="Area chart — no rental data yet"
                         style="min-height:90px;">
                    </div>

                    <div class="chart-x-labels" aria-hidden="true">
                        <span>Sep</span><span>Oct</span><span>Nov</span>
                        <span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span>
                    </div>
                </div>
            </article>
        </div>

        <!-- —— ROW 3: Quick Actions (4 equal columns) ──────────── -->
        <section class="actions-grid" aria-label="Quick actions">

            <a href="equipment-browse.php" class="action-card">
                <div class="action-icon-wrap teal" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </div>
                <div class="action-body">
                    <h3>Browse Equipment</h3>
                    <p>Find tools and machinery available near you</p>
                </div>
                <div class="action-footer">
                    <div class="action-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </div>
            </a>

            <span class="action-card is-disabled" title="Coming soon" aria-disabled="true" style="cursor: not-allowed; opacity: 0.7;">
                <div class="action-icon-wrap amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="action-body">
                    <h3>Join a Pool</h3>
                    <p>Save money by buying seeds &amp; fertilizer in bulk</p>
                </div>
                <div class="action-footer">
                    <div class="action-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </div>
            </span>

            <a href="javascript:void(0)" class="action-card" id="edit-profile-quick-action">
                <div class="action-icon-wrap purple" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
                <div class="action-body">
                    <h3>Edit Profile</h3>
                    <p>Update your contact details and location info</p>
                </div>
                <div class="action-footer">
                    <div class="action-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </div>
            </a>
        </section>

    </main><!-- /.main-content -->
</div><!-- /.app-layout -->

<!-- Equipment Creation Modal -->
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
                        <input type="number" name="safety_deposit" id="eq-safety-deposit" class="form-input" placeholder="5000" min="0" step="100">
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

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<?php require_once __DIR__ . '/includes/viewer-reviews-modal.php'; ?>
<?php require_once __DIR__ . '/includes/user-public-profile-modal.php'; ?>
<script src="assets/js/dashboard.js?v=<?= time() ?>" defer></script>
</body>
</html>
