<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/EquipmentController.php';
requireAuth();

// â”€â”€ Common layout data â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$nameParts = explode(' ', $_SESSION['full_name']);
$initials  = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));

$needsTabCheck = isset($_SESSION['persist']) && $_SESSION['persist'] === false;

// â”€â”€ Handle form submission â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$errors   = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid form submission. Please try again.');
        header('Location: equipment-create.php');
        exit();
    }

    $formData = [
        'title' => $_POST['title'] ?? '',
        'category' => $_POST['category'] ?? '',
        'condition' => $_POST['condition'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price_per_day' => $_POST['price_per_day'] ?? '',
        'includes_operator' => $_POST['includes_operator'] ?? '',
        'location_village' => $_POST['location_village'] ?? '',
        'location_district' => $_POST['location_district'] ?? '',
    ];
    // Auto-derive hourly rate from daily (daily/8) to keep DB column populated
    $formData['price_per_hour'] = is_numeric($formData['price_per_day']) ? round((float)$formData['price_per_day'] / 8, 2) : '';
    $errors   = validateEquipmentData($formData);

    // Process images
    $imageResult = ['paths' => [], 'errors' => []];
    if (!empty($_FILES['images']['name'][0])) {
        $imageResult = processImageUploads($_FILES['images']);
        if (!empty($imageResult['errors'])) {
            $errors['images'] = implode(' ', $imageResult['errors']);
        }
    }

    // If no errors, create the equipment
    if (empty($errors)) {
        $formData['owner_id'] = $_SESSION['user_id'];
        $newId = createEquipment($conn, $formData, $imageResult['paths']);

        if ($newId) {
            setFlash('success', 'Equipment listed successfully!');
            header('Location: my-equipment-detail.php?id=' . $newId);
            exit();
        } else {
            setFlash('error', 'Something went wrong. Please try again.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Equipment â€” <?= e(APP_NAME) ?></title>
    <meta name="description" content="List your agricultural equipment on <?= e(APP_NAME) ?> for farmers near you to rent.">

    <script>
        document.documentElement.setAttribute('data-theme', 'dark');
    </script>

    <?php if ($needsTabCheck): ?>
    <script>
        if (!sessionStorage.getItem('agroshare_tab')) window.location.href = 'logout.php';
    </script>
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/equipment.css?v=<?= time() ?>">
</head>
<body>

<div class="app-layout">

    <!-- -- TOPBAR -------------------------------------------- -->
    <header class="topbar" role="banner">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation menu" aria-expanded="false" aria-controls="sidebar">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <p class="topbar-greeting">List New Equipment</p>
        </div>
        <div class="topbar-right" style="position: relative;">
            <button class="btn-icon" id="notifBtn" aria-label="Notifications" title="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notif-dot" id="notifDot" aria-hidden="true" style="display: none;"></span>
            </button>

            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">Notifications</div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Loading...</div>
                </div>
            </div>
            
            <div class="avatar" id="avatar-btn" role="button" tabindex="0" title="Profile â€” <?= e($_SESSION['full_name']) ?>" aria-label="Open profile"><?= e($initials) ?></div>
        </div>
    </header>

    <!-- -- SIDEBAR ------------------------------------------- -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-brand">
            <div class="brand-mark" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 22c1.25-7 6-10 10.5-10S20 9.9 20 5.4c0-2.3-.9-3.9-.9-3.9C17 5 14.8 6 14.8 6 11.4 2.5 7 2 7 2S3 8 3 13c0 3 1.5 5.5 3.5 7"/>
                    <path d="M6 22c0-4 2-7 6-9"/>
                </svg>
            </div>
            <span class="brand-name"><?= e(APP_NAME) ?></span>
        </div>

        <nav class="sidebar-nav" aria-label="Site navigation">
            <span class="nav-section-label">Main</span>
            <a href="dashboard.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="equipment-browse.php?mine=1" class="nav-link active" aria-current="page">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
                <span>My Equipment</span>
            </a>
            <a href="my-bookings.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>
                </svg>
                <span>My Bookings</span>
            </a>

            <span class="nav-section-label">Community</span>
            <span class=\"nav-link is-disabled\" title=\"Coming soon\" aria-disabled=\"true\"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Pooling</span></span>
            <a href="equipment-browse.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><span>Browse</span></a>
            <span class=\"nav-link is-disabled\" title=\"Coming soon\" aria-disabled=\"true\"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span>Reviews</span></span>

            <span class="nav-section-label">Account</span>
            <a href=\"javascript:void(0)\" class=\"nav-link\" id=\"profile-btn\"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg><span>Profile</span></a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link danger"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Log Out</span></a>
        </div>
    </aside>

    <!-- -- SIDEBAR OVERLAY (mobile backdrop) ---------------- -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- -- MAIN CONTENT -------------------------------------- -->
    <main class="main-content" role="main">

        <?= renderFlash() ?>

        <div class="page-header">
            <div class="page-header-text">
                <h1>List Equipment</h1>
                <p>Add a tool or machine to share with farmers nearby.</p>
            </div>
            <a href="equipment-browse.php?mine=1" class="btn-secondary" role="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                Back to My Equipment
            </a>
        </div>

        <!-- â”€â”€ Equipment Form â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <form class="eq-form glass-card" method="POST" action="equipment-create.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 11V5h9l3 6m0 0H3m12 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m14 0h2a2 2 0 0 1 2 2v4h-3.5"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
                    Equipment Details
                </h2>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="eq-title" class="form-label">Equipment Title</label>
                        <input type="text" name="title" id="eq-title" class="form-input <?= isset($errors['title']) ? 'has-error' : '' ?>"
                               placeholder="e.g. Mahindra 475 DI Tractor" maxlength="150"
                               value="<?= e($formData['title'] ?? '') ?>" required>
                        <?php if (isset($errors['title'])): ?>
                        <span class="form-error"><?= e($errors['title']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="eq-category" class="form-label">Category</label>
                        <select name="category" id="eq-category" class="form-input form-select <?= isset($errors['category']) ? 'has-error' : '' ?>" required>
                            <option value="">Select categoryâ€¦</option>
                            <?php foreach (['tractor','harvester','seeder','sprayer','other'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($formData['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= ucfirst($cat) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category'])): ?>
                        <span class="form-error"><?= e($errors['category']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="eq-condition" class="form-label">Condition</label>
                        <select name="condition" id="eq-condition" class="form-input form-select <?= isset($errors['condition']) ? 'has-error' : '' ?>" required>
                            <option value="">Select conditionâ€¦</option>
                            <?php foreach (['excellent'=>'Excellent','good'=>'Good','fair'=>'Fair'] as $val=>$label): ?>
                            <option value="<?= $val ?>" <?= ($formData['condition'] ?? '') === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                        <span class="form-error"><?= e($errors['condition']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="eq-description" class="form-label">Description</label>
                        <textarea name="description" id="eq-description" class="form-input form-textarea <?= isset($errors['description']) ? 'has-error' : '' ?>"
                                  rows="4" placeholder="Describe your equipment, its features, attachments, and usage historyâ€¦"
                                  required><?= e($formData['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                        <span class="form-error"><?= e($errors['description']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Pricing
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="eq-price-day" class="form-label">Price per Day (â‚¹)</label>
                        <input type="number" name="price_per_day" id="eq-price-day" class="form-input <?= isset($errors['price_per_day']) ? 'has-error' : '' ?>"
                               placeholder="3000" min="0" step="100"
                               value="<?= e($formData['price_per_day'] ?? '') ?>" required>
                        <?php if (isset($errors['price_per_day'])): ?>
                        <span class="form-error"><?= e($errors['price_per_day']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="includes_operator" value="1" class="form-checkbox"
                                   <?= !empty($formData['includes_operator']) ? 'checked' : '' ?>>
                            <span class="checkbox-visual"></span>
                            <span>Includes Operator</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Location
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="eq-village" class="form-label">Village</label>
                        <input type="text" name="location_village" id="eq-village" class="form-input <?= isset($errors['location_village']) ? 'has-error' : '' ?>"
                               placeholder="e.g. Kundgol" maxlength="100"
                               value="<?= e($formData['location_village'] ?? '') ?>" required>
                        <?php if (isset($errors['location_village'])): ?>
                        <span class="form-error"><?= e($errors['location_village']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="eq-district" class="form-label">District</label>
                        <input type="text" name="location_district" id="eq-district" class="form-input <?= isset($errors['location_district']) ? 'has-error' : '' ?>"
                               placeholder="e.g. Dharwad" maxlength="100"
                               value="<?= e($formData['location_district'] ?? '') ?>" required>
                        <?php if (isset($errors['location_district'])): ?>
                        <span class="form-error"><?= e($errors['location_district']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="form-section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Photos (up to 5)
                </h2>

                <div class="image-upload-zone" id="imageUploadZone">
                    <input type="file" name="images[]" id="eq-images" accept="image/jpeg,image/png,image/webp"
                           multiple class="image-upload-input">
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                            <path d="m21 11-3-3a2 2 0 0 0-2.828 0l-8.086 8.086"/>
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                        </svg>
                        <p>Drag & drop images here, or <strong>click to browse</strong></p>
                        <span>JPEG, PNG, WebP â€” max 2MB each</span>
                    </div>
                    <div class="image-preview-grid" id="imagePreviewGrid"></div>
                </div>
                <?php if (isset($errors['images'])): ?>
                <span class="form-error" style="margin-top:0.5rem;display:block;"><?= e($errors['images']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <a href="equipment-browse.php?mine=1" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    List Equipment
                </button>
            </div>
        </form>

    </main>
</div><!-- /.app-layout -->
<?php require_once __DIR__ . '/includes/profile-modal.php'; ?>
<script src="assets/js/dashboard.js" defer></script>
<script src="assets/js/equipment.js" defer></script>
</body>
</html>


