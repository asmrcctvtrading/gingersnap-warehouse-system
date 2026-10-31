-- =============================================================
-- DIAGNOSTIC SCRIPT: Check qc_inspection table structure
-- =============================================================
-- Run this in phpMyAdmin to see what's wrong
-- =============================================================

-- 1. Check if table exists
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN 'Table EXISTS'
        ELSE 'Table DOES NOT exist'
    END AS table_status
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'qc_inspection';

-- 2. Show current table structure (if exists)
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY,
    EXTRA
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'qc_inspection'
ORDER BY ORDINAL_POSITION;

-- 3. Show any existing foreign keys
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE()
  AND table_name = 'qc_inspection'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 4. Show table creation SQL
SHOW CREATE TABLE `qc_inspection`;

-- 5. Check if receiving table has grn_id
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN 'Column EXISTS'
        ELSE 'Column DOES NOT exist'
    END AS column_status
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'receiving'
  AND column_name = 'grn_id';

-- 6. Check if grn and grn_items tables exist
SELECT
    table_name,
    CASE
        WHEN COUNT(*) > 0 THEN 'EXISTS'
        ELSE 'NOT EXISTS'
    END AS status
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('grn', 'grn_items')
GROUP BY table_name;

-- 7. Summary of sprint2 migration status
SELECT
    'qc_inspection table' AS item,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'qc_inspection') AS exists_count,
    'Should have inspection_id primary key' AS expected
UNION ALL
SELECT
    'receiving.grn_id column' AS item,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'receiving' AND column_name = 'grn_id') AS exists_count,
    'Should exist (nullable)' AS expected
UNION ALL
SELECT
    'grn table' AS item,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'grn') AS exists_count,
    'Should exist' AS expected
UNION ALL
SELECT
    'grn_items table' AS item,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'grn_items') AS exists_count,
    'Should exist' AS expected;