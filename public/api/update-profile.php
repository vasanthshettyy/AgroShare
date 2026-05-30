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
$phone    = trim($_POST['phone'] ?? '');

// Simple validation
if (empty($fullName) || empty($email) || empty($phone) || empty($village) || empty($district) || empty($state)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields including email and phone number.']);
    exit();
}

// Check for duplicate email
if (!empty($email)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param('si', $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email address is already registered to another account.']);
        $stmt->close();
        exit();
    }
    $stmt->close();
}

// Check for duplicate phone
if (!empty($phone)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $stmt->bind_param('si', $phone, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This phone number is already registered to another account.']);
        $stmt->close();
        exit();
    }
    $stmt->close();
}

try {
    // Handle Profile Photo Upload
    $photoPath = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        $tmpName = $_FILES['profile_photo']['tmp_name'];
        $originalName = $_FILES['profile_photo']['name'];

        // 1. Validate MIME Type strictly
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format. Use JPG, PNG or WebP.']);
            exit();
        }

        // 2. Validate Structural Integrity
        if (@getimagesize($tmpName) === false) {
            echo json_encode(['success' => false, 'message' => 'Uploaded file is not a valid image.']);
            exit();
        }

        // 3. Validate Extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file extension.']);
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

        // 4. Enforce Safe Random Filenames
        $randomName = bin2hex(random_bytes(16));
        $fileName = 'profile_' . $userId . '_' . $randomName . '.' . $extension;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $photoPath = 'uploads/profiles/' . $fileName;
            
            // Delete old photo if exists
            $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $oldPhoto = $stmt->get_result()->fetch_assoc()['profile_photo'] ?? null;
            $stmt->close();

            if ($oldPhoto && file_exists(__DIR__ . '/../../public/' . $oldPhoto)) {
                @unlink(__DIR__ . '/../../public/' . $oldPhoto);
            }
        }
    }

    // Handle UPI QR Code Upload
    $qrPath = null;
    if (isset($_FILES['upi_qr_image']) && $_FILES['upi_qr_image']['error'] === UPLOAD_ERR_OK) {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        $tmpName = $_FILES['upi_qr_image']['tmp_name'];
        $originalName = $_FILES['upi_qr_image']['name'];

        // 1. Validate MIME Type strictly
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR image format. Use JPG, PNG or WebP.']);
            exit();
        }

        // 2. Validate Structural Integrity
        if (@getimagesize($tmpName) === false) {
            echo json_encode(['success' => false, 'message' => 'Uploaded QR file is not a valid image.']);
            exit();
        }

        // 3. Validate Extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR file extension.']);
            exit();
        }

        $qrUploadDir = __DIR__ . '/../../public/uploads/qrs/';
        if (!is_dir($qrUploadDir)) {
            mkdir($qrUploadDir, 0755, true);
        }

        // 4. Enforce Safe Random Filenames
        $randomName = bin2hex(random_bytes(16));
        $fileName = 'qr_' . $userId . '_' . $randomName . '.' . $extension;
        $destPath = $qrUploadDir . $fileName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $qrPath = 'uploads/qrs/' . $fileName;
            
            // Delete old QR if exists
            $stmt = $conn->prepare("SELECT upi_qr_path FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $oldQr = $stmt->get_result()->fetch_assoc()['upi_qr_path'] ?? null;
            $stmt->close();

            if ($oldQr && file_exists(__DIR__ . '/../../public/' . $oldQr)) {
                @unlink(__DIR__ . '/../../public/' . $oldQr);
            }
        }
    }

    // Build SQL
    $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, village = ?, district = ?, state = ?, upi_id = ?";
    $params = [$fullName, $email, $phone, $village, $district, $state, $upiId];
    $types = "sssssss";

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
        $_SESSION['email']     = $email;
        $_SESSION['phone']     = $phone;
        if ($photoPath) {
            $_SESSION['profile_photo'] = $photoPath;
        }
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'full_name' => $fullName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'message' => 'Email or phone number already in use.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} finally {
    if (isset($stmt)) $stmt->close();
}
