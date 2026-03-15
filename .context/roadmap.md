# AgroShare — P2P Farmer Resource Pooling Platform
## Complete Development Roadmap (2-Month Window)

> **How to use this roadmap:** Work through Modules sequentially. Each module is a **complete vertical slice** — you build the UI, PHP backend, and mysqli database layer for each feature together, then verify it works before moving on. You set your own pace — there are no fixed deadlines per module, only logical dependencies.

---

## Pre-Work: Research & Reference Analysis

### Competitive Landscape Notes
Before writing a single line of code, internalize these platform patterns:

| Platform | Key Lesson for AgroShare |
|---|---|
| **Hello Tractor** | Equipment listing with GPS tagging + booking agent model |
| **Trringo (Mahindra)** | Hourly/daily pricing tiers, operator-included packages |
| **EM3 AgriServices** | Service bundles (equipment + agronomist advice) |
| **JFarm Services** | Cooperative pooling for inputs (seeds/fertilizer) |

**Core UX Insight:** All successful platforms share two traits — radical simplicity in booking flow (≤3 taps to confirm), and trust signals (ratings, verified badges) placed prominently.

---

## Module 1 — Architecture Planning & Environment Setup
*Goal: Everything is decided on paper before touching code.*

### 1.1 — System Architecture Decision
- Decide on a **monolithic PHP MVC** structure (recommended for 2-month scope)
- Define folder structure:
  ```
  /Project V1.0.0                    ← project root (current)
    /.agents                         ← ✅ EXISTS — AI agent config
      /rules
        efficiany.md                 ← ✅ efficiency & silence rules
        techstack.md                 ← ✅ tech stack standards
      /Skills
        /php.mysqli
          SKILL.md                   ← ✅ mysqli (OO) & security skill
        /project-tracker
          SKILL.md                   ← ✅ roadmap sync skill
        /vanilla-js
          SKILL.md                   ← ✅ JS architecture skill
      /workflow
        cleanup                      ← ✅ cleanup workflow
        docs                         ← ✅ docs workflow
        fix-lint.txt                 ← ✅ lint-fix workflow
    /.context                        ← ✅ EXISTS — project memory
      roadmap.md                     ← ✅ this file
      logs.md                        ← ✅ daily session log
      tree.md                        ← ✅ project tree
    /public                          ← ✅ EXISTS — web root
      index.php
      dashboard.php
      equipment-browse.php
      equipment-create.php
      equipment-detail.php
      equipment-edit.php
      login.php
      logout.php
      signup.php
      /api                           ← ✅ EXISTS — AJAX endpoints
        create-equipment.php
        edit-equipment.php
        toggle-availability.php
      /assets                        ← ✅ EXISTS — static files
        /css
        /js
        /img
      /uploads                       ← ✅ EXISTS — user-uploaded equipment images
    /src                             ← ✅ EXISTS — app logic
      /Controllers                   ← ✅ EXISTS
        EquipmentController.php
      /Helpers                       ← ✅ EXISTS — Auth, Session, Validation utilities
        auth.php
    /config                          ← ✅ EXISTS — db.php, constants.php
      constants.php
      db.php
    /sql                             ← 🔲 PLANNED — migration & seed files
  ```
- Choose local dev environment: **XAMPP** or **WAMP** (zero-cost, Windows) or **LAMP** (Linux)
- Confirm PHP version ≥ 8.1 (for named arguments, enums, Argon2 support)
- Confirm MySQL ≥ 8.0

### 1.2 — Complete Database Schema Design
Design all tables with relationships **before** writing any PHP.

**Table: `users`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| full_name | VARCHAR(120) | |
| phone | VARCHAR(15) UNIQUE | Primary identifier in rural India |
| email | VARCHAR(150) UNIQUE NULLABLE | |
| password_hash | VARCHAR(255) | Argon2id output |
| role | ENUM('farmer','admin') | Default: farmer |
| village | VARCHAR(100) | |
| district | VARCHAR(100) | |
| state | VARCHAR(80) | |
| profile_photo | VARCHAR(255) NULLABLE | Path to upload |
| trust_score | DECIMAL(3,2) DEFAULT 0.00 | Computed from reviews |
| is_verified | TINYINT(1) DEFAULT 0 | Admin can verify |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

**Table: `equipment`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| owner_id | INT UNSIGNED FK → users.id | |
| title | VARCHAR(150) | e.g. "Mahindra 475 DI Tractor" |
| category | ENUM('tractor','harvester','seeder','sprayer','other') | |
| description | TEXT | |
| price_per_hour | DECIMAL(8,2) | |
| price_per_day | DECIMAL(8,2) | |
| includes_operator | TINYINT(1) DEFAULT 0 | |
| location_village | VARCHAR(100) | |
| location_district | VARCHAR(100) | |
| images | JSON | Array of image paths |
| condition | ENUM('excellent','good','fair') | |
| is_available | TINYINT(1) DEFAULT 1 | |
| created_at | TIMESTAMP | |

**Table: `bookings`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| equipment_id | INT UNSIGNED FK → equipment.id | |
| renter_id | INT UNSIGNED FK → users.id | |
| owner_id | INT UNSIGNED FK → users.id | Denormalized for fast queries |
| start_datetime | DATETIME | |
| end_datetime | DATETIME | |
| pricing_mode | ENUM('hourly','daily') | |
| total_price | DECIMAL(10,2) | PHP-calculated at booking time |
| status | ENUM('pending','confirmed','active','completed','cancelled') | |
| notes | TEXT NULLABLE | Renter's special requests |
| created_at | TIMESTAMP | |

