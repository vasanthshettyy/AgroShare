# AgroShare — Comprehensive Project Report

## Project Title: AgroShare — Agricultural Equipment Sharing and Community Pooling Platform

**Version:** 1.2.1  
**Technology Stack:** PHP 8.x, MySQL 8.0+, Vanilla JavaScript, CSS3  
**Development Environment:** WAMP Server (Windows, Apache, MySQL, PHP)  
**Date:** April 2026

---

# Chapter 1: INTRODUCTION

## 1.1 Project Overview

AgroShare is a web-based agricultural equipment sharing and community pooling platform designed to address a critical problem faced by small and marginal farmers across India. In a country where over 86% of farmers are small or marginal, the cost of owning modern farming equipment such as tractors, harvesters, seeders, and sprayers is prohibitively expensive. AgroShare bridges this gap by enabling farmers to list their idle equipment for rent and allowing other farmers to discover and book that equipment at affordable daily rates.

Beyond equipment rental, AgroShare introduces a Community Pooling module where farmers can collectively pool their demand for agricultural inputs like fertilizers, seeds, and pesticides. By aggregating demand, farmers unlock bulk purchase discounts that would be impossible to achieve individually. This cooperative buying approach directly reduces input costs and improves profit margins for small-scale farmers.

The platform is built as a full-stack PHP web application running on the WAMP stack (Windows, Apache, MySQL, PHP). It follows a modular architecture with clear separation between configuration, business logic (Controllers and Helpers), data storage (MySQL with InnoDB engine), API endpoints (AJAX-driven), and frontend presentation (responsive HTML/CSS/JavaScript). The entire application is designed to run locally on a development machine with the potential for production deployment on any standard LAMP/WAMP hosting environment.

## 1.2 Problem Statement

Indian agriculture faces several interconnected challenges related to equipment access and input procurement:

1. **High Equipment Cost:** A single tractor costs between ₹5 to ₹10 lakh, which is beyond the reach of most small farmers. Even simpler machines like power tillers, rotavators, and threshers cost ₹1 to ₹3 lakh.

2. **Equipment Underutilization:** Farmers who do own equipment use it only during specific seasons (sowing, harvesting). For the rest of the year, this expensive machinery sits idle, representing a significant waste of capital.

3. **Lack of a Rental Marketplace:** There is no organized, trustworthy platform for farmers to rent or share equipment. Current methods rely on word-of-mouth and local informal networks, which are inefficient and often lead to disputes.

4. **Expensive Agricultural Inputs:** Farmers buying fertilizers, seeds, and pesticides individually pay retail prices. Bulk discounts are only available to large-scale buyers or cooperatives, leaving small farmers at a disadvantage.

5. **Trust Deficit:** Without a rating or verification system, farmers are hesitant to lend expensive machinery to strangers. There is no mechanism to build or verify trust between parties.

AgroShare addresses all five of these problems through a unified digital platform that combines equipment rental, community pooling, trust scoring, and an administrative oversight system.

## 1.3 Objectives of the Project

The primary objectives of the AgroShare project are:

1. **Build a Peer-to-Peer Equipment Rental Marketplace** — Enable farmers to list their agricultural equipment with detailed descriptions, pricing, images, and availability status. Other farmers can browse, filter, and book this equipment for specific date ranges.

2. **Implement a Robust Booking Lifecycle** — Manage the complete booking process from creation through confirmation, activation, completion, and cancellation, with a well-defined state machine that prevents invalid transitions.

3. **Create a Community Pooling System** — Allow farmers to create and participate in demand aggregation campaigns for agricultural inputs, with threshold-based activation and deadline management.

4. **Establish a Trust and Review Mechanism** — Implement a two-way review system (renter-to-owner and owner-to-renter) that calculates dynamic trust scores, encouraging good behavior and building community confidence.

5. **Provide Administrative Oversight** — Build a comprehensive admin panel for user management, equipment moderation, booking oversight, dispute resolution, and platform configuration.

6. **Ensure Security and Data Protection** — Implement industry-standard security measures including Argon2id password hashing, CSRF protection, prepared SQL statements, session management, rate limiting, and audit logging.

7. **Deliver a Premium User Experience** — Create a modern, responsive, dark-themed user interface with smooth animations, micro-interactions, and intuitive navigation that works across desktop and mobile devices.

## 1.4 Scope of the Project

The scope of AgroShare covers the following functional areas:

| Module | Description |
|--------|-------------|
| User Authentication | Registration, Login (phone/email), Password Recovery via Email OTP |
| User Profile Management | View and edit profile details, upload profile photo, view trust score |
| Equipment Management | Create, Read, Update, Delete equipment listings with multi-image upload |
| Equipment Browse & Search | Filter by category, district, price, operator availability with pagination |
| Booking System | Create bookings, conflict detection, auto-promotion, status management |
| Review & Trust Score | Two-way reviews after completed bookings, automated trust score calculation |
| Community Pooling | Create supply campaigns, pledge quantities, threshold monitoring, deadline expiry |
| Notification System | In-app notifications for booking events and pooling milestones |
| Admin Dashboard | Platform statistics, user management, equipment moderation |
| Admin Booking Management | Override booking status, resolve disputes |
| Admin Settings | Platform-wide configuration management |
| Audit Logging | Security event tracking for login attempts and administrative actions |
| Safety Deposit | Refundable deposit system for equipment protection |

## 1.5 Technology Stack

The following technologies and tools have been used in the development of AgroShare:

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend Language** | PHP 8.x | Server-side logic, form processing, API endpoints |
| **Database** | MySQL 8.0+ (InnoDB) | Relational data storage with foreign keys and transactions |
| **Database Driver** | MySQLi (Object-Oriented) | Database connectivity with prepared statements |
| **Web Server** | Apache (via WAMP) | HTTP request handling and URL rewriting |
| **Frontend Markup** | HTML5 | Semantic page structure |
| **Frontend Styling** | CSS3 (Vanilla) | Custom design system with CSS variables, dark theme |
| **Frontend Logic** | Vanilla JavaScript (ES6+) | AJAX calls, DOM manipulation, form validation |
| **Typography** | Google Fonts (Inter) | Modern, readable typeface |
| **Email** | PHPMailer 7.x | SMTP-based transactional emails (Gmail) |
| **Password Hashing** | Argon2id | Industry-standard password hashing algorithm |
| **Dependency Management** | Composer | PHP package management for PHPMailer |
| **Version Control** | Git | Source code version tracking |
| **Development Server** | WAMP Server | Local development environment |

---

## 1.6 Technology Justification: Why We Use What We Use

To build a platform like AgroShare that is both highly secure and premium in feel, we carefully selected a "Modern Monolith" stack. Here is a deep dive into why these specific technologies were chosen and how they benefit the project.

### 1.6.1 PHP 8.x (The Brain of the Platform)
*   **What it is:** PHP is a server-side scripting language. It acts as the "Engine" or "Brain" that runs on the web server.
*   **Why we use it:** We use PHP to handle all the heavy lifting: processing forms, calculating rental prices, enforcing booking rules, and managing user sessions. It handles the "Business Logic" of AgroShare.
*   **Biggest Advantage:** **Deployment Simplicity.** PHP is the most widely supported language in the web world. It allows AgroShare to be hosted very cheaply on almost any server, making it accessible for rural community deployment.

### 1.6.2 MySQL 8.0+ & InnoDB (The Safe for Your Data)
*   **What it is:** MySQL is a Relational Database Management System (RDBMS). It is the digital "Vault" where all our data lives.
*   **Why we use it:** We use it to store everything—from farmer profiles and equipment listings to the history of every booking and review ever made.
*   **Biggest Advantage:** **Data Integrity through Transactions.** By using the "InnoDB" engine, we ensure that critical actions (like taking a security deposit and confirming a booking) happen "Atomically." This means if any part of the process fails, the whole transaction is rolled back, preventing errors like "lost" payments or double-bookings.

### 1.6.3 Vanilla JavaScript & AJAX (The Interactive Engine)
*   **What it is:** JavaScript is the language that runs in the user's browser. AJAX (Asynchronous JavaScript and XML) is a technique that lets the browser talk to the server in the background.
*   **Why we use it:** We use it to make the website feel "Alive." Instead of the page reloading every time you click a button, JavaScript handles the action instantly.
*   **Biggest Advantage:** **Premium User Experience.** By using AJAX for booking requests, searches, and form validations, we provide a smooth, app-like experience that feels high-end and reduces the "wait time" for the farmer.

### 1.6.4 Vanilla CSS3 & Custom Variables (The Visual Identity)
*   **What it is:** CSS is the "Skin" of the platform. It defines how every element looks—colors, fonts, layouts, and animations.
*   **Why we use it:** We built a custom "Design System" using CSS Variables (Custom Properties). This ensures a consistent, high-aesthetic "Dark Mode" theme across the entire application.
*   **Biggest Advantage:** **Maintainability.** Because we use variables like `--primary-action`, we can update the entire platform's branding (e.g., changing the green theme color) by editing just one line of code, rather than hundreds of files.

### 1.6.5 Argon2id Hashing (The Security Shield)
*   **What it is:** Argon2id is currently the most advanced and secure algorithm for protecting passwords.
*   **Why we use it:** We follow the "Zero Trust" principle—we never store a user's real password. We store a "Hash" (a one-way scrambled version) produced by Argon2id.
*   **Biggest Advantage:** **Maximum Security.** Even in the highly unlikely event of a database breach, the stored hashes are practically impossible for hackers to "un-scramble," ensuring that user passwords remain safe from even the most powerful hacking attacks.

### 1.6.6 PHPMailer & SMTP (The Communication Bridge)
*   **What it is:** PHPMailer is a robust library for sending emails, and SMTP is the standard "post office" protocol for the internet.
*   **Why we use it:** We use it to send transactional emails, such as the OTP (One-Time Password) for password recovery, directly to the user's Gmail inbox.
*   **Biggest Advantage:** **Inbox Deliverability.** Standard PHP mail functions are often blocked or marked as spam. By using PHPMailer with professional SMTP settings, we ensure that our security codes actually reach the user's inbox every single time.

---

# Chapter 2: SYSTEM ANALYSIS

## 2.1 Existing System Analysis

Before AgroShare, farmers in India relied on the following informal methods for equipment sharing and input procurement:

1. **Word-of-Mouth Networks:** Farmers would ask neighbors and local contacts about equipment availability. This method is unreliable, limited in reach, and provides no guarantee of availability or fair pricing.

