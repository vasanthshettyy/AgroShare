<?php
/**
 * get-realtime-equipment.php — API endpoint for real-time equipment browse updates.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';

$isLoggedIn = isset($_SESSION['user_id']);

$filters = [];
if (!empty($_GET['category']))     $filters['category']     = $_GET['category'];
if (!empty($_GET['district']))     $filters['district']     = $_GET['district'];
if (!empty($_GET['max_price']))    $filters['max_price']    = $_GET['max_price'];
if (!empty($_GET['search']))       $filters['search']       = $_GET['search'];
if (!empty($_GET['has_operator'])) $filters['has_operator'] = true;

$isMyEquipment = isset($_GET['mine']) && $_GET['mine'] === '1';

if ($isMyEquipment && $isLoggedIn && !isGuest()) {
    $filters['owner_id'] = $_SESSION['user_id'];
    $filters['show_all'] = true; // Show unavailable too for the owner
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

try {
    $results = browseEquipment($conn, $filters, $page, $perPage);

    echo json_encode([
        'success' => true,
        'data' => $results['items'],
        'pagination' => [
            'total' => $results['total'],
            'page' => $results['page'],
            'totalPages' => $results['totalPages'],
            'perPage' => $perPage
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