**Table: `reviews`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| booking_id | INT UNSIGNED FK → bookings.id UNIQUE | One review per booking |
| reviewer_id | INT UNSIGNED FK → users.id | |
| reviewee_id | INT UNSIGNED FK → users.id | |
| rating | TINYINT UNSIGNED | 1–5 |
| comment | TEXT NULLABLE | |
| review_type | ENUM('renter_to_owner','owner_to_renter') | |
| created_at | TIMESTAMP | |

**Table: `pooling_campaigns`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| creator_id | INT UNSIGNED FK → users.id | |
| title | VARCHAR(150) | e.g. "DAP Fertilizer Bulk Buy - Dharwad" |
| item_name | VARCHAR(150) | |
| unit | VARCHAR(30) | e.g. "50kg bag", "litre" |
| price_per_unit_individual | DECIMAL(10,2) | Market price alone |
| price_per_unit_bulk | DECIMAL(10,2) | Negotiated bulk price |
| minimum_quantity | INT UNSIGNED | Threshold to unlock bulk deal |
| current_quantity | INT UNSIGNED DEFAULT 0 | Aggregated from pledges |
| deadline | DATE | |
| status | ENUM('open','threshold_met','closed','cancelled') | |
| district | VARCHAR(100) | |
| description | TEXT | |
| created_at | TIMESTAMP | |

**Table: `pooling_pledges`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| campaign_id | INT UNSIGNED FK → pooling_campaigns.id | |
| farmer_id | INT UNSIGNED FK → users.id | |
| quantity_pledged | INT UNSIGNED | |
| created_at | TIMESTAMP | |
| UNIQUE KEY | (campaign_id, farmer_id) | One pledge per farmer per campaign |

### 1.3 — Define All API Endpoints (PHP Routes)
Map every user action to a PHP handler before building anything:

```
POST /auth/register
POST /auth/login
POST /auth/logout

GET  /dashboard
GET  /equipment/browse          ← public listing
GET  /equipment/{id}            ← detail view
POST /equipment/create          ← owner adds listing
POST /equipment/{id}/edit
POST /equipment/{id}/delete
POST /equipment/{id}/toggle-availability

POST /bookings/check-availability   ← AJAX conflict check
POST /bookings/create
POST /bookings/{id}/confirm         ← owner action
POST /bookings/{id}/cancel
GET  /bookings/my-bookings

POST /reviews/submit
GET  /user/{id}/profile             ← public trust profile

GET  /pooling/browse
GET  /pooling/{id}
POST /pooling/create
POST /pooling/{id}/pledge
POST /pooling/{id}/cancel-pledge

GET  /admin/users
GET  /admin/equipment
GET  /admin/bookings
POST /admin/verify-user/{id}
```

### 1.5 — Localisation Standards (India)
- **Currency:** Strictly use the Indian Rupee symbol (`₹`) for ALL pricing displays across the application.
- **Number Formatting:** Use `number_format($value, 0)` or `number_format($value, 2)` as appropriate for Indian currency.
- **Avoid:** Do not use the `$` symbol or "USD" anywhere in the UI. All monetary values represent INR.
- **Phone Numbers:** Validate for 10-digit Indian mobile numbers (starting with 6-9).
- **Timezone:** Store all datetimes in DB using standardized formats. Always convert to `Asia/Kolkata` (IST) for display. Use `d-m-Y h:i A` formatting.

---

## Module 2 — Security Foundation & User Auth (Vertical Slice)
*Goal: Build a fully working, bulletproof auth system — UI forms, PHP logic, mysqli database, and sessions — all wired and tested before moving on.*

### 2.1 — Database Connection Layer
- Create `/config/db.php` using **mysqli (Object-Oriented style)** with:
  - `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` called **before** instantiating the connection — forces DB errors to throw `mysqli_sql_exception`, making them catchable and keeping the app secure
  - `$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME)` wrapped in a `try/catch (mysqli_sql_exception $e)` block
  - `$conn->set_charset('utf8mb4')` immediately after connection (prevents charset-based injection)
  - All queries use `$stmt = $conn->prepare()` + `$stmt->bind_param()` — **zero variable interpolation into SQL strings**
- Store credentials as constants in a `constants.php` file outside the web root (never in a publicly accessible directory)

### 2.2 — UI: Auth Pages (HTML/CSS)
Build these pages using the design system from Module 3:
- **Register page** (`register.php`) — fields: full name, phone, email (optional), password, confirm password, village, district, state
- **Login page** (`login.php`) — fields: phone, password + "Forgot Password?" link
- **Forgot Password page** (`forgot-password.php`) — phone number input
- **OTP Verification page** (`verify-otp.php`) — 6-digit OTP input
- **Reset Password page** (`reset-password.php`) — new password + confirm password
- All forms must include a hidden CSRF token field
- All forms must show server-side validation errors inline next to the relevant field
- Mobile-responsive: forms must be usable at 375px width

### 2.3 — Backend: Registration Logic (PHP + mysqli)
Step-by-step logic for `AuthController::register()`:
1. Validate all POST fields (PHP server-side — never trust client)
   - `full_name`: not empty, max 120 chars
   - `phone`: regex `/^[6-9]\d{9}$/` (Indian mobile)
   - `password`: min 8 chars, at least one number
