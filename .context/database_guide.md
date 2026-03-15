# AgroShare — Complete Database Architecture & Setup Guide

> **Stack:** PHP 8.1+ (Object-Oriented `mysqli`) · MySQL 8.0+ · WAMP (local) · phpMyAdmin
> **Collation:** `utf8mb4_unicode_ci` (full Unicode — supports Kannada, Hindi, Devanagari, emojis)

---

## 1. Entity-Relationship Map

### 1.1 — All Entities (8 Tables)

| # | Table | Purpose |
|---|---|---|
| 1 | `users` | Every farmer and admin on the platform |
| 2 | `equipment` | Equipment listings owned by farmers |
| 3 | `bookings` | Rental reservations linking renters to equipment |
| 4 | `reviews` | Two-way ratings after a completed booking |
| 5 | `pooling_campaigns` | Bulk-buy campaigns created by farmers |
| 6 | `pooling_pledges` | Individual farmer pledges toward a campaign |
| 7 | `password_resets` | OTP tokens for the password recovery flow |
| 8 | `notifications` | In-app alerts (booking updates, pledge alerts) |

### 1.2 — Relationship Map

```
                         ┌──────────────┐
                         │    users     │
                         │   (PK: id)   │
                         └──────┬───────┘
                                │
        ┌───────────┬───────────┼───────────┬────────────┬────────────┐
        │           │           │           │            │            │
   1:Many      1:Many      1:Many     1:Many       1:Many       1:Many
        │           │           │           │            │            │
        ▼           ▼           ▼           ▼            ▼            ▼
   ┌─────────┐ ┌─────────┐ ┌────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
   │equipment│ │bookings │ │reviews │ │ pooling  │ │ pooling  │ │password  │
   │         │ │(renter) │ │        │ │campaigns │ │ pledges  │ │ _resets  │
   └────┬────┘ │(owner)  │ └────────┘ └─────┬────┘ └──────────┘ └──────────┘
        │      └────┬────┘                   │
        │           │                        │
   1:Many      1:1 (per booking)        1:Many
        │           │                        │
        ▼           ▼                        ▼
   ┌─────────┐ ┌─────────┐            ┌──────────┐
   │bookings │ │ reviews │            │ pooling  │
   │         │ │         │            │ pledges  │
   └─────────┘ └─────────┘            └──────────┘
```

### 1.3 — Relationship Details

| Relationship | Type | Description | FK Rule |
|---|---|---|---|
| **users → equipment** | One-to-Many | A user (farmer) can own many equipment listings | `ON DELETE CASCADE` — if user deleted, their listings go too |
| **users → bookings (as renter)** | One-to-Many | A user can rent many pieces of equipment | `ON DELETE RESTRICT` — cannot delete a user who has bookings |
| **users → bookings (as owner)** | One-to-Many | A user's equipment can have many bookings | `ON DELETE RESTRICT` — cannot delete a user who has bookings |
| **equipment → bookings** | One-to-Many | One piece of equipment can be booked many times | `ON DELETE RESTRICT` — cannot delete equipment with existing bookings |
| **bookings → reviews** | One-to-One (per party) | Each booking can have up to 2 reviews (one from renter, one from owner) | `ON DELETE CASCADE` — if booking deleted, its reviews go too |
| **users → reviews (as reviewer)** | One-to-Many | A user can write many reviews | `ON DELETE CASCADE` |
| **users → reviews (as reviewee)** | One-to-Many | A user can receive many reviews | `ON DELETE CASCADE` |
| **users → pooling_campaigns** | One-to-Many | A user can create many bulk-buy campaigns | `ON DELETE CASCADE` |
| **pooling_campaigns → pooling_pledges** | One-to-Many | A campaign can have many pledges from different farmers | `ON DELETE CASCADE` — if campaign deleted, all pledges go too |
| **users → pooling_pledges** | One-to-Many | A user can pledge to many campaigns | `ON DELETE CASCADE` |
| **users → password_resets** | One-to-Many | A user can request multiple OTPs over time | `ON DELETE CASCADE` |
| **users → notifications** | One-to-Many | A user can receive many notifications | `ON DELETE CASCADE` |

> **Key safety insight:** Because `bookings` uses `ON DELETE RESTRICT` on both `renter_id` and `owner_id`, you can **never accidentally delete a user who has booking history**. The database itself blocks it. This is intentional — booking records are financial evidence and must be preserved.

---

## 2. Step-by-Step phpMyAdmin Walkthrough (WAMP)

### 2.1 — Start WAMP & Open phpMyAdmin

