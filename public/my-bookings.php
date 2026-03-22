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
    <script>document.documentElement.setAttribute('data-theme', 'dark');</script>
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
        .status-rejected  { background: rgba(244, 67, 54, 0.15); color: #F44336; border: 1px solid rgba(244, 67, 54, 0.2); }
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

            <a href="#" class="nav-link">
                <!-- users icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Pooling</span>
            </a>

            <a href="equipment-browse.php" class="nav-link">
                <!-- search icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <span>Browse</span>
            </a>

            <a href="#" class="nav-link">
                <!-- star icon -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Reviews</span>
            </a>

            <span class="nav-section-label">Account</span>

            <a href="#" class="nav-link" id="profile-btn">
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
                <button class="tab-btn active" data-tab="rentals">My Rentals</button>
                <button class="tab-btn" data-tab="requests">Incoming Requests</button>
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
                        <div class="card-footer" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span>
                            </div>
                            <div class="actions-wrap" style="margin-left: auto;">
                                <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn-secondary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="cancelled">Cancel</button>
                                <?php endif; ?>
                                <?php if ($b['status'] === 'active'): ?>
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
                        </div>
                        <div class="card-footer" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span>
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <div class="contact-reveal-container">
                                        <button type="button" class="btn-contact btn-reveal-contact" data-phone="<?= e($b['renter_phone'] ?? '') ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            Contact Info
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="actions-wrap" style="margin-left: auto;">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <button class="btn-primary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="confirmed">Accept</button>
                                    <button class="btn-danger btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="rejected">Reject</button>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <button class="btn-primary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="completed">Mark Completed</button>
                                    <button class="btn-secondary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="cancelled">Cancel</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
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
            
            if (!confirm(`Are you sure you want to set this booking as ${newStatus}?`)) return;

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
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            } catch (err) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    });
</script>
</body>
</html>