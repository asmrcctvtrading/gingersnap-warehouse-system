-- RSS (Return/Stock Adjustment) table
-- Tracks items returned for processing or return to vendor

CREATE TABLE IF NOT EXISTS `rss` (
  `rss_id` INT NOT NULL AUTO_INCREMENT,
  `grn_id` INT DEFAULT NULL,
  `item_id` INT DEFAULT NULL,
  `po_id` INT DEFAULT NULL,
  `qty_out` INT NOT NULL DEFAULT 0,
  `qty_in` INT NOT NULL DEFAULT 0,
  `status` ENUM('PENDING','APPROVED','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `rss_type` ENUM('RETURN_VEND','REPAIR','DISPOSE','OTHER') NOT NULL DEFAULT 'OTHER',
  `remarks` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rss_id`),
  KEY `idx_rss_grn` (`grn_id`),
  KEY `idx_rss_item` (`item_id`),
  KEY `idx_rss_po` (`po_id`),
  KEY `idx_rss_status` (`status`),
  KEY `idx_rss_type` (`rss_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
