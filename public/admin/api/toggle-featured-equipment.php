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

if ($eqId > 0) {
    try {
        // Optimistic toggle for is_featured. Fallback if column missing.
        $stmt = $conn->prepare("UPDATE equipment SET is_featured = NOT COALESCE(is_featured, 0) WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $eqId);
            $stmt->execute();
            logAuditEvent($conn, 'admin_toggle_equipment_featured', $eqId, "Admin toggled featured status for equipment $eqId", $_SESSION['user_id']);
            setFlash('success', "Equipment featured status toggled.");
        } else {
            setFlash('error', "is_featured column may not exist in schema yet.");
        }
    } catch (Exception $e) {
        setFlash('error', "Database error: " . $e->getMessage());
    }
}

header('Location: ../equipment.php');
exit();
