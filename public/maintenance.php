<?php
/**
 * maintenance.php — "Emerald Harvest" themed maintenance page.
 */
require_once __DIR__ . '/../config/db.php';

// If we are on this page but maintenance mode is OFF, redirect back to home/dashboard
$res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
if ($res) {
    $mMode = $res->fetch_column();
    if ($mMode !== '1') {
        header('Location: ' . getBasePath() . '/public/dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* ── Emerald Harvest Design System ── */
            --bg-color: hsl(144, 28%, 6%);
            --surface: hsl(150, 24%, 10%);
            --text-main: hsl(90, 20%, 90%);
            --text-muted: hsl(140, 14%, 60%);
            --primary: hsl(150, 50%, 45%);
            --primary-glow: hsla(150, 50%, 45%, 0.15);
            --font: 'Inter', system-ui, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, hsla(150, 50%, 45%, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, hsla(160, 40%, 30%, 0.05) 0px, transparent 50%);
            color: var(--text-main);
            font-family: var(--font);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        .maintenance-card {
            max-width: 560px;
            width: 90%;
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        /* ── The Emerald Glow ── */
        .glow-orb {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            height: 40px;
            background: var(--primary);
            filter: blur(40px);
            opacity: 0.2;
            z-index: -1;
        }

        .brand-icon {
            width: 72px;
            height: 72px;
            background: var(--bg-color);
            border: 1px solid var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            color: var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
            animation: pulse 3s infinite ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px var(--primary-glow); }
            50% { transform: scale(1.05); box-shadow: 0 0 35px var(--primary-glow); }
        }

        h1 {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            color: #fff;
        }

        .apology {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 24px;
            display: block;
        }

        p {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 1.1rem;
            margin-bottom: 32px;
        }

        .status-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px dashed rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .pulse-indicator {
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            position: relative;
        }

        .pulse-indicator::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            animation: ring 1.5s infinite;
        }

        @keyframes ring {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }

        .status-text {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .relogin-hint {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 12px;
        }

        footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.8rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="glow-orb"></div>
        <div class="brand-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                <path d="M6 22c0-4 2-7 6-9"/>
            </svg>
        </div>

        <span class="apology">We're Sincerely Sorry</span>
        <h1>Enhancing Your Field</h1>
        
        <p>
            We are currently performing essential maintenance to improve the platform's stability. 
            <strong>We apologize for this temporary inconvenience</strong> and appreciate your patience as we work to serve you better.
        </p>

        <div class="status-box">
            <div class="pulse-indicator"></div>
            <span class="status-text">System upgrade in progress</span>
        </div>
        
        <div class="relogin-hint">
            Please try logging back in after a few minutes.
        </div>

        <footer>
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &bull; SECURE &bull; STABLE &bull; SUSTAINABLE
        </footer>
    </div>
</body>
</html>
