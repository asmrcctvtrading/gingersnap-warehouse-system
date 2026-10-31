-- =====================================================
-- PHASE 3 MINIMAL INSTALL - COORDINATORS ONLY
-- Safe, minimal SQL to get DBASED working
-- DB: yitrmcft_gingersnaps
-- =====================================================
-- This creates ONLY what's needed for the coordinator
-- dropdown to work. Run the full phase3_install.sql
-- later for complete Phase 3 features.
-- =====================================================

-- 1. Create coordinators table
CREATE TABLE IF NOT EXISTS `coordinators` (
  `coordinator_id` INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `coordinator_name` VARCHAR(100)  NOT NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`coordinator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed the 5 coordinators
INSERT IGNORE INTO `coordinators` (`coordinator_id`, `coordinator_name`) VALUES
  (1, 'JUDITH'),
  (2, 'GLENDA'),
  (3, 'VIVIAN'),
  (4, 'BETH'),
  (5, 'ROPHA');

-- 3. Add coordinator_id column to purchase_orders (if not exists)
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'purchase_orders'
    AND COLUMN_NAME = 'coordinator_id'
);

SET @add_col_sql = IF(@col_exists = 0,
  'ALTER TABLE `purchase_orders` ADD COLUMN `coordinator_id` INT UNSIGNED NULL COMMENT "FK to coordinators"',
  'SELECT "coordinator_id column already exists" AS _skip'
);

PREPARE stmt FROM @add_col_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Verify installation
SELECT
  'Installation Complete' AS status,
  COUNT(*) AS coordinator_count
FROM coordinators;

SELECT coordinator_id, coordinator_name, is_active
FROM coordinators
ORDER BY coordinator_name;
