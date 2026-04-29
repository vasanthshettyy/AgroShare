-- Migration: Add Google Auth Support
-- Description: Adds google_id column and makes password_hash nullable for OAuth users.

USE agroshare;

-- Add google_id column if it doesn't exist
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email;

-- Make password_hash nullable
-- Note: In MySQL, we use MODIFY to change the nullability
ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL;

-- Log the migration in the audit_logs (optional but good practice)
INSERT INTO audit_logs (action_type, description) 
VALUES ('migration', 'Applied add_google_auth migration to users table.');