2. **Local Middlemen:** In some regions, middlemen act as brokers between equipment owners and renters, taking a commission of 10-20%. This increases costs for both parties and introduces an unnecessary intermediary.

3. **Government Equipment Banks:** Some state governments operate equipment rental centers, but these suffer from bureaucratic delays, limited inventory, poor maintenance, and geographical inaccessibility.

4. **Individual Retail Purchase of Inputs:** Farmers purchase seeds, fertilizers, and pesticides from local dealers at maximum retail prices. There is no mechanism for collective bargaining.

### Drawbacks of the Existing System

- No centralized database of available equipment
- No standardized pricing or transparent cost structure
- No trust or verification mechanism between parties
- No booking system to prevent double-booking conflicts
- No dispute resolution mechanism for damaged equipment
- No collective buying option for agricultural inputs
- No digital record of transactions for reference

## 2.2 Proposed System

AgroShare proposes a comprehensive digital solution that addresses every drawback of the existing system:

1. **Centralized Equipment Marketplace:** All equipment listings are stored in a MySQL database and displayed through a searchable, filterable browse page. Farmers can list equipment with up to 5 high-quality images, detailed descriptions, pricing information, and location data.

2. **Automated Booking Management:** The booking system handles the entire lifecycle from request to completion. It includes automatic conflict detection (preventing double-bookings), server-side price calculation (preventing client-side manipulation), and auto-promotion of booking statuses based on date/time.

3. **Trust-Based Community:** A two-way review system allows both renters and owners to rate each other after a completed booking. These ratings are averaged into a trust score displayed on user profiles, creating accountability and encouraging responsible behavior.

4. **Community Pooling for Bulk Buying:** The pooling module enables farmers to create supply campaigns with target quantities and deadlines. Other farmers can pledge their demand, and when the aggregate reaches the threshold, all participants get access to the bulk discount price.

5. **Administrative Oversight:** A dedicated admin panel provides platform-wide visibility into users, equipment, bookings, and system settings. Administrators can verify users, toggle equipment featured status, override booking statuses, resolve disputes, and manage platform settings.

6. **Multi-Layer Security:** The system implements defense-in-depth security with CSRF tokens, prepared SQL statements, Argon2id password hashing, session idle timeouts, rate limiting on sensitive operations, audit logging, and security headers.

## 2.3 Feasibility Study

### 2.3.1 Technical Feasibility

The project uses well-established, open-source technologies (PHP, MySQL, Apache) that are widely available, well-documented, and run on any standard hosting environment. The WAMP stack provides a zero-cost development environment. All external dependencies (PHPMailer) are managed through Composer. No proprietary software or paid APIs are required.

### 2.3.2 Economic Feasibility

The entire platform can be developed and deployed at near-zero cost:
- **Development:** WAMP Server is free, PHP and MySQL are open-source
- **Hosting:** Can be deployed on shared hosting plans starting at ₹200-500/month
- **Email:** Uses Gmail SMTP with free tier (500 emails/day)
- **Maintenance:** No recurring license fees or vendor lock-in

### 2.3.3 Operational Feasibility

The platform uses a web browser as its primary interface, requiring no software installation for end users. The dark-themed, responsive design works across desktop computers, tablets, and mobile phones. The interface uses simple, farmer-friendly language and intuitive navigation patterns. Admin operations are separated into a dedicated panel with clear categorization.

## 2.4 Requirements Analysis

### 2.4.1 Functional Requirements

| ID | Requirement | Module |
|----|-------------|--------|
| FR-01 | Users shall register with full name, phone, email, password, city, and state | Authentication |
| FR-02 | Users shall log in using phone number or email address with password | Authentication |
| FR-03 | Users shall recover forgotten passwords via email-based OTP | Authentication |
| FR-04 | OTP shall expire after 15 minutes and be rate-limited to 3 per 15 minutes | Authentication |
| FR-05 | Users shall view and edit their profile including photo upload | Profile |
| FR-06 | Users shall create equipment listings with title, category, description, price, location, condition, and up to 5 images | Equipment |
| FR-07 | Users shall browse equipment with filters for category, district, price, and operator availability | Equipment |
| FR-08 | Users shall book equipment for specific date ranges with conflict detection | Booking |
| FR-09 | Equipment owners shall accept or reject incoming booking requests | Booking |
| FR-10 | Booking status shall automatically promote from confirmed to active, and active to completed based on date/time | Booking |
| FR-11 | Users shall submit reviews with 1-5 star ratings after completed bookings | Review |
| FR-12 | Trust scores shall be automatically recalculated after each new review | Review |
| FR-13 | Users shall create community pooling campaigns with target quantities and deadlines | Pooling |
| FR-14 | Users shall pledge quantities toward active pooling campaigns | Pooling |
| FR-15 | Campaigns shall automatically transition to "threshold_met" when aggregate pledges reach the target | Pooling |
| FR-16 | Users shall receive in-app notifications for booking and pooling events | Notification |
| FR-17 | Administrators shall manage users (verify, activate/deactivate, create admin accounts) | Admin |
| FR-18 | Administrators shall override booking statuses and resolve disputes | Admin |
| FR-19 | Administrators shall manage platform settings | Admin |
| FR-20 | All security events shall be logged in an audit trail | Security |

### 2.4.2 Non-Functional Requirements

| ID | Requirement | Category |
|----|-------------|----------|
| NFR-01 | All passwords shall be hashed using Argon2id algorithm | Security |
| NFR-02 | All database queries shall use prepared statements to prevent SQL injection | Security |
| NFR-03 | All forms shall include CSRF token validation | Security |
| NFR-04 | Sessions shall expire after 1 hour of inactivity | Security |
| NFR-05 | The UI shall be responsive across desktop (1920px) to mobile (320px) viewports | Usability |
| NFR-06 | Page load times shall be under 3 seconds on standard connections | Performance |
| NFR-07 | Image uploads shall be validated by MIME type using finfo (not file extension) | Security |
| NFR-08 | Maximum image size shall be 10MB per file, maximum 5 images per listing | Performance |
| NFR-09 | The system shall support concurrent users without data corruption using database transactions | Reliability |
| NFR-10 | All user-generated content shall be escaped using htmlspecialchars to prevent XSS | Security |

### 2.4.3 Hardware Requirements

| Component | Minimum Requirement |
|-----------|-------------------|
| Processor | Intel Core i3 or equivalent |
| RAM | 4 GB |
| Hard Disk | 500 MB for application (excluding database growth) |
| Network | Internet connection for email functionality |
| Display | 1024 x 768 minimum resolution |

### 2.4.4 Software Requirements

| Component | Requirement |
|-----------|-------------|
| Operating System | Windows 10/11 (development), Linux (production) |
| Web Server | Apache 2.4+ with mod_rewrite |
| PHP | Version 8.1 or higher |
| MySQL | Version 8.0 or higher |
| Browser | Chrome 90+, Firefox 88+, Edge 90+, Safari 14+ |
| Composer | Version 2.x for dependency management |
| WAMP Server | Version 3.3+ (for local development) |

---

# Chapter 3: SYSTEM DESIGN

## 3.1 System Architecture

