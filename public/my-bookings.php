<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/BookingController.php';
requireAuth();

$userId = (int)$_SESSION['user_id'];
$rentals = getRentalsForUser($conn, $userId);
$requests = getRequestsForOwner($conn, $userId);

// Layout Data
$fullName  = trim($_SESSION['full_name'] ?? '');
$nameParts = explode(' ', $fullName);
$initials  = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : '?';
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings — <?= e(APP_NAME) ?></title>
    <script>(function(){ var t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .bookings-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        /* -- Animated Tabs -- */
        .bookings-tabs {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 600;
            padding: 0.75rem 1rem;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tab-btn:hover { color: var(--text-main); }
        .tab-btn.active { color: var(--primary-action); }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -0.6rem;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-action);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 -2px 10px var(--primary-40);
        }

        /* -- Booking Cards Grid -- */
        .booking-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            display: none;
            animation: fadeSlideUp 0.4s ease-out forwards;
        }
        .booking-grid.active { display: flex; }

        .booking-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .booking-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary-action);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }
        .eq-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }
        .price-tag {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary-action);
        }

        .card-body {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.88rem;
            color: var(--text-subtle);
        }
        .info-row svg { opacity: 0.7; }
        .info-label { font-weight: 600; color: var(--text-muted); min-width: 60px; }

        .card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
        }

        .actions-wrap {
            display: flex;
            gap: 0.5rem;
        }

        /* -- Badges -- */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending   { background: rgba(255, 193, 7, 0.15); color: #FFC107; border: 1px solid rgba(255, 193, 7, 0.2); }
        .status-confirmed { background: rgba(76, 175, 80, 0.15); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.2); }
        .status-completed { background: rgba(33, 150, 243, 0.15); color: #2196F3; border: 1px solid rgba(33, 150, 243, 0.2); }
        .status-cancelled { background: rgba(158, 158, 158, 0.15); color: #9E9E9E; border: 1px solid rgba(158, 158, 158, 0.2); }
        .status-active    { background: rgba(156, 39, 176, 0.15); color: #E040FB; border: 1px solid rgba(156, 39, 176, 0.2); }

        .btn-sm {
            padding: 0.45rem 0.9rem;
            font-size: 0.8rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary.btn-sm { background: var(--primary-action); color: #fff; }
        .btn-danger.btn-sm { background: var(--danger); color: #fff; }
        .btn-secondary.btn-sm { background: var(--border-color); color: var(--text-main); }
        .btn-sm:hover { filter: brightness(1.2); transform: translateY(-1px); }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        /* -- Renter Profile Info -- */
        .renter-profile-card {
            background: var(--primary-10);
            border: 1px solid var(--primary-20);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .renter-info-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .renter-avatar-sm {
            width: 32px;
            height: 32px;
            background: var(--primary-action);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .renter-meta {
            display: flex;
            flex-direction: column;
        }
        .renter-name-link {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.9rem;
        }
        .renter-sub-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.72rem;
            color: var(--text-muted);
        }
        .renter-contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--primary-20);
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-main);
            text-decoration: none;
        }
        .contact-item svg { color: var(--primary-action); }
        .contact-item:hover { color: var(--primary-action); }
    </style>
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
                My Bookings
            </p>
        </div>

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

            <a href="dashboard.php" class="nav-link">
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

            <a href="my-bookings.php" class="nav-link active" aria-current="page">
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

    <main class="main-content">
        <div class="bookings-container">
            <div class="bookings-tabs">
                <button class="tab-btn active" data-tab="rentals">Equipment I Rented</button>
                <button class="tab-btn" data-tab="requests">Requests for My Equipment</button>
            </div>

            <!-- Rentals Grid -->
            <div class="booking-grid active" id="rentals">
                <?php if (empty($rentals)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
                        <p>You haven't rented any equipment yet.</p>
                        <a href="equipment-browse.php" class="btn-primary" style="margin-top:1rem; display:inline-block;">Browse Equipment</a>
                    </div>
                <?php else: foreach ($rentals as $b): ?>
                    <div class="booking-card" id="booking-<?= $b['id'] ?>">
                        <div class="card-header">
                            <h3 class="eq-title"><?= e($b['equipment_title']) ?></h3>
                            <div style="text-align: right;">
                                <span class="price-tag">₹<?= number_format($b['total_price'] + $b['deposit_amount'], 0) ?></span>
                                <?php if ($b['deposit_amount'] > 0): ?>
                                    <div style="font-size: 0.65rem; color: var(--text-subtle); margin-top: 2px;">
                                        (includes ₹<?= number_format($b['deposit_amount'], 0) ?> Deposit)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span class="info-label">Owner:</span> 
                                <button type="button" class="btn-text-link" onclick="showUserReviews(<?= (int)$b['owner_id'] ?>)"><?= e($b['owner_name']) ?></button>
                            </div>
                            <div class="info-row">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <span class="info-label">Dates:</span> <span><?= date('d M', strtotime($b['start_datetime'])) ?> - <?= date('d M', strtotime($b['end_datetime'])) ?></span>
                            </div>
                        </div>
                        <div class="card-footer" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span>
                            </div>
                            <div class="actions-wrap" style="margin-left: auto;">
                                <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn-secondary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="cancelled">Cancel</button>
                                <?php endif; ?>
                                <?php if ($b['status'] === 'active'): ?>
                                    <button class="btn-primary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="completed">Mark Completed</button>
                                <?php endif; ?>
                                <?php if ($b['status'] === 'completed'): ?>
                                    <button class="btn-danger btn-sm btn-dispute" data-id="<?= $b['id'] ?>" style="background: var(--danger, #dc3545);">Raise Dispute</button>
                                    <?php if (empty($b['review_id'])): ?>
                                        <button class="btn-secondary btn-sm" 
                                                data-review-booking="<?= (int)$b['id'] ?>"
                                                data-review-reviewee="<?= (int)($b['owner_id'] ?? 0) ?>">
                                            ⭐ Leave a Review
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Requests Grid -->
            <div class="booking-grid" id="requests">
                <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <p>No incoming booking requests yet.</p>
                    </div>
                <?php else: foreach ($requests as $b): ?>
                    <div class="booking-card" id="booking-<?= $b['id'] ?>">
                        <div class="card-header">
                            <h3 class="eq-title"><?= e($b['equipment_title']) ?></h3>
                            <div style="text-align: right;">
                                <span class="price-tag">₹<?= number_format($b['total_price'] + $b['deposit_amount'], 0) ?></span>
                                <?php if ($b['deposit_amount'] > 0): ?>
                                    <div style="font-size: 0.65rem; color: var(--text-subtle); margin-top: 2px;">
                                        (includes ₹<?= number_format($b['deposit_amount'], 0) ?> Deposit)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span class="info-label">Renter:</span> 
                                <button type="button" class="btn-text-link" onclick="showUserReviews(<?= (int)$b['renter_id'] ?>)"><?= e($b['renter_name']) ?></button>
                            </div>
                            <div class="info-row">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <span class="info-label">Dates:</span> <span><?= date('d M', strtotime($b['start_datetime'])) ?> - <?= date('d M', strtotime($b['end_datetime'])) ?></span>
                            </div>

                            <?php if (in_array($b['status'], ['confirmed', 'active', 'completed'])): ?>
                                <div class="renter-profile-card">
                                    <div class="renter-info-header">
                                        <div class="renter-avatar-sm">
                                            <?= strtoupper(substr(trim($b['renter_name'] ?? ''), 0, 1)) ?: '?' ?>
                                        </div>
                                        <div class="renter-meta">
                                            <button type="button" class="renter-name-link btn-text-link" onclick="showUserReviews(<?= (int)$b['renter_id'] ?>)"><?= e($b['renter_name']) ?></button>
                                            <div class="renter-sub-meta">
                                                <span>📍 <?= e($b['renter_village']) ?>, <?= e($b['renter_district']) ?></span>
                                                <span>•</span>
                                                <span style="color:var(--amber);">⭐ <?= number_format($b['renter_trust'], 1) ?></span>
                                                <?php if ($b['renter_verified']): ?>
                                                    <span style="color:var(--secondary-action);">✔ Verified</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="renter-contact-grid">
                                        <a href="tel:<?= e($b['renter_phone']) ?>" class="contact-item">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            <?= e($b['renter_phone']) ?>
                                        </a>
                                        <?php if ($b['renter_email']): ?>
                                            <a href="mailto:<?= e($b['renter_email']) ?>" class="contact-item">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                                Email Renter
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span>
                            </div>
                            <div class="actions-wrap" style="margin-left: auto;">
                                <?php
                                    $btnData = 'data-id="' . $b['id'] . '"'
                                             . ' data-renter="' . e($b['renter_name']) . '"'
                                             . ' data-dates="' . date('d M Y', strtotime($b['start_datetime'])) . ' — ' . date('d M Y', strtotime($b['end_datetime'])) . '"'
                                             . ' data-price="₹' . number_format($b['total_price'], 0) . '"'
                                             . ' data-equipment="' . e($b['equipment_title']) . '"';
                                ?>
                                <?php if ($b['status'] === 'pending'): ?>
                                    <button class="btn-primary btn-sm status-action" <?= $btnData ?> data-status="confirmed">Accept</button>
                                    <button class="btn-secondary btn-sm status-action" <?= $btnData ?> data-status="cancelled">Decline</button>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <button class="btn-primary btn-sm status-action" <?= $btnData ?> data-status="completed">Mark Completed</button>
                                    <button class="btn-secondary btn-sm status-action" <?= $btnData ?> data-status="cancelled">Cancel</button>
                                <?php elseif ($b['status'] === 'completed' && empty($b['review_id'])): ?>
                                    <button class="btn-secondary btn-sm" 
                                            data-review-booking="<?= (int)$b['id'] ?>"
                                            data-review-reviewee="<?= (int)($b['renter_id'] ?? 0) ?>">
                                        ⭐ Leave a Review
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
</div>

<div id="reviewModal" class="modal-overlay" style="display: none;">
    <div class="modal-content profile-modal-content review-premium-modal" style="max-width:480px; width:90%; padding:2.5rem;">
        <button id="reviewModalCloseBtn" class="modal-close-x" aria-label="Close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        
        <div class="modal-header-section">
            <h2 class="premium-title">Leave a Review</h2>
            <p class="premium-subtitle">Share your experience to help others make better decisions.</p>
        </div>

        <input type="hidden" id="review-booking-id" value="">
        
        <style>
            .review-premium-modal {
                background: var(--glass-bg-heavy) !important;
                border: 1px solid var(--glass-border) !important;
                box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.6) !important;
                position: relative;
                overflow: hidden;
            }
            .review-premium-modal::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; height: 4px;
                background: linear-gradient(90deg, var(--primary-action), var(--secondary-action));
            }
            .modal-close-x {
                position: absolute;
                top: 1.25rem;
                right: 1.25rem;
                background: rgba(255,255,255,0.05);
                border: 1px solid var(--border-color);
                color: var(--text-muted);
                cursor: pointer;
                padding: 0.4rem;
                border-radius: 8px;
                transition: all 0.2s;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .modal-close-x:hover { 
                color: var(--text-main); 
                background: rgba(255,255,255,0.1);
                transform: rotate(90deg);
            }
            
            .premium-title {
                font-size: 1.4rem;
                font-weight: 800;
                color: var(--text-main);
                margin-bottom: 0.25rem;
                letter-spacing: -0.02em;
            }
            .premium-subtitle {
                font-size: 0.85rem;
                color: var(--text-muted);
                margin-bottom: 1.25rem;
                line-height: 1.4;
            }

            /* Star & Trust Layout */
            .rating-overview-row {
                display: flex;
                align-items: center;
                gap: 1.25rem;
                margin-bottom: 1.25rem;
            }

            /* Liquid Fill Star Animation (Vector Precision) */
            .liquid-stars-wrapper {
                position: relative;
                width: 190px;
                height: 36px;
                cursor: pointer;
                user-select: none;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s ease;
            }
            .liquid-stars-wrapper:hover {
                transform: scale(1.02);
            }
            
            .star-engine-svg {
                width: 100%;
                height: 100%;
                filter: drop-shadow(0 0 0px transparent);
                transition: filter 0.3s ease;
            }
            
            .star-pop-anim .star-engine-svg {
                animation: star-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                filter: drop-shadow(0 0 12px rgba(251, 191, 36, 0.4));
            }

            @keyframes star-pop {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }

            .star-path {
                fill: url(#starGradient);
                stroke: rgba(255,255,255,0.05);
                stroke-width: 0.5;
            }
            .star-svg { width: 38px; height: 38px; fill: currentColor; flex-shrink: 0; }
            
            /* Trust Score Preview Box */
            .trust-preview-box {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 0.5rem;
                background: rgba(76, 175, 120, 0.08);
                border: 1px solid rgba(76, 175, 120, 0.2);
                border-radius: 12px;
                min-width: 90px;
                transition: all 0.3s ease;
            }
            .trust-preview-box.highlight {
                background: var(--primary-10);
                border-color: var(--primary-action);
                transform: translateY(-2px);
            }
            .trust-input {
                background: transparent;
                border: none;
                font-size: 1.25rem;
                font-weight: 800;
                color: var(--primary-action);
                width: 50px;
                text-align: center;
                outline: none;
                margin-bottom: -2px;
            }
            /* Remove arrows from number input */
            .trust-input::-webkit-outer-spin-button,
            .trust-input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .trust-input {
                -moz-appearance: textfield;
            }
            .trust-label {
                font-size: 0.65rem;
                font-weight: 700;
                color: var(--text-muted);
                text-transform: uppercase;
                margin-top: 2px;
            }

            /* Textarea & Char Count */
            .comment-container {
                position: relative;
                margin-bottom: 1rem;
            }
            .premium-textarea {
                width: 100%;
                background: rgba(0, 0, 0, 0.25);
                border: 1px solid var(--border-color);
                border-radius: 14px;
                padding: 1rem;
                color: var(--text-main);
                font-family: inherit;
                font-size: 0.9rem;
                resize: none;
                transition: all 0.3s ease;
                min-height: 100px;
            }
            .premium-textarea:focus {
                outline: none;
                border-color: var(--primary-action);
                background: rgba(0, 0, 0, 0.4);
                box-shadow: 0 0 0 4px var(--primary-10);
            }
            .char-counter {
                position: absolute;
                bottom: 10px; right: 10px;
                font-size: 0.65rem;
                font-weight: 600;
                color: var(--text-subtle);
                background: rgba(0,0,0,0.3);
                padding: 1px 6px;
                border-radius: 4px;
                pointer-events: none;
            }

            .review-tags-container {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
            }
            .review-tag {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.4rem 0.8rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid var(--border-color);
                border-radius: 100px;
                color: var(--text-muted);
                font-size: 0.72rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .review-tag svg { width: 14px; height: 14px; opacity: 0.6; transition: opacity 0.2s; }
            .review-tag:hover {
                background: rgba(255, 255, 255, 0.07);
                border-color: var(--text-subtle);
                color: var(--text-main);
                transform: translateY(-1px);
            }
            .review-tag.active {
                background: var(--primary-10);
                border-color: var(--primary-action);
                color: var(--text-main);
                box-shadow: 0 4px 12px -4px rgba(76, 175, 120, 0.3);
            }
            .review-tag.active svg { opacity: 1; color: var(--primary-action); }

            .modal-footer-premium {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
                padding-top: 0.75rem;
                border-top: 1px solid var(--border-color);
            }
            .premium-btn-primary {
                background: var(--primary-action);
                color: white;
                border: none;
                padding: 0.7rem 1.5rem;
                border-radius: 12px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 6px 15px -5px rgba(76, 175, 120, 0.4);
                font-size: 0.9rem;
            }
            .premium-btn-primary:hover { 
                filter: brightness(1.1);
                transform: translateY(-1px);
                box-shadow: 0 10px 20px -5px rgba(76, 175, 120, 0.5);
            }
            .premium-btn-secondary {
                background: transparent;
                color: var(--text-muted);
                border: 1px solid var(--border-color);
                padding: 0.7rem 1.25rem;
                border-radius: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 0.9rem;
            }
            .premium-btn-secondary:hover {
                background: rgba(255, 255, 255, 0.05);
                color: var(--text-main);
                border-color: var(--text-subtle);
            }
                border-color: var(--text-subtle);
            }
        </style>

        <div class="rating-overview-row">
            <div class="liquid-stars-wrapper" id="liquid-stars-container">
                <svg class="star-engine-svg" viewBox="0 0 200 40" preserveAspectRatio="xMidYMid meet">
                    <defs>
                        <linearGradient id="starGradient" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="200" y2="0">
                            <stop id="grad-stop-active" offset="0" stop-color="#fbbf24" />
                            <stop id="grad-stop-ghost" offset="0" stop-color="rgba(255,255,255,0.1)" />
                        </linearGradient>
                    </defs>
                    <!-- 5 Perfectly Spaced Stars (Width 40 each, 0 to 200) -->
                    <path class="star-path" d="M20 28.78l10.3 6.22-2.73-11.72L36.67 15.4l-11.98-1.02L20 3.37l-4.69 11.01-11.98 1.02 9.1 7.88-2.73 11.72z" />
                    <path class="star-path" d="M60 28.78l10.3 6.22-2.73-11.72L76.67 15.4l-11.98-1.02L60 3.37l-4.69 11.01-11.98 1.02 9.1 7.88-2.73 11.72z" />
                    <path class="star-path" d="M100 28.78l10.3 6.22-2.73-11.72L116.67 15.4l-11.98-1.02L100 3.37l-4.69 11.01-11.98 1.02 9.1 7.88-2.73 11.72z" />
                    <path class="star-path" d="M140 28.78l10.3 6.22-2.73-11.72L156.67 15.4l-11.98-1.02L140 3.37l-4.69 11.01-11.98 1.02 9.1 7.88-2.73 11.72z" />
                    <path class="star-path" d="M180 28.78l10.3 6.22-2.73-11.72L196.67 15.4l-11.98-1.02L180 3.37l-4.69 11.01-11.98 1.02 9.1 7.88-2.73 11.72z" />
                </svg>
                <input type="hidden" id="review-rating" value="0">
            </div>

            <div class="trust-preview-box" id="trust-preview">
                <input type="number" id="manual-rating-input" class="trust-input" min="0" max="5" step="0.1" placeholder="0.0">
                <span class="trust-label" id="trust-text-display">Rate It</span>
            </div>
        </div>

        <div class="comment-container">
            <textarea id="review-comment" rows="4" class="premium-textarea" 
                      placeholder="How was the equipment condition? Was the owner helpful?"
                      maxlength="500"></textarea>
            <div class="char-counter"><span id="char-count">0</span>/500</div>
        </div>
        
        <div class="review-tags-container">
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                Great Condition
            </button>
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                On Time
            </button>
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Good Support
            </button>
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                Fair Pricing
            </button>
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Highly Recommended
            </button>
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>
                Helpful Owner
            </button>
            <button type="button" class="review-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="M20 12h2"></path><path d="m19.07 19.07 1.41 1.41"></path><path d="M12 20v2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="M2 12h2"></path><path d="m7.76 7.76-1.41 1.41"></path><circle cx="12" cy="12" r="4"></circle></svg>
                Clean Equipment
            </button>
        </div>

        <div class="modal-footer-premium">
            <button id="reviewCancelBtn" class="premium-btn-secondary">Cancel</button>
            <button id="reviewSubmitBtn" class="premium-btn-primary">Submit Review</button>
        </div>
    </div>
</div>

<input type="hidden" id="csrf_token" value="<?= generateCsrfToken() ?>">

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<?php require_once __DIR__ . '/includes/viewer-reviews-modal.php'; ?>
<script src="assets/js/dashboard.js" defer></script>
<script src="assets/js/reviews.js" defer></script>
<script>
    // Tab Switching Logic
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn, .booking-grid').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
        });
    });

    function showInlineToast(type, message) {
        if (window.showToast) {
            window.showToast(type, message);
            return;
        }
        const existingToast = document.querySelector('.toast');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.background = type === 'error' ? 'var(--danger, #dc3545)' : 'var(--primary-action, #28a745)';
        toast.style.color = '#fff';
        toast.style.padding = '12px 24px';
        toast.style.borderRadius = '8px';
        toast.style.zIndex = '99999';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Reveal Contact Info Logic
    document.querySelectorAll('.btn-reveal-contact').forEach(btn => {
        btn.addEventListener('click', function() {
            const phone = this.dataset.phone;
            const container = this.parentElement;
            
            // Create the tel link dynamically
            const link = document.createElement('a');
            link.href = `tel:${phone}`;
            link.style.color = 'var(--secondary-action)';
            link.style.fontWeight = 'bold';
            link.style.fontSize = '0.9rem';
            link.style.textDecoration = 'none';
            link.style.display = 'inline-flex';
            link.style.alignItems = 'center';
            link.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> ${phone}`;
            
            // Replace button with link
            container.innerHTML = '';
            container.appendChild(link);
        });
    });

    // AJAX Status Management
    document.querySelectorAll('.status-action').forEach(btn => {
        btn.addEventListener('click', async () => {
            const bookingId = btn.dataset.id;
            const newStatus = btn.dataset.status;
            
            const info = {
                renter: btn.dataset.renter || '—',
                dates: btn.dataset.dates || '—',
                price: btn.dataset.price || '—',
                equipment: btn.dataset.equipment || '—'
            };

            const confirmed = await showActionConfirm(newStatus, bookingId, info);
            if (!confirmed) return;

            btn.disabled = true;
            btn.style.opacity = '0.5';

            try {
                const formData = new FormData();
                formData.append('id', bookingId);
                formData.append('status', newStatus);
                formData.append('csrf_token', document.getElementById('csrf_token').value);

                const res = await fetch('api/update-booking-status.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    const card = document.getElementById(`booking-${bookingId}`);
                    const badge = card.querySelector('.status-badge');
                    const actions = card.querySelector('.actions-wrap');

                    // Update Badge UI
                    badge.className = `status-badge status-${newStatus}`;
                    badge.textContent = newStatus;
                    
                    // Hide buttons smoothly
                    actions.style.opacity = '0';
                    setTimeout(() => actions.remove(), 300);

                    // Show visual success feedback
                    if (window.showToast) {
                        window.showToast('success', data.message);
                    } else {
                        const existingToast = document.querySelector('.toast');
                        if (existingToast) existingToast.remove();
                        
                        const toast = document.createElement('div');
                        toast.className = 'toast toast-success';
                        toast.style.position = 'fixed';
                        toast.style.bottom = '20px';
                        toast.style.right = '20px';
                        toast.style.background = 'var(--primary-action)';
                        toast.style.color = '#fff';
                        toast.style.padding = '12px 24px';
                        toast.style.borderRadius = '8px';
                        toast.style.zIndex = '9999';
                        toast.textContent = data.message;
                        document.body.appendChild(toast);
                        
                        setTimeout(() => toast.remove(), 3000);
                    }
                } else {
                    showInlineToast('error', data.message);
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            } catch (err) {
                showInlineToast('error', 'Network error. Please try again.');
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    });

    // Dispute Management
    document.querySelectorAll('.btn-dispute').forEach(btn => {
        btn.addEventListener('click', async () => {
            const bookingId = btn.dataset.id;
            const confirmed = confirm("Are you sure you want to dispute this deposit return?");
            if (!confirmed) return;

            btn.disabled = true;
            btn.style.opacity = '0.5';

            try {
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('csrf_token', document.getElementById('csrf_token').value);

                const res = await fetch('api/raise_dispute.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    const card = document.getElementById(`booking-${bookingId}`);
                    const badge = card.querySelector('.status-badge');
                    const actions = card.querySelector('.actions-wrap');

                    badge.className = 'status-badge status-disputed';
                    badge.textContent = 'disputed';
                    badge.style.background = 'rgba(255, 87, 34, 0.15)';
                    badge.style.color = '#FF5722';
                    badge.style.border = '1px solid rgba(255, 87, 34, 0.2)';

                    actions.remove();
                    showInlineToast('success', data.message);
                } else {
                    showInlineToast('error', data.message);
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            } catch (err) {
                showInlineToast('error', 'Network error. Please try again.');
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    });

    function showActionConfirm(status, bookingId, info) {
        return new Promise((resolve) => {
            document.getElementById('actionConfirmOverlay')?.remove();

            const isAccept = (status === 'confirmed');
            const isComplete = (status === 'completed');
            let title, desc, icon, btnColor, btnText;

            if (isAccept) {
                title = 'Accept Booking?';
                desc = 'The renter will be notified and your equipment will be marked as booked.';
                icon = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`;
                btnColor = 'background:#2e7d32;'; btnText = 'Yes, Accept';
            } else if (isComplete) {
                title = 'Mark as Completed?';
                desc = 'This will mark the booking as completed and free up your equipment.';
                icon = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`;
                btnColor = 'background:#1565c0;'; btnText = 'Yes, Complete';
            } else {
                title = 'Decline Booking?';
                desc = 'The renter will be notified that their request was declined.';
                icon = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
                btnColor = 'background:#c62828;'; btnText = 'Yes, Decline';
            }

            const detailsCard = `
                <div style="background:var(--bg-color,#111);border:1px solid var(--border-color,rgba(255,255,255,.08));border-radius:12px;padding:1rem 1.25rem;margin:1rem 0 1.25rem;text-align:left;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;">
                        <span style="font-size:.82rem;color:var(--text-muted,#aaa);display:flex;align-items:center;gap:.5rem;">🔧 Equipment</span>
                        <span style="font-size:.88rem;font-weight:600;color:var(--text-main,#fff);">${info.equipment}</span>
                    </div>
                    <div style="border-top:1px solid var(--border-color,rgba(255,255,255,.06));display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;">
                        <span style="font-size:.82rem;color:var(--text-muted,#aaa);display:flex;align-items:center;gap:.5rem;">👤 Renter</span>
                        <span style="font-size:.88rem;font-weight:600;color:var(--text-main,#fff);">${info.renter}</span>
                    </div>
                    <div style="border-top:1px solid var(--border-color,rgba(255,255,255,.06));display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;">
                        <span style="font-size:.82rem;color:var(--text-muted,#aaa);display:flex;align-items:center;gap:.5rem;">📅 Dates</span>
                        <span style="font-size:.88rem;font-weight:600;color:var(--text-main,#fff);">${info.dates}</span>
                    </div>
                    <div style="border-top:1px solid var(--border-color,rgba(255,255,255,.06));display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;">
                        <span style="font-size:.82rem;color:var(--text-muted,#aaa);display:flex;align-items:center;gap:.5rem;">💰 Total</span>
                        <span style="font-size:1rem;font-weight:800;color:var(--primary-action,#2e7d32);">${info.price}</span>
                    </div>
                </div>`;

            const overlay = document.createElement('div');
            overlay.id = 'actionConfirmOverlay';
            overlay.style.cssText = 'position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);opacity:0;transition:opacity .3s ease;padding:1.5rem;';
            overlay.innerHTML = `
                <div style="background:var(--surface-color,#1a2e1a);border:1px solid var(--border-color,rgba(255,255,255,.1));border-radius:20px;padding:2.5rem 2rem;max-width:440px;width:100%;text-align:center;transform:scale(.9) translateY(20px);transition:transform .4s cubic-bezier(.22,.61,.36,1);box-shadow:0 24px 64px rgba(0,0,0,.4);">
                    <div style="margin-bottom:1rem;">${icon}</div>
                    <h2 style="font-size:1.3rem;font-weight:700;color:var(--text-main,#fff);margin:0 0 .25rem;">${title}</h2>
                    <p style="font-size:.82rem;color:var(--text-muted,#aaa);margin:0;line-height:1.5;">${desc}</p>
                    ${detailsCard}
                    <div style="display:flex;flex-direction:column;gap:.6rem;">
                        <button id="actionConfirmYes" style="${btnColor}color:#fff;padding:.85rem 1.5rem;border-radius:10px;font-size:.92rem;font-weight:600;border:none;cursor:pointer;transition:filter .2s ease;">${btnText}</button>
                        <button id="actionConfirmNo" style="background:var(--surface-color,#1a2e1a);color:var(--text-main,#fff);border:1px solid var(--border-color,rgba(255,255,255,.1));padding:.85rem 1.5rem;border-radius:10px;font-size:.92rem;font-weight:600;cursor:pointer;transition:all .2s ease;">Cancel</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                overlay.firstElementChild.style.transform = 'scale(1) translateY(0)';
            });

            overlay.querySelector('#actionConfirmYes').addEventListener('click', () => {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 300);
                resolve(true);
            });

            overlay.querySelector('#actionConfirmNo').addEventListener('click', () => {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 300);
                resolve(false);
            });
        });
    }
</script>
</body>
</html>
