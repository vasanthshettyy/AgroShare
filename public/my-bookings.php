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

/**
 * Render escrow progress tracker stepper bar.
 */
function renderEscrowProgress(string $escrowStatus): string {
    $steps = [
        'PENDING_PAYMENT' => ['label' => 'Payment',  'icon' => '💳'],
        'FUNDS_LOCKED'    => ['label' => 'Handover', 'icon' => '🤝'],
        'ACTIVE_RENTAL'   => ['label' => 'Active',   'icon' => '🚜'],
        'COMPLETED'       => ['label' => 'Return',   'icon' => '↩️'],
        'DONE'            => ['label' => 'Done',     'icon' => '✅'], // Map actually requires 5 steps. Let's fix map to match exactly.
    ];
    // Wait, the prompt says: Map status to current step: PENDING_PAYMENT -> Payment active, FUNDS_LOCKED -> Handover active, ACTIVE_RENTAL -> Return active, COMPLETED -> Done active
    $steps = [
        'PENDING_PAYMENT' => ['label' => 'Payment',  'icon' => '💳'],
        'FUNDS_LOCKED'    => ['label' => 'Handover', 'icon' => '🤝'],
        'ACTIVE_RENTAL'   => ['label' => 'Return',   'icon' => '↩️'],
        'COMPLETED'       => ['label' => 'Done',     'icon' => '✅'],
    ];
    
    // Oh wait, prompt said 5 steps: Payment, Handover, Active Rental, Return, Done.
    // The instructions say: Map status to current step: PENDING_PAYMENT -> Payment, FUNDS_LOCKED -> Handover, ACTIVE_RENTAL -> Return (wait, Active Rental?), COMPLETED -> Done. Let's make 5 steps exactly.
    
    $isCancelled = ($escrowStatus === 'CANCELLED');
    $statusMap = [
        'PENDING_PAYMENT' => 0, // Payment
        'FUNDS_LOCKED'    => 1, // Handover
        'ACTIVE_RENTAL'   => 3, // Return active
        'COMPLETED'       => 4, // Done
    ];
    $currentIdx = $isCancelled ? -1 : ($statusMap[$escrowStatus] ?? -1);

    $uiSteps = [
        ['label' => 'Payment', 'icon' => '💳'],
        ['label' => 'Handover', 'icon' => '🤝'],
        ['label' => 'Active Rental', 'icon' => '🚜'],
        ['label' => 'Return', 'icon' => '↩️'],
        ['label' => 'Done', 'icon' => '✅']
    ];

    $html = '<div class="escrow-tracker' . ($isCancelled ? ' is-cancelled' : '') . '">';
    foreach ($uiSteps as $idx => $step) {
        $cls = 'escrow-step';
        if (!$isCancelled) {
            if ($idx < $currentIdx) $cls .= ' step-completed';
            elseif ($idx === $currentIdx) $cls .= ' step-active';
        }
        $html .= '<div class="' . $cls . '">';
        $html .= '<div class="step-dot"><span>' . $step['icon'] . '</span></div>';
        $html .= '<span class="step-label">' . $step['label'] . '</span>';
        $html .= '</div>';
        if ($idx < count($uiSteps) - 1) {
            $lineCls = (!$isCancelled && $idx < $currentIdx) ? 'step-line step-line-done' : 'step-line';
            $html .= '<div class="' . $lineCls . '"></div>';
        }
    }
    $html .= '</div>';
    return $html;
}
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

            <span class="nav-link is-disabled" title="Coming soon" aria-disabled="true">
                <!-- star icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Reviews</span>
            </span>

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
                            <span class="price-tag">₹<?= number_format($b['total_price'], 0) ?></span>
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
                        <?php if ($b['booking_type'] === 'ESCROW' && $b['escrow_status']): ?>
                            <?= renderEscrowProgress($b['escrow_status']) ?>
                        <?php endif; ?>
                        <div class="card-footer" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span>
                                <?php if ($b['booking_type'] === 'ESCROW' && $b['escrow_status']): ?>
                                    <span class="escrow-chip escrow-<?= $b['escrow_status'] ?>"><?= str_replace('_', ' ', $b['escrow_status']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="actions-wrap" style="margin-left: auto;">
                                <?php if ($b['booking_type'] === 'ESCROW'): ?>
                                    <?php if ($b['escrow_status'] === 'PENDING_PAYMENT'): ?>
                                        <button class="btn-primary btn-sm btn-pay-escrow" data-txn="<?= e($b['transaction_id']) ?>" data-amount="<?= $b['total_price'] ?>">💳 Pay & Lock Funds</button>
                                    <?php elseif ($b['escrow_status'] === 'FUNDS_LOCKED'): ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); align-self: center; margin-right: 0.5rem;">Handover PIN: <strong style="color:var(--primary-action); letter-spacing: 0.1em;"><?= e($b['handover_otp']) ?></strong></span>
                                    <?php elseif ($b['escrow_status'] === 'ACTIVE_RENTAL'): ?>
                                        <button class="btn-primary btn-sm btn-otp-action" data-txn="<?= e($b['transaction_id']) ?>" data-type="return">↩ Return Equipment</button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn-secondary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="cancelled">Cancel</button>
                                <?php endif; ?>
                                <?php if ($b['status'] === 'active' && $b['booking_type'] !== 'ESCROW'): ?>
                                    <button class="btn-primary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="completed">Mark Completed</button>
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
                            <span class="price-tag">₹<?= number_format($b['total_price'], 0) ?></span>
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
                        <?php if ($b['booking_type'] === 'ESCROW' && $b['escrow_status']): ?>
                            <?= renderEscrowProgress($b['escrow_status']) ?>
                        <?php endif; ?>
                        <div class="card-footer" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span>
                                <?php if ($b['booking_type'] === 'ESCROW' && $b['escrow_status']): ?>
                                    <span class="escrow-chip escrow-<?= $b['escrow_status'] ?>"><?= str_replace('_', ' ', $b['escrow_status']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="actions-wrap" style="margin-left: auto;">
                                <?php if ($b['booking_type'] === 'ESCROW'): ?>
                                    <?php if ($b['escrow_status'] === 'FUNDS_LOCKED'): ?>
                                        <button class="btn-primary btn-sm btn-otp-action" data-txn="<?= $b['transaction_id'] ?>" data-type="handover">Verify Handover PIN</button>
                                    <?php elseif ($b['escrow_status'] === 'ACTIVE_RENTAL'): ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); align-self: center; margin-right: 0.5rem;">Return PIN: <strong style="color:var(--primary-action); letter-spacing: 0.1em;"><?= e($b['return_otp']) ?></strong></span>
                                    <?php endif; ?>
                                <?php endif; ?>

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
                                    <?php if ($b['booking_type'] !== 'ESCROW'): ?>
                                        <button class="btn-primary btn-sm status-action" <?= $btnData ?> data-status="completed">Mark Completed</button>
                                    <?php endif; ?>
                                    <button class="btn-secondary btn-sm status-action" <?= $btnData ?> data-status="cancelled">Cancel</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Escrow Payment Modal -->
