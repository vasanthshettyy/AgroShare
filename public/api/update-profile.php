<?php
/**
 * update-profile.php — Handle profile updates and photo uploads.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Helpers/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$userId = (int)$_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$village  = trim($_POST['village'] ?? '');
$district = trim($_POST['district'] ?? '');
$state    = trim($_POST['state'] ?? '');
$upiId    = trim($_POST['upi_id'] ?? '');

// Simple validation
if (empty($fullName) || empty($village) || empty($district) || empty($state)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

try {
    // Handle Profile Photo Upload
    $photoPath = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['profile_photo']['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Use JPG, PNG or WebP.']);
            exit();
        }

        if ($_FILES['profile_photo']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Image size exceeds 2MB limit.']);
            exit();
        }

        $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destPath)) {
            $photoPath = 'uploads/profiles/' . $fileName;
            
            // Delete old photo if exists
            $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $oldPhoto = $stmt->get_result()->fetch_assoc()['profile_photo'] ?? null;
            $stmt->close();

            if ($oldPhoto && file_exists(__DIR__ . '/../../public/' . $oldPhoto)) {
                unlink(__DIR__ . '/../../public/' . $oldPhoto);
            }
        }
    }

    // Handle UPI QR Code Upload
    $qrPath = null;
    if (isset($_FILES['upi_qr_image']) && $_FILES['upi_qr_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['upi_qr_image']['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR image format. Use JPG, PNG or WebP.']);
            exit();
        }

        if ($_FILES['upi_qr_image']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'QR image size exceeds 2MB limit.']);
            exit();
        }

        $qrUploadDir = __DIR__ . '/../../public/uploads/qrs/';
        if (!is_dir($qrUploadDir)) {
            mkdir($qrUploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['upi_qr_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'qr_' . $userId . '_' . time() . '.' . $extension;
        $destPath = $qrUploadDir . $fileName;

        if (move_uploaded_file($_FILES['upi_qr_image']['tmp_name'], $destPath)) {
            $qrPath = 'uploads/qrs/' . $fileName;
            
            // Delete old QR if exists
            $stmt = $conn->prepare("SELECT upi_qr_path FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $oldQr = $stmt->get_result()->fetch_assoc()['upi_qr_path'] ?? null;
            $stmt->close();

            if ($oldQr && file_exists(__DIR__ . '/../../public/' . $oldQr)) {
                unlink(__DIR__ . '/../../public/' . $oldQr);
            }
        }
    }

    // Build SQL
    $query = "UPDATE users SET full_name = ?, email = ?, village = ?, district = ?, state = ?, upi_id = ?";
    $params = [$fullName, $email, $village, $district, $state, $upiId];
    $types = "ssssss";

    if ($photoPath) {
        $query .= ", profile_photo = ?";
        $params[] = $photoPath;
        $types .= "s";
    }
    if ($qrPath) {
        $query .= ", upi_qr_path = ?";
        $params[] = $qrPath;
        $types .= "s";
    }

    $query .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $fullName;
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!', 'full_name' => $fullName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database.']);
    }
    $stmt->close();

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred. Please try again later.']);
}
