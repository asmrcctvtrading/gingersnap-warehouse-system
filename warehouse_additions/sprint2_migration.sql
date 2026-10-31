-- =============================================================
-- Gingersnaps Warehouse — Sprint 2 Migration
-- =============================================================
-- PURPOSE
--   1. Creates `grn`        — GRN header table
--   2. Creates `grn_items`  — GRN line items
--   3. Wires FK constraints on `receiving` and `qc_inspection`
--      that were left as stubs in Sprint 1
--   4. Inserts RBAC permissions for the grn module
--
-- SAFE-TO-RE-RUN NOTES
--   • CREATE TABLE statements use IF NOT EXISTS — silently
--     skipped if tables already exist.
--   • INSERT statements use INSERT IGNORE — duplicates skipped.
--   • ALTER TABLE (FK constraints) are ONE-TIME only. If this
--     script is run a second time those two statements will
--     fail with "Duplicate foreign key constraint name". That
--     error is benign — it just means the FK already exists.
--     Simply skip those two ALTER statements on any re-run.
--   • SET FOREIGN_KEY_CHECKS=0 lets tables be created/altered
--     in any order without referential-integrity errors on
--     existing rows.
--
-- HOW TO APPLY
--   cPanel phpMyAdmin → select gingersnaps_db →
--   Import tab → choose this file → Go
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. GRN header
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grn` (
  `grn_id`              INT          NOT NULL AUTO_INCREMENT,
  `grn_code`            VARCHAR(50)  NOT NULL UNIQUE,   -- GRN-YYYYMMDD-NNNN
  `grn_date`            DATE         NOT NULL,
  `po_id`               INT          NOT NULL,
  `subcon_id`           INT          DEFAULT NULL,
  `invoice_no`          VARCHAR(100) DEFAULT NULL,
  `invoice_date`        DATE         DEFAULT NULL,
  `gate_entry`          VARCHAR(100) DEFAULT NULL,
  `total_qty_received`  INT          NOT NULL DEFAULT 0,
  `total_qty_rejected`  INT          NOT NULL DEFAULT 0,
  `notes`               TEXT         DEFAULT NULL,
  `status`              ENUM('DRAFT','RECEIVED','QC_IN_PROGRESS','COMPLETED','REJECTED')
                                     NOT NULL DEFAULT 'DRAFT',
  `created_by`          INT          NOT NULL,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`grn_id`),
  KEY `idx_grn_po`       (`po_id`),
  KEY `idx_grn_subcon`   (`subcon_id`),
  KEY `idx_grn_date`     (`grn_date`),
  KEY `idx_grn_status`   (`status`),
  CONSTRAINT `grn_ibfk_po`     FOREIGN KEY (`po_id`)     REFERENCES `purchase_orders` (`po_id`),
  CONSTRAINT `grn_ibfk_subcon` FOREIGN KEY (`subcon_id`) REFERENCES `subcons` (`subcon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. GRN line items
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grn_items` (
  `grn_item_id`       INT          NOT NULL AUTO_INCREMENT,
  `grn_id`            INT          NOT NULL,
  `item_id`           INT          DEFAULT NULL,
  `sku_code`          VARCHAR(100) DEFAULT NULL,
  `item_name`         VARCHAR(255) DEFAULT NULL,
  `color`             VARCHAR(100) DEFAULT NULL,
  `size`              VARCHAR(50)  DEFAULT NULL,
  `brand`             VARCHAR(100) DEFAULT NULL,
  `qty_received`      INT          NOT NULL DEFAULT 0,
  `qty_rejected`      INT          NOT NULL DEFAULT 0,
  `rejection_reason`  TEXT         DEFAULT NULL,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`grn_item_id`),
  KEY `idx_grni_grn`  (`grn_id`),
  KEY `idx_grni_item` (`item_id`),
  CONSTRAINT `grni_ibfk_grn`  FOREIGN KEY (`grn_id`)  REFERENCES `grn` (`grn_id`) ON DELETE CASCADE,
  CONSTRAINT `grni_ibfk_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3a. Add grn_id column to receiving (if not already there),
--     then wire the FK.
--     The Sprint 1 stub was skipped if receiving already existed
--     before Sprint 1 ran, so we add the column defensively.
--     ONE-TIME — skip on re-run if constraint already exists
-- -------------------------------------------------------------
ALTER TABLE `receiving`
  ADD COLUMN IF NOT EXISTS `grn_id` INT DEFAULT NULL AFTER `po_id`;

ALTER TABLE `receiving`
  ADD CONSTRAINT `recv_ibfk_grn`
  FOREIGN KEY (`grn_id`) REFERENCES `grn` (`grn_id`) ON DELETE SET NULL;

-- -------------------------------------------------------------
-- 3b. Wire FK: qc_inspection.grn_id → grn.grn_id
--     ONE-TIME — skip on re-run if constraint already exists
-- -------------------------------------------------------------
ALTER TABLE `qc_inspection`
  ADD CONSTRAINT `qci_ibfk_grn`
  FOREIGN KEY (`grn_id`) REFERENCES `grn` (`grn_id`);

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 4. RBAC — permissions for the grn module
-- -------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('grn', 'view',   'View GRNs'),
  ('grn', 'create', 'Create GRN'),
  ('grn', 'edit',   'Edit GRN'),
  ('grn', 'close',  'Close/Complete GRN');

-- Roles 1 (Admin) and 2 (Warehouse Manager): all 4 grn permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (1, 2)
    AND  p.module_key = 'grn';

-- Roles 3 (Coordinator) and 4 (QC): view + create
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (3, 4)
    AND  p.module_key = 'grn'
    AND  p.action_key IN ('view', 'create');

-- Role 5 (Viewer): view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 5
    AND  p.module_key = 'grn'
    AND  p.action_key = 'view';

-- =============================================================
-- End of Sprint 2 Migration
-- =============================================================
