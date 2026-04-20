-- AgroShare Migration: Update password_resets for Email OTP
-- This script is designed to be safe for re-running.

-- 1. Add email column if not exists
SET @dropdown = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'password_resets' AND COLUMN_NAME = 'email' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@dropdown = 0, 'ALTER TABLE password_resets ADD COLUMN email VARCHAR(150) NULL AFTER phone', 'SELECT "Column email already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Rename 'used' to 'is_used' if 'used' exists
SET @dropdown = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'password_resets' AND COLUMN_NAME = 'used' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@dropdown > 0, 'ALTER TABLE password_resets CHANGE COLUMN used is_used TINYINT(1) UNSIGNED NOT NULL DEFAULT 0', 'SELECT "Column used already renamed or does not exist"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Add index for rate limiting performance
SET @dropdown = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'password_resets' AND INDEX_NAME = 'idx_email_created' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@dropdown = 0, 'CREATE INDEX idx_email_created ON password_resets(email, created_at)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