2. Check for duplicate phone in DB using prepared statement
3. Hash password: `password_hash($password, PASSWORD_ARGON2ID)`
4. INSERT into `users` table
5. Start session, store `$_SESSION['user_id']` and `$_SESSION['role']`
6. Redirect to dashboard

### 2.4 — Backend: Login Logic (PHP + mysqli)
1. Fetch user by phone using prepared statement
2. Verify: `password_verify($input, $stored_hash)`
3. Regenerate session ID on login: `session_regenerate_id(true)` — prevents session fixation attacks
4. Store minimal session data: user_id, role, full_name
5. Redirect based on role (farmer → /dashboard, admin → /admin/dashboard)

### 2.5 — Session Middleware
- Create a `requireAuth()` helper function
- Create a `requireRole('admin')` helper function
- Every protected route calls these at the very top — before any output
- Session timeout: set `session.gc_maxlifetime` to 3600 (1 hour) and explicitly check `$_SESSION['last_activity']` for deterministic 1-hour idle timeout.

### 2.6 — CSRF Protection
- Generate a CSRF token on every form render: `bin2hex(random_bytes(32))`
- Store in `$_SESSION['csrf_token']`
- Validate on every POST request before processing
- This prevents cross-site request forgery on all booking/review/pledge forms

### 2.7 — Error Handling & Flash Messages
*These patterns are established here and reused in every subsequent module.*
- Every PHP action returns user-readable success/error messages
- Use session flash messages for form submissions: store in `$_SESSION['flash']`, display once, then unset
- AJAX responses always include `{success: true/false, message: "..."}` JSON structure
- Never show PHP error details to users in production (`display_errors = Off` in php.ini)
- **Logging:** Implement server-side error logging to capture critical events, unhandled exceptions, and authentication failures.

### 2.8 — Password Recovery Simulation
*Goal: Demonstrate a realistic recovery flow without requiring real SMS infrastructure.*

**New Table: `password_resets`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| phone | VARCHAR(15) | FK → users.phone |
| otp | CHAR(6) | 6-digit numeric code |
| expires_at | DATETIME | Set to NOW() + 15 minutes |
| is_used | TINYINT(1) DEFAULT 0 | Prevent OTP reuse |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

**Step 1 — Request OTP (`/auth/forgot-password` GET/POST):**
1. Show a form asking for the user's registered phone number.
2. On POST, check if phone exists in `users` table using a prepared statement.
3. If not found → show generic error: *"If this number is registered, you will receive an OTP."* (never confirm whether a phone exists — security best practice).
4. Rate Limit: Check if more than 3 requests were made in the last 15 minutes for this phone. If so, block the request to prevent brute-force attacks.
5. Generate OTP: `$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)`
5. Delete any existing unused OTPs for this phone first (one active OTP at a time).
6. INSERT into `password_resets`: store phone, otp, `expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE)`.
7. **Simulation display:** Show a styled info box on screen: *"📱 In production, an SMS would be sent. Your OTP is: **123456**"* — clearly labeled as a demo simulation.
8. Redirect to the OTP verification form.

**Step 2 — Verify OTP (`/auth/verify-otp` GET/POST):**
1. User enters the 6-digit OTP.
2. PHP looks up: `SELECT * FROM password_resets WHERE phone = ? AND is_used = 0 AND expires_at > NOW()` using prepared statement.
3. If no valid record found → *"OTP is invalid or has expired."* Redirect back to Step 1.
4. If found, mark as used: `UPDATE password_resets SET is_used = 1 WHERE id = ?`
5. Store phone in a short-lived session variable `$_SESSION['reset_phone']` to carry the identity to Step 3.
6. Redirect to the password reset form.

**Step 3 — Reset Password (`/auth/reset-password` GET/POST):**
1. Check `$_SESSION['reset_phone']` exists — if not, reject and redirect (prevents direct URL access).
2. Show a form with new password + confirm password fields.
3. Validate: passwords match, min 8 chars, at least one number.
4. Hash: `password_hash($newPassword, PASSWORD_ARGON2ID)`
5. `UPDATE users SET password_hash = ? WHERE phone = ?`
6. Unset `$_SESSION['reset_phone']` immediately after use.
7. Redirect to login page with success message: *"Password reset successfully. Please log in."*

**Security Notes:**
- OTP expires in 15 minutes — enforced in DB query, not just application logic.
- `is_used` flag prevents replay attacks (using the same OTP twice).
- Generic error messages prevent phone number enumeration.
- Session variable cleared immediately after password update.

### 2.9 — Security Hardening Checklist
- [ ] All DB queries use **mysqli prepared statements** (`$conn->prepare()` + `bind_param()`) — zero string interpolation into SQL
- [ ] `htmlspecialchars()` wrapping on all user-generated content rendered to HTML
- [ ] File upload validation (images only: check MIME type, not just extension)
- [ ] `X-Frame-Options: DENY` header in PHP
- [ ] Uploads directory has no PHP execution (`.htaccess` deny rule)
- [ ] Passwords never logged or stored in plain text anywhere

### 2.10 — Test & Verify: Auth Module
**Do not proceed to Module 4 until ALL of these pass:**
- [ ] Register with valid data → user row created in DB, redirect to dashboard
- [ ] Register with duplicate phone → "Phone already registered" error displayed
- [ ] Login with correct credentials → session created, redirect to dashboard
- [ ] Login with wrong password → "Invalid credentials" error displayed
- [ ] Access `/dashboard` without session → redirect to login page
- [ ] CSRF token missing/invalid on POST → request rejected
- [ ] Forgot password → OTP generated, verified, password updated in DB
- [ ] Flash messages display once and disappear on next page load
- [ ] All auth forms render correctly at 375px mobile width

