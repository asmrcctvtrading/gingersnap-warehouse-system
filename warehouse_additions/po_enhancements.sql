DROP PROCEDURE IF EXISTS _AddCol;
DELIMITER $$
CREATE PROCEDURE _AddCol(tbl VARCHAR(64), col VARCHAR(64), def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

-- Add to purchase_orders:
CALL _AddCol('purchase_orders', 'jo_number',          'VARCHAR(100) NULL AFTER po_number');
CALL _AddCol('purchase_orders', 'jo_date',            'DATE NULL AFTER jo_number');
CALL _AddCol('purchase_orders', 'export_destination', "ENUM('LOCAL','SG','MALAYSIA','INDO','SAUDI','SM BALIWAG') NULL AFTER market_type");

DROP PROCEDURE IF EXISTS _AddCol;
