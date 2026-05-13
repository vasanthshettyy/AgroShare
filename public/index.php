<?php
require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to dashboard unless they want to see the landing page
if (isLoggedIn() && !isGuest() && !isset($_GET['home'])) {
    header('Location: ' . getBasePath() . '/public/dashboard.php');
    exit();
}

// ── Fetch REAL stats from the database ─────────────────────
$farmerCount = 0;
$equipCount  = 0;
$bookingCount = 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM users");
if ($res) $farmerCount = (int)$res->fetch_assoc()['c'];

$res = $conn->query("SELECT COUNT(*) AS c FROM equipment");
if ($res) $equipCount = (int)$res->fetch_assoc()['c'];

$res = $conn->query("SELECT COUNT(*) AS c FROM bookings");
if ($res) $bookingCount = (int)$res->fetch_assoc()['c'];

// ── Fetch recent 3 equipment for the floating card ────────
$recentEquip = [];
$stmt = $conn->query("SELECT e.title, e.category, e.price_per_day, e.is_available, e.images
    FROM equipment e WHERE e.is_available = 1 ORDER BY e.created_at DESC LIMIT 3");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        // Extract first image from JSON array
        $imgs = json_decode($row['images'] ?? '[]', true);
        $row['image'] = !empty($imgs) ? $imgs[0] : null;
        $recentEquip[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroShare — #1 Community Equipment Sharing Platform</title>
    <meta name="description" content="AgroShare connects farmers with shared agricultural equipment. Rent tractors, harvesters, and tools at affordable rates. Join the community pooling revolution.">

    <?php require_once __DIR__ . '/includes/theme-script.php'; ?>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

    <!-- ── Navigation ───────────────────────────────────────── -->
    <nav class="navbar" id="navbar">
        <a href="#" class="logo">
            <i class="fas fa-seedling"></i>
            Agro <span>Share</span>
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">How it Works</a>
            <?php require_once __DIR__ . '/includes/theme-toggle-btn.php'; ?>
            <a href="login.php" class="btn-login">Sign In <i class="fas fa-arrow-right"></i></a>
        </div>
        <!-- Mobile Toggle -->
        <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

    <!-- ── Hero Section ─────────────────────────────────────── -->
    <main class="main-content">
    <section class="hero">
        <!-- Left Content -->
        <div class="hero-left" data-aos="fade-right" data-aos-duration="800">
            <span class="hero-badge"><i class="fas fa-seedling"></i> #1 Community Equipment Sharing Platform</span>
            <h1>Empowering Farmers through <span class="gradient-text">Shared Growth.</span></h1>
            <p class="hero-desc">Access high-end agricultural machinery without the burden of ownership. Join the community pooling revolution and maximize your harvest efficiency.</p>
            <div class="hero-btns">
                <a href="login.php" class="btn-main btn-primary">Get Started Now <i class="fas fa-arrow-right"></i></a>
                <a href="auth/guest-login.php" class="btn-main btn-outline"><i class="fas fa-search"></i> Explore as Guest</a>
            </div>

            <!-- Stats Strip -->
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-icon"><i class="fas fa-users"></i></div>
                    <div class="hero-stat-text">
                        <strong><?= $farmerCount ?>+</strong>
                        <span>Active Farmers</span>
                    </div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-icon"><i class="fas fa-tractor"></i></div>
                    <div class="hero-stat-text">
                        <strong><?= $equipCount ?>+</strong>
                        <span>Equipments</span>
                    </div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-icon"><i class="fas fa-indian-rupee-sign"></i></div>
                    <div class="hero-stat-text">
                        <strong><?= $bookingCount ?>+</strong>
                        <span>Bookings Made</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Visual -->
        <div class="hero-right" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
            <div class="hero-image-wrapper">
                <img src="assets/images/tractor-hero.png" alt="Modern Agricultural Tractor" class="hero-tractor">

                <!-- Floating Equipment Card -->
                <div class="floating-card equipment-card" data-aos="fade-up" data-aos-delay="600">
                    <div class="fc-header">
                        <strong>Nearby Equipments</strong>
                        <a href="equipment-browse.php" class="fc-link">View all</a>
                    </div>
                    <?php foreach ($recentEquip as $eq): ?>
                    <div class="fc-item">
                        <div class="fc-item-icon">
                            <?php if (!empty($eq['image'])): ?>
                                <img src="<?= e($eq['image']) ?>" alt="<?= e($eq['title']) ?>">
                            <?php else: ?>
                                <i class="fas fa-tractor"></i>
                            <?php endif; ?>
                        </div>
                        <div class="fc-item-info">
                            <span class="fc-item-title"><?= e($eq['title']) ?></span>
                            <span class="fc-item-cat"><?= ucfirst(str_replace('_', ' ', $eq['category'])) ?></span>
                            <span class="fc-item-price">₹<?= number_format($eq['price_per_day'], 0) ?> / day</span>
                        </div>
                        <span class="fc-item-status available">Available</span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Floating Stats Card -->
                <div class="floating-card stats-card" data-aos="fade-up" data-aos-delay="800">
                    <div class="sc-grid">
                        <div class="sc-item">
                            <span class="sc-label">Bookings</span>
                            <div class="sc-val-row">
                                <strong><?= $bookingCount ?></strong>
                                <i class="fas fa-chart-line sc-trend"></i>
                            </div>
                            <span class="sc-sub">This Month</span>
                        </div>
                        <div class="sc-item">
                            <span class="sc-label">Equipments</span>
                            <div class="sc-val-row">
                                <strong><?= $equipCount ?></strong>
                                <i class="fas fa-chart-line sc-trend"></i>
                            </div>
                            <span class="sc-sub">Listed</span>
                        </div>
                        <div class="sc-item">
                            <span class="sc-label">Utilization</span>
                            <div class="sc-val-row">
                                <strong>78%</strong>
                                <i class="fas fa-chart-line sc-trend"></i>
                            </div>
                            <span class="sc-sub">This Month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Background Decorations -->
        <div class="hero-bg-shape shape-1"></div>
        <div class="hero-bg-shape shape-2"></div>
    </section>

    <!-- ── Features Section ─────────────────────────────────── -->
    <section id="features" class="section">
        <span class="section-tag" data-aos="fade-up">Core Capabilities</span>
        <h2 class="section-title" data-aos="fade-up" data-aos-delay="100">Everything you need to grow.</h2>

        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up">
                <div class="feature-img-wrap">
                    <img src="assets/images/feature-rental.png" alt="Smart Rentals">
                </div>
                <h3>Smart Rentals</h3>
                <p>Browse a wide range of tractors, harvesters, and tools from local owners with transparent daily pricing.</p>
                <a href="auth/guest-login.php" class="feature-link">Explore Equipment <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="150">
                <div class="feature-img-wrap">
                    <img src="assets/images/feature-pooling.png" alt="Community Pooling">
                </div>
                <h3>Community Pooling</h3>
                <p>Pool your demand with neighboring farmers to unlock bulk-buy discounts and shared logistics.</p>
                <a href="auth/guest-login.php" class="feature-link">See Campaigns <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-img-wrap">
                    <img src="assets/images/feature-trust.png" alt="Trust System">
                </div>
                <h3>Trust & Ratings</h3>
                <p>Community-driven rating system ensures safe transactions and reliable equipment quality for all users.</p>
                <a href="auth/guest-login.php" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- ── How it Works ─────────────────────────────────────── -->
    <section id="how-it-works" class="section section-alt">
        <span class="section-tag" data-aos="fade-up">Simple 3-Step Process</span>
        <h2 class="section-title" data-aos="fade-up" data-aos-delay="100">Start sharing in minutes.</h2>

        <div class="steps-grid">
            <div class="step-card" data-aos="fade-up">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="fas fa-search"></i></div>
                <h3>Discover</h3>
                <p>Search for specific machinery needed for your current harvest cycle or soil preparation.</p>
            </div>
            <div class="step-connector" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="fas fa-handshake"></i></div>
                <h3>Connect & Book</h3>
                <p>Book directly or join a pooling campaign to share costs with others in your village.</p>
            </div>
            <div class="step-connector" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-chevron-right"></i>
            </div>
            <div class="step-card" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="fas fa-wheat-awn"></i></div>
                <h3>Harvest</h3>
                <p>Use the equipment, complete your work, and rate your experience to help the community.</p>
            </div>
        </div>
    </section>

    <!-- ── CTA Section ──────────────────────────────────────── -->
    <section class="cta-section">
        <div class="cta-content" data-aos="zoom-in">
            <h2>Ready to transform your farming?</h2>
            <p>Join thousands of farmers already sharing equipment and saving costs on the platform.</p>
            <div class="hero-btns" style="justify-content: center;">
                <a href="signup.php" class="btn-main btn-primary">Create Free Account <i class="fas fa-arrow-right"></i></a>
                <a href="auth/guest-login.php" class="btn-main btn-outline"><i class="fas fa-play"></i> Take a Demo</a>
            </div>
        </div>
    </section>

    <!-- ── Footer ───────────────────────────────────────────── -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="logo">
                    <i class="fas fa-seedling"></i>
                    Agro<span>Share</span>
                </a>
                <p class="footer-desc">Transforming rural economy through technological empowerment and shared resources.</p>
            </div>
            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="equipment-browse.php">Browse Equipment</a></li>
                    <li><a href="pooling-browse.php">Community Pooling</a></li>
                    <li><a href="auth/guest-login.php">Demo Mode</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get Started</h4>
                <ul>
                    <li><a href="signup.php">Create Account</a></li>
                    <li><a href="login.php">Sign In</a></li>
                    <li><a href="forgot-password.php">Reset Password</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 AgroShare. Built for Modern Agriculture.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/theme-toggle.js"></script>
    <script>
        AOS.init({ once: true, offset: 80 });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
        });

        // Mobile menu toggle
        document.getElementById('mobileToggle')?.addEventListener('click', () => {
            document.querySelector('.nav-links').classList.toggle('active');
        });
    </script>
<script src="assets/js/realtime.js?v=<?= time() ?>" defer></script>
</body>
</html>