1. **Start WAMP Server** — click the WAMP icon in your system tray. Wait until the icon turns **green** (all services running).
2. Open your browser and go to: **`http://localhost/phpmyadmin`**
3. Login credentials (WAMP defaults):
   - **Username:** `root`
   - **Password:** *(leave blank — WAMP has no root password by default)*
4. You should see the phpMyAdmin dashboard with the server tree on the left.

### 2.2 — Create the Database

1. Click **"New"** in the left sidebar (or click the **"Databases"** tab at the top).
2. In the **"Create database"** field, type: **`agroshare`**
3. In the **"Collation"** dropdown next to it, select: **`utf8mb4_unicode_ci`**
   - *Why:* This supports full Unicode — Kannada, Hindi, Devanagari scripts, and even emojis in user reviews. It is the recommended collation for modern MySQL applications.
4. Click **"Create"**.
5. You should see `agroshare` appear in the left sidebar. Click on it to select it.

### 2.3 — Paste & Execute the SQL Script

1. With the `agroshare` database selected (shown in bold in the left sidebar), click the **"SQL"** tab at the top of the page.
2. You will see a large text area labeled **"Run SQL query/queries on database agroshare"**.
3. **Copy the entire SQL script** from Section 3 below.
4. **Paste** it into the SQL text area.
5. Click the **"Go"** button (bottom-right of the text area).
6. phpMyAdmin will execute all statements. You should see green success messages for each `CREATE TABLE`.
7. Click on **`agroshare`** in the left sidebar — you should now see all 8 tables listed.

### 2.4 — Verify the Setup

1. Click on any table (e.g., `users`) → click the **"Structure"** tab to verify columns, types, and indexes.
2. To verify Foreign Keys: click on a table → click **"Structure"** → scroll down to the **"Relation view"** link (or click the **"Relations"** section). You should see all FKs listed with their referenced tables.
3. To verify the engine: click on the `agroshare` database → click the **"Operations"** tab → each table should show `InnoDB` as the engine.

### 2.5 — Create the Admin Seed Account

After the tables are created, paste and run this SQL to create your first admin account:

```sql
-- Default admin account (password: Admin@1234)
-- The hash below is the Argon2id hash of "Admin@1234"
-- In production, generate this via PHP: password_hash('Admin@1234', PASSWORD_ARGON2ID)
INSERT INTO users (full_name, phone, email, password_hash, role, village, district, state, is_verified)
VALUES (
    'Platform Admin',
    '9999999999',
    'admin@agroshare.local',
    'REPLACE_WITH_REAL_HASH_GENERATED_FROM_PHP',
    'admin',
    'N/A',
    'N/A',
    'N/A',
    1
);
```

> [!IMPORTANT]
> **Do NOT use the placeholder hash above in production.** Generate a real hash from PHP first:
> ```php
> echo password_hash('Admin@1234', PASSWORD_ARGON2ID);
> ```
> Copy the output and replace `REPLACE_WITH_REAL_HASH_GENERATED_FROM_PHP` with it.

---

## 3. Master SQL Script

> **Instructions:** Copy everything below and paste it into phpMyAdmin's SQL tab (with the `agroshare` database selected). Execute it in one go.

