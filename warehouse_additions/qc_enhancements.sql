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

CALL _AddCol('qc_inspection', 'disposition',      "VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `status`");
CALL _AddCol('qc_inspection', 'change_slip_no',   "VARCHAR(100) NULL AFTER `disposition`");
CALL _AddCol('qc_inspection', 'change_slip_date', "DATE NULL AFTER `change_slip_no`");

CALL _AddCol('grn', 'ticket_id',             "INT UNSIGNED NULL AFTER `shortage_qty`");
CALL _AddCol('grn', 'polybag_qty_allocated', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER `ticket_id`");
CALL _AddCol('grn', 'putaway_status',        "VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `polybag_qty_allocated`");
CALL _AddCol('grn', 'putaway_completed_at',  "DATETIME NULL AFTER `putaway_status`");

DROP PROCEDURE IF EXISTS _AddCol;
