-- Migration: Safety Deposit and Disputes
-- Run this in your MySQL / phpMyAdmin

-- 1. Add safety_deposit to equipment
ALTER TABLE equipment 
ADD COLUMN safety_deposit DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_per_day;

-- 2. Add deposit_amount to bookings
ALTER TABLE bookings 
ADD COLUMN deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_price;

-- 3. Add 'disputed' to bookings status ENUM
ALTER TABLE bookings 
MODIFY status ENUM('pending','confirmed','active','completed','cancelled','rejected','disputed') NOT NULL DEFAULT 'pending';
