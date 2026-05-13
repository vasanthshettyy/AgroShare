<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$userId = (int)$_SESSION['user_id'];

// Ensure full_name is in session (fallback: fetch from DB)
if (empty($_SESSION['full_name'])) {
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $_SESSION['full_name'] = $row['full_name'] ?? 'Farmer';
}

$fullName = $_SESSION['full_name'];
$nameParts = explode(' ', $fullName);
$initials   = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

// —— Greeting based on time of day ──────────────────────────
$hour     = (int) date('G');
$greeting = match(true) {
    $hour < 12  => 'Good Morning',
    $hour < 17  => 'Good Afternoon',
    default     => 'Good Evening',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Feedback — <?= e(APP_NAME) ?></title>
    
    <?php require_once __DIR__ . '/includes/theme-script.php'; ?>
    
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .feedback-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 32px;
            background: var(--glass-bg-heavy);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            animation: fadeSlideUp 0.5s var(--ease-out) forwards;
        }
        .feedback-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .feedback-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .feedback-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .feedback-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }
        .feedback-form textarea {
            width: 100%;
            min-height: 150px;
            padding: 16px;
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-main);
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            transition: var(--transition-base);
            outline: none;
        }
        .feedback-form textarea:focus {
            border-color: var(--primary-action);
            box-shadow: 0 0 0 3px rgba(76, 175, 120, 0.15);
            background: var(--bg-color);
        }
        .submit-btn {
            background: linear-gradient(135deg, var(--primary-action), var(--accent-dark));
            color: #fff;
            padding: 12px 24px;
            border-radius: 40px; /* Rounded pill style */
            font-weight: 700;
            font-size: 1rem;
            transition: var(--transition-base);
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 14px rgba(19, 83, 44, 0.3);
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(19, 83, 44, 0.4), var(--glow-primary);
        }
        .submit-btn:active {
            transform: translateY(0) scale(0.96);
        }
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        #status-message {
            margin-top: 16px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            min-height: 20px;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <!-- -- TOPBAR -- -->
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <p class="topbar-greeting">
                <?= e($greeting) ?>, <strong><?= e($fullName) ?></strong>
            </p>
        </div>

        <div class="topbar-right">
            <!-- Theme Toggle (Pill) -->
            <?php include __DIR__ . '/includes/theme-toggle-btn.php'; ?>

            <button class="btn-icon" id="notifBtn" title="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span class="notif-dot" id="notifDot" style="display: none;"></span>
            </button>

            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">Notifications</div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Loading...</div>
                </div>
            </div>

            <div class="avatar" id="avatar-btn" role="button" tabindex="0">
                <?= e($initials) ?>
            </div>
        </div>
    </header>

    <!-- -- SIDEBAR -- -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-brand">
            <div class="brand-mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Main</span>
            <a href="dashboard.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="equipment-browse.php?mine=1" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
                <span>My Equipment</span>
            </a>
            <a href="my-bookings.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                <span>My Bookings</span>
            </a>

            <span class="nav-section-label">Community</span>
            <a href="pooling-browse.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Pooling</span>
            </a>
            <a href="equipment-browse.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <span>Browse</span>
            </a>

            <span class="nav-section-label">Account</span>
            <a href="javascript:void(0)" class="nav-link" id="profile-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>
                <span>Profile</span>
            </a>
            <a href="feedback.php" class="nav-link active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                <span>Send Feedback</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- -- MAIN CONTENT -- -->
    <main class="main-content">
        <div class="feedback-container">
            <header class="feedback-header">
                <h1>Help Us Grow</h1>
                <p>Your suggestions and feedback help us make AgroShare better for everyone.</p>
            </header>

            <form id="standalone-feedback-form" class="feedback-form">
                <input type="hidden" id="csrfToken" value="<?= generateCsrfToken() ?>">
                
                <div class="form-group">
                    <label for="feedback-message">What's on your mind?</label>
                    <textarea id="feedback-message" placeholder="Type your feedback here..." required></textarea>
                </div>

                <button type="submit" class="submit-btn" id="submit-btn">Send Feedback</button>
            </form>
            
            <div id="status-message"></div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<?php require_once __DIR__ . '/includes/viewer-reviews-modal.php'; ?>
<?php require_once __DIR__ . '/includes/user-public-profile-modal.php'; ?>
<script src="assets/js/theme-toggle.js?v=<?= time() ?>" defer></script>
<script src="assets/js/reviews.js?v=<?= time() ?>" defer></script>
<script src="assets/js/dashboard.js?v=<?= time() ?>" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('standalone-feedback-form');
    const messageInput = document.getElementById('feedback-message');
    const submitBtn = document.getElementById('submit-btn');
    const statusMsg = document.getElementById('status-message');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;

            submitBtn.disabled = true;
            statusMsg.innerHTML = '<span style="color:var(--text-muted);">Sending...</span>';

            try {
                const formData = new FormData();
                formData.append('message', message);
                formData.append('csrf_token', document.getElementById('csrfToken').value);

                const response = await fetch('api/submit-support.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageInput.value = '';
                    statusMsg.innerHTML = `<span style="color:var(--primary-action);">${result.message}</span>`;
                } else {
                    statusMsg.innerHTML = `<span style="color:var(--danger);">${result.message}</span>`;
                }
            } catch (error) {
                console.error('Feedback Error:', error);
                statusMsg.innerHTML = '<span style="color:var(--danger);">Network error. Please try again.</span>';
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});
</script>
</body>
</html>