```sql
-- ============================================================
-- AgroShare Database Schema
-- Platform: MySQL 8.0+ | Engine: InnoDB | Collation: utf8mb4_unicode_ci
-- Generated for: WAMP + phpMyAdmin local development
-- ============================================================

/*
  DESTRUCTIVE — UNCOMMENT TO RUN (development only)
  WARNING: This will delete all existing data in the tables below.
  
  SET FOREIGN_KEY_CHECKS = 0;
  DROP TABLE IF EXISTS notifications;
  DROP TABLE IF EXISTS password_resets;
  DROP TABLE IF EXISTS pooling_pledges;
  DROP TABLE IF EXISTS pooling_campaigns;
  DROP TABLE IF EXISTS reviews;
  DROP TABLE IF EXISTS bookings;
  DROP TABLE IF EXISTS equipment;
  DROP TABLE IF EXISTS users;
  SET FOREIGN_KEY_CHECKS = 1;
*/


-- ============================================================
-- TABLE 1: users
-- Core identity table for every farmer and admin on the platform.
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(120)        NOT NULL,
    phone           VARCHAR(15)         NOT NULL,
    email           VARCHAR(150)        NULL DEFAULT NULL,
    password_hash   VARCHAR(255)        NOT NULL COMMENT 'Argon2id output from password_hash()',
    role            ENUM('farmer','admin') NOT NULL DEFAULT 'farmer',
    village         VARCHAR(100)        NOT NULL,
    district        VARCHAR(100)        NOT NULL,
    state           VARCHAR(80)         NOT NULL,
    profile_photo   VARCHAR(255)        NULL DEFAULT NULL COMMENT 'Relative path to uploaded photo',
    trust_score     DECIMAL(3,2)        NOT NULL DEFAULT 1.00 COMMENT 'Computed average from reviews (1.00–5.00)',
    is_verified     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Admin can verify a farmer',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_users_phone (phone),
    UNIQUE KEY uk_users_email (email),
    CONSTRAINT chk_users_trust_score CHECK (trust_score BETWEEN 1.00 AND 5.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 2: equipment
-- Equipment listings owned by farmers.
-- ============================================================
CREATE TABLE equipment (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    owner_id            INT UNSIGNED        NOT NULL,
    title               VARCHAR(150)        NOT NULL COMMENT 'e.g. "Mahindra 475 DI Tractor"',
    category            ENUM('tractor','harvester','seeder','sprayer','other') NOT NULL,
    description         TEXT                NOT NULL,
    price_per_hour      DECIMAL(8,2)        NOT NULL,
    price_per_day       DECIMAL(8,2)        NOT NULL,
    includes_operator   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    location_village    VARCHAR(100)        NOT NULL,
    location_district   VARCHAR(100)        NOT NULL,
    images              JSON                NULL DEFAULT NULL COMMENT 'JSON array of image file paths',
    `condition`         ENUM('excellent','good','fair') NOT NULL DEFAULT 'good',
    is_available        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_equipment_browse (is_available, location_district, category),

    CONSTRAINT fk_equipment_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_equipment_price_hour CHECK (price_per_hour > 0),
    CONSTRAINT chk_equipment_price_day CHECK (price_per_day > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 3: bookings
-- Rental reservations linking a renter to a piece of equipment.
-- Both renter_id and owner_id use RESTRICT to prevent accidental
-- deletion of users involved in financial transactions.
-- ============================================================
CREATE TABLE bookings (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    equipment_id    INT UNSIGNED        NOT NULL,
    renter_id       INT UNSIGNED        NOT NULL,
    owner_id        INT UNSIGNED        NOT NULL COMMENT 'Denormalized from equipment for fast queries',
    start_datetime  DATETIME            NOT NULL,
    end_datetime    DATETIME            NOT NULL,
    pricing_mode    ENUM('hourly','daily') NOT NULL,
    total_price     DECIMAL(10,2)       NOT NULL COMMENT 'PHP-calculated at booking time',
    status          ENUM('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT                NULL DEFAULT NULL COMMENT 'Renter special requests',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_bookings_conflict (equipment_id, status, start_datetime, end_datetime),
    INDEX idx_bookings_renter (renter_id, status),
    INDEX idx_bookings_owner (owner_id, status),

    CONSTRAINT fk_bookings_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_renter
        FOREIGN KEY (renter_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_bookings_dates CHECK (end_datetime > start_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 4: reviews
-- Two-way reviews: renter rates owner AND owner rates renter.
-- One review per party per booking (enforced by unique key).
-- ============================================================
CREATE TABLE reviews (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    booking_id      INT UNSIGNED        NOT NULL,
    reviewer_id     INT UNSIGNED        NOT NULL,
    reviewee_id     INT UNSIGNED        NOT NULL,
    rating          TINYINT UNSIGNED    NOT NULL COMMENT '1–5 star rating',
    comment         TEXT                NULL DEFAULT NULL,
    review_type     ENUM('renter_to_owner','owner_to_renter') NOT NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_reviews_booking_reviewer (booking_id, reviewer_id),
    INDEX idx_reviews_reviewee (reviewee_id),

    CONSTRAINT fk_reviews_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT chk_reviews_no_self CHECK (reviewer_id != reviewee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 5: pooling_campaigns
-- Bulk-buy campaigns where farmers pool demand for cheaper inputs.
-- ============================================================
CREATE TABLE pooling_campaigns (
    id                          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    creator_id                  INT UNSIGNED    NOT NULL,
    title                       VARCHAR(150)    NOT NULL COMMENT 'e.g. "DAP Fertilizer Bulk Buy - Dharwad"',
    item_name                   VARCHAR(150)    NOT NULL,
    unit                        VARCHAR(30)     NOT NULL COMMENT 'e.g. "50kg bag", "litre"',
    price_per_unit_individual   DECIMAL(10,2)   NOT NULL COMMENT 'Market price buying alone',
    price_per_unit_bulk         DECIMAL(10,2)   NOT NULL COMMENT 'Negotiated bulk price',
    minimum_quantity            INT UNSIGNED    NOT NULL COMMENT 'Threshold to unlock bulk deal',
    current_quantity            INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Aggregated from pledges',
    deadline                    DATE            NOT NULL,
    status                      ENUM('open','threshold_met','closed','cancelled') NOT NULL DEFAULT 'open',
    district                    VARCHAR(100)    NOT NULL,
    description                 TEXT            NOT NULL,
    created_at                  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_campaigns_browse (status, district, deadline),

    CONSTRAINT fk_campaigns_creator
        FOREIGN KEY (creator_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT chk_campaigns_bulk_cheaper CHECK (price_per_unit_bulk < price_per_unit_individual),
    CONSTRAINT chk_campaigns_price_individual CHECK (price_per_unit_individual > 0),
    CONSTRAINT chk_campaigns_price_bulk CHECK (price_per_unit_bulk > 0),
    CONSTRAINT chk_campaigns_min_qty CHECK (minimum_quantity > 0),
    CONSTRAINT chk_campaigns_curr_qty CHECK (current_quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 6: pooling_pledges
-- Individual farmer pledges toward a bulk-buy campaign.
-- One pledge per farmer per campaign (enforced by unique key).
-- ============================================================
CREATE TABLE pooling_pledges (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    campaign_id         INT UNSIGNED    NOT NULL,
    farmer_id           INT UNSIGNED    NOT NULL,
    quantity_pledged    INT UNSIGNED    NOT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_pledges_campaign_farmer (campaign_id, farmer_id),
    INDEX idx_pledges_farmer (farmer_id),

    CONSTRAINT fk_pledges_campaign
        FOREIGN KEY (campaign_id) REFERENCES pooling_campaigns(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_pledges_farmer
        FOREIGN KEY (farmer_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_pledges_qty CHECK (quantity_pledged >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 7: password_resets
-- OTP tokens for the password recovery simulation flow.
-- ============================================================
CREATE TABLE password_resets (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    phone       VARCHAR(15)     NOT NULL COMMENT 'Stored for quick lookup in recovery flow',
    otp         CHAR(6)         NOT NULL COMMENT '6-digit numeric OTP',
    expires_at  DATETIME        NOT NULL COMMENT 'NOW() + 15 minutes',
    is_used     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Prevents OTP reuse',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_resets_lookup (phone, is_used, expires_at),

    CONSTRAINT fk_resets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 8: notifications
-- Simple in-app notification system for booking & pledge events.
-- ============================================================
CREATE TABLE notifications (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    message     TEXT            NOT NULL,
    is_read     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_notifications_user (user_id, is_read, created_at),

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- VERIFICATION: Show all tables created
-- ============================================================
SHOW TABLES;
```

