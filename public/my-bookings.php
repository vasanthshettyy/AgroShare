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
        .bookings-tabs {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0.5rem 0.25rem;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
        }
        .tab-btn.active {
            color: var(--primary-action);
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -0.6rem;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-action);
        }
        .booking-table-container {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        .booking-table-container.active {
            display: block;
        }
        .status-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending   { background: rgba(255, 193, 7, 0.15); color: #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.15); color: #4CAF50; }
        .status-rejected  { background: rgba(244, 67, 54, 0.15); color: #F44336; }
        .status-completed { background: rgba(33, 150, 243, 0.15); color: #2196F3; }
        .status-cancelled { background: rgba(158, 158, 158, 0.15); color: #9E9E9E; }

        @media (max-width: 768px) {
            .data-table thead { display: none; }
            .data-table tr { 
                display: flex; 
                flex-direction: column; 
                background: var(--surface-color);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 1rem;
                margin-bottom: 1rem;
                gap: 0.5rem;
            }
            .data-table td { 
                display: flex; 
                justify-content: space-between; 
                border: none; 
                padding: 0.25rem 0;
            }
            .data-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--text-muted);
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>

<div class="app-layout">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <p class="topbar-greeting">My Bookings</p>
        </div>
        <div class="avatar"><?= e($initials) ?></div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/><path d="M6 22c0-4 2-7 6-9"/></svg></div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>
        <nav class="sidebar-nav">
            <span class="nav-section-label">Main</span>
            <a href="dashboard.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span>Dashboard</span></a>
            <a href="equipment-browse.php?mine=1" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg><span>My Equipment</span></a>
            <a href="my-bookings.php" class="nav-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg><span>My Bookings</span></a>
            <span class="nav-section-label">Community</span>
            <a href="equipment-browse.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><span>Browse</span></a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="nav-link danger">Log Out</a></div>
    </aside>

    <main class="main-content">
        <div class="bookings-tabs">
            <button class="tab-btn active" data-tab="rentals">My Rentals</button>
            <button class="tab-btn" data-tab="requests">Incoming Requests</button>
        </div>

        <!-- Rentals Table -->
        <div class="booking-table-container active" id="rentals">
            <div class="glass-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Owner</th>
                            <th>Dates</th>
                            <th>Total Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $b): ?>
                        <tr>
                            <td data-label="Equipment"><?= e($b['equipment_title']) ?></td>
                            <td data-label="Owner"><?= e($b['owner_name']) ?></td>
                            <td data-label="Dates"><?= date('d M', strtotime($b['start_datetime'])) ?> - <?= date('d M', strtotime($b['end_datetime'])) ?></td>
                            <td data-label="Total Price">₹<?= number_format($b['total_price'], 0) ?></td>
                            <td data-label="Status"><span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="booking-table-container" id="requests">
            <div class="glass-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Renter</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $b): ?>
                        <tr>
                            <td data-label="Equipment"><?= e($b['equipment_title']) ?></td>
                            <td data-label="Renter"><?= e($b['renter_name']) ?><br><small><?= e($b['renter_phone']) ?></small></td>
                            <td data-label="Dates"><?= date('d M', strtotime($b['start_datetime'])) ?> - <?= date('d M', strtotime($b['end_datetime'])) ?></td>
                            <td data-label="Status"><span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                            <td data-label="Actions">
                                <?php if ($b['status'] === 'pending'): ?>
                                <button class="btn-primary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="confirmed">Confirm</button>
                                <button class="btn-secondary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="rejected">Reject</button>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                <button class="btn-primary btn-sm status-action" data-id="<?= $b['id'] ?>" data-status="completed">Complete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<input type="hidden" id="csrf_token" value="<?= generateCsrfToken() ?>">

<script>
    // Tab Switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn, .booking-table-container').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
        });
    });

    // Status Actions
    document.querySelectorAll('.status-action').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm(`Are you sure you want to ${btn.dataset.status} this booking?`)) return;

            const formData = new FormData();
            formData.append('id', btn.dataset.id);
            formData.append('status', btn.dataset.status);
            formData.append('csrf_token', document.getElementById('csrf_token').value);

            try {
                const res = await fetch('api/update-booking-status.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Network error.');
            }
        });
    });
</script>
</body>
</html>
