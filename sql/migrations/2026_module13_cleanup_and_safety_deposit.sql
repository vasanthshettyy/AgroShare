-- ============================================================
-- Migration: Escrow Cleanup + Safety Deposit Integration
-- Run this directly in phpMyAdmin / MySQL
-- ============================================================

-- ------------------------------------------------------------
-- PART A: Escrow Module Cleanup (Dropping old transactions)
-- ------------------------------------------------------------

-- 1. Drop the foreign key from bookings that links to transactions
ALTER TABLE bookings 
DROP FOREIGN KEY fk_bookings_transaction;

-- 2. Drop the transaction_id column from bookings
ALTER TABLE bookings 
DROP COLUMN transaction_id;

-- 3. Drop the old escrow transactions table entirely
DROP TABLE IF EXISTS transactions;

-- ------------------------------------------------------------
-- PART B: Safety Deposit Feature Implementation
-- ------------------------------------------------------------

-- 1. Add safety_deposit column to equipment table
-- This allows owners to specify an optional deposit amount
ALTER TABLE equipment 
ADD COLUMN safety_deposit DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_per_day;

-- 2. Add deposit_amount column to bookings table
-- This locks in the deposit amount at the time of booking
ALTER TABLE bookings 
ADD COLUMN deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_price;

-- 3. Update the booking status ENUM to include 'disputed'
-- This allows a booking to be flagged if the owner refuses to return the deposit
ALTER TABLE bookings 
MODIFY status ENUM('pending','confirmed','active','completed','cancelled','rejected','disputed') NOT NULL DEFAULT 'pending';
