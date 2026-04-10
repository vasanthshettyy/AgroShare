-- Module 12 migration: dual-path escrow/manual transaction layer
-- Date: 2026-04-10
-- Purpose:
--   1) Create `transactions` table
--   2) Link `bookings.transaction_id` -> `transactions.transaction_id`
-- Notes:
--   - Safe to run on MySQL 8+
--   - Includes existence checks for repeatability

-- 1) Create transactions table (idempotent)
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id      VARCHAR(50) NOT NULL,
    equipment_id        INT UNSIGNED NOT NULL,
    renter_id           INT UNSIGNED NOT NULL,
    owner_id            INT UNSIGNED NOT NULL,
    booking_type        ENUM('ESCROW','MANUAL') NOT NULL,
    amount              DECIMAL(10,2) NOT NULL,
    status              ENUM('PENDING_PAYMENT','FUNDS_LOCKED','ACTIVE_RENTAL','COMPLETED','DISPUTED','MANUAL_DEAL_INITIATED') NOT NULL,
    handover_otp        INT(4) DEFAULT NULL,
    return_otp          INT(4) DEFAULT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (transaction_id),
    INDEX idx_transactions_renter (renter_id, status, created_at),
    INDEX idx_transactions_owner (owner_id, status, created_at),
    INDEX idx_transactions_equipment (equipment_id, status, created_at),
    INDEX idx_transactions_type_status (booking_type, status),

    CONSTRAINT fk_transactions_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_renter
        FOREIGN KEY (renter_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Add bookings.transaction_id only if missing
SET @has_tx_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND COLUMN_NAME = 'transaction_id'
);
SET @sql := IF(
    @has_tx_col = 0,
    'ALTER TABLE bookings ADD COLUMN transaction_id VARCHAR(50) NULL DEFAULT NULL AFTER id',
    'SELECT "bookings.transaction_id already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3) Add index only if missing
SET @has_tx_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND INDEX_NAME = 'idx_bookings_transaction'
);
SET @sql := IF(
    @has_tx_idx = 0,
    'ALTER TABLE bookings ADD INDEX idx_bookings_transaction (transaction_id)',
    'SELECT "idx_bookings_transaction already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Add FK only if missing
SET @has_tx_fk := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_bookings_transaction'
      AND TABLE_NAME = 'bookings'
);
SET @sql := IF(
    @has_tx_fk = 0,
    'ALTER TABLE bookings ADD CONSTRAINT fk_bookings_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "fk_bookings_transaction already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) Verification queries
SHOW TABLES LIKE 'transactions';
DESCRIBE transactions;
DESCRIBE bookings;
SHOW CREATE TABLE transactions;
SHOW CREATE TABLE bookings;
