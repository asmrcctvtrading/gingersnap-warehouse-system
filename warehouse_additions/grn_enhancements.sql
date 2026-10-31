-- ============================================================
-- GRN Enhancements Migration
-- Safe to re-run (idempotent).
-- Run this on the server BEFORE deploying the updated PHP files.
-- DB: yitrmcft_gingersnaps
-- ============================================================

DROP PROCEDURE IF EXISTS _AddCol;

DELIMITER $$

CREATE PROCEDURE _AddCol(tbl VARCHAR(64), col VARCHAR(64), def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

DELIMITER ;

CALL _AddCol('grn', 'collection_name',  'VARCHAR(255) NULL AFTER notes');
CALL _AddCol('grn', 'ctrl_no',          'VARCHAR(20)  NULL AFTER collection_name');
CALL _AddCol('grn', 'receiving_person', 'VARCHAR(255) NULL AFTER ctrl_no');
CALL _AddCol('grn', 'time_in',          'TIME NULL AFTER receiving_person');
CALL _AddCol('grn', 'time_start',       'TIME NULL AFTER time_in');
CALL _AddCol('grn', 'discrepancy_type', "ENUM('NONE','SHORTAGE','EXCESS','DAMAGED') NOT NULL DEFAULT 'NONE' AFTER time_start");
CALL _AddCol('grn', 'shortage_qty',     'INT UNSIGNED NOT NULL DEFAULT 0 AFTER discrepancy_type');

-- Add unique index on ctrl_no if not already present
-- (safe: CREATE UNIQUE INDEX fails silently if index already exists via IF NOT EXISTS in MySQL 8+)
CREATE UNIQUE INDEX IF NOT EXISTS uq_grn_ctrl_no ON grn (ctrl_no);

DROP PROCEDURE IF EXISTS _AddCol;