---

## Module 3 — Responsive UI Shell & Design System
*Goal: Build the visual skeleton that all features will live inside.*

### 3.1 — Design Tokens (CSS Variables)
Define in `:root` before writing any component styles:
```
--color-primary: #2E7D32      (deep agricultural green)
--color-accent: #F9A825       (harvest amber/gold)
--color-bg: #F5F5F0           (off-white — easy on eyes outdoors)
--color-text: #1A1A1A         (near-black for contrast)
--color-danger: #C62828
--color-card-bg: #FFFFFF
--shadow-card: 0 2px 8px rgba(0,0,0,0.10)
--radius: 8px
--font-primary: 'Noto Sans', sans-serif   (supports Kannada/Devanagari)
--font-size-base: 16px        (WCAG minimum for accessibility)
```

### 3.2 — Desktop Layout Architecture
The desktop layout uses CSS Grid for the macro layout:
```
+----------------------------------+
| TOPBAR (logo, user menu, notifs) |
+--------+-------------------------+
|        |                         |
| SIDE   |   MAIN CONTENT AREA     |
| NAV    |   (CSS Grid/Flexbox)    |
| (220px)|                         |
|        |                         |
+--------+-------------------------+
```
- Sidebar: fixed position, collapsible, contains nav links with icons
- Main area: scrollable, padded, contains page content

### 3.3 — Mobile Responsive Collapse Strategy
Media query breakpoints:
- `max-width: 768px` → Mobile: sidebar hides, hamburger icon appears
- `max-width: 1024px` → Tablet: sidebar collapses to icon-only strip (60px)

Mobile behavior:
- Sidebar becomes a drawer (slides in from left, overlay backdrop)
- Equipment cards: grid collapses from 3-column to 1-column stack
- Data tables: collapse to "card per row" layout using `display: flex` with `data-label` attributes for field labels
- Booking calendar: horizontal scroll enabled on small screens
- All tap targets ≥ 44×44px (Apple/Google accessibility standard)

### 3.4 — Component Library (Pure HTML/CSS)
Build these reusable components before building pages:
- **`.card`** — white box, shadow, rounded corners, hover lift effect
- **`.btn`** variants: `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-outline`
- **`.badge`** — for status labels (Pending, Confirmed, Available)
- **`.form-group`** — label + input + error message container
- **`.alert`** — success/error/info message banners
- **`.data-table`** — styled table with zebra rows + mobile collapse behavior
- **`.progress-bar`** — for pooling campaign thresholds
- **`.rating-stars`** — 1–5 star display with half-star support
- **`.avatar`** — circular profile photo with fallback initials


### 3.5 — Light / Dark Mode Strategy
*Goal: Follow OS preference by default, allow manual override, persist the user's choice.*

**Approach: CSS custom properties + `data-theme` attribute + `localStorage`**

This is the cleanest zero-dependency implementation for a Vanilla CSS project.

**Step 1 — Extend the CSS design tokens in `:root`:**
```css
/* Light mode (default) */
:root {
  --color-primary:   #2E7D32;
  --color-accent:    #F9A825;
  --color-bg:        #F5F5F0;
  --color-surface:   #FFFFFF;
  --color-text:      #1A1A1A;
  --color-text-muted:#555555;
  --color-border:    #DDDDDD;
  --color-danger:    #C62828;
  --shadow-card:     0 2px 8px rgba(0,0,0,0.10);
}

/* Dark mode overrides */
[data-theme="dark"] {
  --color-bg:        #121212;
  --color-surface:   #1E1E1E;
  --color-text:      #F0F0F0;
  --color-text-muted:#AAAAAA;
  --color-border:    #333333;
  --shadow-card:     0 2px 8px rgba(0,0,0,0.40);
  /* primary and accent stay the same — they work on both backgrounds */
}

/* Respect OS preference when no manual override is set */
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --color-bg:        #121212;
    --color-surface:   #1E1E1E;
    --color-text:      #F0F0F0;
    --color-text-muted:#AAAAAA;
    --color-border:    #333333;
    --shadow-card:     0 2px 8px rgba(0,0,0,0.40);
  }
}
```

**Step 2 — Theme Toggle Button (HTML in topbar):**
```html
<button id="theme-toggle" class="btn-icon" aria-label="Toggle dark mode" title="Toggle dark mode">
  <span id="theme-icon">🌙</span>
</button>
```

**Step 3 — Theme Toggle Logic (JS in `assets/js/theme.js`):**
```javascript
const html = document.documentElement;
const toggle = document.getElementById('theme-toggle');
const icon   = document.getElementById('theme-icon');

// On page load: apply saved preference
const saved = localStorage.getItem('theme');
if (saved) {
  html.setAttribute('data-theme', saved);
  icon.textContent = saved === 'dark' ? '☀️' : '🌙';
}

// On button click: flip and save
toggle.addEventListener('click', () => {
  const current = html.getAttribute('data-theme');
  const next    = current === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  icon.textContent = next === 'dark' ? '☀️' : '🌙';
});
```

**Load `theme.js` in `<head>` (not at end of body)** to prevent a flash of wrong theme on page load.

**Rules for building components in this system:**
- Every color in CSS must use a variable (e.g., `color: var(--color-text)`) — never hardcode hex values outside of `:root`.
- Background colors use `--color-bg` (page) or `--color-surface` (cards, modals).
- Borders use `--color-border`. Muted text uses `--color-text-muted`.
- Test every component in both modes before marking it done.

