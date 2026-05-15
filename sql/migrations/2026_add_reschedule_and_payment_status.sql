-- ============================================================
-- Migration: Add Rescheduling and Payment Status Support
-- File: 2026_add_reschedule_and_payment_status.sql
-- ============================================================

-- 1. Extend the status ENUM to include 'rescheduled'
-- This preserves all existing statuses and allows us to mark a booking as replaced.
ALTER TABLE bookings 
MODIFY COLUMN status ENUM(
    'pending',
    'confirmed',
    'active',
    'completed',
    'cancelled',
    'rejected',
    'disputed',
    'rescheduled'
) NOT NULL DEFAULT 'pending';

-- 2. Add payment_status column
-- tracks whether the renter has completed the UPI payment. 
-- Defaults to 'pending' (unpaid).
ALTER TABLE bookings 
ADD COLUMN payment_status ENUM('pending', 'confirmed') 
NOT NULL DEFAULT 'pending' 
AFTER status,
ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL AFTER payment_status,
ADD COLUMN payment_verified_at DATETIME DEFAULT NULL AFTER payment_reference;

-- 3. Create a composite index for conflict checks
-- Optimized for: WHERE equipment_id = ? AND status IN (...) AND start < ? AND end > ?
-- Also includes payment_status to support the new gating rule.
CREATE INDEX idx_bookings_conflict_reschedule 
ON bookings (equipment_id, status, payment_status, start_datetime, end_datetime);

-- ============================================================
-- ROLLBACK SQL (FOR REFERENCE)
-- ============================================================
/*
DROP INDEX idx_bookings_conflict_reschedule ON bookings;
ALTER TABLE bookings DROP COLUMN payment_status;
ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','active','completed','cancelled','rejected','disputed') NOT NULL DEFAULT 'pending';
*/