AgroShare follows a layered architecture pattern with clear separation of concerns. The system is organized into four distinct layers:

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│  (HTML5, CSS3, Vanilla JavaScript, AJAX)                    │
│  login.php │ signup.php │ dashboard.php │ equipment-*.php   │
│  my-bookings.php │ pooling-*.php │ admin/*.php              │
├─────────────────────────────────────────────────────────────┤
│                    API LAYER (AJAX Endpoints)                │
│  public/api/*.php  │  public/admin/api/*.php                │
│  23 user endpoints │  7 admin endpoints                     │
├─────────────────────────────────────────────────────────────┤
│                    BUSINESS LOGIC LAYER                      │
│  src/Controllers/  │  src/Helpers/                           │
│  AdminController   │  auth.php (guards, CSRF, flash)        │
│  BookingController │  audit.php (event logging)              │
│  EquipmentController│ mail.php (PHPMailer SMTP)             │
│  PoolingController │                                        │
│  ReviewController  │                                        │
├─────────────────────────────────────────────────────────────┤
│                    DATA LAYER                                │
│  MySQL 8.0+ (InnoDB) │ 11 Tables │ Foreign Keys             │
│  config/db.php │ config/constants.php                       │
│  sql/agroshare_schema.sql │ sql/migrations/                 │
└─────────────────────────────────────────────────────────────┘
```

### 3.1.1 Presentation Layer

The presentation layer consists of PHP template files that render HTML with embedded PHP logic for dynamic content. Each page includes inline CSS or references external stylesheets from `public/assets/css/`. JavaScript files in `public/assets/js/` handle client-side interactivity including AJAX calls, form validation, calendar widgets, and modal management.

Key frontend files:
- `login.php` — Combined login/signup page with sliding panel animation (1,443 lines)
- `signup.php` — Standalone registration page (906 lines)
- `dashboard.php` — Main user dashboard with KPI cards, activity table, and chart (672 lines)
- `equipment-browse.php` — Equipment marketplace with filtering and pagination (32,698 bytes)
- `equipment-detail.php` — Product detail page with booking widget (35,528 bytes)
- `my-bookings.php` — Booking management with tabs and status actions (73,182 bytes)
- `pooling-browse.php` — Community pooling campaign browser (30,364 bytes)
- `pooling-detail.php` — Campaign detail page with pledge interface (43,219 bytes)

### 3.1.2 API Layer

The API layer consists of 23 user-facing endpoints and 7 admin-specific endpoints. All API endpoints follow a consistent pattern:
1. Include `config/db.php` for database and session initialization
2. Verify authentication using `requireAuth()`
3. Validate CSRF tokens on POST requests
4. Process business logic through Controller functions
5. Return JSON responses for AJAX consumption

**User API Endpoints (public/api/):**

| Endpoint | Method | Purpose |
|----------|--------|---------|
| calculate-price.php | POST | Server-side rental price calculation |
| check-signup-availability.php | POST | Real-time phone/email duplicate check |
| create-booking.php | POST | Submit new booking request |
| create-equipment.php | POST | Create new equipment listing |
| delete-equipment.php | POST | Delete owned equipment |
| edit-equipment.php | POST | Update equipment details |
| get-booked-slots.php | GET | Fetch existing bookings for calendar |
| get-notifications.php | GET | Fetch unread notifications |
| get-profile.php | GET | Fetch current user profile data |
| get_user_public_profile.php | GET | Fetch another user's public profile |
| get_user_reviews.php | GET | Fetch reviews for a user |
| initiate_booking.php | POST | Start booking with escrow flow |
| mark-notification-read.php | POST | Mark notification as read |
| pooling-cancel-pledge.php | POST | Cancel pledge from campaign |
| pooling-close.php | POST | Close a campaign (owner only) |
| pooling-create.php | POST | Create new pooling campaign |
| pooling-pledge.php | POST | Add pledge to campaign |
| raise_dispute.php | POST | Raise a booking dispute |
| submit-review.php | POST | Submit a review for completed booking |
| toggle-availability.php | POST | Toggle equipment available/unavailable |
| update-booking-status.php | POST | Update booking status (confirm/cancel/complete) |
| update-profile.php | POST | Update user profile details |
| validate-login-identifier.php | POST | Real-time login identifier validation |

**Admin API Endpoints (public/admin/api/):**

| Endpoint | Method | Purpose |
|----------|--------|---------|
| admin-booking-override.php | POST | Force-change booking status |
| admin-resolve-dispute.php | POST | Resolve disputed bookings |
| create-admin.php | POST | Create new admin accounts |
| toggle-featured-equipment.php | POST | Toggle equipment featured status |
| toggle-user-active.php | POST | Activate/deactivate user accounts |
| update-setting.php | POST | Update platform settings |
| verify-user.php | POST | Verify farmer identity |

### 3.1.3 Business Logic Layer

The business logic is organized into 5 Controller files and 3 Helper files:

**Controllers (src/Controllers/):**

1. **AdminController.php** (87 lines) — Administrative functions: dashboard statistics, user listing, equipment listing for admin, booking listing, settings retrieval, and audit log display.

2. **BookingController.php** (210 lines) — Complete booking lifecycle management: server-side price calculation, overlap conflict detection, automatic status promotion (confirmed→active→completed), booking retrieval for renters and owners, notification creation, and status update with state machine enforcement.

3. **EquipmentController.php** (467 lines) — Full CRUD for equipment listings: data validation, multi-image upload with MIME verification, equipment creation, update, deletion (with image cleanup), availability toggling, single record retrieval with owner details, and browse/filter with pagination.

4. **PoolingController.php** (276 lines) — Community pooling campaign management: campaign creation with validation, filtered listing, single campaign retrieval, pledge listing, user pledge lookup, atomic pledge addition with threshold checking, pledge cancellation, campaign closure, and deadline-based expiration.

5. **ReviewController.php** (75 lines) — Review submission and trust score management: booking ownership verification, duplicate review prevention, review insertion, and automated trust score recalculation using average ratings.

**Helpers (src/Helpers/):**

1. **auth.php** (189 lines) — Authentication guards (`requireAuth()`, `requireRole()`), session idle timeout enforcement, CSRF token generation and validation, flash message system (set, get, render), security headers (X-Frame-Options, X-Content-Type-Options), base path helper, and XSS-safe output escaping (`e()`).

2. **audit.php** (46 lines) — Audit logging helper that records security and business events to the `audit_logs` table. Designed to fail silently (never breaks application flow) with exception swallowing and error_log fallback.

3. **mail.php** (84 lines) — PHPMailer integration for sending transactional emails. Currently supports OTP dispatch for password recovery with a branded HTML template and plain-text fallback. Handles both Composer autoload and manual include paths.

## 3.2 Database Design

### 3.2.1 Entity-Relationship Summary

The AgroShare database consists of 11 tables organized in a relational structure with foreign key constraints and referential integrity. The database uses the InnoDB engine with `utf8mb4_unicode_ci` collation for full Unicode support.

```
users (1) ──── (N) equipment
users (1) ──── (N) bookings (as renter)
users (1) ──── (N) bookings (as owner)
equipment (1) ── (N) bookings
bookings (1) ─── (N) reviews
users (1) ──── (N) reviews (as reviewer)
users (1) ──── (N) reviews (as reviewee)
users (1) ──── (N) pooling_campaigns (as creator)
pooling_campaigns (1) ── (N) pooling_pledges
users (1) ──── (N) pooling_pledges (as farmer)
users (1) ──── (N) notifications
users (1) ──── (N) password_resets
```

### 3.2.2 Table Descriptions

#### Table 1: `users`
Core identity table for every farmer and admin on the platform.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Unique user identifier |
| full_name | VARCHAR(120) | NOT NULL | User's display name |
| phone | VARCHAR(15) | NOT NULL, UNIQUE | 10-digit Indian mobile number |
| email | VARCHAR(150) | UNIQUE, NULLABLE | Email address for OTP recovery |
| password_hash | VARCHAR(255) | NOT NULL | Argon2id hashed password |
| role | ENUM('farmer','admin') | DEFAULT 'farmer' | User role for access control |
| village | VARCHAR(100) | NOT NULL | User's village/city |
| district | VARCHAR(100) | NOT NULL | User's district |
| state | VARCHAR(80) | NOT NULL | User's state |
| profile_photo | VARCHAR(255) | NULLABLE | Relative path to uploaded photo |
| trust_score | DECIMAL(3,2) | DEFAULT 0.00 | Computed average from reviews (1.00-5.00) |
| is_verified | TINYINT(1) | DEFAULT 0 | Admin verification flag |
| is_active | TINYINT(1) | DEFAULT 1 | Account active/suspended flag |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |

#### Table 2: `equipment`
Equipment listings owned by farmers.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Unique equipment identifier |
| owner_id | INT UNSIGNED | FK → users(id), CASCADE | Equipment owner |
| title | VARCHAR(150) | NOT NULL | Equipment name (e.g., "Mahindra 475 DI Tractor") |
| category | ENUM(17 values) | NOT NULL | Equipment type classification |
| description | TEXT | NOT NULL | Detailed equipment description |
| price_per_day | DECIMAL(8,2) | NOT NULL | Daily rental rate in INR |
| safety_deposit | DECIMAL(10,2) | DEFAULT 0.00 | Refundable security deposit |
| includes_operator | TINYINT(1) | DEFAULT 0 | Whether operator is included |
| location_village | VARCHAR(100) | NOT NULL | Equipment location village |
| location_district | VARCHAR(100) | NOT NULL | Equipment location district |
| images | JSON | NULLABLE | JSON array of image file paths |
| condition | ENUM('excellent','good','fair') | DEFAULT 'good' | Equipment condition rating |
| is_available | TINYINT(1) | DEFAULT 1 | Current availability status |
| is_featured | TINYINT(1) | DEFAULT 0 | Admin-pinned to top of browse |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Listing creation time |

**Supported Categories:** tractor, harvester, seeder, sprayer, plough, chain_saw, rotavator, cultivator, thresher, water_pump, earth_auger, baler, trolley, brush_cutter, power_tiller, chaff_cutter, other

#### Table 3: `bookings`
Rental reservations linking a renter to a piece of equipment.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Unique booking identifier |
| equipment_id | INT UNSIGNED | FK → equipment(id), RESTRICT | Rented equipment |
| renter_id | INT UNSIGNED | FK → users(id), RESTRICT | Person renting |
| owner_id | INT UNSIGNED | FK → users(id), RESTRICT | Equipment owner (denormalized) |
| start_datetime | DATETIME | NOT NULL | Rental start date and time |
| end_datetime | DATETIME | NOT NULL | Rental end date and time |
| total_price | DECIMAL(10,2) | NOT NULL | Server-calculated total cost |
| deposit_amount | DECIMAL(10,2) | DEFAULT 0.00 | Safety deposit amount |
| status | ENUM(7 values) | DEFAULT 'pending' | Current booking status |
| admin_override | TINYINT(1) | DEFAULT 0 | Flag for admin-forced changes |
| admin_override_reason | VARCHAR(255) | NULLABLE | Reason for admin override |
| notes | TEXT | NULLABLE | Renter special requests |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Booking creation time |

**Booking Statuses:** pending → confirmed → active → completed, with possible transitions to cancelled, rejected, or disputed.

#### Table 4: `reviews`
Two-way reviews: renter rates owner AND owner rates renter.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Unique review identifier |
| booking_id | INT UNSIGNED | FK → bookings(id), CASCADE | Associated booking |
| reviewer_id | INT UNSIGNED | FK → users(id), CASCADE | Person giving the review |
| reviewee_id | INT UNSIGNED | FK → users(id), CASCADE | Person being reviewed |
| rating | DECIMAL(2,1) | CHECK (1-5) | Star rating (supports 0.5 increments) |
| comment | TEXT | NULLABLE | Written review comment |
| review_type | ENUM('renter_to_owner','owner_to_renter') | NOT NULL | Direction of the review |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Review submission time |

**Unique Constraint:** One review per reviewer per booking (`uk_reviews_booking_reviewer`).

#### Table 5: `pooling_campaigns`
Bulk-buy campaigns where farmers pool demand for cheaper inputs.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Campaign identifier |
| creator_id | INT UNSIGNED | FK → users(id), CASCADE | Campaign creator |
| title | VARCHAR(150) | NOT NULL | Campaign title |
| item_name | VARCHAR(150) | NOT NULL | Item being pooled |
| unit | VARCHAR(30) | NOT NULL | Measurement unit (e.g., "50kg bag") |
| price_per_unit_individual | DECIMAL(10,2) | NOT NULL | Retail market price |
| price_per_unit_bulk | DECIMAL(10,2) | NOT NULL | Bulk discount price |
| minimum_quantity | INT UNSIGNED | NOT NULL | Threshold to unlock bulk deal |
| current_quantity | INT UNSIGNED | DEFAULT 0 | Current aggregate pledges |
| deadline | DATE | NOT NULL | Campaign deadline |
| status | ENUM(4 values) | DEFAULT 'open' | Campaign status |
| district | VARCHAR(100) | NOT NULL | Target district |
| description | TEXT | NOT NULL | Campaign details |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation time |

**Check Constraint:** `price_per_unit_bulk < price_per_unit_individual` (bulk must be cheaper).

#### Table 6: `pooling_pledges`
Individual farmer pledges toward bulk-buy campaigns.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Pledge identifier |
| campaign_id | INT UNSIGNED | FK → pooling_campaigns(id), CASCADE | Target campaign |
| farmer_id | INT UNSIGNED | FK → users(id), CASCADE | Pledging farmer |
| quantity_pledged | INT UNSIGNED | NOT NULL | Quantity committed |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Pledge time |

**Unique Constraint:** One pledge per farmer per campaign (`uk_pledges_campaign_farmer`).

#### Table 7: `password_resets`
OTP tokens for the password recovery flow.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| user_id | INT UNSIGNED | FK → users(id) |
| phone | VARCHAR(15) | Stored for quick lookup |
| otp | CHAR(6) | 6-digit numeric OTP |
| expires_at | DATETIME | NOW() + 15 minutes |
| is_used | TINYINT(1) | Prevents OTP reuse |
| created_at | TIMESTAMP | Creation time |

#### Table 8: `notifications`
Simple in-app notification system for booking and pledge events.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| user_id | INT UNSIGNED | FK → users(id) |
| message | TEXT | Notification content |
| is_read | TINYINT(1) | Read/unread status |
| created_at | TIMESTAMP | Creation time |

#### Table 9: `settings`
Global platform settings (key-value store).

| Column | Type | Description |
|--------|------|-------------|
| setting_key | VARCHAR(50) | PK — Setting identifier |
| setting_value | TEXT | Setting value |
| updated_at | TIMESTAMP | Last modification time |

#### Table 10: `audit_logs`
Lightweight audit logging for authentication failures and critical events.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| admin_id | INT UNSIGNED | Nullable — admin performing action |
| action_type | VARCHAR(50) | Event type (e.g., 'login_failed') |
| target_id | INT UNSIGNED | Nullable — affected entity ID |
| description | TEXT | Detailed event description |
| created_at | TIMESTAMP | Event timestamp |

### 3.2.3 Database Indexes

The schema includes carefully chosen composite indexes for query performance:

| Table | Index Name | Columns | Purpose |
|-------|-----------|---------|---------|
| equipment | idx_equipment_browse | is_available, location_district, category | Optimize browse page filtering |
| bookings | idx_bookings_conflict | equipment_id, status, start_datetime, end_datetime | Fast conflict overlap detection |
| bookings | idx_bookings_renter | renter_id, status | Renter's booking list queries |
| bookings | idx_bookings_owner | owner_id, status | Owner's booking list queries |
| pooling_campaigns | idx_campaigns_browse | status, district, deadline | Campaign browse filtering |
| password_resets | idx_resets_lookup | phone, is_used, expires_at | OTP verification lookup |
| notifications | idx_notifications_user | user_id, is_read, created_at | Notification retrieval |

## 3.3 Data Flow Diagram

### Level 0 — Context Diagram

```
                    ┌─────────┐
  Registration ───→ │         │ ←── Equipment Listing
  Login ──────────→ │         │ ←── Booking Request
  Browse Request ──→│AgroShare│ ←── Review Submission
  Pooling Pledge ──→│ System  │ ──→ Booking Confirmation
  Profile Update ──→│         │ ──→ Notification
                    │         │ ──→ OTP Email
    Admin Actions ──→│         │ ──→ Audit Log Entry
                    └─────────┘
```

### Level 1 — Major Processes

```
[Farmer] → (1.0 Authentication) → [Session/DB]
[Farmer] → (2.0 Equipment Management) → [Equipment Table]
[Farmer] → (3.0 Booking Management) → [Bookings Table]
[Farmer] → (4.0 Review System) → [Reviews Table] → [Users.trust_score]
[Farmer] → (5.0 Community Pooling) → [Campaigns/Pledges]
[Admin]  → (6.0 Admin Management) → [All Tables]
[System] → (7.0 Notification Engine) → [Notifications Table]
```

## 3.4 User Interface Design Principles

AgroShare follows a premium dark-themed design system built entirely with CSS custom properties (CSS Variables). The design language is inspired by modern SaaS dashboard aesthetics with the following design tokens:

```css
--bg-color:         hsl(144, 28%, 6%)     /* Deep dark green background */
--surface-color:    hsl(150, 24%, 10%)    /* Card/panel surface */
--text-main:        hsl(90, 20%, 90%)     /* Primary text */
--text-muted:       hsl(140, 14%, 60%)    /* Secondary text */
--text-subtle:      hsl(150, 12%, 38%)    /* Tertiary/hint text */
--border-color:     hsl(150, 20%, 16%)    /* Border color */
--primary-action:   hsl(150, 50%, 45%)    /* Primary green */
--secondary-action: hsl(171, 35%, 55%)    /* Teal accent */
--accent-dark:      hsl(150, 50%, 30%)    /* Dark green accent */
--danger:           #E11D48               /* Error/danger red */
--radius:           18px                  /* Large border radius */
--radius-sm:        12px                  /* Small border radius */
--font:             'Inter', system-ui    /* Typography */
```

The design features:
- **Glassmorphism effects** on buttons and panels
- **Micro-animations** on hover (translateY, scale, shimmer effects)
- **Responsive grid layouts** that collapse gracefully on mobile
- **Consistent component styling** across all pages (inputs, buttons, cards, modals)
- **Accessible color contrast** ratios meeting WCAG 2.1 guidelines
- **Light/Dark theme toggle** with localStorage persistence

---

# Chapter 4: SYSTEM DEVELOPMENT

## 4.1 Development Methodology

AgroShare was developed using an iterative, module-by-module development approach. Each module was built end-to-end (database schema → controller logic → API endpoint → frontend page) before moving to the next module. This allowed for continuous testing and validation at each stage.

**Development Order:**
1. Database schema design and creation
2. Configuration and core helpers (db.php, auth.php)
3. User authentication (login, signup, password reset)
4. Equipment management module
5. Equipment browse and detail pages
6. Booking lifecycle system
7. Review and trust score module
8. Community pooling module
9. Admin panel and management tools
10. Notification system
11. Safety deposit integration
12. UI polish and responsive design

## 4.2 Project Directory Structure

```
Project V1.2.1/
├── config/                          # Configuration files
│   ├── constants.php                # DB credentials, app settings, SMTP config
│   ├── db.php                       # Database connection, session init, auto-loads helpers
│   ├── local.secrets.php            # Git-ignored local overrides
│   └── local.secrets.example.php    # Template for local secrets
│
├── src/                             # Backend business logic
│   ├── Controllers/                 # Module-specific logic
│   │   ├── AdminController.php      # Admin dashboard stats, user/equipment/booking lists
│   │   ├── BookingController.php    # Booking lifecycle, pricing, conflicts, notifications
│   │   ├── EquipmentController.php  # CRUD, image upload, browse/filter, validation
│   │   ├── PoolingController.php    # Campaign CRUD, pledges, threshold, expiry
│   │   └── ReviewController.php     # Review submission, trust score recalculation
│   └── Helpers/                     # Cross-cutting utility functions
│       ├── auth.php                 # Auth guards, CSRF, flash messages, XSS sanitization
│       ├── audit.php                # Audit event logging (fail-safe)
│       └── mail.php                 # PHPMailer setup for transactional emails
│
├── public/                          # Web-accessible files (Apache DocumentRoot)
│   ├── .htaccess                    # Security: disable directory listing, block sensitive files
│   ├── login.php                    # Combined login/signup with sliding panel
│   ├── signup.php                   # Standalone registration
│   ├── dashboard.php                # User dashboard with KPIs and quick actions
│   ├── equipment-browse.php         # Equipment marketplace with filters
│   ├── equipment-detail.php         # Product detail page with booking widget
│   ├── my-equipment-detail.php      # Owner's view of their equipment
│   ├── my-bookings.php              # Booking management (renter + owner tabs)
│   ├── pooling-browse.php           # Community pooling campaigns browser
│   ├── pooling-detail.php           # Campaign detail with pledge interface
│   ├── forgot-password.php          # Email-based password recovery
│   ├── verify-otp.php               # OTP verification step
│   ├── reset-password.php           # New password entry
│   ├── logout.php                   # Session destruction and redirect
│   │
│   ├── api/                         # AJAX endpoints (23 files)
│   │   ├── calculate-price.php      # Server-side price calculation
│   │   ├── check-signup-availability.php  # Real-time duplicate checks
│   │   ├── create-booking.php       # Booking creation with conflict check
│   │   ├── create-equipment.php     # Equipment listing creation
│   │   ├── delete-equipment.php     # Equipment deletion
│   │   ├── edit-equipment.php       # Equipment updates
│   │   ├── get-booked-slots.php     # Calendar availability data
│   │   ├── get-notifications.php    # Notification fetch
│   │   ├── get-profile.php          # User profile data
│   │   ├── get_user_public_profile.php  # Public profile view
│   │   ├── get_user_reviews.php     # Review listing
│   │   ├── initiate_booking.php     # Booking initiation with escrow
│   │   ├── mark-notification-read.php   # Notification read status
│   │   ├── pooling-cancel-pledge.php    # Pledge cancellation
│   │   ├── pooling-close.php        # Campaign closure
│   │   ├── pooling-create.php       # Campaign creation
│   │   ├── pooling-pledge.php       # Pledge submission
│   │   ├── raise_dispute.php        # Dispute raising
│   │   ├── submit-review.php        # Review submission
│   │   ├── toggle-availability.php  # Equipment availability toggle
│   │   ├── update-booking-status.php    # Status transitions
│   │   ├── update-profile.php       # Profile updates
│   │   └── validate-login-identifier.php  # Login field validation
│   │
│   ├── admin/                       # Admin panel pages
│   │   ├── dashboard.php            # Admin overview with statistics
│   │   ├── users.php                # User management (verify, suspend, create admin)
│   │   ├── equipment.php            # Equipment moderation (feature/unfeature)
│   │   ├── bookings.php             # Booking oversight (override, resolve disputes)
│   │   ├── logs.php                 # Audit log viewer
│   │   ├── settings.php             # Platform settings management
│   │   └── api/                     # Admin-specific API endpoints (7 files)
│   │
│   ├── includes/                    # Reusable modal templates
│   │   ├── booking-detail-modal.php # Booking detail popup
│   │   ├── profile-modal.php        # Profile edit modal
│   │   ├── user-public-profile-modal.php  # Public profile view modal
│   │   └── viewer-reviews-modal.php # Reviews listing modal
│   │
│   ├── assets/                      # Static frontend assets
│   │   ├── css/
│   │   │   ├── dashboard.css        # Dashboard and common layout styles (64KB)
│   │   │   └── equipment.css        # Equipment pages styling (58KB)
│   │   ├── js/
│   │   │   ├── dashboard.js         # Dashboard logic, notifications, profile, charts (28KB)
│   │   │   ├── equipment.js         # Equipment CRUD, image upload, inline editing (16KB)
│   │   │   ├── calendar.js          # Booking calendar widget (24KB)
│   │   │   └── reviews.js           # Review modal and submission logic (16KB)
│   │   └── img/                     # Static images and icons
│   │
│   └── uploads/                     # User-uploaded equipment images
│       └── equipment/               # Auto-created by EquipmentController
│
├── sql/                             # Database scripts
│   ├── agroshare_schema.sql         # Complete schema (11 tables, 288 lines)
│   └── migrations/                  # Incremental schema updates
│       ├── add_audit_logs.sql       # Audit logs table creation
│       ├── add_settings_table.sql   # Settings table creation
│       ├── 2026_update_password_resets.sql  # Password reset schema update
│       ├── 2026_module6_pivot.sql   # Pooling module schema adjustments
│       ├── 2026_module6_add_min_contribution.sql  # Min contribution column
│       ├── 2026_module13_safety_deposit.sql  # Safety deposit column
│       └── 2026_module13_cleanup_and_safety_deposit.sql  # Deposit cleanup
│
├── vendor/                          # Composer dependencies (PHPMailer)
├── composer.json                    # Composer configuration
├── composer.lock                    # Dependency lock file
└── .gitignore                       # Git exclusions
```

## 4.3 Module-Wise Development Details

### 4.3.1 Authentication Module

The authentication module handles user registration, login, and password recovery. It is the foundation upon which all other modules depend.

**Registration Flow:**
1. User fills out the signup form with full name, phone, email, password, city, and state
2. Client-side JavaScript performs real-time availability checks on phone and email via AJAX calls to `api/check-signup-availability.php`
3. A password strength meter provides visual feedback (Too short → Weak → Fair → Good → Strong)
4. On form submission, the server validates all fields, checks for duplicates, and hashes the password using `password_hash($password, PASSWORD_ARGON2ID)`
5. The user record is inserted into the `users` table with `role = 'farmer'`
6. A flash message confirms success and redirects to the login page

**Login Flow:**
1. User enters phone number or email and password
2. The system queries the `users` table matching both phone and email fields
3. Password is verified using `password_verify()` against the stored Argon2id hash
4. If the account is deactivated (`is_active = 0`), login is denied with an audit log entry
5. On successful login, `session_regenerate_id(true)` prevents session fixation attacks
6. Session variables are set: `user_id`, `role`, `full_name`, `persist`, `last_activity`
7. If "Remember Me" is unchecked, a `sessionStorage` flag ensures the session dies when the browser tab closes
8. Admin users are redirected to `admin/dashboard.php`; farmers to `dashboard.php`
9. Failed attempts are logged via `logAuditEvent()` with masked identifiers and IP addresses

**Password Recovery Flow:**
1. User enters their registered email on `forgot-password.php`
2. Rate limiting prevents more than 3 OTP requests per 15 minutes per email
3. If the email exists, all previous unused OTPs are invalidated
4. A 6-digit OTP is generated using `random_int(0, 999999)` and stored in `password_resets` with a 15-minute expiry
5. PHPMailer sends the OTP via Gmail SMTP with a branded HTML email template
6. The user enters the OTP on `verify-otp.php` which validates against the database
7. On success, `reset-password.php` allows the user to set a new password
8. The response message is deliberately generic ("If this email is registered...") to prevent email enumeration

### 4.3.2 Equipment Management Module

This module handles the complete lifecycle of equipment listings from creation to deletion.

**Equipment Creation:**
1. The owner fills out a form with title, category, condition, description, price per day, safety deposit, operator inclusion flag, village, district, and up to 5 images
2. Server-side validation in `validateEquipmentData()` checks all field constraints
3. Image uploads are processed by `processImageUploads()` which validates MIME types using `finfo` (not file extensions), enforces size limits (10MB max), and generates unique filenames using `uniqid('eq_', true)`
4. Images are stored in `public/uploads/equipment/` and paths are stored as a JSON array in the `images` column
5. The equipment record is inserted via a prepared statement with 11 bound parameters

**Equipment Browse and Filter:**
The `browseEquipment()` function in `EquipmentController.php` supports dynamic query building with the following filters:
- Category (exact match)
- District (LIKE partial match)
- Maximum price per day (less than or equal)
- Operator availability (boolean flag)
- Availability status (default: available only)
- Owner filter (for "My Equipment" view)

Results are paginated (12 items per page by default) with featured equipment pinned to the top via `ORDER BY e.is_featured DESC, e.created_at DESC`.

**Equipment Update and Delete:**
- Updates use ownership verification (`WHERE id = ? AND owner_id = ?`)
- Deletion first fetches image paths, deletes files from disk using path traversal protection (`str_starts_with` check), then removes the database record
- Availability toggling uses an atomic SQL toggle: `SET is_available = NOT is_available`

### 4.3.3 Booking Lifecycle Module

The booking system implements a complete state machine with the following transitions:

```
  pending ──→ confirmed ──→ active ──→ completed
    │            │                        
    │            │                        
    ↓            ↓                        
  cancelled   cancelled                   
                                          
  Any status can transition to → disputed (via raise_dispute)
