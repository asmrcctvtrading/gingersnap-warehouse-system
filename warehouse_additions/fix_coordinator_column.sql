-- Quick Fix for Coordinator Dropdown Issue
-- Problem: Query uses 'coordinator_name' but column might be 'name'
-- Run this in phpMyAdmin to diagnose and fix

-- Step 1: Check actual column name
SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'yitrmcft_gingersnaps'
  AND TABLE_NAME = 'coordinators'
  AND COLUMN_NAME IN ('name', 'coordinator_name');

-- Step 2: Check if data exists
SELECT * FROM coordinators;

-- Step 3: If column is 'name', rename it to 'coordinator_name'
-- (Run this ONLY if Step 1 shows column is 'name')
ALTER TABLE coordinators
CHANGE COLUMN `name` `coordinator_name` VARCHAR(100) NOT NULL;

-- Step 4: Verify fix
SELECT coordinator_id, coordinator_name FROM coordinators WHERE is_active = 1 ORDER BY coordinator_name;
