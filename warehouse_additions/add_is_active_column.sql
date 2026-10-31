-- Quick fix: Add is_active column to coordinators table
-- Run this in phpMyAdmin SQL tab

-- Check current table structure
DESCRIBE coordinators;

-- Add is_active column if missing
ALTER TABLE coordinators
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1
COMMENT 'Active status flag';

-- Verify it worked
SELECT coordinator_id, coordinator_name, is_active
FROM coordinators
ORDER BY coordinator_name;