---

## Module 4 — Equipment Inventory Management (Vertical Slice)
*Goal: Farmers can list, edit, browse, and manage equipment — fully working from UI to database.*

### 4.1 — UI: Equipment Forms & Browse Page
Build these pages using the Module 3 design system:
- **Add Equipment page** (`equipment-create.php`) — fields: title, category (dropdown), description, price_per_hour, price_per_day, includes_operator (checkbox), village, district, condition, image uploads (up to 5)
- **Edit Equipment page** (`equipment-edit.php`) — same form, pre-populated with existing data
- **Equipment Browse page** (`equipment-browse.php`) — filter sidebar (category, district, max price, operator included, availability) + responsive card grid (3-col → 1-col on mobile)
- **Equipment Detail page** (`equipment-detail.php`) — image gallery, specs table, pricing info, owner trust score, booking CTA
- All pages include CSRF tokens on forms and use design system components (`.card`, `.btn`, `.form-group`, `.badge`)

### 4.2 — Backend: Equipment CRUD & Image Upload (PHP + mysqli)
**Create/Edit Logic:**
1. Validate all POST fields server-side
2. INSERT or UPDATE `equipment` table using `$conn->prepare()` + `$stmt->bind_param()`
3. **Image Upload handling:**
   - Validate: `$_FILES['images']['type']` must be in `['image/jpeg','image/png','image/webp']`. Also validate file content using functions like `getimagesize()` to prevent bypassing extension checks.
   - Validate size: max 2MB per image
   - Generate unique filename: `uniqid() . '.' . $extension`
   - Move to `/uploads/equipment/` using `move_uploaded_file()`
   - Store array of paths as JSON in `equipment.images` column
   - **Security:** Place `/uploads/.htaccess` with `php_flag engine off` to block PHP execution

**Delete Logic:**
- Verify ownership (session `user_id` must equal `equipment.owner_id`)
- Delete associated image files from disk
- DELETE row from `equipment` table

### 4.3 — Backend: Browse, Search & Filter (PHP + mysqli)
Filter options: category, district, max price per day, includes_operator, availability
- PHP builds dynamic WHERE clause using `$conn->prepare()` with positional `?` placeholders and `$stmt->bind_param()`
- Never concatenate filter values directly into SQL string
- Results paginated: 12 per page, PHP LIMIT/OFFSET

### 4.4 — AJAX: Availability Toggle & Dynamic Pricing
**Availability Toggle (no page reload):**
- POST endpoint `/equipment/{id}/toggle-availability`
- Checks ownership (session user_id must equal equipment.owner_id)
- Flips `is_available` bit in DB
- Returns `{success: true/false, message: "..."}` JSON
- Vanilla JS `fetch()` call updates the toggle button state on success

**Dynamic Pricing Display:**
When a user selects date/time on the booking form, PHP calculates:
- Duration in hours: `(strtotime($end) - strtotime($start)) / 3600`
- If duration < 8 hours → charge hourly rate
- If duration ≥ 8 hours → compare `hours × hourly_rate` vs `days × daily_rate`, use whichever is lower
- Return price via AJAX as JSON to update a live "Estimated Cost" display

### 4.5 — Test & Verify: Equipment Module
**Do not proceed to Module 5 until ALL of these pass:**
- [ ] Create equipment listing with image upload → row in DB, images in `/uploads/equipment/`
- [ ] Edit equipment → DB row updated correctly
- [ ] Delete equipment → row removed, images deleted from disk
- [ ] Toggle availability via AJAX → `is_available` flips in DB without page reload
- [ ] Browse page with filters → correct results returned, pagination works
- [ ] Browse page at 375px → cards stack to 1-column, filter panel collapses
- [ ] Non-owner tries to edit/delete → rejected with error
- [ ] Image upload with invalid type (e.g., .php file) → rejected

---

## Module 5 — Booking & Conflict-Free Scheduling (Vertical Slice)
*Goal: Zero double-bookings, intuitive calendar UI — fully wired from UI to database.*

### 5.1 — UI: Booking Calendar & Detail Section
- On equipment detail page, add a **booking section** below specs
- **Booking Calendar** (JavaScript): fetch booked slots via AJAX (`GET /api/equipment/{id}/booked-slots`), render a monthly calendar marking booked dates in red/grey
- User clicks start date, then end date — JavaScript validates selection is not blocked
- **Booking Form**: date range display, pricing mode selector (hourly/daily), live price estimate, notes textarea, submit button
- **My Bookings page** (`my-bookings.php`) — table/card list of user's bookings with status badges and action buttons (confirm/cancel)

### 5.2 — Backend: Conflict Detection Algorithm (PHP + mysqli)
This is the most critical backend logic. Before creating any booking:

```
INPUT: equipment_id, requested_start, requested_end

QUERY: SELECT id FROM bookings
WHERE equipment_id = ?
AND status IN ('pending', 'confirmed', 'active')
AND start_datetime < ? (requested_end, exclusive)
AND end_datetime > ? (requested_start)
LIMIT 1

If any row returned → CONFLICT EXISTS → reject booking
If no rows → slot is free → proceed
```

This uses the standard interval overlap formula: two intervals overlap if `A.start < B.end AND A.end > B.start`.