```

**Booking Creation:**
1. The renter selects start and end dates/times from the calendar widget
2. Client-side price calculation shows an estimate
3. On submission, `calculateServerSidePrice()` recalculates the price server-side to prevent manipulation
4. `hasBookingConflict()` checks for overlapping bookings using the overlap algorithm: `start_datetime < end AND end_datetime > start`
5. The booking is created with status `pending` and a notification is sent to the equipment owner

**Auto-Promotion:**
`autoPromoteBookings()` runs automatically when fetching bookings and handles two transitions:
- `confirmed → active`: When the current time is between `start_datetime` and `end_datetime`
- `active → completed`: When the current time is past `end_datetime`

**Status Updates:**
`updateBookingStatus()` enforces role-based permissions and valid state transitions:
- **Confirm:** Only owner can confirm; only from `pending` status
- **Cancel:** Both owner and renter can cancel; only from `pending` or `confirmed`
- **Complete:** Both owner and renter can complete; only from `confirmed` or `active`

All status changes are wrapped in database transactions with notifications sent to the affected party.

### 4.3.4 Review and Trust Score Module

**Review Submission:**
1. After a booking is completed, both the renter and owner can submit a review
2. `ReviewController::submitReview()` verifies: the booking exists and is completed, the reviewer is a party to the booking, and no duplicate review exists
3. The review type is automatically determined (renter_to_owner or owner_to_renter)
4. After insertion, `recalculateTrustScore()` computes the new average: `AVG(rating)` across all reviews where the user is the reviewee
5. The `trust_score` column in the `users` table is updated with the rounded result

### 4.3.5 Community Pooling Module

**Campaign Creation:**
1. A farmer creates a campaign specifying the item, unit, offering price, target quantity, minimum contribution, deadline, district, and description
2. Validation ensures all fields are valid and the deadline is in the future
3. The campaign starts with `status = 'open'` and `current_quantity = 0`

**Pledge Management (Atomic Operations):**
The `addPledge()` function uses database transactions with `SELECT ... FOR UPDATE` row-level locking:
1. Lock the campaign row to prevent concurrent modification
2. Verify the campaign is open and the deadline hasn't passed
3. Check the pledge meets the minimum contribution requirement
4. Insert the pledge (catch duplicate key error for re-pledging)
5. Atomically increment `current_quantity`
6. Check if the threshold is met; if so, update status to 'threshold_met'
7. Notify all pledgers when threshold is reached
8. Commit or rollback the entire transaction

**Campaign Lifecycle:**
- `closeCampaign()`: Only the creator can close their campaign (open → closed)
- `expireDeadlines()`: Automatically cancels campaigns past their deadline (open → cancelled)

### 4.3.6 Admin Panel Module

The admin panel provides six main pages:

1. **Dashboard** (`admin/dashboard.php`): Displays platform-wide statistics (user count, equipment count, booking count) and the 5 most recent audit log entries.

2. **User Management** (`admin/users.php`): Lists all farmer accounts with the ability to verify users, activate/deactivate accounts, and create new admin accounts.

3. **Equipment Management** (`admin/equipment.php`): Lists all equipment with owner names, allowing admins to toggle the featured status of listings.

4. **Booking Management** (`admin/bookings.php`): Lists all bookings with the ability to override booking statuses (with mandatory reason) and resolve disputes.

5. **Audit Logs** (`admin/logs.php`): Displays the 100 most recent audit events in reverse chronological order.

6. **Settings** (`admin/settings.php`): Key-value settings management for platform-wide configuration.

### 4.3.7 Notification System

Notifications are created as database records in the `notifications` table whenever significant events occur:
- Booking confirmed, cancelled, or completed
- Pooling campaign threshold met

The frontend polls for notifications via `api/get-notifications.php` and displays them in a dropdown panel. Users can mark individual notifications as read via `api/mark-notification-read.php`.

### 4.3.8 Security Implementation

AgroShare implements a defense-in-depth security strategy:

1. **Password Security:** Argon2id hashing (the strongest available in PHP) with automatic salt generation
2. **SQL Injection Prevention:** 100% prepared statements using MySQLi with bound parameters — zero string interpolation in SQL queries
3. **CSRF Protection:** Every form includes a session-based CSRF token generated with `bin2hex(random_bytes(32))`, validated using constant-time `hash_equals()`
4. **XSS Prevention:** All user-generated output is sanitized through the `e()` helper function using `htmlspecialchars(ENT_QUOTES, 'UTF-8')`
5. **Session Security:** HTTP-only cookies, SameSite=Strict, strict mode, session regeneration on login, 1-hour idle timeout
6. **File Upload Security:** MIME type validation using `finfo` (not file extensions), `getimagesize()` verification, 10MB size limit, unique filenames, path traversal protection
7. **Rate Limiting:** Password reset capped at 3 requests per 15 minutes per email
8. **Security Headers:** X-Frame-Options: DENY, X-Content-Type-Options: nosniff
9. **Account Security:** Deactivated accounts cannot login, audit logging for failed attempts
10. **Apache Hardening:** `.htaccess` disables directory listing, blocks direct access to dotfiles and sensitive file extensions (.env, .sql, .log, .md, .json)

## 4.4 External Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| PHPMailer | ^7.0 | SMTP email delivery for OTP dispatch |

Managed via Composer (`composer.json`). The mail helper also supports manual include paths as a fallback if Composer autoload is not available.

---

# Chapter 5: SYSTEM IMPLEMENTATION

## 5.1 Installation and Setup Procedure

### Step 1: Prerequisites
1. Install WAMP Server 3.3+ (includes Apache, MySQL, PHP 8.x)
2. Install Composer 2.x globally
3. Ensure PHP has the following extensions enabled: `mysqli`, `mbstring`, `fileinfo`, `openssl`

### Step 2: Project Setup
1. Clone or extract the project to `C:\wamp64\www\agroshare3\`
2. Open a terminal in the project root and run:
   ```
   composer install
   ```
   This installs PHPMailer into the `vendor/` directory.

### Step 3: Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `agroshare` with collation `utf8mb4_unicode_ci`
3. Import `sql/agroshare_schema.sql` to create all 11 tables
4. Optionally run migration scripts from `sql/migrations/` for incremental updates

### Step 4: Configuration
1. Copy `config/local.secrets.example.php` to `config/local.secrets.php`
2. Update the following constants:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` for your MySQL setup
   - `SMTP_USER`, `SMTP_PASS` for Gmail App Password (for OTP emails)
   - `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` for sender identity

