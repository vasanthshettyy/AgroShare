<?php
/**
 * get-booked-slots.php — Fetch confirmed/active booking dates for a piece of equipment.
 *
 * Expects: GET with id.
 * Returns: JSON { success: bool, booked_ranges: [{start, end}, ...] }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    exit();
}

$sql = "SELECT start_datetime, end_datetime 
        FROM bookings 
        WHERE equipment_id = ? 
        AND status IN ('pending', 'confirmed', 'active')
        AND end_datetime >= CURRENT_DATE
        ORDER BY start_datetime ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

$ranges = [];
while ($row = $result->fetch_assoc()) {
    $ranges[] = [
        'start' => $row['start_datetime'],
        'end'   => $row['end_datetime']
    ];
}

echo json_encode([
    'success' => true,
    'booked_ranges' => $ranges
]);
