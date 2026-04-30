-- Migration: Add UPI fields to users table for AgroPay module
ALTER TABLE users 
ADD COLUMN upi_id VARCHAR(100) NULL DEFAULT NULL AFTER profile_photo,
ADD COLUMN upi_qr_path VARCHAR(255) NULL DEFAULT NULL AFTER upi_id;
