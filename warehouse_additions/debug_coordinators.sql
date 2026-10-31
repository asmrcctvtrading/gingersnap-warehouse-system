-- Check Coordinators Table Structure and Data
-- Run this in phpMyAdmin for database: yitrmcft_gingersnaps

-- 1. Check if coordinators table exists
SHOW TABLES LIKE 'coordinators';

-- 2. Check table structure
DESCRIBE coordinators;

-- 3. Check actual data
SELECT * FROM coordinators;

-- 4. Check if is_active column exists
SHOW COLUMNS FROM coordinators LIKE 'is_active';

-- 5. If is_active doesn't exist, check without it
SELECT coordinator_id, coordinator_name FROM coordinators ORDER BY coordinator_name;
