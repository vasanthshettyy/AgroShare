<?php
require_once __DIR__ . '/../config/db.php';

// If they are fully logged in (active), send them to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// If they don't even have a deactivated session, send to login
if (!isset($_SESSION['deactivated_user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['deactivated_user_id'];
$userName = 'User';

// Fetch the user's name just for a friendly greeting (optional)
try {
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $userName = $row['full_name'];
    }
    $stmt->close();
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deactivated — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/fonts.css">
    <style>
        :root {
            --bg-color:            hsl(144, 28%, 6%);
            --surface-color:       hsl(150, 24%, 10%);
            --text-main:           hsl(90, 20%, 90%);
            --text-muted:          hsl(140, 14%, 60%);
            --border-color:        hsl(150, 20%, 16%);
            --primary-action:      hsl(150, 50%, 45%); 
            --danger:              #E11D48;
            --danger-bg:           rgba(225, 29, 72, 0.1);
            --shadow-lg:           0 10px 25px rgba(0, 0, 0, 0.5);
            --radius:              18px;
            --radius-sm:           12px;
            --font:                'Inter', system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a { color: inherit; text-decoration: none; }
        body { font-family: var(--font); background: var(--bg-color); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        
        .deactivated-wrapper { max-width: 500px; width: 100%; background: var(--surface-color); border-radius: var(--radius); box-shadow: var(--shadow-lg); padding: 40px; text-align: center; border-top: 4px solid var(--danger); }
        
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
        .icon-circle svg { width: 40px; height: 40px; }
        
        h1 { font-size: 1.75rem; margin-bottom: 0.75rem; }
        p { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; line-height: 1.6; }
        
        .contact-form { text-align: left; background: var(--bg-color); padding: 24px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); }
        .contact-form h3 { font-size: 1.1rem; margin-bottom: 1rem; color: var(--text-main); }
        
        .form-group { margin-bottom: 1rem; }
        textarea { width: 100%; background: var(--surface-color); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px; color: var(--text-main); font-family: var(--font); min-height: 120px; resize: vertical; outline: none; transition: border-color 0.2s; }
        textarea:focus { border-color: var(--primary-action); }
        
        .btn-submit { width: 100%; background: var(--primary-action); color: #fff; border: none; padding: 12px; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer; transition: filter 0.2s ease; margin-top: 0.5rem; }
        .btn-submit:hover { filter: brightness(1.1); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .logout-link { display: inline-block; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem; text-decoration: underline; transition: color 0.2s; }
        .logout-link:hover { color: var(--text-main); }

        #status-msg { margin-top: 1rem; font-size: 0.85rem; font-weight: 600; text-align: center; }
        .text-success { color: var(--primary-action); }
        .text-danger { color: var(--danger); }
    </style>
</head>
<body>
<main class="main-content deactivated-wrapper">
    <div class="icon-circle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
    </div>
    
    <h1>Account Deactivated</h1>
    <p>
        Hello <?= e($userName) ?>, your account has been temporarily deactivated by an administrator. 
        You currently do not have access to the platform's features.
    </p>

    <div class="contact-form">
        <h3>Request Reactivation</h3>
        <form id="appealForm">
            <input type="hidden" id="csrfToken" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <textarea id="appealMessage" placeholder="Please explain why you need your account reactivated or ask for clarification..." required></textarea>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">Send Message to Admin</button>
            <div id="status-msg"></div>
        </form>
    </div>

    <a href="logout.php" class="logout-link">Log out of this session</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('appealForm');
    const messageInput = document.getElementById('appealMessage');
    const submitBtn = document.getElementById('submitBtn');
    const statusMsg = document.getElementById('status-msg');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            statusMsg.innerHTML = '';

            try {
                const formData = new FormData();
                formData.append('message', `[REACTIVATION REQUEST]: ${message}`);
                formData.append('csrf_token', document.getElementById('csrfToken').value);

                const response = await fetch('api/submit-support.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageInput.value = '';
                    statusMsg.className = 'text-success';
                    statusMsg.textContent = result.message;
                    form.style.display = 'none'; // Hide form on success
                    
                    const successDiv = document.createElement('div');
                    successDiv.style.color = 'var(--primary-action)';
                    successDiv.style.fontWeight = 'bold';
                    successDiv.style.textAlign = 'center';
                    successDiv.style.marginTop = '1rem';
                    successDiv.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom:8px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><br>Your request has been submitted.';
                    form.parentNode.appendChild(successDiv);
                } else {
                    statusMsg.className = 'text-danger';
                    statusMsg.textContent = result.message;
                }
            } catch (error) {
                console.error('Submission Error:', error);
                statusMsg.className = 'text-danger';
                statusMsg.textContent = 'Network error. Please try again.';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Message to Admin';
            }
        });
    }
});
</script>
<script src="assets/js/realtime.js?v=<?= time() ?>" defer></script>
</body>
</html>