### Step 5: Email Configuration (Gmail)
1. Enable 2-Step Verification on your Google Account
2. Generate an App Password (Google Account → Security → App Passwords)
3. Use this 16-character App Password as `SMTP_PASS`

### Step 6: Access the Application
1. Start WAMP Server (all services green)
2. Navigate to `http://localhost/agroshare3/public/login.php`
3. Register a new farmer account or use the default test credentials

## 5.2 User Roles and Access Control

| Role | Access Level | Pages Accessible |
|------|-------------|-----------------|
| Guest (Unauthenticated) | Public only | login.php, signup.php, forgot-password.php, verify-otp.php, reset-password.php |
| Farmer | Full user features | dashboard.php, equipment-*.php, my-bookings.php, pooling-*.php, all user APIs |
| Admin | Full platform management | All farmer pages + admin/dashboard.php, admin/users.php, admin/equipment.php, admin/bookings.php, admin/logs.php, admin/settings.php, all admin APIs |

Access control is enforced at two levels:
1. `requireAuth()` — Redirects unauthenticated users to login
2. `requireRole('admin')` — Restricts admin pages to admin-role sessions

## 5.3 Key Code Highlights

### 5.3.1 Argon2id Password Hashing
```php
$password_hash = password_hash($password, PASSWORD_ARGON2ID);
```
Argon2id is a memory-hard hashing algorithm that is resistant to both GPU-based attacks and side-channel attacks. It automatically generates a unique salt for each hash.