<div class="otp-modal-overlay" id="paymentModalOverlay">
    <div class="otp-modal-card">
        <h2 class="otp-title" id="paymentTitle">Pay & Lock Funds</h2>
        <span class="otp-subtitle">Amount to be held in escrow:</span>
        <div style="font-size:2rem; font-weight:800; color:var(--primary-action); margin: 1rem 0;">₹<span id="paymentAmount">0</span></div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom: 1.5rem;">
            This amount will be locked securely. The owner only receives it after you verify the return of the equipment.
        </p>
        <div class="otp-actions">
            <button class="btn-secondary" id="paymentCancelBtn">Cancel</button>
            <button class="btn-primary" id="paymentConfirmBtn">Confirm Payment</button>
        </div>
    </div>
</div>

<!-- OTP Verification Modal -->
<div class="otp-modal-overlay" id="otpModalOverlay">
    <div class="otp-modal-card">
        <h2 class="otp-title" id="otpTitle">Verify PIN</h2>
        <span class="otp-subtitle" id="otpSubtitle">Enter the 4-digit PIN provided by the other party.</span>

        <div class="otp-input-group">
            <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
            <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
            <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
            <input type="text" class="otp-field" maxlength="1" pattern="\d*" inputmode="numeric">
        </div>

        <div class="otp-actions">
            <button class="btn-secondary" id="otpCancelBtn">Cancel</button>
            <button class="btn-primary" id="otpVerifyBtn">Verify PIN</button>
        </div>
    </div>
