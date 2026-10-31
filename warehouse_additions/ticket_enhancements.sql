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

CALL _AddCol('tickets', 'priority_no',          'INT UNSIGNED NOT NULL DEFAULT 999');
CALL _AddCol('tickets', 'handoff_time',          'TIME NULL');
CALL _AddCol('tickets', 'polybag_qty',           'INT UNSIGNED NOT NULL DEFAULT 0');
CALL _AddCol('tickets', 'merged_report_time',    'TIME NOT NULL DEFAULT ''15:00:00''');

DROP PROCEDURE IF EXISTS _AddCol;
