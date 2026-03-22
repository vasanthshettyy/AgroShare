<?php
/**
 * calculate-price.php — AJAX endpoint for dynamic pricing calculation.
 *
 * Expects: GET/POST with equipment_id, start_datetime, end_datetime.
 * Rules:
 *   - Duration in hours = (end - start).
 *   - If < 8 hrs, use hourly rate.
 *   - If >= 8 hrs, compare (hours * hourly) vs (days * daily) and return cheaper.
 *
 * Returns: JSON { success: bool, total_price: float, breakdown: string }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Controllers/EquipmentController.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$id    = (int)($_REQUEST['equipment_id'] ?? 0);
$start = $_REQUEST['start_datetime'] ?? '';
$end   = $_REQUEST['end_datetime'] ?? '';

if ($id <= 0 || !$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit();
}

$eq = getEquipmentById($conn, $id);
if (!$eq) {
    echo json_encode(['success' => false, 'message' => 'Equipment not found.']);
    exit();
}

$startTime = strtotime($start);
$endTime   = strtotime($end);

if (!$startTime || !$endTime || $endTime <= $startTime) {
    echo json_encode(['success' => false, 'message' => 'Invalid date range.']);
    exit();
}

$durationSeconds = $endTime - $startTime;
$durationHours   = $durationSeconds / 3600;

$hourlyRate = (float)$eq['price_per_hour'];
$dailyRate  = (float)$eq['price_per_day'];

$totalPrice = 0;
$breakdown  = "";

if ($durationHours < 8) {
    // Strictly hourly
    $totalPrice = ceil($durationHours) * $hourlyRate;
    $breakdown  = sprintf("%d hours × ₹%s/hr", ceil($durationHours), number_format($hourlyRate, 0));
} else {
    // Compare hourly vs daily
    $hourlyTotal = ceil($durationHours) * $hourlyRate;
    
    $days       = floor($durationHours / 24);
    $extraHours = ceil($durationHours % 24);
    
    // If extra hours are significant, daily might be better
    // Simplified: how many full days + (remaining hours vs daily rate)
    $dayCount = ceil($durationHours / 24);
    $dailyTotal = $dayCount * $dailyRate;
    
    if ($hourlyTotal <= $dailyTotal) {
        $totalPrice = $hourlyTotal;
        $breakdown  = sprintf("%d hours × ₹%s/hr (Hourly rate applied)", ceil($durationHours), number_format($hourlyRate, 0));
    } else {
        $totalPrice = $dailyTotal;
        $breakdown  = sprintf("%d days × ₹%s/day (Daily discount applied)", $dayCount, number_format($dailyRate, 0));
    }
}

echo json_encode([
    'success'     => true,
    'total_price' => $totalPrice,
    'breakdown'   => $breakdown,
    'currency'    => '₹'
]);