### 5.3.2 Booking Conflict Detection Algorithm
```php
$sql = "SELECT id FROM bookings 
        WHERE equipment_id = ? 
        AND status IN ('pending', 'confirmed', 'active') 
        AND start_datetime < ? 
        AND end_datetime > ? 
        LIMIT 1";
$stmt->bind_param('iss', $equipmentId, $end, $start);
```
This uses the standard overlap detection formula: two intervals (S1,E1) and (S2,E2) overlap if and only if S1 < E2 AND S2 < E1.

### 5.3.3 Atomic Pooling Pledge with Row Locking
```php
$conn->begin_transaction();
$stmt = $conn->prepare("SELECT ... FROM pooling_campaigns WHERE id = ? FOR UPDATE");
// ... validate, insert pledge, update quantity, check threshold ...
$conn->commit();
```
The `FOR UPDATE` lock prevents race conditions when multiple farmers pledge simultaneously.

### 5.3.4 CSRF Token Generation
```php
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
```
Uses cryptographically secure random bytes, validated with constant-time `hash_equals()`.

---

# Chapter 6: SCREENSHOTS

> **Note:** The following section describes the key screens of the AgroShare application. In a printed document, actual screenshots would be inserted here. The descriptions below detail what each screen contains.

## 6.1 Authentication Screens

### Screen 1: Login Page
The login page features a split-panel design with a sliding animation. The left panel contains the login form (phone/email input, password with visibility toggle, remember me checkbox, forgot password link). The right panel displays the AgroShare brand with a gradient green background, tagline, and feature badges. When toggling to signup mode, the green panel slides to the left with a smooth 0.6s CSS transition, revealing the signup form underneath.

### Screen 2: Signup Page
A dedicated registration page with a two-column layout. The left column shows a branded panel with a three-step progress indicator (Visit → Create Account → List Equipment → Join Pooling). The right column contains the registration form with 7 fields arranged in a grid: full name, phone number (with real-time AJAX availability check), email (with real-time AJAX availability check), password (with strength meter), confirm password, city/village, and state.

### Screen 3: Forgot Password Page
A single-column centered card with an email input field and "Send OTP" button. Includes a "Back to Login" navigation chip with a left-arrow icon.

### Screen 4: OTP Verification Page
A centered card with a 6-digit OTP input field. Shows a visual timer for OTP expiry and a resend link.

### Screen 5: Reset Password Page
A centered card with new password and confirm password fields, each with visibility toggles and a password strength meter.

## 6.2 Dashboard Screen

### Screen 6: User Dashboard
The dashboard uses a sidebar + topbar layout. The sidebar contains navigation links grouped into Main (Dashboard, My Equipment, My Bookings), Community (Pooling, Browse), and Account (Profile, Logout) sections. The topbar shows a personalized greeting ("Good Morning, Vasanth"), search bar, theme toggle (light/dark), notification bell with dropdown, and user avatar with initials.

The main content area displays:
- **Row 1 — KPI Cards (4 columns):** Total Equipment, Active Rentals, Pool Campaigns, Trust Score. Each card has a label, large numeric value, trend indicator, and decorative SVG icon.
- **Row 2 — Two-column bento grid:** Recent Activity table (Equipment, Type, Date, Status columns) and Rental Activity chart (7-month area chart with booking and earnings data).
- **Row 3 — Quick Actions (3 columns):** Browse Equipment, Join a Pool (disabled), Edit Profile. Each card has a colored icon, title, description, and arrow indicator.

## 6.3 Equipment Screens

### Screen 7: Equipment Browse Page
A grid of equipment cards with a filter sidebar. Each card shows the equipment image, title, category badge, condition badge, price per day, location, owner name with trust score, and an availability indicator. The filter panel includes category dropdown, district search, max price slider, and operator toggle.

### Screen 8: Equipment Detail Page
A three-column layout with an image gallery (supporting multiple images with thumbnails), equipment details panel (title, category, condition, description, includes operator badge, safety deposit badge), and a booking widget (date picker, price calculator, and "Book Now" button). Below the main content: owner profile card with trust score and verified badge.

### Screen 9: Equipment Creation Modal
A full-screen modal with sectioned form: Equipment Details (title, category, condition, description), Pricing (price per day, safety deposit, includes operator checkbox), Location (village, district), and Photos (drag-and-drop zone with preview grid, supporting up to 5 images in JPEG, PNG, or WebP format).

## 6.4 Booking Screens

### Screen 10: My Bookings Page
A tabbed interface with two main views:
- **My Rentals:** Equipment the user has rented from others. Each booking card shows equipment image, title, dates, total price, status badge, and action buttons (Cancel, Complete, Review).
- **Requests for My Equipment:** Bookings from other farmers. Each card shows renter details with trust score, equipment title, dates, and action buttons (Accept, Reject, Complete).

A statistics grid at the top shows counts for each status (Pending, Active, Completed, Cancelled).

## 6.5 Community Pooling Screens

### Screen 11: Pooling Browse Page
A grid of campaign cards. Each card displays the campaign title, item name, unit, savings percentage, progress bar (current quantity vs. target), deadline countdown, district, creator name, and status badge (Open, Threshold Met, Closed, Cancelled).

### Screen 12: Pooling Detail Page
A detailed view of a single campaign with: savings calculator (individual vs. bulk price comparison), progress tracker (visual bar with percentage), pledge form (quantity input with minimum contribution validation), list of existing pledgers (names and quantities), and campaign management actions (Close Campaign for creators, Cancel Pledge for pledgers).

## 6.6 Admin Screens

### Screen 13: Admin Dashboard
Platform overview with statistics cards (Total Users, Total Equipment, Total Bookings) and a recent audit logs table showing action type, target, description, and timestamp.

### Screen 14: Admin User Management
A table listing all farmer accounts with columns for ID, name, phone, email, village, district, trust score, verified status, active status, and action buttons (Verify, Activate/Deactivate).

### Screen 15: Admin Booking Management
A table of all bookings with columns for ID, equipment title, renter name, owner name, dates, total price, status, and action buttons (Override Status, Resolve Dispute).

### Screen 16: Admin Settings
A key-value settings editor where administrators can add, update, and manage platform-wide configuration parameters.

---

# Chapter 7: TESTING

## 7.1 Testing Strategy

AgroShare was tested using a combination of manual functional testing, boundary value analysis, and security-focused testing. Each module was tested independently and then integrated for end-to-end flow verification.

## 7.2 Test Cases

### 7.2.1 Authentication Module Tests

| Test ID | Test Case | Input | Expected Output | Status |
|---------|-----------|-------|----------------|--------|
| TC-01 | Valid registration | Full name, valid phone (10 digits starting with 6-9), valid email, password (8+ chars with number), city, state | Account created, redirect to login | Pass |
| TC-02 | Duplicate phone registration | Phone number already in database | Error: "This phone number is already registered" | Pass |
| TC-03 | Duplicate email registration | Email already in database | Error: "This email address is already registered" | Pass |
| TC-04 | Weak password | Password "12345" (less than 8 chars) | Error: "Min 8 characters required" | Pass |
| TC-05 | Password without number | Password "abcdefgh" | Error: "Must contain at least one number" | Pass |
| TC-06 | Password mismatch | Password ≠ Confirm password | Error: "Passwords do not match" | Pass |
| TC-07 | Valid login with phone | Registered phone + correct password | Session created, redirect to dashboard | Pass |
| TC-08 | Valid login with email | Registered email + correct password | Session created, redirect to dashboard | Pass |
| TC-09 | Invalid credentials | Wrong password | Error: "Invalid credentials" | Pass |
| TC-10 | Deactivated account login | Correct credentials but is_active = 0 | Error: "Account has been deactivated" | Pass |
| TC-11 | CSRF token missing | POST without csrf_token | Error: "Invalid form submission" | Pass |
| TC-12 | Session timeout | No activity for 1+ hour | Session destroyed, redirect to login | Pass |
| TC-13 | Admin redirect | Admin account login | Redirect to admin/dashboard.php | Pass |
| TC-14 | OTP rate limiting | 4th OTP request within 15 mins | Error: "Too many requests" | Pass |
| TC-15 | Valid OTP verification | Correct OTP within 15 minutes | Reset verified, redirect to reset-password.php | Pass |
| TC-16 | Expired OTP | OTP entered after 15 minutes | Error: "OTP expired" | Pass |

