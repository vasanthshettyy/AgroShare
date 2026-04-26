<?php
/**
 * EquipmentController.php — Equipment CRUD and image upload logic.
 *
 * Handles: create, update, delete, toggle availability, browse/filter.
 * Uses: mysqli (OO) prepared statements, zero string interpolation.
 *
 * Included by equipment-create.php, equipment-edit.php, etc.
 */

// ── Configuration ──────────────────────────────────────────
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_IMAGE_SIZE     = 10 * 1024 * 1024; // 10MB (Maximum Quality)
const MAX_IMAGES         = 5;
const UPLOAD_DIR         = __DIR__ . '/../../public/uploads/equipment/';
const UPLOAD_URL_PREFIX  = 'uploads/equipment/';

// ── Validation ─────────────────────────────────────────────

/**
 * Validate equipment form data and return an array of errors.
 * Returns empty array if everything is valid.
 */
function validateEquipmentData(array $data): array
{
    $errors = [];

    // Title
    $title = trim($data['title'] ?? '');
    if ($title === '') {
        $errors['title'] = 'Equipment title is required.';
    } elseif (mb_strlen($title) > 150) {
        $errors['title'] = 'Title must be 150 characters or fewer.';
    }

    // Category
    $validCategories = ['tractor', 'harvester', 'seeder', 'sprayer', 'plough', 'chain_saw', 'rotavator', 'cultivator', 'thresher', 'water_pump', 'earth_auger', 'baler', 'trolley', 'brush_cutter', 'power_tiller', 'chaff_cutter', 'other'];
    if (!in_array($data['category'] ?? '', $validCategories, true)) {
        $errors['category'] = 'Please select a valid category.';
    }

    // Description
    $desc = trim($data['description'] ?? '');
    if ($desc === '') {
        $errors['description'] = 'Please add a description.';
    }

    // Price per day
    $ppd = $data['price_per_day'] ?? '';
    if (!is_numeric($ppd) || (float)$ppd <= 0) {
        $errors['price_per_day'] = 'Enter a valid daily price.';
    }

    // Safety Deposit
    if (!empty($data['safety_deposit'])) {
        if (!is_numeric($data['safety_deposit']) || (float)$data['safety_deposit'] < 0) {
            $errors['safety_deposit'] = 'Enter a valid safety deposit amount.';
        }
    }

    // Condition
    $validConditions = ['excellent', 'good', 'fair'];
    if (!in_array($data['condition'] ?? '', $validConditions, true)) {
        $errors['condition'] = 'Please select equipment condition.';
    }

    // Location
    if (trim($data['location_village'] ?? '') === '') {
        $errors['location_village'] = 'Village is required.';
    }
    if (trim($data['location_district'] ?? '') === '') {
        $errors['location_district'] = 'District is required.';
    }

    return $errors;
}

// ── Image Upload ───────────────────────────────────────────

/**
 * Process uploaded images from $_FILES['images'].
 * Returns ['paths' => [...], 'errors' => [...]]
 */
function processImageUploads(array $files): array
{
    $paths  = [];
    $errors = [];

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
            $errors[] = 'Failed to create upload directory.';
            return ['paths' => $paths, 'errors' => $errors];
        }
    }

    // Normalize $_FILES array for multiple uploads
    $fileCount = is_array($files['name']) ? count($files['name']) : 0;

    if ($fileCount > MAX_IMAGES) {
        $errors[] = 'You can upload a maximum of ' . MAX_IMAGES . ' images.';
        return ['paths' => $paths, 'errors' => $errors];
    }

    for ($i = 0; $i < $fileCount; $i++) {
        // Skip empty slots
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Check for upload errors
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error on file ' . ($i + 1) . '.';
            continue;
        }

        // Validate MIME type using finfo (server-side, not extension-based)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($files['tmp_name'][$i]);
        if (!in_array($mimeType, ALLOWED_MIME_TYPES, true) || @getimagesize($files['tmp_name'][$i]) === false) {
            $errors[] = 'File "' . htmlspecialchars($files['name'][$i]) . '" is not a valid image (JPEG, PNG, or WebP only).';
            continue;
        }

        // Validate size
        if ($files['size'][$i] > MAX_IMAGE_SIZE) {
            $errors[] = 'File "' . htmlspecialchars($files['name'][$i]) . '" exceeds 2MB.';
            continue;
        }

        // Generate unique filename
        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $filename = uniqid('eq_', true) . '.' . $ext;
        $destPath = UPLOAD_DIR . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
            $paths[] = UPLOAD_URL_PREFIX . $filename;
        } else {
            $errors[] = 'Failed to save file ' . ($i + 1) . '.';
        }
    }

    return ['paths' => $paths, 'errors' => $errors];
}

