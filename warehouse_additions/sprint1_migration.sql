-- =============================================================
-- Gingersnaps Warehouse — Sprint 1 Migration
-- =============================================================
-- PURPOSE
--   1. Creates stub table `receiving`      (DEV-002)
--   2. Creates stub table `qc_inspection`  (DEV-003)
--   3. Inserts missing permissions for the `locations` and
--      `stock` modules that were never applied to production
--      because migration6.sql aborted before reaching the
--      INSERT section  (DEV-001)
--
-- SAFE TO RE-RUN ON PRODUCTION
--   • All CREATE TABLE statements use IF NOT EXISTS — they are
--     skipped silently if the table already exists.
--   • All INSERT statements use INSERT IGNORE — duplicate rows
--     are silently skipped; no data is overwritten.
--   • SET FOREIGN_KEY_CHECKS=0 allows `receiving` and
--     `qc_inspection` to be created before the `grn` table
--     (which arrives in Sprint 2) without FK errors.
--
-- HOW TO APPLY
--   cPanel phpMyAdmin → select gingersnaps_db →
--   Import tab → choose this file → Go
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- DEV-002: Stub table — receiving
-- -------------------------------------------------------------
-- Tracks quantities received against purchase orders.
-- grn_id column is a placeholder; the grn table and FK will
-- be wired up in Sprint 2.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `receiving` (
  `receiving_id`  INT          NOT NULL AUTO_INCREMENT,
  `po_id`         INT          NOT NULL,
  `grn_id`        INT          DEFAULT NULL,    -- FK to grn added in Sprint 2
  `received_qty`  INT          NOT NULL DEFAULT 0,
  `received_date` DATE         NOT NULL,
  `received_by`   INT          NOT NULL,        -- FK to users
  `notes`         TEXT         DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receiving_id`),
  KEY `idx_recv_po`   (`po_id`),
  KEY `idx_recv_date` (`received_date`),
  KEY `idx_recv_by`   (`received_by`),
  CONSTRAINT `recv_ibfk_1` FOREIGN KEY (`po_id`)       REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `recv_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- DEV-003: Stub table — qc_inspection
-- -------------------------------------------------------------
-- Tracks QC results per received batch.
-- grn_id and grn_item_id are placeholders; the grn table and
-- FKs will be wired up in Sprint 2.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qc_inspection` (
  `inspection_id`   INT          NOT NULL AUTO_INCREMENT,
  `grn_id`          INT          NOT NULL,       -- FK to grn added in Sprint 2
  `grn_item_id`     INT          DEFAULT NULL,   -- FK to grn_items added in Sprint 2
  `item_id`         INT          DEFAULT NULL,   -- FK to items
  `inspection_date` DATE         NOT NULL,
  `inspected_qty`   INT          NOT NULL DEFAULT 0,
  `passed_qty`      INT          NOT NULL DEFAULT 0,
  `failed_qty`      INT          NOT NULL DEFAULT 0,
  `qc_notes`        TEXT         DEFAULT NULL,
  `inspected_by`    INT          NOT NULL,       -- FK to users
  `status`          ENUM('PENDING','IN_PROGRESS','PASSED','FAILED','PARTIAL') NOT NULL DEFAULT 'PENDING',
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`inspection_id`),
  KEY `idx_qci_date`        (`inspection_date`),
  KEY `idx_qci_item`        (`item_id`),
  KEY `idx_qci_inspected_by`(`inspected_by`),
  CONSTRAINT `qci_ibfk_1` FOREIGN KEY (`item_id`)      REFERENCES `items` (`item_id`) ON DELETE SET NULL,
  CONSTRAINT `qci_ibfk_2` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- DEV-001: Missing permissions for `locations` and `stock`
-- modules — extracted from migration6.sql, never applied.
-- INSERT IGNORE makes this block safe to re-run.
-- -------------------------------------------------------------

-- Step A: Ensure permission rows exist in the permissions table
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('locations', 'view',   'View Warehouse Locations'),
  ('locations', 'create', 'Create Warehouse Locations'),
  ('locations', 'edit',   'Edit Warehouse Locations'),
  ('locations', 'delete', 'Delete Warehouse Locations'),
  ('stock',     'view',   'View Stock Positions'),
  ('stock',     'create', 'Record Stock Movement'),
  ('stock',     'edit',   'Adjust Stock'),
  ('stock',     'delete', 'Delete Stock Movement');

-- Step B: Full access to Admin (role 1) and Warehouse Manager (role 2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (1, 2)
    AND  p.module_key IN ('locations', 'stock');

-- Step C: View-only access to Coordinator (3), QC (4), Viewer (5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (3, 4, 5)
    AND  p.module_key IN ('locations', 'stock')
    AND  p.action_key = 'view';

-- =============================================================
-- End of Sprint 1 Migration
-- =============================================================