### 5.3 — Backend: Booking State Machine (PHP + mysqli)
Bookings follow a strict status flow:
```
pending → confirmed (owner approves)
pending → cancelled (either party)
confirmed → active (start_datetime reached)
active → completed (end_datetime reached OR manual completion)
confirmed → cancelled (before start, with optional reason)
```
- PHP enforces these transitions — a `completed` booking cannot be moved back to `active`
- Implement as a `BookingModel::transition($bookingId, $newStatus)` method that validates the transition

### 5.4 — AJAX: Calendar, Price Calculator & Conflict Check
- **Calendar render**: JS `fetch()` to get booked slots, render visual calendar
- **Live price calculator**: JS sends start/end to PHP endpoint, PHP returns calculated price, JS updates "Estimated Cost" display
- **Conflict re-check on submit**: AJAX POST to server for final conflict validation before creating booking (client calendar is UX only, server is the real guard)
- All AJAX responses use `{success: true/false, message: "..."}` structure

### 5.5 — Backend: In-App Notifications (PHP + mysqli)
- Add a `notifications` table: user_id, message, is_read, created_at
- On booking created → notify owner
- On booking confirmed → notify renter
- On booking cancelled → notify other party
- Show unread count badge in top navigation bar
- Mark as read via AJAX on click

### 5.6 — Test & Verify: Booking Module
**Do not proceed to Module 6 until ALL of these pass:**
- [ ] Book Equipment A from Jan 10 9am–12pm, then try Jan 10 10am–2pm → **must be rejected** (overlap)
- [ ] Book Equipment A Jan 10 9am–12pm, then Jan 10 12pm–3pm → **must succeed** (adjacent)
- [ ] Book Equipment A Jan 10 9am–12pm, then Jan 10 7am–9am → **must succeed** (before)
- [ ] Booking calendar shows booked dates as blocked
- [ ] Live price calculator returns correct hourly vs daily pricing
- [ ] Status transitions enforce state machine (e.g., completed → active rejected)
- [ ] Notifications created for owner on new booking, renter on confirmation
- [ ] My Bookings page renders correctly on mobile

---

## Module 6 — Community Pooling / Bulk-Buy (Vertical Slice)
*Goal: Farmers pool demand to unlock wholesale prices — fully working from UI to database.*

### 6.1 — UI: Campaign & Pledge Pages
- **Campaign Browse page** (`pooling-browse.php`) — grid of campaign cards with progress bars, showing savings potential
- **Campaign Detail page** (`pooling-detail.php`) — full info, progress bar, pledge form, member list, savings breakdown
- **Create Campaign page** (`pooling-create.php`) — form: item name, unit, individual price, bulk price, min quantity, deadline, district, description
- All pages use design system components (`.progress-bar`, `.card`, `.badge`, `.form-group`)

### 6.2 — Backend: Campaign Creation & Pledge Logic (PHP + mysqli)
**Campaign Creation:**
- Any verified farmer can create a campaign
- PHP validates: `bulk_price` must be < `individual_price`, `deadline` must be future date
- INSERT into `pooling_campaigns` using prepared statement

**Pledge Logic — when a farmer pledges a quantity:**
1. Check campaign status is 'open'
2. Check deadline has not passed
3. Check farmer hasn't already pledged (UNIQUE constraint catches this — handle the exception gracefully)
4. INSERT into `pooling_pledges`
5. UPDATE `pooling_campaigns SET current_quantity = current_quantity + ?` (use atomic SQL update, not read-then-write)
6. Check if `current_quantity >= minimum_quantity` → if yes, UPDATE status to 'threshold_met', notify all pledgers

### 6.3 — UI + PHP: Progress Bar & Savings Display
PHP calculates and returns:
- `progress_percentage = (current_quantity / minimum_quantity) * 100` (capped at 100)
- `quantity_remaining = max(0, minimum_quantity - current_quantity)`
- `savings_per_unit = individual_price - bulk_price`
- `total_savings_if_threshold_met = savings_per_unit × minimum_quantity`

Display these prominently on the campaign card — the "savings unlocked" number is the key motivator.

### 6.4 — Backend: Campaign Lifecycle (PHP + mysqli)
- Farmer can cancel their pledge while status is 'open' (decrements `current_quantity` atomically)
- At deadline, a PHP cron job (or triggered check on page load) closes expired 'open' campaigns that didn't meet threshold → status = 'cancelled', notify pledgers
- Creator can manually close/cancel their campaign

### 6.5 — AJAX: Pledge Cancel
- POST `/pooling/{id}/cancel-pledge` endpoint
- Verifies pledge belongs to session user
- Atomically decrements `current_quantity`
- Returns JSON response, JS removes pledge from UI without page reload

### 6.6 — Test & Verify: Pooling Module
**Do not proceed to Module 7 until ALL of these pass:**
- [ ] Create campaign → row in DB with correct fields
- [ ] Pledge to campaign → `current_quantity` increments correctly
- [ ] Pledge from same farmer twice → graceful duplicate error, NOT double-increment
- [ ] Pledge that pushes total past threshold → status changes to 'threshold_met', notifications sent
- [ ] Cancel pledge via AJAX → `current_quantity` decrements, UI updates without reload
- [ ] Progress bar displays correct percentage
- [ ] Savings display shows correct per-unit and total savings
- [ ] Campaign with passed deadline → status changes to 'cancelled'

---

## Module 7 — Trust & Fairness / Two-Way Reviews (Vertical Slice)
*Goal: Build community safety through mutual accountability — fully wired from UI to database.*

