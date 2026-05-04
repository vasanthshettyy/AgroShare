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

try {
    $settingKey   = trim($_POST['setting_key'] ?? '');
    $settingValue = $_POST['setting_value'] ?? '';

    if ($settingKey === '') {
        throw new Exception("Setting key is required.");
    }

    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $settingKey, $settingValue);
        $stmt->execute();
        $stmt->close();

        logAuditEvent($conn, 'admin_update_setting', null, "Admin updated setting: $settingKey", null, $_SESSION['user_id']);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Setting updated successfully.',
                'setting_key' => $settingKey,
                'setting_value' => $settingValue
            ]);
            exit();
        }
        setFlash('success', "Setting updated successfully.");
    } else {
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Settings table may not exist.']);
            exit();
        }
        setFlash('error', "Settings table may not exist.");
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
