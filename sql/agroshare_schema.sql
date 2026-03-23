-- ============================================================
-- AgroShare Database Schema
-- Platform: MySQL 8.0+ | Engine: InnoDB | Collation: utf8mb4_unicode_ci
-- Generated for: WAMP + phpMyAdmin local development
-- ============================================================

-- Safety: drop existing tables in reverse-dependency order (development only)
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
    trust_score     DECIMAL(3,2)        NOT NULL DEFAULT 0.00 COMMENT 'Computed average from reviews (1.00-5.00)',
    is_verified     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Admin can verify a farmer',
    is_active       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Admin can suspend/ban a user by setting to 0',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_users_phone (phone),
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 2: equipment
-- Equipment listings owned by farmers.
-- ============================================================
CREATE TABLE equipment (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    owner_id            INT UNSIGNED        NOT NULL,
    title               VARCHAR(150)        NOT NULL COMMENT 'e.g. Mahindra 475 DI Tractor',
    category            ENUM('tractor','harvester','seeder','sprayer','plough','chain_saw','rotavator','cultivator','thresher','water_pump','earth_auger','baler','trolley','brush_cutter','power_tiller','chaff_cutter','other') NOT NULL,
    description         TEXT                NOT NULL,
    price_per_hour      DECIMAL(8,2)        NOT NULL,
    price_per_day       DECIMAL(8,2)        NOT NULL,
    includes_operator   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    location_village    VARCHAR(100)        NOT NULL,
    location_district   VARCHAR(100)        NOT NULL,
    images              JSON                NULL DEFAULT NULL COMMENT 'JSON array of image file paths',
    `condition`         ENUM('excellent','good','fair') NOT NULL DEFAULT 'good',
    is_available        TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    is_featured         TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Admin can pin listing to top of browse page',
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_equipment_browse (is_available, location_district, category),

    CONSTRAINT fk_equipment_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
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
    status          ENUM('pending','confirmed','active','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
    admin_override  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 when status was changed by admin override',
    admin_override_reason VARCHAR(255)  NULL DEFAULT NULL COMMENT 'Optional reason for admin-forced booking action',
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
        ON UPDATE CASCADE
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
    rating          TINYINT UNSIGNED    NOT NULL COMMENT '1-5 star rating',
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

    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 5: pooling_campaigns
-- Bulk-buy campaigns where farmers pool demand for cheaper inputs.
-- ============================================================
CREATE TABLE pooling_campaigns (
    id                          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    creator_id                  INT UNSIGNED    NOT NULL,
    title                       VARCHAR(150)    NOT NULL COMMENT 'e.g. DAP Fertilizer Bulk Buy - Dharwad',
    item_name                   VARCHAR(150)    NOT NULL,
    unit                        VARCHAR(30)     NOT NULL COMMENT 'e.g. 50kg bag, litre',
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

    CONSTRAINT chk_campaigns_bulk_cheaper CHECK (price_per_unit_bulk < price_per_unit_individual)
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
        ON UPDATE CASCADE
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
    is_used     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Prevents OTP reuse',
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
    is_read     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_notifications_user (user_id, is_read, created_at),

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 9: audit_logs
-- Lightweight audit logging for auth failures and critical events.
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    admin_id        INT UNSIGNED    NULL DEFAULT NULL,
    action_type     VARCHAR(50)     NOT NULL,
    target_id       INT UNSIGNED    NULL DEFAULT NULL,
    description     TEXT            NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- VERIFICATION: Show all tables created
-- ============================================================
SHOW TABLES;