/**
 * Delete image files from disk given an array of relative paths.
 */
function deleteEquipmentImages(array $imagePaths): void
{
    $publicDir = realpath(__DIR__ . '/../../public/');
    foreach ($imagePaths as $path) {
        $fullPath = realpath($publicDir . '/' . $path);
        if ($fullPath && str_starts_with($fullPath, $publicDir) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}

// ── CRUD Operations ────────────────────────────────────────

/**
 * Create a new equipment listing.
 * Returns the newly inserted ID on success, or false on failure.
 */
function createEquipment(mysqli $conn, array $data, array $imagePaths): int|false
{
    $sql = "INSERT INTO equipment 
            (owner_id, title, category, description, price_per_day, safety_deposit,
             includes_operator, location_village, location_district, images, `condition`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $ownerId          = (int)$data['owner_id'];
    $title            = trim($data['title']);
    $category         = $data['category'];
    $description      = trim($data['description']);
    $pricePerDay      = (float)$data['price_per_day'];
    $safetyDeposit    = (float)($data['safety_deposit'] ?? 0);
    $includesOperator = isset($data['includes_operator']) ? 1 : 0;
    $village          = trim($data['location_village']);
    $district         = trim($data['location_district']);
    $imagesJson       = !empty($imagePaths) ? json_encode($imagePaths) : null;
    $condition        = $data['condition'];

    $stmt->bind_param(
        'isssddissss',
        $ownerId, $title, $category, $description,
        $pricePerDay, $safetyDeposit, $includesOperator,
        $village, $district, $imagesJson, $condition
    );

    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    return $newId ?: false;
}

/**
 * Update an existing equipment listing.
 * Returns true on success.
 */
function updateEquipment(mysqli $conn, int $equipmentId, int $ownerId, array $data, ?string $imagesJson): bool
{
    $sql = "UPDATE equipment SET 
                title = ?, category = ?, description = ?, 
                price_per_day = ?, safety_deposit = ?, includes_operator = ?,
                location_village = ?, location_district = ?, images = ?, `condition` = ?
            WHERE id = ? AND owner_id = ?";

    $stmt = $conn->prepare($sql);

    $title            = trim($data['title']);
    $category         = $data['category'];
    $description      = trim($data['description']);
    $pricePerDay      = (float)$data['price_per_day'];
    $safetyDeposit    = (float)($data['safety_deposit'] ?? 0);
    $includesOperator = isset($data['includes_operator']) ? 1 : 0;
    $village          = trim($data['location_village']);
    $district         = trim($data['location_district']);
    $condition        = $data['condition'];

    $stmt->bind_param(
        'sssddissssii',
        $title, $category, $description,
        $pricePerDay, $safetyDeposit, $includesOperator,
        $village, $district, $imagesJson, $condition,
        $equipmentId, $ownerId
    );

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected >= 0; // 0 = no change, still success
}

/**
 * Delete equipment by ID (only if owned by the given user).
 * Deletes associated images from disk.
 */
function deleteEquipment(mysqli $conn, int $equipmentId, int $ownerId): bool
{
    // First, fetch images to delete from disk
    $stmt = $conn->prepare("SELECT images FROM equipment WHERE id = ? AND owner_id = ?");
    $stmt->bind_param('ii', $equipmentId, $ownerId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        return false; // Not found or not owned
    }

    // Delete image files
    $images = $result['images'] ? json_decode($result['images'], true) : [];
    if (!empty($images)) {
        deleteEquipmentImages($images);
    }

    // Delete the DB row
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ? AND owner_id = ?");
    $stmt->bind_param('ii', $equipmentId, $ownerId);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    return $deleted;
}

/**
 * Toggle equipment availability.
 * Returns the new is_available value, or null on failure.
 */
function toggleAvailability(mysqli $conn, int $equipmentId, int $ownerId): ?int
{
    // 1. Try to toggle if owned by this user
    $stmt = $conn->prepare("UPDATE equipment SET is_available = NOT is_available WHERE id = ? AND owner_id = ?");
    $stmt->bind_param('ii', $equipmentId, $ownerId);
    $stmt->execute();
    $stmt->close();

    // 2. Fetch the current state (this confirms the row exists and is owned by the user)
    $stmt = $conn->prepare("SELECT is_available FROM equipment WHERE id = ? AND owner_id = ?");
    $stmt->bind_param('ii', $equipmentId, $ownerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ($row !== null) ? (int)$row['is_available'] : null;
}

/**
 * Fetch a single equipment record with owner info.
 */
function getEquipmentById(mysqli $conn, int $id): ?array
{
    $sql = "SELECT e.*, u.full_name AS owner_name, u.trust_score AS owner_trust, 
                   u.is_verified AS owner_verified, u.village AS owner_village,
                   u.district AS owner_district
            FROM equipment e
            JOIN users u ON e.owner_id = u.id
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Browse equipment with filters.
 * Returns ['items' => [...], 'total' => int, 'page' => int, 'totalPages' => int]
 */
function browseEquipment(mysqli $conn, array $filters = [], int $page = 1, int $perPage = 12): array
{
    $where   = ['1=1'];
    $types   = '';
    $params  = [];

    // Filter: category
    if (!empty($filters['category'])) {
        $where[]  = 'e.category = ?';
        $types   .= 's';
        $params[] = $filters['category'];
    }

    // Filter: district
    if (!empty($filters['district'])) {
        $where[]  = 'e.location_district LIKE ?';
        $types   .= 's';
        $params[] = '%' . $filters['district'] . '%';
    }

    // Filter: max price per day
    if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
        $where[]  = 'e.price_per_day <= ?';
        $types   .= 'd';
        $params[] = (float)$filters['max_price'];
    }

    // Filter: includes operator
    if (!empty($filters['has_operator'])) {
        $where[] = 'e.includes_operator = 1';
    }

    // Filter: available only (default: yes)
    if (!isset($filters['show_all']) || !$filters['show_all']) {
        $where[] = 'e.is_available = 1';
    }

    // Filter: my equipment only
    if (!empty($filters['owner_id'])) {
        $where[]  = 'e.owner_id = ?';
        $types   .= 'i';
        $params[] = (int)$filters['owner_id'];
    }

    $whereClause = implode(' AND ', $where);

    // Count total results
    $countSql  = "SELECT COUNT(*) AS total FROM equipment e WHERE {$whereClause}";
    $countStmt = $conn->prepare($countSql);
    if ($types !== '' && !empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;

    // Fetch page of results
    $sql  = "SELECT e.*, u.full_name AS owner_name, u.trust_score AS owner_trust, u.is_verified AS owner_verified
             FROM equipment e
             JOIN users u ON e.owner_id = u.id
             WHERE {$whereClause}
             ORDER BY e.is_featured DESC, e.created_at DESC
             LIMIT ? OFFSET ?";

    $fetchTypes  = $types . 'ii';
    $fetchParams = array_merge($params, [$perPage, $offset]);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($fetchTypes, ...$fetchParams);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'items'      => $items,
        'total'      => $total,
        'page'       => $page,
        'totalPages' => $totalPages,
    ];
}

/**
 * Get total equipment count for a specific user.
 */
function getUserEquipmentCount(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM equipment WHERE owner_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/**
 * Get active rentals count for a specific user (equipment owned by user that is currently booked).
 * Note: Assuming a 'bookings' table exists. If not, returns 0 for now.
 */
function getUserActiveRentalsCount(mysqli $conn, int $userId): int
{
    // Check if bookings table exists first
    $result = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($result->num_rows === 0) return 0;

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM bookings b
        JOIN equipment e ON b.equipment_id = e.id
        WHERE e.owner_id = ? AND b.status = 'active'
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/**
 * Get the trust score for a specific user.
 */
function getUserTrustScore(mysqli $conn, int $userId): float
{
    $stmt = $conn->prepare("SELECT trust_score FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($row['trust_score'] ?? 0.0);
}

/**
 * Get pool campaigns count for a user (placeholder logic).
 */
function getUserPoolCount(mysqli $conn, int $userId): int
{
    // Placeholder: returning 0 until pooling table is implemented
    return 0;
}
