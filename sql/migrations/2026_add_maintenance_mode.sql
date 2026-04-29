-- Migration: Add Maintenance Mode Setting
-- Description: Adds a system-wide maintenance mode toggle.

USE agroshare;

INSERT IGNORE INTO settings (setting_key, setting_value) 
VALUES ('maintenance_mode', '0');
