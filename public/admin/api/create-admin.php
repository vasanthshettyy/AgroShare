<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../src/Helpers/audit.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php');
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token.');
    header('Location: ../users.php');
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if (empty($fullName)) {
    $errors[] = "Full Name is required.";
}

if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
    $errors[] = "Valid 10-digit Indian mobile number required.";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
}

if (empty($errors)) {
    // Check if phone already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "This phone number is already registered.";
    }
    $stmt->close();
}

if (!empty($errors)) {
    setFlash('error', implode("<br>", $errors));
    header('Location: ../users.php');
    exit();
}

try {
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    // Add default location details as they are required by the schema
    $village = 'AdminHQ';
    $district = 'System';
    $state = 'System';
    $role = 'admin';
    $isVerified = 1;

    $stmt = $conn->prepare("INSERT INTO users (full_name, phone, password_hash, role, village, district, state, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssssi', $fullName, $phone, $hash, $role, $village, $district, $state, $isVerified);
    
    if ($stmt->execute()) {
        $newAdminId = $stmt->insert_id;
        logAuditEvent($conn, 'admin_created', $newAdminId, "Admin created a new admin account: $fullName ($phone)", null, $_SESSION['user_id']);
        setFlash('success', "New admin account created successfully.");
        header('Location: ../users.php');
        exit();
    } else {
        setFlash('error', "Failed to create admin account.");
    }
} catch (Exception $e) {
    setFlash('error', "Database error: " . $e->getMessage());
}

header('Location: ../users.php');
exit();