---

## 4. Quick Reference — Index Strategy

These indexes are included in the `CREATE TABLE` statements above and are specifically optimized for the most frequent queries:

| Index | On Table | Optimizes |
|---|---|---|
| `idx_equipment_browse` | `equipment` | Browse page filtering by availability + district + category |
| `idx_bookings_conflict` | `bookings` | The conflict detection query — most performance-critical |
| `idx_bookings_renter` | `bookings` | "My Bookings" page for renters |
| `idx_bookings_owner` | `bookings` | Owner dashboard — bookings on their equipment |
| `idx_campaigns_browse` | `pooling_campaigns` | Campaign browse page with status/district/deadline filters |
| `uk_pledges_campaign_farmer` | `pooling_pledges` | Prevents duplicate pledges + fast lookup per campaign |
| `idx_resets_lookup` | `password_resets` | OTP verification query (phone + not used + not expired) |
| `idx_notifications_user` | `notifications` | Unread notification count in topbar badge |

---

## 5. Foreign Key Behavior Summary

| When you delete... | What happens | Why |
|---|---|---|
| A **user** with no bookings | ✅ Cascades: equipment, campaigns, pledges, reviews, resets, notifications all deleted | Clean removal of inactive user |
| A **user** with bookings | ❌ **Blocked** by `RESTRICT` on `bookings.renter_id` / `owner_id` | Booking records are financial evidence — must be preserved |
| An **equipment** listing with bookings | ❌ **Blocked** by `RESTRICT` on `bookings.equipment_id` | Cannot delete equipment that has booking history |
| An **equipment** listing with no bookings | ✅ Deleted normally (no cascade needed — no dependent rows) | Safe cleanup |
| A **booking** | ✅ Cascades: associated reviews deleted | Reviews only make sense in context of their booking |
| A **pooling campaign** | ✅ Cascades: all pledges for that campaign deleted | Pledges are meaningless without their campaign |

> [!CAUTION]
> The `DROP TABLE` statements at the top of the script will **destroy all existing data**. They are included for development convenience (re-running the script on a fresh install). **Never run them on a database with real data.**
