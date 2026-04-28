<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../src/Helpers/audit.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../equipment.php');
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token.');
    header('Location: ../equipment.php');
    exit();
}

$eqId = (int)($_POST['equipment_id'] ?? 0);
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($eqId > 0) {
    try {
        $stmt = $conn->prepare("UPDATE equipment SET is_featured = NOT COALESCE(is_featured, 0) WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $eqId);
            $stmt->execute();
            logAuditEvent($conn, 'admin_toggle_equipment_featured', $eqId, "Admin toggled featured status for equipment $eqId", $_SESSION['user_id']);
            
            if ($isAjax) {
                echo json_encode(['success' => true, 'message' => 'Equipment status updated.']);
                exit();
            }
            setFlash('success', "Equipment featured status toggled.");
        } else {
            if ($isAjax) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit(); }
            setFlash('error', "Database error.");
        }
    } catch (Exception $e) {
        if ($isAjax) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit(); }
        setFlash('error', "Database error: " . $e->getMessage());
    }
}

if ($isAjax) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit(); }
header('Location: ../index.php?view=equipment');
exit();
