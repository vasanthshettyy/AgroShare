<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../src/Controllers/AdminController.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole('admin');

$view = $_GET['view'] ?? 'overview';

// Map views to files
$viewFiles = [
    'overview' => __DIR__ . '/../views/overview.php',
    'users' => __DIR__ . '/../views/users.php',
    'equipment' => __DIR__ . '/../views/equipment.php',
    'bookings' => __DIR__ . '/../views/bookings.php',
    'settings' => __DIR__ . '/../views/settings.php',
    'logs' => __DIR__ . '/../views/logs.php',
    'pooling' => __DIR__ . '/../views/pooling.php'
];

if (isset($viewFiles[$view]) && file_exists($viewFiles[$view])) {
    require_once $viewFiles[$view];
} else {
    echo "<div class='admin-card'><p>View not found.</p></div>";
}
