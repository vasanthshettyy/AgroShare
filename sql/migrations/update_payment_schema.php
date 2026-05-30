<?php
/**
 * update_payment_schema.php — Standalone migration to harden the payment schema.
 * 
 * Run this from the project root: php sql/migrations/update_payment_schema.php
 */

require_once __DIR__ . '/../../config/db.php';

echo "Starting migration: update_payment_schema\n";

try {
    // 1. Check/Add payment_status
    $res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
    if ($res->num_rows === 0) {
        echo "Adding 'payment_status' column...\n";
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending', 'confirmed') NOT NULL DEFAULT 'pending' AFTER status");
    } else {
        echo "'payment_status' already exists.\n";
    }

    // 2. Check/Add payment_reference
    $res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_reference'");
    if ($res->num_rows === 0) {
        echo "Adding 'payment_reference' column...\n";
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_reference VARCHAR(100) NULL DEFAULT NULL AFTER payment_status");
    } else {
        echo "'payment_reference' already exists.\n";
    }

    // 3. Check/Add payment_verified_at
    $res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_verified_at'");
    if ($res->num_rows === 0) {
        echo "Adding 'payment_verified_at' column...\n";
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_verified_at DATETIME NULL DEFAULT NULL AFTER payment_reference");
    } else {
        echo "'payment_verified_at' already exists.\n";
    }

    echo "Migration completed successfully.\n";
} catch (mysqli_sql_exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