### 7.2.2 Equipment Module Tests

| Test ID | Test Case | Input | Expected Output | Status |
|---------|-----------|-------|----------------|--------|
| TC-17 | Valid equipment creation | All required fields + 2 images | Equipment created, success response | Pass |
| TC-18 | Missing equipment title | Empty title field | Error: "Equipment title is required" | Pass |
| TC-19 | Invalid category | category = "invalid_value" | Error: "Please select a valid category" | Pass |
| TC-20 | Negative price | price_per_day = -100 | Error: "Enter a valid daily price" | Pass |
| TC-21 | Image MIME validation | Upload a .txt file renamed to .jpg | Error: "Not a valid image" | Pass |
| TC-22 | Oversized image | Image > 10MB | Error: "Exceeds size limit" | Pass |
| TC-23 | More than 5 images | Upload 6 images | Error: "Maximum of 5 images" | Pass |
| TC-24 | Equipment update | Modified title and price | Equipment updated successfully | Pass |
| TC-25 | Unauthorized update | User B tries to update User A's equipment | Update fails (owner_id check) | Pass |
| TC-26 | Equipment deletion | Owner deletes their equipment | Record removed, images deleted from disk | Pass |
| TC-27 | Availability toggle | Toggle available equipment | is_available flipped, new state returned | Pass |
| TC-28 | Browse with filters | Category = "tractor", District = "Dharwad" | Only matching results shown | Pass |
| TC-29 | Browse pagination | More than 12 results | Paginated with page navigation | Pass |

### 7.2.3 Booking Module Tests

| Test ID | Test Case | Input | Expected Output | Status |
|---------|-----------|-------|----------------|--------|
| TC-30 | Valid booking creation | Valid equipment ID, future dates, no conflicts | Booking created with status "pending" | Pass |
| TC-31 | Double booking conflict | Overlapping dates with existing booking | Error: "Booking conflict detected" | Pass |
| TC-32 | Self-booking prevention | Owner tries to book own equipment | Error or blocked at UI level | Pass |
| TC-33 | Owner confirms booking | Owner clicks Accept on pending booking | Status → confirmed, renter notified | Pass |
| TC-34 | Owner rejects booking | Owner clicks Reject on pending booking | Status → cancelled, renter notified | Pass |
| TC-35 | Auto-promote to active | Current time within booking window | Status auto-updated to active | Pass |
| TC-36 | Auto-promote to completed | Current time past end_datetime | Status auto-updated to completed | Pass |
| TC-37 | Cancel confirmed booking | Either party cancels confirmed booking | Status → cancelled, other party notified | Pass |
| TC-38 | Invalid state transition | Try to confirm a completed booking | Returns false, no change | Pass |
| TC-39 | Server-side price validation | Client sends modified price | Server recalculates independently | Pass |

### 7.2.4 Review Module Tests

| Test ID | Test Case | Input | Expected Output | Status |
|---------|-----------|-------|----------------|--------|
| TC-40 | Valid review submission | Completed booking, 4-star rating, comment | Review created, trust score updated | Pass |
| TC-41 | Review non-completed booking | Booking with status "pending" | Error: "Booking not yet completed" | Pass |
| TC-42 | Duplicate review | Same reviewer for same booking | Error: "Review already submitted" | Pass |
| TC-43 | Unauthorized reviewer | Non-party user tries to review | Error: "Unauthorized" | Pass |
| TC-44 | Trust score calculation | 3 reviews with ratings 4, 5, 3 | trust_score = 4.00 | Pass |

### 7.2.5 Pooling Module Tests

| Test ID | Test Case | Input | Expected Output | Status |
|---------|-----------|-------|----------------|--------|
| TC-45 | Valid campaign creation | All fields, future deadline | Campaign created with status "open" | Pass |
| TC-46 | Past deadline campaign | Deadline in the past | Error: "Deadline must be a future date" | Pass |
| TC-47 | Valid pledge | Open campaign, quantity >= min_contribution | Pledge created, current_quantity updated | Pass |
| TC-48 | Pledge below minimum | quantity < min_contribution | Error: "Minimum contribution is X" | Pass |
| TC-49 | Duplicate pledge | Same farmer pledges twice | Error: "Already pledged" | Pass |
| TC-50 | Threshold met notification | Aggregate pledges reach target | Status → threshold_met, all pledgers notified | Pass |
| TC-51 | Pledge cancellation | Farmer cancels their pledge | Pledge removed, current_quantity decreased | Pass |
| TC-52 | Campaign closure by owner | Creator closes own campaign | Status → closed | Pass |
| TC-53 | Unauthorized campaign closure | Non-creator tries to close | Error: "Unauthorized" | Pass |
| TC-54 | Deadline expiry | Open campaign past deadline | Status → cancelled (auto-expired) | Pass |

### 7.2.6 Security Tests

| Test ID | Test Case | Input | Expected Output | Status |
|---------|-----------|-------|----------------|--------|
| TC-55 | SQL injection attempt | phone = "' OR '1'='1" | Prepared statement blocks injection | Pass |
| TC-56 | XSS in equipment title | title = "<script>alert(1)</script>" | Escaped to safe HTML entities | Pass |
| TC-57 | CSRF token replay | Reuse old CSRF token | Rejected if session token changed | Pass |
| TC-58 | Direct API access without auth | Call api/create-booking.php without session | Redirected to login | Pass |
| TC-59 | Farmer accessing admin panel | Farmer navigates to admin/dashboard.php | Redirected to dashboard with error | Pass |
| TC-60 | Image path traversal | Upload with path containing "../" | Blocked by str_starts_with check | Pass |

## 7.3 Testing Summary

| Category | Total Tests | Passed | Failed |
|----------|-----------|--------|--------|
| Authentication | 16 | 16 | 0 |
| Equipment | 13 | 13 | 0 |
| Booking | 10 | 10 | 0 |
| Review | 5 | 5 | 0 |
| Pooling | 10 | 10 | 0 |
| Security | 6 | 6 | 0 |
| **Total** | **60** | **60** | **0** |

All 60 test cases passed successfully, confirming that the application meets its functional and security requirements.

---

# Chapter 8: LIMITATIONS

While AgroShare is a fully functional platform, the following limitations exist in the current version (V1.2.1):

## 8.1 Technical Limitations

1. **No Payment Gateway Integration:** The platform does not process actual financial transactions. All payments (rental fees, safety deposits, pooling amounts) are assumed to happen offline between the parties. There is no escrow, digital wallet, or UPI integration.

2. **Single-Server Architecture:** The application runs on a single WAMP server without load balancing, caching layers (Redis/Memcached), or CDN integration. This limits scalability to a few hundred concurrent users.

3. **No Real-Time Communication:** Notifications are stored in the database and fetched via periodic AJAX polling. There is no WebSocket or Server-Sent Events implementation for instant push notifications.

4. **No Mobile Application:** AgroShare is a web-only platform. While the responsive design works on mobile browsers, there is no native Android or iOS application, which limits accessibility for farmers with limited internet literacy.

5. **Local File Storage:** Equipment images are stored on the local filesystem (`public/uploads/equipment/`). This approach does not scale to multiple servers and provides no CDN acceleration or automatic backup.

6. **No Automated Email for Bookings:** While OTP emails are functional, the system does not send email notifications for booking confirmations, cancellations, or pooling updates. Only in-app notifications are generated.

7. **Single Language Support:** The platform interface is available only in English. Many Indian farmers are more comfortable in regional languages (Hindi, Kannada, Telugu, Tamil, etc.).

## 8.2 Functional Limitations

8. **No GPS/Location-Based Search:** Equipment search uses text-based district matching rather than GPS coordinates. There is no map view or distance-based proximity search.

9. **No Equipment Verification:** While users can be verified by admins, there is no verification process for equipment listings. An owner could potentially list equipment they do not actually own.

10. **No Insurance Integration:** The safety deposit system provides basic protection, but there is no integration with agricultural equipment insurance providers for comprehensive coverage.

11. **No Calendar Synchronization:** The booking calendar operates independently and does not sync with external calendar applications (Google Calendar, iCal).

12. **No Revenue/Commission Model:** The platform does not collect any commission on transactions. There is no built-in monetization mechanism for platform sustainability.

13. **No Chat/Messaging System:** Communication between renters and owners must happen outside the platform (phone calls, WhatsApp). There is no in-app messaging or chat feature.

14. **No Multi-Image Editing:** While equipment listings support up to 5 images during creation, the editing interface has limitations in reordering or selectively replacing individual images.

---

# Chapter 9: FUTURE SCOPE AND ENHANCEMENT

## 9.1 Short-Term Enhancements (3-6 months)

1. **Payment Gateway Integration:** Integrate Razorpay or PhonePe payment gateway to enable secure in-app transactions. Implement an escrow system where rental payments are held until the booking is completed, protecting both renters and owners.

2. **Multi-Language Support (i18n):** Add support for regional languages starting with Hindi, Kannada, and Telugu. Implement a language switcher in the topbar and store translations in JSON/PHP files for each supported language.

3. **In-App Chat System:** Build a real-time messaging feature using WebSockets (Socket.io or Ratchet for PHP) allowing renters and owners to communicate within the platform. Include message read receipts and typing indicators.

4. **Email Notification System:** Extend the existing PHPMailer setup to send automated emails for booking confirmations, status changes, review reminders, and pooling milestone updates. Implement email preferences for users.

5. **GPS-Based Location Search:** Add GPS coordinate storage to equipment listings and implement a radius-based search using the Haversine formula. Integrate Google Maps or OpenStreetMap for visual location display and distance calculation.

6. **Advanced Image Management:** Implement drag-and-drop image reordering, individual image deletion, and image cropping/resizing before upload. Use WebP format conversion for storage optimization.

## 9.2 Medium-Term Enhancements (6-12 months)

