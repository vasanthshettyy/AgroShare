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
                                <span><?= e($b['owner_name']) ?></span>
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
                                <span><?= e($b['renter_name']) ?></span>
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
                                            <span class="renter-name-link"><?= e($b['renter_name']) ?></span>
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
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
                position: relative;
            }
            .modal-close-x {
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
                background: none;
                border: none;
                color: var(--text-muted);
                cursor: pointer;
                padding: 0.5rem;
                transition: color 0.2s;
                z-index: 10;
            }
            .modal-close-x:hover { color: var(--text-main); }
            
            .premium-title {
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--text-main);
                margin-bottom: 0.5rem;
            }
            .premium-subtitle {
                font-size: 0.875rem;
                color: var(--text-muted);
                margin-bottom: 1.5rem;
            }

            /* Liquid Fill Star Animation */
            .liquid-stars-wrapper {
                position: relative;
                display: inline-flex;
                gap: 0.4rem;
                cursor: pointer;
                user-select: none;
                --star-fill-width: 0%;
                margin-bottom: 1.75rem;
                padding: 5px;
                border-radius: 12px;
                transition: background 0.2s;
            }
            .liquid-stars-wrapper:hover {
                background: rgba(255, 255, 255, 0.03);
            }
            
            .stars-background, .stars-active {
                display: flex;
                gap: 0.4rem;
            }
            .stars-background {
                color: rgba(255, 255, 255, 0.1);
            }
            .stars-active {
                position: absolute;
                top: 5px;
                left: 5px;
                width: var(--star-fill-width);
                overflow: hidden;
                white-space: nowrap;
                color: #fbbf24;
                transition: width 0.15s ease-out;
                pointer-events: none;
                z-index: 2;
            }
            .star-svg {
                width: 32px;
                height: 32px;
                fill: currentColor;
                flex-shrink: 0;
            }
            
            /* Pop & Glow Animation */
            @keyframes star-pop {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.5)); }
                100% { transform: scale(1); }
            }
            .star-pop-anim {
                animation: star-pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            /* Interact Layer */
            .stars-interact {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                z-index: 5;
            }
            .star-hitbox {
                flex: 1;
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
            }

            .premium-textarea {
                width: 100%;
                background: rgba(0, 0, 0, 0.2);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 1rem;
                color: var(--text-main);
                font-family: inherit;
                font-size: 0.9rem;
                resize: none;
                margin-bottom: 1.25rem;
                transition: all 0.2s;
            }
            .premium-textarea:focus {
                outline: none;
                border-color: var(--primary-action);
                background: rgba(0, 0, 0, 0.3);
                box-shadow: 0 0 0 3px var(--primary-10);
            }

            .review-tags-container {
                display: flex;
                flex-wrap: wrap;
                gap: 0.65rem;
                margin-bottom: 2rem;
            }
            .review-tag {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.55rem 0.95rem;
                background: transparent;
                border: 1px solid var(--border-color);
                border-radius: 100px;
                color: var(--text-muted);
                font-size: 0.78rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .review-tag:hover {
                background: rgba(255, 255, 255, 0.05);
                border-color: var(--text-subtle);
                color: var(--text-main);
                transform: translateY(-1px);
            }
            .review-tag.active {
                background: var(--primary-10);
                border-color: var(--primary-action);
                color: var(--text-main);
                box-shadow: 0 4px 12px rgba(76, 175, 120, 0.15);
            }

            .modal-footer-premium {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
            }
            .premium-btn-primary {
                background: var(--primary-action);
                color: white;
                border: none;
                padding: 0.75rem 1.75rem;
                border-radius: 12px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: 0 4px 12px rgba(76, 175, 120, 0.25);
            }
            .premium-btn-primary:hover { 
                filter: brightness(1.1);
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(76, 175, 120, 0.35);
            }
            .premium-btn-secondary {
                background: transparent;
                color: var(--text-muted);
                border: 1px solid var(--border-color);
                padding: 0.75rem 1.5rem;
                border-radius: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }
            .premium-btn-secondary:hover {
                background: rgba(255, 255, 255, 0.05);
                color: var(--text-main);
            }
        </style>

        <div class="liquid-stars-wrapper" id="liquid-stars-container">
            <div class="stars-background">
                <?php for($i=0;$i<5;$i++): ?>
                <svg class="star-svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path></svg>
                <?php endfor; ?>
            </div>
            <div class="stars-active" id="stars-fill-layer">
                <?php for($i=0;$i<5;$i++): ?>
                <svg class="star-svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path></svg>
                <?php endfor; ?>
            </div>
            <input type="hidden" id="selected-rating" value="0">
        </div>

        <textarea id="review-comment" rows="4" class="premium-textarea" placeholder="Tell us about the equipment quality, owner support, and overall experience..."></textarea>
        
        <div class="review-tags-container">
            <button type="button" class="review-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                Great Condition
            </button>
            <button type="button" class="review-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                On Time
            </button>
            <button type="button" class="review-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                Good Support
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
