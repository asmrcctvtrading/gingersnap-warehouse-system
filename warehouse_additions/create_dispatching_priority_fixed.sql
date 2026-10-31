-- Dispatching Priority Table Creation
-- Run this in phpMyAdmin
-- Database: yitrmcft_gingersnaps

CREATE TABLE IF NOT EXISTS `dispatching_priority` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ctrl_number` VARCHAR(50) NOT NULL,
  `po_number` VARCHAR(100) NULL,
  `style_no` VARCHAR(100) NULL,
  `po_date` DATE NULL,
  `priority_rank` INT UNSIGNED NULL,
  `gatepass_order` VARCHAR(30) NULL COMMENT 'Text codes not integers',
  `gatepass_qty` INT NULL,
  `qty_local` INT NULL,
  `qty_sg` INT NULL,
  `qty_malaysia` INT NULL,
  `qty_indo` INT NULL,
  `qty_sm_baliwag` INT NULL,
  `status` ENUM('NEW','DONE','CANCELLED','WIP') NOT NULL DEFAULT 'NEW',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ctrl_number` (`ctrl_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test if table was created successfully
SELECT 'Table created successfully' AS status;