### 7.1 — UI: Review Form & Trust Profile Page
- **Review Form** (on completed booking page) — star rating selector (1–5), comment textarea, review type auto-set based on user role in booking
- **User Public Profile page** (`user-profile.php`) — trust score (large stars), review count, "As Equipment Owner" reviews tab, "As Renter" reviews tab, reviewer name + date + comment for each
- **Trust signals on equipment cards** — owner name, trust_score stars, verified badge (checkmark if `is_verified = 1`), completed booking count

### 7.2 — Backend: Review Eligibility & Submission (PHP + mysqli)
A review can only be submitted when ALL of these are true:
- The booking status is `completed`
- The reviewer is either the renter or the owner of that booking
- The reviewer has not already submitted a review for this booking (UNIQUE constraint on `booking_id` + `reviewer_id`)
- Review window: within 14 days of completion (check `bookings.updated_at`)

On valid submission: INSERT into `reviews`, then trigger trust score recalculation.

### 7.3 — Backend: Trust Score Calculation (PHP + mysqli)
After every review submission, recalculate the reviewee's trust_score:
```sql
UPDATE users SET trust_score = (
  SELECT ROUND(AVG(rating), 2) FROM reviews WHERE reviewee_id = ?
) WHERE id = ?
```
Display as a star rating (1–5) with total review count. Show on all equipment listings and user profiles.

### 7.4 — Test & Verify: Review Module
**Do not proceed to Module 8 until ALL of these pass:**
- [ ] Submit review for completed booking → review row in DB, trust_score recalculated
- [ ] Submit review for non-completed booking → rejected
- [ ] Submit duplicate review for same booking → rejected (UNIQUE constraint)
- [ ] Review after 14-day window → rejected
- [ ] Trust score correctly reflects average of all reviews
- [ ] User profile page shows reviews split by type (Owner / Renter)
- [ ] Equipment cards display owner trust score and verified badge

---

## Module 8 — Admin Panel (Vertical Slice)
*Goal: Admin oversight and moderation — fully working from UI to database.*

### 8.1 — UI: Admin Dashboard & Management Pages
- **Admin Dashboard** (`admin-dashboard.php`) — stats widgets grid showing key metrics
- **User Management page** (`admin-users.php`) — paginated table with search by name/phone/district, action buttons (verify, view profile, deactivate)
- **Equipment Moderation page** (`admin-equipment.php`) — all listings with owner name, flag/remove actions
- **Booking Overview page** (`admin-bookings.php`) — all platform bookings with status filters
- All pages use design system data tables with mobile card collapse

### 8.2 — Backend: Dashboard Metrics (PHP + mysqli)
Display live stats using prepared statements:
- Total registered users / new this week
- Total equipment listings / currently available
- Total bookings / by status breakdown
- Active pooling campaigns / threshold met count
- Total platform reviews / average rating

### 8.3 — Backend: User & Equipment Management (PHP + mysqli)
**User Management:**
- Verify user: `UPDATE users SET is_verified = 1 WHERE id = ?`
- View profile: link to public profile page
- Deactivate account: check for active bookings before allowing

**Equipment Moderation:**
- List all equipment with owner name (JOIN query)
- Flag/remove listings that violate platform rules
- Toggle availability on behalf of owner if reported as unavailable

### 8.4 — Admin Role Protection
- Every admin route wrapped in `requireRole('admin')` — if non-admin tries to access, redirect to farmer dashboard with error message
- Admin cannot be created via public registration — only via direct DB seed or a one-time setup script

### 8.5 — Test & Verify: Admin Module
**Do not proceed to Module 9 until ALL of these pass:**
- [ ] Admin dashboard displays correct live metrics (cross-check with DB)
- [ ] Verify user → `is_verified` = 1 in DB, verified badge appears on profile
- [ ] Deactivate user with active bookings → rejected with error
- [ ] Non-admin accesses `/admin/*` → redirect to farmer dashboard
- [ ] User search by name/phone/district returns correct results
- [ ] Equipment moderation: flag/remove listing → DB updated
- [ ] Admin pages render correctly on mobile (data tables collapse to cards)

---

## Module 9 — Comprehensive Testing Protocol

### 9.1 — Backend Unit Logic Tests (Manual)
Test each PHP function in isolation using a test script or Postman:

**Auth Tests:**
- Register with valid data → expect redirect to dashboard
- Register with duplicate phone → expect "phone already registered" error
- Login with wrong password → expect "invalid credentials" error
- Access `/dashboard` without session → expect redirect to login

**Booking Conflict Tests (Most Critical):**
- Book Equipment A from Jan 10 9am–12pm, then try booking same equipment Jan 10 10am–2pm → must be rejected
- Book Equipment A Jan 10 9am–12pm, then book Jan 10 12pm–3pm → must succeed (adjacent, not overlapping)
- Book Equipment A Jan 10 9am–12pm, then book Jan 10 7am–9am → must succeed (before, not overlapping)

**Pooling Tests:**
- Pledge to campaign → verify `current_quantity` increments correctly
- Pledge from same farmer twice → expect duplicate error, not double-increment
- Pledge that reaches threshold → verify status changes to 'threshold_met'

### 9.2 — SQL Injection Tests
Test every input field connected to a DB query:
- Enter `' OR '1'='1` in login phone field → must NOT bypass login
- Enter `'; DROP TABLE users; --` in search field → must NOT execute
- Verify all queries use **mysqli prepared statements** via `$conn->prepare()` + `bind_param()` (code review, not just behavior test)
- Use **sqlmap** (open-source tool) for automated injection scanning before final submission

