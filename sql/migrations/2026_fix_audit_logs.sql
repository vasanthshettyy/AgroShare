-- Migration: Fix Audit Logs Table
-- Description: Aligning audit_logs table with the application's reporting requirements.

USE agroshare;

-- Rename target_id to actor_user_id if it exists, otherwise add actor_user_id
-- In MySQL, we can't easily check for column existence in a single statement without a procedure, 
-- but we can add columns and drop target_id if needed.
-- However, for simplicity in this dev environment:

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS actor_user_id INT UNSIGNED NULL DEFAULT NULL AFTER action_type;
ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS metadata_json JSON NULL DEFAULT NULL AFTER description;

-- If target_id exists and has data, migrate it to actor_user_id
UPDATE audit_logs SET actor_user_id = target_id WHERE actor_user_id IS NULL AND target_id IS NOT NULL;
