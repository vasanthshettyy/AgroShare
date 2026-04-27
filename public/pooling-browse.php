<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/PoolingController.php';

requireAuth();
$userId = (int)$_SESSION['user_id'];

// —— Common layout data ─────────────────────────────────────
$fullName  = trim($_SESSION['full_name'] ?? '');
$nameParts = explode(' ', $fullName);
$initials  = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : '?';
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

// —— Filters ───────────────────────────────────────────────
$status_filter = $_GET['status'] ?? 'open';
$filters = [
    'district' => $_GET['district'] ?? '',
    'status'   => ($status_filter === 'all') ? '' : $status_filter
];

$campaigns = getCampaigns($conn, $filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pooling Campaigns — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/equipment.css?v=<?= time() ?>">

    <script>
        (function(){
            var t = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <style>
        .page-header-premium {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .header-title-main {
            font-size: 2.25rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .header-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            margin-top: 4px;
        }

        /* Capsule Filter Row */
        .capsule-filter-row { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            overflow-x: auto; 
            padding-bottom: 0.5rem; 
            margin-bottom: 2.5rem; 
            scrollbar-width: none; 
        }
        .capsule-filter-row::-webkit-scrollbar { display: none; }

        .filter-capsule {
            padding: 0.55rem 1.4rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            border: 1.5px solid var(--border-color);
            background: var(--surface-color);
            color: var(--text-muted);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }
        .filter-capsule:hover {
            border-color: var(--text-subtle);
            color: var(--text-main);
            background: var(--surface-color-alt);
        }
        .filter-capsule.active {
            background: var(--primary-10);
            border-color: var(--primary-action);
            color: var(--primary-action);
        }

        .filter-divider {
            width: 1.5px;
            height: 24px;
            background: var(--border-color);
            margin: 0 0.5rem;
            flex-shrink: 0;
        }

        .district-input-wrapper {
            position: relative;
            flex-shrink: 0;
        }
        .district-input-premium {
            border-radius: 50px;
            padding: 0.55rem 1rem 0.55rem 2.4rem;
            border: 1.5px solid var(--border-color);
            background: var(--surface-color);
            color: var(--text-main);
            font-size: 0.85rem;
            font-weight: 600;
            width: 180px;
            transition: all 0.25s ease;
        }
        .district-input-premium:focus {
            width: 220px;
            border-color: var(--primary-action);
            background: var(--surface-color-alt);
            box-shadow: 0 0 0 4px rgba(76, 175, 120, 0.1);
        }
        .district-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .pooling-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .pool-card-premium {
            background: var(--surface-color);
            border: 1px solid rgba(76, 175, 120, 0.15);
            border-radius: 20px;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .pool-card-premium:hover {
            transform: translateY(-4px);
            border-color: var(--primary-action);
            background: var(--surface-color-alt);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        .card-badges-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .badge-status {
            background: rgba(76, 175, 120, 0.1);
            color: var(--primary-action);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-status.status-threshold_met {
            background: rgba(76, 175, 120, 0.2);
            color: #4ade80;
        }
        .badge-status.status-closed {
            background: rgba(255,255,255,0.05);
            color: var(--text-muted);
        }
        .badge-savings {
            background: var(--primary-action);
            color: #000;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .card-info-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .card-title-main {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
        }
        .card-item-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary-action);
        }
        .card-creator {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 2px;
        }

        .card-meta-row {
            display: flex;
            gap: 1.25rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
        }
        .meta-pill {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .progress-section {
            margin-top: 0.5rem;
        }
        .progress-bar-sleek {
            height: 6px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255,255,255,0.03);
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--primary-action);
            border-radius: 10px;
            transition: width 0.6s ease;
        }
        .progress-stats-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .stat-val-bold {
            color: #fff;
            font-weight: 800;
        }
    </style>
    </style>
</head>
<body>

<div class="app-layout">
    <!-- -- TOPBAR -- -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <p class="topbar-greeting">Community Pooling</p>
        </div>

        <!-- Search bar -->
        <label class="topbar-search" for="topbar-search-input" aria-label="Search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="search" id="topbar-search-input" placeholder="Search anything…" autocomplete="off">
        </label>

        <div class="topbar-right" style="position: relative;">
            <!-- Theme Toggle -->
            <button class="btn-icon theme-toggle" id="themeToggleBtn" aria-label="Toggle light/dark mode" title="Toggle theme">
                <svg class="theme-icon sun" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <svg class="theme-icon moon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>

            <!-- Notifications -->
            <button class="btn-icon" id="notifBtn" aria-label="Notifications" title="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

            <div class="avatar" id="avatar-btn"><?= e($initials) ?></div>
        </div>
    </header>

    <!-- -- SIDEBAR -- -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/><path d="M6 22c0-4 2-7 6-9"/></svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>
        <nav class="sidebar-nav">
            <span class="nav-section-label">Main</span>
            <a href="dashboard.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span>Dashboard</span></a>
            <a href="equipment-browse.php?mine=1" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg><span>My Equipment</span></a>
            <a href="my-bookings.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg><span>My Bookings</span></a>
            
            <span class="nav-section-label">Community</span>
            <a href="pooling-browse.php" class="nav-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Pooling</span></a>
            <a href="equipment-browse.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><span>Browse</span></a>

            <span class="nav-section-label">Account</span>
            <a href="javascript:void(0)" class="nav-link" id="profile-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>
                </svg>
                <span>Profile</span>
            </a>
        </nav>

        <!-- Logout -->
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

    <!-- -- MAIN CONTENT -- -->
    <main class="main-content">
        <?= renderFlash() ?>

        <div class="page-header-premium">
            <div class="header-left">
                <h2 class="header-title-main">Community Pooling</h2>
                <p class="header-subtitle">Join forces with nearby farmers to buy supplies in bulk and save money.</p>
            </div>
            <button type="button" class="btn-primary" id="openCreateModalBtn" style="padding: 0 2.5rem; border-radius: 50px; height: 50px; font-weight: 700;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" style="margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create Campaign
            </button>
        </div>

        <!-- Capsule Filters -->
        <form method="GET" action="pooling-browse.php" class="capsule-filter-row">
            <label class="filter-capsule <?= $status_filter === 'all' ? 'active' : '' ?>">
                <input type="radio" name="status" value="all" style="display: none;" onchange="this.form.submit()" <?= $status_filter === 'all' ? 'checked' : '' ?>>
                All
            </label>
            <label class="filter-capsule <?= $status_filter === 'open' ? 'active' : '' ?>">
                <input type="radio" name="status" value="open" style="display: none;" onchange="this.form.submit()" <?= $status_filter === 'open' ? 'checked' : '' ?>>
                Open
            </label>
            <label class="filter-capsule <?= $status_filter === 'threshold_met' ? 'active' : '' ?>">
                <input type="radio" name="status" value="threshold_met" style="display: none;" onchange="this.form.submit()" <?= $status_filter === 'threshold_met' ? 'checked' : '' ?>>
                Threshold Met
            </label>

            <div class="filter-divider"></div>

            <div class="district-input-wrapper">
                <svg class="district-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <input type="text" name="district" class="district-input-premium" placeholder="Enter district..." value="<?= htmlspecialchars($_GET['district'] ?? '') ?>">
            </div>
            
            <?php if (!empty($_GET['district'])): ?>
                <a href="pooling-browse.php" class="btn-clear-premium" style="margin-left: 0.5rem;">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($campaigns)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-subtle)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <h3>No campaigns found</h3>
                <p>Try changing your filters or start a new bulk-buy campaign.</p>
            </div>
        <?php else: ?>
            <div class="pooling-grid">
                <?php foreach ($campaigns as $camp): 
                    $progress = ($camp['target_quantity'] > 0) ? min(100, round(($camp['current_quantity'] / $camp['target_quantity']) * 100)) : 0;
                ?>
                    <a href="pooling-detail.php?id=<?= $camp['id'] ?>" class="pool-card-premium">
                        <div class="card-badges-row">
                            <span class="badge-status status-<?= $camp['status'] ?>">
                                <?= str_replace('_', ' ', $camp['status']) ?>
                            </span>
                        </div>

                        <div class="card-info-stack">
                            <h3 class="card-title-main"><?= e($camp['title']) ?></h3>
                            <div class="card-item-name"><?= e($camp['item_name']) ?></div>
                            <div class="card-creator">By <?= e($camp['creator_name']) ?></div>
                        </div>

                        <div style="font-size: 0.95rem; font-weight: 700; color: #fff;">
                            Offering: <span style="color: var(--secondary-action);">₹<?= number_format($camp['offering_price'], 0) ?></span> per <?= e($camp['unit']) ?>
                        </div>

                        <div class="card-meta-row">
                            <div class="meta-pill">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?= e($camp['district']) ?>
                            </div>
                            <div class="meta-pill">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <?= date('d M', strtotime($camp['deadline'])) ?>
                            </div>
                        </div>

                        <div class="progress-section">
                            <div class="progress-bar-sleek">
                                <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.75rem 1rem; margin-top: 1rem;">
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Target Needed</div>
                                    <div style="font-size: 1.15rem; font-weight: 800; color: white;"><?= number_format($camp['target_quantity']) ?> <span style="font-size: 0.55em; opacity: 0.75; font-weight: 500; text-transform: lowercase; margin-left: 2px;"><?= htmlspecialchars($camp['unit']) ?></span></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.75rem; color: var(--primary-action); text-transform: uppercase; letter-spacing: 0.5px;">Already Filled</div>
                                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--primary-action);"><?= number_format($camp['current_quantity']) ?> <span style="font-size: 0.55em; opacity: 0.75; font-weight: 500; text-transform: lowercase; margin-left: 2px;"><?= htmlspecialchars($camp['unit']) ?></span></div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Create Campaign Modal -->
<div id="createCampaignModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 700px;">
        <button type="button" class="modal-close" id="closeCreateModalBtn" aria-label="Close modal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="modal-header">
            <h2>Create Supply Campaign</h2>
            <p>Broadcast your need for supplies and set the price you're willing to pay.</p>
        </div>
        
        <div id="createError" style="display: none; color: var(--danger); background: rgba(225, 29, 72, 0.05); padding: 1rem; border-radius: 10px; border: 1px solid rgba(225, 29, 72, 0.2); margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 600;"></div>

        <form id="createCampaignForm" class="eq-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Section 1: Campaign Info -->
            <div class="form-section">
                <h3 class="section-label">Campaign Info</h3>
                <div class="form-group">
                    <label class="form-label">Campaign Title</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. Seeking 500kg Wheat Seeds" required maxlength="150">
                </div>
                
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="item_name" class="form-input" placeholder="e.g. Needs 50kg of Urea Fertilizer" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" id="campUnit" class="form-input" placeholder="e.g. 50kg bag, litre" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Explain what you need and the quality requirements..."></textarea>
                </div>
            </div>

            <!-- Section 2: Pricing & Threshold -->
            <div class="form-section">
                <h3 class="section-label">Pricing & Threshold</h3>
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Offering Price per unit (₹)</label>
                        <input type="number" name="offering_price" class="form-input" min="1" step="1" required placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Target Quantity Needed</label>
                        <input type="number" name="target_quantity" class="form-input" min="1" required placeholder="e.g. 100">
                    </div>
                </div>
                <div class="form-group" style="margin-top: 1.25rem;">
                    <label class="form-label">Minimum contribution per farmer</label>
                    <input type="number" name="min_contribution" class="form-input" min="1" value="1" required>
                    <p style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 6px;">Smallest amount a single person can contribute.</p>
                </div>
            </div>

            <!-- Section 3: Location & Deadline -->
            <div class="form-section" style="border-bottom: none;">
                <h3 class="section-label">Location & Deadline</h3>
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">District</label>
                        <input type="text" name="district" class="form-input" placeholder="e.g. Dharwad" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" class="form-input" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 1.5rem;">
                <button type="button" class="btn-secondary" id="cancelCreateBtn">Cancel</button>
                <button type="submit" class="btn-primary" id="createCampaignBtn">Create Campaign</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<?php require_once __DIR__ . '/includes/viewer-reviews-modal.php'; ?>
<?php require_once __DIR__ . '/includes/user-public-profile-modal.php'; ?>

<script src="assets/js/theme-toggle.js" defer></script>
<script src="assets/js/dashboard.js" defer></script>
<script>
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('createCampaignModal');
    const openBtn = document.getElementById('openCreateModalBtn');
    const closeBtn = document.getElementById('closeCreateModalBtn');
    const cancelBtn = document.getElementById('cancelCreateBtn');
    const form = document.getElementById('createCampaignForm');

    // Modal control
    const openModal = () => {
        modal.classList.add('show-modal');
        document.body.style.overflow = 'hidden';
    };
    const closeModal = () => {
        modal.classList.remove('show-modal');
        document.body.style.overflow = '';
        form.reset();
    };

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // AJAX submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = document.getElementById('createCampaignBtn');
        const errDiv = document.getElementById('createError');
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        errDiv.style.display = 'none';

        try {
            const formData = new FormData(form);
            const body = new URLSearchParams(formData);

            const res = await fetch('api/pooling-create.php', { method: 'POST', body });
            const data = await res.json();
            
            if (data.success) {
                window.location.href = 'pooling-detail.php?id=' + data.id;
            } else {
                errDiv.textContent = data.message || 'Error creating campaign.';
                errDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Campaign';
            }
        } catch (e) {
            errDiv.textContent = 'Connection error. Please try again.';
            errDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Campaign';
        }
    });
});
</script>
</body>
</html>