### 9.3 — UI Responsive Tests
Test these exact breakpoints using browser DevTools (Chrome: Toggle Device Toolbar):

| Width | Expected Behavior |
|---|---|
| 1440px | Full sidebar, 3-column equipment grid, full data tables |
| 1024px | Sidebar collapses to icons, 2-column grid |
| 768px | Hamburger menu appears, sidebar hidden, 2-column grid |
| 375px (iPhone SE) | 1-column stack, all elements full-width, large tap targets |
| 414px (iPhone 14) | 1-column stack, booking calendar horizontally scrollable |

Specific checks:
- All buttons ≥ 44px tall on mobile
- No horizontal scroll on page body at 375px
- Forms readable without zooming
- Navigation accessible via hamburger on mobile

### 9.4 — Cross-Browser Tests
Test on: Chrome (latest), Firefox (latest), Safari (if available), Chrome for Android.
Focus on: Flexbox/Grid rendering, date inputs, file upload behavior.

### 9.5 — End-to-End Scenario Tests
These are the full user journeys to test manually before demo:

**Scenario A — Full Rental Journey:**
1. Register as Farmer A (equipment owner)
2. Register as Farmer B (renter)
3. Farmer A lists a tractor with image upload
4. Farmer B browses, finds the tractor, checks availability
5. Farmer B books for 2 days
6. Farmer A confirms booking
7. Booking status changes to completed
8. Both parties submit reviews
9. Trust scores update on both profiles

**Scenario B — Conflict Prevention:**
1. Farmer B books tractor for Jan 15–16
2. Farmer C tries to book same tractor for Jan 15–17
3. System must reject Farmer C's booking with clear error message

**Scenario C — Bulk Buy Pooling:**
1. Farmer A creates a fertilizer pooling campaign (min: 100 bags)
2. Farmer B pledges 40 bags
3. Farmer C pledges 30 bags
4. Farmer D pledges 35 bags (running total: 105 — threshold met)
5. Campaign status changes, all three farmers notified

**Scenario D — Admin Oversight:**
1. Admin logs in to admin panel
2. Admin verifies Farmer A's account
3. Admin views all bookings on the platform
4. Admin sees platform-wide statistics

---

## Module 10 — Performance, Polish & Demo Preparation

### 10.1 — Performance Optimizations
- Add MySQL indexes on frequently queried columns:
  - `equipment(is_available, location_district)`
  - `bookings(equipment_id, status, start_datetime, end_datetime)`
  - `pooling_pledges(campaign_id, farmer_id)`
- Compress all uploaded images using PHP GD library (resize to max 1200px width on upload)
- Minify CSS into a single file for production
- Use PHP output buffering if needed for performance

### 10.2 — Demo Environment Setup
- Create a comprehensive `seed.sql` file populating:
  - 6+ sample farmers with profile photos
  - 8+ equipment listings across multiple districts and categories
  - 10+ bookings in various statuses (pending, confirmed, completed)
  - 3+ pooling campaigns (one open, one threshold_met, one closed)
  - 15+ reviews with varied ratings
  - 1 admin account
- Test the entire demo flow using only seeded data (no live data entry during demo)

### 10.3 — Desktop Demo Checklist (Examiner View)
Prepare a demo script hitting these "wow factor" moments in order:
1. **Landing page** — professional hero section, clear value proposition
2. **Browse equipment** — filter by district, show responsive card grid
3. **Equipment detail** — image gallery, live price calculator, booking calendar with blocked dates
4. **Dashboard** — Farmer dashboard with their listings, bookings, and trust score
5. **Pooling campaign** — animated progress bar reaching threshold
6. **Admin panel** — live stats, user verification workflow
7. **Mobile demo** — resize browser to 375px live, show hamburger nav, stacked layout

### 10.4 — Final Code Quality Pass
- Remove all `var_dump()`, `print_r()`, `die()` debug statements
- Ensure no PHP warnings in error log
- Consistent indentation (4 spaces)
- All database credentials in config file (not hardcoded anywhere else)
- README.md with setup instructions (DB import, web server config, default admin credentials)

---

## Dependency Map (Build Order)

```
Module 1 (Architecture Planning)
    └── Module 2 (Auth — Vertical Slice)
            ├── Module 3 (UI Shell & Design System)
            │       └── Module 4 (Equipment CRUD — Vertical Slice)
            │               └── Module 5 (Booking — Vertical Slice)
            │                       ├── Module 7 (Reviews — Vertical Slice)
            │                       └── Module 6 (Pooling — Vertical Slice)
            └── Module 8 (Admin — Vertical Slice)

Module 9 (Testing) — after Modules 4–8
Module 10 (Polish & Demo) — after Module 9
```

**Each vertical slice module (2, 4–8) must pass its own Test & Verify checklist before moving on. Do not start Module 5 until Module 4 equipment listings exist to book. Do not start Module 7 until Module 5 bookings can reach 'completed' status.**

---

## Zero-Cost Tools Reference

| Need | Tool |
|---|---|
| Local server | WAMP |
| DB GUI | phpMyAdmin (included in WAMPP) |
| CSS icons | Font Awesome Free (CDN) |
| Fonts | Google Fonts (Noto Sans) |
| Security testing | sqlmap (open source) |
| Image compression | PHP GD library (built-in) |
| API testing | Hoppscotch (browser-based Postman alternative) |
| Version control | Git + GitHub (free) |