</div>

<input type="hidden" id="csrf_token" value="<?= generateCsrfToken() ?>">

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<script src="assets/js/dashboard.js" defer></script>
<script>
    // Tab Switching Logic
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn, .booking-grid').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
        });
    });

    // OTP Modal Logic
    const otpModal = {
        overlay: document.getElementById('otpModalOverlay'),
        fields: document.querySelectorAll('.otp-field'),
        verifyBtn: document.getElementById('otpVerifyBtn'),
        cancelBtn: document.getElementById('otpCancelBtn'),
        currentTxn: null,
        currentType: null,

        open(txnId, type) {
            this.currentTxn = txnId;
            this.currentType = type;
            document.getElementById('otpTitle').textContent = type === 'handover' ? 'Handover Verification' : 'Return Equipment';
            document.getElementById('otpSubtitle').textContent = type === 'handover'
                ? 'Ask the renter for the handover PIN to start the rental.'
                : 'Enter the return PIN provided by the owner to complete the return.';            
            this.fields.forEach(f => f.value = '');
            this.overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
            this.fields[0].focus();
        },

        close() {
            this.overlay.classList.remove('visible');
            document.body.style.overflow = '';
        },

        getOTP() {
            return Array.from(this.fields).map(f => f.value).join('');
        }
    };

    otpModal.fields.forEach((field, idx) => {
        field.addEventListener('input', (e) => {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val.slice(0, 1);
            if (val && idx < 3) {
                otpModal.fields[idx + 1].focus();
            }
            // Auto-submit on 4th digit
            if (idx === 3 && val) {
                setTimeout(() => {
                    if (!otpModal.verifyBtn.disabled) otpModal.verifyBtn.click();
                }, 100);
            }
        });
        field.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && idx > 0) otpModal.fields[idx - 1].focus();
        });
        // Paste support
        field.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
            if (paste.length === 0) return;
            paste.split('').forEach((ch, i) => {
                if (otpModal.fields[i]) otpModal.fields[i].value = ch;
            });
            const focusIdx = Math.min(paste.length, 3);
            otpModal.fields[focusIdx].focus();
            if (paste.length === 4) {
                setTimeout(() => {
                    if (!otpModal.verifyBtn.disabled) otpModal.verifyBtn.click();
                }, 100);
            }
        });
    });

    otpModal.cancelBtn.addEventListener('click', () => otpModal.close());
    otpModal.overlay.addEventListener('click', (e) => { if (e.target === otpModal.overlay) otpModal.close(); });

    document.querySelectorAll('.btn-otp-action').forEach(btn => {
        btn.addEventListener('click', () => {
            otpModal.open(btn.dataset.txn, btn.dataset.type);
        });
    });

    // Payment Modal Logic
    const paymentModal = {
        overlay: document.getElementById('paymentModalOverlay'),
        amountSpan: document.getElementById('paymentAmount'),
        cancelBtn: document.getElementById('paymentCancelBtn'),
        confirmBtn: document.getElementById('paymentConfirmBtn'),
        currentTxn: null,

        open(txnId, amount) {
            this.currentTxn = txnId;
            this.amountSpan.textContent = amount ? amount : '—';
            this.overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
            this.confirmBtn.disabled = false;
            this.confirmBtn.textContent = 'Confirm Payment';
        },

        close() {
            this.overlay.classList.remove('visible');
            document.body.style.overflow = '';
        },
        
        init() {
            // Add close X button dynamically
            const closeX = document.createElement('button');
            closeX.innerHTML = '&times;';
            closeX.style.cssText = 'position:absolute;top:15px;right:15px;background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1;padding:0;';
            closeX.addEventListener('click', () => this.close());
            this.overlay.querySelector('.otp-modal-card').style.position = 'relative';
            this.overlay.querySelector('.otp-modal-card').appendChild(closeX);

            this.cancelBtn.addEventListener('click', () => this.close());
            this.overlay.addEventListener('click', (e) => { if (e.target === this.overlay) this.close(); });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.overlay.classList.contains('visible')) {
                    this.close();
                }
            });

            this.confirmBtn.addEventListener('click', async () => {
                this.confirmBtn.disabled = true;
                this.confirmBtn.innerHTML = '<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px;"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('transaction_id', this.currentTxn);
                    formData.append('csrf_token', document.getElementById('csrf_token').value);

                    const res = await fetch('api/process_escrow_payment.php', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.success) {
                        showInlineToast('success', data.message || 'Payment successful!');
                        this.close();
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showInlineToast('error', data.message || 'Payment failed.');
                        this.confirmBtn.disabled = false;
                        this.confirmBtn.textContent = 'Confirm Payment';
                    }
                } catch (err) {
                    showInlineToast('error', 'Network error. Please try again.');
                    this.confirmBtn.disabled = false;
                    this.confirmBtn.textContent = 'Confirm Payment';
                }
            });
        }
    };
    
    // Initialize Modal Events
    paymentModal.init();

    document.querySelectorAll('.btn-pay-escrow').forEach(btn => {
        btn.addEventListener('click', () => {
            paymentModal.open(btn.dataset.txn, btn.dataset.amount);
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

    otpModal.verifyBtn.addEventListener('click', async () => {
        const otp = otpModal.getOTP();
        if (otp.length !== 4) {
            showInlineToast('error', 'Please enter all 4 digits.');
            return;
        }

        // Loading spinner state
        otpModal.verifyBtn.disabled = true;
        otpModal.verifyBtn.innerHTML = '<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:6px;"></span>Verifying...';

        try {
            const endpoint = otpModal.currentType === 'handover' ? 'api/verify_handover.php' : 'api/verify_return.php';
            const formData = new FormData();
            formData.append('transaction_id', otpModal.currentTxn);
            formData.append('submitted_otp', otp);
            formData.append('csrf_token', document.getElementById('csrf_token').value);

            const res = await fetch(endpoint, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                // Check if we need to show return PIN (handover success case)
                if (otpModal.currentType === 'handover' && data.data && data.data.demo_return_otp) {
                    otpModal.close();
                    
                    // Show Return PIN Success Modal
                    const returnPin = data.data.demo_return_otp;
                    const txnId = data.data.transaction_id || otpModal.currentTxn;
                    
                    const overlay = document.createElement('div');
                    overlay.className = 'otp-modal-overlay visible';
                    overlay.innerHTML = `
                        <div class="otp-modal-card" style="max-width:420px;">
                            <div style="margin-bottom:1.5rem;">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary-action)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                            <h2 class="otp-title">Handover Verified</h2>
                            <p class="otp-subtitle" style="margin-bottom:1.5rem;">Rental is now active. Please note down the <strong>Return PIN</strong> below.</p>
                            
                            <div style="background:rgba(255,255,255,0.05); border:1px dashed var(--primary-action); border-radius:16px; padding:1.5rem; margin-bottom:2rem;">
                                <span style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--primary-action); display:block; margin-bottom:0.5rem; font-weight:800;">Demo Return PIN</span>
                                <span style="font-size:2.5rem; font-weight:900; letter-spacing:0.2em; color:#fff;">${returnPin}</span>
                                <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.5rem;">Ask the renter for this PIN when the equipment is returned.</p>
                            </div>

                            <div style="font-size:0.8rem; color:var(--text-subtle); margin-bottom:2rem;">
                                Transaction: <code style="color:var(--text-main);">${txnId}</code>
                            </div>

                            <button class="btn-primary" id="handoverFinalBtn" style="width:100%;">Got it, continue</button>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                    document.body.style.overflow = 'hidden';

                    overlay.querySelector('#handoverFinalBtn').addEventListener('click', () => {
                        location.reload();
                    });
                } else {
                    showInlineToast('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                // Shake animation on wrong PIN
                const inputGroup = document.querySelector('.otp-input-group');
                inputGroup.classList.add('otp-shake');
                setTimeout(() => inputGroup.classList.remove('otp-shake'), 600);
                otpModal.fields.forEach(f => f.value = '');
                otpModal.fields[0].focus();

                showInlineToast('error', data.message);
                otpModal.verifyBtn.disabled = false;
                otpModal.verifyBtn.textContent = 'Verify PIN';
            }
        } catch (err) {
            showInlineToast('error', 'Network error. Please try again.');
            otpModal.verifyBtn.disabled = false;
            otpModal.verifyBtn.textContent = 'Verify PIN';
        }
    });

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