7. **Mobile Application (React Native / Flutter):** Develop a cross-platform mobile application to reach farmers who primarily use smartphones. Features would include push notifications, offline mode for areas with poor connectivity, and camera integration for equipment photography.

8. **Equipment IoT Integration:** Enable GPS tracking and usage monitoring for high-value equipment (tractors, harvesters) through IoT devices. Track operating hours, fuel consumption, and maintenance schedules.

9. **AI-Powered Equipment Pricing:** Use machine learning to analyze historical rental data, seasonal demand, and equipment age/condition to suggest optimal daily rental prices to equipment owners.

10. **Smart Contract-Based Escrow:** Implement blockchain-based smart contracts for rental agreements and safety deposit management. This would provide transparent, tamper-proof transaction records.

11. **Automated Dispute Resolution:** Build an AI-assisted dispute resolution system that analyzes booking history, review patterns, and equipment condition reports to suggest fair outcomes for disputed bookings.

12. **Supply Chain Analytics for Pooling:** Provide data dashboards showing regional demand patterns for agricultural inputs. Help cooperatives and suppliers identify high-demand areas and optimize distribution.

## 9.3 Long-Term Vision (12-24 months)

13. **Government Integration:** Connect with government agricultural subsidy programs (PM-KISAN, SMAM) to help farmers access equipment subsidies directly through the platform.

14. **Equipment Financing:** Partner with rural banks and NBFCs to offer equipment loans and micro-financing options. Farmers could apply for equipment purchase loans with their rental history as creditworthiness proof.

15. **Marketplace Extension:** Expand beyond equipment rental to include sale/purchase of agricultural produce, making AgroShare a comprehensive agricultural marketplace connecting farmers directly with buyers.

16. **Multi-Tenant SaaS Model:** Convert the platform into a white-label SaaS solution that agricultural cooperatives, FPOs (Farmer Producer Organizations), and state governments can deploy for their specific regions with custom branding and rules.

---

# Chapter 10: SOURCE CODE

## 10.1 Configuration Files

### 10.1.1 config/constants.php
This file defines all core application constants including database credentials, application name, base URL path, SMTP settings for email delivery, session timeouts, and upload limits. Sensitive values are overridden by `local.secrets.php` in production.

### 10.1.2 config/db.php
The central initialization file loaded by every page and API endpoint. It:
- Starts/resumes PHP sessions with secure cookie parameters
- Loads constants and secrets
- Establishes MySQLi database connection with UTF-8 character set
- Sets security headers (X-Frame-Options, X-Content-Type-Options)
- Auto-includes all helper files from `src/Helpers/`
- Enforces session idle timeout

## 10.2 Helper Files

### 10.2.1 src/Helpers/auth.php (189 lines)
Contains authentication and security utility functions:
- `requireAuth()` — Session-based authentication guard
- `requireRole($role)` — Role-based access control  
- `generateCsrfToken()` — Creates session-bound CSRF tokens
- `validateCsrfToken($token)` — Constant-time token validation
- `setFlash($type, $msg)` / `getFlash($type)` / `renderFlash()` — Flash message system
- `basePath()` — Dynamic base URL path resolver
- `e($str)` — XSS-safe output escaping wrapper

### 10.2.2 src/Helpers/audit.php (46 lines)
Provides the `logAuditEvent()` function for recording security and business events. Designed with a fail-safe pattern — wraps all database operations in try-catch blocks and falls back to `error_log()` if the audit insert fails. This ensures that audit logging never breaks application flow.

### 10.2.3 src/Helpers/mail.php (84 lines)
Configures PHPMailer for SMTP-based email delivery through Gmail. The `sendOtpEmail($email, $otp)` function creates a branded HTML email with the 6-digit OTP and a plain-text fallback. Handles both Composer autoload and manual PHPMailer inclusion paths.

## 10.3 Controller Files

### 10.3.1 src/Controllers/AdminController.php (87 lines)
Static methods for administrative operations:
- `getDashboardStats($conn)` — Counts of users, equipment, bookings
- `listUsers($conn, $role)` — Filtered user listing
- `listAllEquipment($conn)` — All equipment with owner details
- `listAllBookings($conn)` — All bookings with equipment and user details
- `getSettings($conn)` — Platform settings retrieval
- `getAuditLogs($conn, $limit)` — Recent audit log entries

### 10.3.2 src/Controllers/BookingController.php (210 lines)
Complete booking lifecycle management:
- `calculateServerSidePrice($conn, $equipmentId, $start, $end)` — Tamper-proof price calculation
- `hasBookingConflict($conn, $equipmentId, $start, $end, $excludeId)` — Overlap detection
- `createBooking($conn, $data)` — Booking creation with conflict check
- `autoPromoteBookings($conn, $userId)` — Time-based status auto-promotion
- `getMyRentals($conn, $userId)` / `getRequestsForMyEquipment($conn, $userId)` — Booking retrieval
- `updateBookingStatus($conn, $bookingId, $newStatus, $userId)` — State machine enforcement

### 10.3.3 src/Controllers/EquipmentController.php (467 lines)
Full equipment CRUD with image management:
- `validateEquipmentData($data)` — Comprehensive field validation with 17 category ENUM values
- `processImageUploads($files, $existingImages)` — MIME-verified multi-image upload
- `createEquipment($conn, $data, $ownerId)` — Equipment listing creation
- `updateEquipment($conn, $id, $data, $ownerId)` — Owner-verified updates
- `deleteEquipment($conn, $id, $ownerId)` — Deletion with image cleanup
- `toggleAvailability($conn, $id, $ownerId)` — Atomic availability toggle
- `getEquipmentById($conn, $id)` — Single record retrieval with owner details
- `browseEquipment($conn, $filters, $page, $perPage)` — Dynamic filtered browse with pagination

### 10.3.4 src/Controllers/PoolingController.php (276 lines)
Community pooling campaign management:
- `createCampaign($conn, $data, $creatorId)` — Campaign creation with deadline validation
- `listCampaigns($conn, $filters)` — Filtered campaign listing
- `getCampaignById($conn, $id)` — Single campaign with creator details
- `getRelatedPledges($conn, $campaignId)` — All pledges for a campaign
- `getUserPledge($conn, $campaignId, $userId)` — User's pledge for specific campaign
- `addPledge($conn, $campaignId, $userId, $quantity)` — Atomic pledge with row locking
- `cancelPledge($conn, $campaignId, $userId)` — Pledge cancellation with quantity rollback
- `closeCampaign($conn, $campaignId, $userId)` — Creator-only campaign closure
- `expireDeadlines($conn)` — Automatic deadline-based expiry

### 10.3.5 src/Controllers/ReviewController.php (75 lines)
Review submission and trust score management:
- `submitReview($conn, $bookingId, $reviewerId, $rating, $comment)` — Review creation with ownership verification and duplicate prevention
- `recalculateTrustScore($conn, $userId)` — Automated average calculation and trust score update

## 10.4 API Endpoints Summary

The project contains 30 API endpoints (23 user + 7 admin) located in `public/api/` and `public/admin/api/`. Each endpoint follows the same pattern: include `config/db.php`, verify authentication, validate CSRF on POST, delegate to controller, return JSON. Full source code is available in the project repository.

## 10.5 Frontend Pages Summary

The project contains 14 public-facing PHP pages and 6 admin pages, totaling approximately 300KB of combined HTML/CSS/JavaScript. Each page uses inline CSS with consistent design tokens (CSS variables) and includes both responsive layout rules and interactive JavaScript.

**Total lines of code across all source files:**

| Category | Files | Approximate Lines |
|----------|-------|-------------------|
| Configuration | 4 | ~150 |
| Controllers | 5 | ~1,115 |
| Helpers | 3 | ~320 |
| API Endpoints | 30 | ~1,800 |
| Frontend Pages | 20 | ~8,500 |
| CSS Stylesheets | 2 | ~2,800 |
| JavaScript | 4 | ~2,400 |
| SQL Schema + Migrations | 8 | ~450 |
| **Total** | **76** | **~17,535** |

---

# Chapter 11: BIBLIOGRAPHY

## 11.1 Books and Publications

1. Welling, L., & Thomson, L. (2023). *PHP and MySQL Web Development* (6th ed.). Addison-Wesley Professional.

2. Lockhart, J. (2015). *Modern PHP: New Features and Good Practices*. O'Reilly Media.

3. Nixon, R. (2021). *Learning PHP, MySQL & JavaScript* (6th ed.). O'Reilly Media.

4. Duckett, J. (2014). *HTML and CSS: Design and Build Websites*. Wiley.

## 11.2 Online Documentation

5. PHP Official Documentation — https://www.php.net/docs.php

6. MySQL 8.0 Reference Manual — https://dev.mysql.com/doc/refman/8.0/en/

7. MySQLi Extension Documentation — https://www.php.net/manual/en/book.mysqli.php

8. PHPMailer Documentation — https://github.com/PHPMailer/PHPMailer

9. MDN Web Docs (HTML, CSS, JavaScript) — https://developer.mozilla.org/

10. Google Fonts (Inter Typeface) — https://fonts.google.com/specimen/Inter

## 11.3 Security References

11. OWASP Top Ten Web Application Security Risks — https://owasp.org/www-project-top-ten/

12. PHP Password Hashing (Argon2id) — https://www.php.net/manual/en/function.password-hash.php

13. OWASP CSRF Prevention Cheat Sheet — https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

14. PHP Session Security Best Practices — https://www.php.net/manual/en/session.security.php

## 11.4 Design References

15. CSS Custom Properties (Variables) — https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties

16. CSS Grid Layout — https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_grid_layout

17. Fetch API — https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API

18. Apache .htaccess Documentation — https://httpd.apache.org/docs/2.4/howto/htaccess.html

## 11.5 Agricultural Context

19. Ministry of Agriculture & Farmers Welfare, Government of India — https://agricoop.nic.in/

20. Indian Council of Agricultural Research (ICAR) — https://icar.org.in/

21. National Bank for Agriculture and Rural Development (NABARD) — https://www.nabard.org/

22. PM-KISAN (Pradhan Mantri Kisan Samman Nidhi) — https://pmkisan.gov.in/

---

**— End of Project Report —**

**Project:** AgroShare — Agricultural Equipment Sharing and Community Pooling Platform  
**Version:** 1.2.1  
**Total Report Sections:** 11 Chapters  
**Total Test Cases:** 60  
**Total Source Files:** 76  
**Total Lines of Code:** ~17,535
