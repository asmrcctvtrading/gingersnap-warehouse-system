DROP PROCEDURE IF EXISTS _AddCol;

DELIMITER $$
CREATE PROCEDURE _AddCol(tbl VARCHAR(64), col VARCHAR(64), def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL _AddCol('gatepasses', 'rss_reason_code', 'VARCHAR(100) NULL AFTER `reference`');
CALL _AddCol('gatepasses', 'vendor_code',     'VARCHAR(100) NULL AFTER `rss_reason_code`');
CALL _AddCol('gatepasses', 'returnable_date', 'DATE NULL AFTER `vendor_code`');
CALL _AddCol('gatepasses', 'po_id',           'INT UNSIGNED NULL AFTER `returnable_date`');
CALL _AddCol('gatepasses', 'grn_id',          'INT UNSIGNED NULL AFTER `po_id`');
CALL _AddCol('gatepasses', 'confirmed_at',    'DATETIME NULL AFTER `closed_at`');
CALL _AddCol('gatepasses', 'confirmed_by',    'INT UNSIGNED NULL AFTER `confirmed_at`');

DROP PROCEDURE IF EXISTS _AddCol;
