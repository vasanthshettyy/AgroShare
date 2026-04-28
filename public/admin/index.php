<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

// Start session if not already started in config/db.php
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole('admin');

// Default view
$view = $_GET['view'] ?? 'overview';
$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Common layout data
$fullName  = trim($_SESSION['full_name'] ?? '');
$nameParts = explode(' ', $fullName);
$initials  = !empty($nameParts[0]) ? strtoupper(substr($nameParts[0], 0, 1)) : 'A';
if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Hub — <?= e(APP_NAME) ?></title>
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    
    <!-- Theme Flash Prevention -->
    <?php require_once __DIR__ . '/../includes/theme-script.php'; ?>

    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/admin-layout.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
</head>
<body class="admin-body">

<div class="admin-sidebar" role="navigation">
    <div class="admin-sidebar-header">
        <div class="brand-mark" style="color: var(--admin-accent);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/><path d="M6 22c0-4 2-7 6-9"/>
            </svg>
        </div>
        <span style="font-weight: 800; font-size: 1.2rem; letter-spacing: -0.5px;">Admin<span style="color: var(--admin-accent);">Core</span></span>
    </div>

    <nav class="admin-nav">
        <div class="admin-nav-item <?= $view === 'overview' ? 'active' : '' ?>" data-view="overview">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Overview</span>
        </div>
        <div class="admin-nav-item <?= $view === 'users' ? 'active' : '' ?>" data-view="users">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Users</span>
        </div>
        <div class="admin-nav-item <?= $view === 'equipment' ? 'active' : '' ?>" data-view="equipment">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
            <span>Equipment</span>
        </div>
        <div class="admin-nav-item <?= $view === 'bookings' ? 'active' : '' ?>" data-view="bookings">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <span>Bookings</span>
        </div>
        <div class="admin-nav-item <?= $view === 'pooling' ? 'active' : '' ?>" data-view="pooling">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M5 12h14"/><path d="m17 7 5 5-5 5M7 7l-5 5 5 5"/></svg>
            <span>Pooling</span>
        </div>
        <div class="admin-nav-item <?= $view === 'logs' ? 'active' : '' ?>" data-view="logs">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>Audit Logs</span>
        </div>
        
        <div style="margin-top: auto; padding-top: 2rem;">
            <div class="admin-nav-item <?= $view === 'settings' ? 'active' : '' ?>" data-view="settings">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Settings</span>
            </div>
            <a href="../logout.php" class="admin-nav-item" style="color: var(--error-color);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</div>

<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <h2 id="view-title" style="font-weight: 700; margin: 0; text-transform: capitalize;"><?= e($view) ?></h2>
        </div>
        
        <div class="topbar-right" style="display: flex; align-items: center; gap: 1.5rem;">
            <!-- Theme Toggle -->
            <button class="btn-icon theme-toggle" id="themeToggleBtn" aria-label="Toggle light/dark mode">
                <svg class="theme-icon sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="theme-icon moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>

            <!-- Admin Profile Link -->
            <div style="display: flex; align-items: center; gap: 10px; padding: 6px 12px; background: rgba(255,255,255,0.05); border-radius: 50px; border: 1px solid var(--admin-border);">
                <div class="avatar" style="width: 32px; height: 32px; font-size: 0.8rem; background: var(--admin-accent); color: white;">
                    <?= e($initials) ?>
                </div>
                <span style="font-weight: 600; font-size: 0.85rem; padding-right: 4px;"><?= e($adminName) ?></span>
            </div>
        </div>
    </header>

    <div class="admin-view-container" id="main-view-content">
        <!-- Content will be loaded here via AJAX or current PHP include -->
        <div class="admin-skeleton" style="height: 400px; width: 100%;"></div>
    </div>
</div>

<script src="../assets/js/theme-toggle.js"></script>
<script src="../assets/js/admin-core.js?v=<?= time() ?>"></script>
</body>
</html>
