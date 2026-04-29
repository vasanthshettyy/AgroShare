<?php
/**
 * maintenance-check.php — Lightweight endpoint for real-time maintenance status.
 */
header('Content-Type: application/json');

// Bypass full db.php to save resources, but we need the connection
require_once __DIR__ . '/../../config/constants.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo json_encode(['maintenance' => false, 'error' => 'db_error']);
        exit();
    }
    
    $res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
    $isMaintenance = false;
    if ($res) {
        $val = $res->fetch_column();
        $isMaintenance = ($val === '1');
    }
    
    $conn->close();
    echo json_encode(['maintenance' => $isMaintenance]);
} catch (Exception $e) {
    echo json_encode(['maintenance' => false, 'error' => $e->getMessage()]);
}
