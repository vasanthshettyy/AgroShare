<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

requireRole('admin');

$settings = getSettings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Settings — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        .form-container { background: var(--surface-color); padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); max-width: 600px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; color: var(--text-main); font-weight: 600; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 10px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-family: inherit; }
        .btn-submit { background: var(--primary-action); color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s ease; }
        .btn-submit:hover { background: var(--accent-dark); }
        .btn-submit.is-saving { opacity: 0.7; pointer-events: none; }

        .setting-card {
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: linear-gradient(160deg, rgba(24, 38, 29, 0.9), rgba(18, 30, 22, 0.9));
        }
        .setting-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .setting-title {
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 700;
        }
        .status-pill {
            border: 1px solid var(--border-color);
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.4px;
        }
        .status-pill.on {
            color: #8EEFA8;
            border-color: rgba(76, 175, 120, 0.45);
            background: rgba(76, 175, 120, 0.14);
            box-shadow: 0 0 0 1px rgba(76, 175, 120, 0.15), 0 0 12px rgba(76, 175, 120, 0.18);
        }
        .status-pill.off {
            color: #C5D0C8;
            border-color: rgba(180, 195, 184, 0.35);
            background: rgba(180, 195, 184, 0.10);
        }
        .setting-help {
            color: var(--text-muted);
            font-size: 0.82rem;
            margin-bottom: 12px;
        }

        .switch-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .switch {
            --switch-w: 62px;
            --switch-h: 34px;
            width: var(--switch-w);
            height: var(--switch-h);
            border-radius: 999px;
            border: 1px solid rgba(180, 195, 184, 0.35);
            background: rgba(98, 112, 101, 0.35);
            position: relative;
            transition: background 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease, transform 0.18s ease;
            cursor: pointer;
        }
        .switch:hover {
            transform: translateY(-1px);
        }
        .switch:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 120, 0.28);
        }
        .switch-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #F1F6F2;
            transition: transform 0.28s ease, box-shadow 0.28s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        .switch.is-on {
            background: linear-gradient(130deg, #2F8A4A, #45A866);
            border-color: rgba(76, 175, 120, 0.8);
            box-shadow: 0 0 16px rgba(76, 175, 120, 0.22);
        }
        .switch.is-on .switch-thumb {
            transform: translateX(28px);
            box-shadow: 0 2px 12px rgba(26, 82, 44, 0.45);
        }
        .switch.is-saving {
            cursor: progress;
            opacity: 0.85;
        }
        .switch.is-saving .switch-thumb {
            animation: pulseThumb 0.8s ease-in-out infinite;
        }
        @keyframes pulseThumb {
            0%, 100% { transform: translateX(0) scale(1); }
            50% { transform: translateX(0) scale(0.94); }
        }
        .switch.is-on.is-saving .switch-thumb {
            animation: pulseThumbOn 0.8s ease-in-out infinite;
        }
        @keyframes pulseThumbOn {
            0%, 100% { transform: translateX(28px) scale(1); }
            50% { transform: translateX(28px) scale(0.94); }
        }

        .save-state {
            min-height: 18px;
            font-size: 0.76rem;
            color: var(--text-subtle);
        }
        .save-state.ok {
            color: #8EEFA8;
        }
        .save-state.err {
            color: #FF8C8C;
        }

        .toast {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 1200;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            border: 1px solid var(--border-color);
            background: rgba(17, 30, 22, 0.94);
            color: var(--text-main);
            box-shadow: 0 8px 22px rgba(0,0,0,0.38);
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.success {
            border-color: rgba(76, 175, 120, 0.55);
            color: #8EEFA8;
        }
        .toast.error {
            border-color: rgba(220, 86, 86, 0.6);
            color: #FF8C8C;
        }
    </style>
</head>
<body data-theme="dark">

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?> Admin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Users</span>
            </a>
            <a href="equipment.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                <span>Equipment</span>
            </a>
            <a href="bookings.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span>Bookings</span>
            </a>
            <a href="settings.php" class="nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>Settings</span>
            </a>
            <a href="logs.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>Audit Logs</span>
            </a>
            <a href="../logout.php" class="nav-link danger" style="margin-top: auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 24px;">
            <h1>Global Settings</h1>
        </header>

        <?= renderFlash() ?>

        <div class="form-container">
            <form method="POST" action="api/update-setting.php" id="settingsForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" id="maintenance_mode" name="maintenance_mode" value="<?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? '1' : '0' ?>">

                <div class="setting-card">
                    <div class="setting-head">
                        <h2 class="setting-title">Maintenance Mode</h2>
                        <span id="maintenanceStatusPill" class="status-pill <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'on' : 'off' ?>">
                            <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'ON' : 'OFF' ?>
                        </span>
                    </div>
                    <p id="maintenanceHelper" class="setting-help">
                        <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1')
                            ? 'Public pages are blocked. Admin access remains available.'
                            : 'Platform is live for all users.' ?>
                    </p>
                    <div class="switch-row">
                        <button
                            type="button"
                            id="maintenanceSwitch"
                            class="switch <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'is-on' : '' ?>"
                            role="switch"
                            aria-checked="<?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'true' : 'false' ?>"
                            aria-label="Maintenance Mode"
                        >
                            <span class="switch-thumb" aria-hidden="true"></span>
                        </button>
                        <span id="maintenanceSaveState" class="save-state"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" class="form-input" value="<?= e($settings['site_name'] ?? APP_NAME) ?>">
                </div>

                <button type="submit" class="btn-submit">Save Settings</button>
            </form>
        </div>
    </main>
</div>

<div id="settingsToast" class="toast" aria-live="polite"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settingsForm');
    const switchBtn = document.getElementById('maintenanceSwitch');
    const maintenanceInput = document.getElementById('maintenance_mode');
    const helper = document.getElementById('maintenanceHelper');
    const pill = document.getElementById('maintenanceStatusPill');
    const saveState = document.getElementById('maintenanceSaveState');
    const toast = document.getElementById('settingsToast');
    const siteNameInput = document.getElementById('site_name');

    if (!form || !switchBtn || !maintenanceInput) return;

    const showToast = (message, type) => {
        if (!toast) return;
        toast.textContent = message;
        toast.className = `toast ${type} show`;
        window.clearTimeout(window.__settingsToastTimer);
        window.__settingsToastTimer = window.setTimeout(() => {
            toast.classList.remove('show');
        }, 2400);
    };

    const setUiState = (isOn) => {
        maintenanceInput.value = isOn ? '1' : '0';
        switchBtn.classList.toggle('is-on', isOn);
        switchBtn.setAttribute('aria-checked', isOn ? 'true' : 'false');
        pill.textContent = isOn ? 'ON' : 'OFF';
        pill.classList.toggle('on', isOn);
        pill.classList.toggle('off', !isOn);
        helper.textContent = isOn
            ? 'Public pages are blocked. Admin access remains available.'
            : 'Platform is live for all users.';
    };

    const saveMaintenanceMode = async (nextValue) => {
        const previousValue = maintenanceInput.value;
        setUiState(nextValue === '1');
        switchBtn.classList.add('is-saving');
        saveState.textContent = 'Saving...';
        saveState.className = 'save-state';

        try {
            const payload = new FormData();
            payload.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);
            payload.append('site_name', siteNameInput ? siteNameInput.value : '');
            payload.append('maintenance_mode', nextValue);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Could not save maintenance mode.');
            }

            saveState.textContent = 'Saved';
            saveState.classList.add('ok');
            showToast('Maintenance mode updated.', 'success');
        } catch (error) {
            setUiState(previousValue === '1');
            saveState.textContent = 'Save failed';
            saveState.classList.add('err');
            showToast(error.message || 'Failed to save setting.', 'error');
        } finally {
            switchBtn.classList.remove('is-saving');
        }
    };

    switchBtn.addEventListener('click', () => {
        if (switchBtn.classList.contains('is-saving')) return;
        const next = maintenanceInput.value === '1' ? '0' : '1';
        saveMaintenanceMode(next);
    });

    switchBtn.addEventListener('keydown', (event) => {
        if (event.key !== ' ' && event.key !== 'Enter') return;
        event.preventDefault();
        switchBtn.click();
    });

    form.addEventListener('submit', () => {
        const submitBtn = form.querySelector('.btn-submit');
        if (submitBtn) {
            submitBtn.classList.add('is-saving');
            submitBtn.textContent = 'Saving...';
        }
    });
});
</script>

</body>
</html>
