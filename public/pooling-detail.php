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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: pooling-browse.php');
    exit;
}

$campaign = getCampaignById($conn, $id);
if (!$campaign) {
    header('Location: pooling-browse.php');
    exit;
}

$pledges = getPledges($conn, $id);
$userPledge = getUserPledge($conn, $id, $userId);

// Calculations
$progress = ($campaign['target_quantity'] > 0) ? min(100, round(($campaign['current_quantity'] / $campaign['target_quantity']) * 100)) : 0;
$qty_remaining = max(0, $campaign['target_quantity'] - $campaign['current_quantity']);
$days_left = max(0, (int)ceil((strtotime($campaign['deadline']) - time()) / 86400));
$is_expired = strtotime($campaign['deadline']) < time();
$can_pledge = ($campaign['status'] === 'open') && !$is_expired && !$userPledge;
$is_creator = ($campaign['creator_id'] === $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($campaign['title']) ?> — Supply Detail</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/equipment.css">
    <style>
        .campaign-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2.5rem;
            max-width: 1200px;
            margin: 2rem auto;
            align-items: start;
        }
        @media (max-width: 992px) {
            .campaign-grid { grid-template-columns: 1fr; }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-action);
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease;
        }
        .back-link:hover { transform: translateX(-4px); }

        .campaign-header-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .status-badge-premium {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-badge-premium.status-open { background: rgba(76, 175, 120, 0.1); color: var(--primary-action); }
        .status-badge-premium.status-threshold_met { background: rgba(76, 175, 120, 0.2); color: #4ade80; }
        .status-badge-premium.status-closed { background: rgba(255,255,255,0.05); color: var(--text-muted); }

        .metadata-row-premium {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: var(--text-muted);
            font-size: 0.92rem;
            font-weight: 500;
            margin-top: 1.25rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        .meta-item-premium {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .description-card {
            background: var(--surface-color);
            border: 1px solid rgba(76, 175, 120, 0.15);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 2.5rem 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .section-mini-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 0.75rem;
            display: block;
        }

        /* Specs Strip */
        .specs-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
            padding: 2rem 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 3rem;
        }
        .spec-strip-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .spec-strip-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .spec-strip-label svg { color: var(--primary-action); }
        .spec-strip-value {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        /* Right Column Cards */
        .premium-card-right {
            background: var(--surface-color);
            border: 1px solid rgba(76, 175, 120, 0.15);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .creator-block {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .creator-avatar-init {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: var(--glow-primary);
        }
        .creator-info-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .creator-name-premium {
            font-weight: 800;
            color: #fff;
            font-size: 1rem;
        }
        .creator-since {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        .btn-contact-creator {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--surface-color-alt);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-action);
            transition: all 0.2s ease;
        }
        .btn-contact-creator:hover {
            background: var(--primary-10);
            border-color: var(--primary-action);
        }

        .pooling-progress-wrap {
            height: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            overflow: hidden;
            margin: 1.5rem 0;
            border: 1px solid var(--border-color);
        }
        .pooling-progress-fill {
            height: 100%;
            background: var(--primary-action);
            border-radius: 6px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Pledgers Table */
        .pledgers-list-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            background: rgba(0,0,0,0.1);
            scrollbar-width: thin;
            scrollbar-color: var(--border-color) transparent;
        }
        .pledgers-list-container::-webkit-scrollbar { width: 6px; }
        .pledgers-list-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }

        .pledgers-table-premium {
            width: 100%;
            border-collapse: collapse;
        }
        .pledgers-table-premium th {
            position: sticky;
            top: 0;
            background: var(--surface-color-alt);
            padding: 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            z-index: 10;
        }
        .pledgers-table-premium td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            vertical-align: middle;
        }
        .farmer-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .farmer-avatar-sm {
            width: 32px;
            height: 32px;
            background: var(--primary-10);
            color: var(--primary-action);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .farmer-name {
            font-weight: 600;
            color: #fff;
            font-size: 0.9rem;
        }
        .status-badge-confirmed {
            background: rgba(74, 222, 128, 0.1);
            color: #4ade80;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Summary List */
        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dotted var(--border-color);
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        .summary-value {
            color: #fff;
            font-weight: 700;
        }

        .btn-outline-sm {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }
        .btn-outline-sm:hover {
            border-color: var(--primary-action);
            color: var(--primary-action);
        }
    </style>
</head>
<body data-theme="dark">

<div class="app-layout">
    <!-- -- TOPBAR -- -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <p class="topbar-greeting">Campaign Details</p>
        </div>

        <!-- Search bar -->
        <label class="topbar-search" for="topbar-search-input" aria-label="Search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="search" id="topbar-search-input" placeholder="Search anything…" autocomplete="off">
        </label>

        <div class="topbar-right" style="position: relative;">
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

    <main class="main-content">
        <div class="content-wrapper">
            <div class="campaign-grid">
                <!-- LEFT COLUMN -->
                <div class="campaign-main-col">
                    <div class="campaign-header-row">
                        <a href="pooling-browse.php" class="back-link" style="margin-bottom: 0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Back to Campaigns
                        </a>
                        <span class="status-badge-premium status-<?= $campaign['status'] ?>">
                            <?= str_replace('_', ' ', $campaign['status']) ?>
                        </span>
                    </div>

                    <h1 style="font-size: 2.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; color: var(--text-main);">
                        <?= e($campaign['title']) ?>
                    </h1>

                    <div class="metadata-row-premium">
                        <div class="meta-item-premium">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= e($campaign['district']) ?>
                        </div>
                        <div class="meta-item-premium">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Deadline: <?= date('d M Y', strtotime($campaign['deadline'])) ?>
                        </div>
                        <div class="meta-item-premium" style="color: var(--secondary-action); font-weight: 700;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $days_left ?> days left
                        </div>
                        <div class="meta-item-premium">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <?= count($pledges) ?> Pledgers
                        </div>
                    </div>

                    <!-- Description Card -->
                    <div class="description-card">
                        <span class="section-mini-label">Campaign Description</span>
                        <p style="font-size: 1.1rem; line-height: 1.7; color: var(--text-muted); margin-bottom: 2rem;">
                            <?= nl2br(e($campaign['description'])) ?>
                        </p>

                        <div class="pooling-progress-wrap">
                            <div class="pooling-progress-fill" style="width: <?= $progress ?>%;"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.5rem;">
                            <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; text-align: center;">
                                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">1. Target Needed</div>
                                <div style="color: white; font-size: 1.5rem; font-weight: 800; margin-top: 0.25rem;"><?= htmlspecialchars($campaign['target_quantity'] . ' ' . $campaign['unit']) ?></div>
                            </div>
                            <div style="background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; text-align: center;">
                                <div style="color: var(--primary-action); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">2. Already Filled</div>
                                <div style="color: var(--primary-action); font-size: 1.5rem; font-weight: 800; margin-top: 0.25rem;"><?= htmlspecialchars($campaign['current_quantity'] . ' ' . $campaign['unit']) ?></div>
                            </div>
                            <div style="background: rgba(255, 152, 0, 0.1); border: 1px solid rgba(255, 152, 0, 0.3); border-radius: 12px; padding: 1.25rem; text-align: center;">
                                <div style="color: #ff9800; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">3. Still Short By</div>
                                <div style="color: #ff9800; font-size: 1.5rem; font-weight: 800; margin-top: 0.25rem;"><?= htmlspecialchars(max(0, $campaign['target_quantity'] - $campaign['current_quantity']) . ' ' . $campaign['unit']) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Specs Strip -->
                    <div class="specs-strip">
                        <div class="spec-strip-item">
                            <span class="spec-strip-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                Category
                            </span>
                            <span class="spec-strip-value">Agriculture</span>
                        </div>
                        <div class="spec-strip-item">
                            <span class="spec-strip-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                Item
                            </span>
                            <span class="spec-strip-value"><?= e($campaign['item_name']) ?></span>
                        </div>
                        <div class="spec-strip-item">
                            <span class="spec-strip-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                Unit
                            </span>
                            <span class="spec-strip-value"><?= e($campaign['unit']) ?></span>
                        </div>
                        <div class="spec-strip-item">
                            <span class="spec-strip-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Target Needed
                            </span>
                            <span class="spec-strip-value"><?= number_format($campaign['target_quantity']) ?></span>
                        </div>
                        <div class="spec-strip-item">
                            <span class="spec-strip-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                Offering Price
                            </span>
                            <span class="spec-strip-value">₹<?= number_format($campaign['offering_price'], 0) ?></span>
                        </div>
                    </div>

                    <!-- MEMBER LIST -->
                    <div style="margin-top: 4rem;">
                        <h3 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 1.5rem; color: #fff;">
                            Contributors <span style="color: var(--primary-action); margin-left: 8px;"><?= count($pledges) ?></span>
                        </h3>
                        
                        <?php if (empty($pledges)): ?>
                            <div style="padding: 3rem; text-align: center; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed var(--border-color);">
                                <p style="color: var(--text-muted); font-weight: 600;">Be the first to contribute to this campaign!</p>
                            </div>
                        <?php else: ?>
                            <div class="pledgers-list-container">
                                <table class="pledgers-table-premium">
                                    <thead>
                                        <tr>
                                            <th>Farmer</th>
                                            <th>Quantity</th>
                                            <th>Estimated Total</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pledges as $p): 
                                            $pledgeTotal = $p['quantity_pledged'] * $campaign['offering_price'];
                                            $fParts = explode(' ', $p['full_name']);
                                            $fInit = strtoupper(substr($fParts[0], 0, 1) . (isset($fParts[1]) ? substr($fParts[1], 0, 1) : ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="farmer-cell">
                                                    <div class="farmer-avatar-sm"><?= e($fInit) ?></div>
                                                    <span class="farmer-name"><?= e($p['full_name']) ?></span>
                                                </div>
                                            </td>
                                            <td style="font-weight: 700; color: #fff;"><?= number_format($p['quantity_pledged']) ?> <?= e($campaign['unit']) ?></td>
                                            <td style="font-weight: 700; color: var(--secondary-action);">₹<?= number_format($pledgeTotal, 0) ?></td>
                                            <td style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                            <td><span class="status-badge-confirmed">Confirmed</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="campaign-sidebar">
                    <!-- Pledge Widget / Status Box -->
                    <div class="premium-card-right">
                        <?php if ($campaign['status'] === 'threshold_met' || $campaign['status'] === 'closed' || $is_expired): ?>
                            <div style="text-align: center; padding: 1rem 0;">
                                <div style="width: 56px; height: 56px; background: rgba(74, 222, 128, 0.1); color: #4ade80; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">
                                    <?= ($campaign['status'] === 'threshold_met') ? 'Goal Achieved!' : 'Campaign Closed' ?>
                                </h3>
                                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                                    This campaign has reached its target quantity. No more contributions are being accepted.
                                </p>
                            </div>
                        <?php elseif ($userPledge): ?>
                            <div style="text-align: center; padding: 1rem 0;">
                                <div style="width: 56px; height: 56px; background: var(--primary-10); color: var(--primary-action); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">You're in!</h3>
                                <p style="font-size: 0.9rem; color: var(--primary-action); font-weight: 700; margin-bottom: 1.5rem;">
                                    You contributed <?= number_format($userPledge['quantity_pledged']) ?> <?= e($campaign['unit']) ?>
                                </p>
                                <button type="button" class="btn-secondary" id="cancelPledgeBtn" style="width: 100%; height: 48px; border-radius: 12px;">
                                    Cancel Contribution
                                </button>
                            </div>
                        <?php else: ?>
                            <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff;">Help Reach Goal</h3>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">Contribute your excess supply to help this farmer reach their target.</p>
                            
                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Quantity (<?= e($campaign['unit']) ?>)</label>
                                <input type="number" id="pledgeQty" class="form-input" min="1" placeholder="Enter quantity..." style="background: rgba(0,0,0,0.2); border-radius: 10px; height: 50px;">
                            </div>

                            <button type="button" class="btn-primary" id="pledgeBtn" style="width: 100%; height: 52px; font-size: 0.95rem; font-weight: 700; border-radius: 12px;">
                                Contribute Supply
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Creator Box -->
                    <div class="premium-card-right">
                        <h4 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-subtle); margin-bottom: 1.25rem;">Campaign Creator</h4>
                        <div class="creator-block">
                            <?php 
                                $creatorInitials = !empty($campaign['creator_name']) ? strtoupper(substr($campaign['creator_name'], 0, 1)) : '?';
                                if (strpos($campaign['creator_name'], ' ') !== false) {
                                    $parts = explode(' ', $campaign['creator_name']);
                                    $creatorInitials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1));
                                }
                            ?>
                            <div class="creator-avatar-init"><?= e($creatorInitials) ?></div>
                            <div class="creator-info-text">
                                <span class="creator-name-premium"><?= e($campaign['creator_name']) ?></span>
                                <span class="creator-since">Verified Farmer</span>
                            </div>
                            <button class="btn-contact-creator" title="Contact Creator">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </button>
                        </div>

                        <?php if ($is_creator && $campaign['status'] === 'open' && !$is_expired): ?>
                            <button type="button" class="btn-pill danger" id="closeCampaignBtn" style="width: 100%; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 1.5rem; border: 1.5px solid rgba(225, 29, 72, 0.2); background: rgba(225, 29, 72, 0.05);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                Close Campaign
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- How it works helper -->
                    <div style="padding: 1.5rem; background: rgba(251, 191, 36, 0.03); border: 1px solid rgba(251, 191, 36, 0.2); border-radius: 16px; margin-bottom: 1.5rem;">
                        <h4 style="color: #fbbf24; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem;">How Pooling Works</h4>
                        <ul style="font-size: 0.82rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 10px; padding: 0;">
                            <li style="display: flex; gap: 10px;"><span style="color: #fbbf24; font-weight: 800;">1.</span> A farmer posts what they need and what they will pay.</li>
                            <li style="display: flex; gap: 10px;"><span style="color: #fbbf24; font-weight: 800;">2.</span> You contribute your excess supply.</li>
                            <li style="display: flex; gap: 10px;"><span style="color: #fbbf24; font-weight: 800;">3.</span> The creator coordinates pickup/payment once the target is met.</li>
                        </ul>
                        <a href="#" class="btn-outline-sm">Learn More</a>
                    </div>

                    <!-- Campaign Summary Card -->
                    <div class="premium-card-right" style="padding: 1.5rem;">
                        <h4 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-subtle); margin-bottom: 1.25rem;">Campaign Summary</h4>
                        <div class="summary-list">
                            <div class="summary-item">
                                <span class="summary-label">Total Contributed</span>
                                <span class="summary-value"><?= number_format($campaign['current_quantity']) ?> <?= e($campaign['unit']) ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Target Quantity</span>
                                <span class="summary-value"><?= number_format($campaign['target_quantity']) ?> <?= e($campaign['unit']) ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Percentage Met</span>
                                <span class="summary-value" style="color: var(--primary-action);"><?= $progress ?>%</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total Offering Value</span>
                                <span class="summary-value">₹<?= number_format($campaign['current_quantity'] * $campaign['offering_price'], 0) ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Campaign Created</span>
                                <span class="summary-value"><?= date('d M Y', strtotime($campaign['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/dashboard.js" defer></script>
<script>
'use strict';

const CAMPAIGN_ID = <?= $campaign['id'] ?>;
const CSRF_TOKEN = '<?= generateCsrfToken() ?>';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Pledge submit
    document.getElementById('pledgeBtn')?.addEventListener('click', async () => {
        const qty = parseInt(document.getElementById('pledgeQty').value);
        if (!qty || qty < 1) {
            alert('Please enter a valid quantity of at least 1.');
            return;
        }

        try {
            const res = await fetch('api/pooling-pledge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    campaign_id: CAMPAIGN_ID,
                    quantity: qty,
                    csrf_token: CSRF_TOKEN
                })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Failed to submit contribution. Please try again.');
        }
    });

    // 2. Cancel pledge
    document.getElementById('cancelPledgeBtn')?.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to cancel your contribution? This might affect the campaign target.')) return;

        try {
            const res = await fetch('api/pooling-cancel-pledge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    campaign_id: CAMPAIGN_ID,
                    csrf_token: CSRF_TOKEN
                })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Failed to cancel contribution.');
        }
    });

    // 3. Close campaign
    document.getElementById('closeCampaignBtn')?.addEventListener('click', async () => {
        if (!confirm('Close this campaign permanently? No more contributions will be allowed.')) return;

        try {
            const res = await fetch('api/pooling-close.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    campaign_id: CAMPAIGN_ID,
                    csrf_token: CSRF_TOKEN
                })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Failed to close campaign.');
        }
    });
});
</script>

</body>
</html>
