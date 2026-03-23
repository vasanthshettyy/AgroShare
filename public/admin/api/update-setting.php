<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../src/Helpers/audit.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../settings.php');
    exit();
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit();
    }
    setFlash('error', 'Invalid security token.');
    header('Location: ../settings.php');
    exit();
}

// Ensure settings table exists, or catch error gracefully
try {
    $siteName = trim($_POST['site_name'] ?? APP_NAME);
    $maintenanceMode = (($_POST['maintenance_mode'] ?? '0') === '1') ? 1 : 0;

    // Simplistic approach for two settings using REPLACE INTO or INSERT ON DUPLICATE KEY UPDATE
    // Assumes `settings` table has `setting_key` (VARCHAR, UNIQUE) and `setting_value` (VARCHAR)
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Save Site Name
        $key1 = 'site_name';
        $stmt->bind_param('ss', $key1, $siteName);
        $stmt->execute();

        // Save Maintenance Mode
        $key2 = 'maintenance_mode';
        $val2 = (string)$maintenanceMode;
        $stmt->bind_param('ss', $key2, $val2);
        $stmt->execute();
        
        $stmt->close();

        logAuditEvent($conn, 'admin_update_settings', null, "Admin updated global settings", $_SESSION['user_id']);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Settings saved successfully.',
                'maintenance_mode' => (string)$maintenanceMode
            ]);
            exit();
        }
        setFlash('success', "Settings saved successfully.");
    } else {
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Settings table may not exist in schema yet.']);
            exit();
        }
        setFlash('error', "Settings table may not exist in schema yet.");
    }
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
        exit();
    }
    setFlash('error', "Database error: " . $e->getMessage());
} catch (Error $e) {
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
        exit();
    }
    setFlash('error', "Database error: " . $e->getMessage());
}

header('Location: ../settings.php');
exit();
