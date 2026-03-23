-- ============================================================
-- Migration: Add settings table
-- Purpose: Store global platform settings
-- ============================================================

CREATE TABLE IF NOT EXISTS settings (
    setting_key     VARCHAR(50)     NOT NULL,
    setting_value   TEXT            NULL,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
