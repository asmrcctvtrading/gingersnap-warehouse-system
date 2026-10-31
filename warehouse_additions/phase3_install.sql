-- =============================================================
-- Gingersnaps Warehouse — Phase 3 Migration
-- DBASED Coordinators, WIP Tracking, Dispatching Priority,
-- Destination Allocations
-- DB: yitrmcft_gingersnaps
-- =============================================================
-- SAFE TO RE-RUN ON PRODUCTION
--   • DROP/CREATE PROCEDURE _AddCol  — helper rebuilt each run
--   • CREATE TABLE IF NOT EXISTS     — silently skipped if exists
--   • _AddCol procedure              — skips ALTER if column exists
--   • CREATE INDEX IF NOT EXISTS     — silently skipped if exists
--   • INSERT IGNORE                  — skips duplicate rows
--   • FK guards use IF() + PREPARE   — skipped if constraint exists
--   • SET FOREIGN_KEY_CHECKS wraps DDL section only
-- HOW TO APPLY
--   cPanel phpMyAdmin → select yitrmcft_gingersnaps →
--   Import tab → choose this file → Go
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================
-- Helper: _AddCol
-- Idempotent ALTER TABLE ADD COLUMN via information_schema check.
-- Safe to call multiple times on the same table/column pair.
-- =============================================================
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

-- =============================================================
-- Section 1: coordinators lookup table
-- New table — stores the 5 known DBASED coordinators.
-- =============================================================
CREATE TABLE IF NOT EXISTS `coordinators` (
  `coordinator_id` INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(100)  NOT NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`coordinator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed known coordinators; INSERT IGNORE skips on re-run.
INSERT IGNORE INTO `coordinators` (`coordinator_id`, `name`) VALUES
  (1, 'JUDITH'),
  (2, 'GLENDA'),
  (3, 'VIVIAN'),
  (4, 'BETH'),
  (5, 'ROPHA');

-- =============================================================
-- Section 2: ALTER purchase_orders — DBASED columns
-- coordinator_id FK is guarded; other cols use _AddCol.
-- =============================================================
CALL _AddCol('purchase_orders', 'coordinator_id',
  'INT UNSIGNED NULL COMMENT "FK → coordinators"');

CALL _AddCol('purchase_orders', 'receiving_priority',
  'INT UNSIGNED NULL COMMENT "legacy DBASED col E"');

CALL _AddCol('purchase_orders', 'inspection_priority',
  'INT UNSIGNED NULL COMMENT "legacy DBASED col Z"');

CALL _AddCol('purchase_orders', 'po_number_alias',
  'VARCHAR(100) NULL COMMENT "strip -1 suffix for legacy lookup, e.g. IBPA0444_WFR"');

-- FK: purchase_orders.coordinator_id → coordinators.coordinator_id ON DELETE SET NULL
SET @_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME    = 'purchase_orders'
    AND CONSTRAINT_NAME = 'fk_po_coordinator'
);
SET @_fk_sql = IF(@_fk_exists = 0,
  'ALTER TABLE `purchase_orders` ADD CONSTRAINT `fk_po_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinators` (`coordinator_id`) ON DELETE SET NULL',
  'SELECT "fk_po_coordinator already exists" AS _skip'
);
PREPARE _fk FROM @_fk_sql; EXECUTE _fk; DEALLOCATE PREPARE _fk;

-- =============================================================
-- Section 3: ALTER tickets — coordinator, placeholder, dispatch FK col
-- dispatching_priority_id FK is wired in Section 6 after that
-- table is created.
-- =============================================================
CALL _AddCol('tickets', 'coordinator_id',
  'INT UNSIGNED NULL COMMENT "FK → coordinators"');

CALL _AddCol('tickets', 'po_placeholder',
  'VARCHAR(200) NULL COMMENT "free-text: PO NOT YET CREATED CHECK WITH MPCD"');

CALL _AddCol('tickets', 'dispatching_priority_id',
  'INT UNSIGNED NULL COMMENT "FK → dispatching_priority; wired after table creation"');

-- FK: tickets.coordinator_id → coordinators.coordinator_id ON DELETE SET NULL
SET @_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME    = 'tickets'
    AND CONSTRAINT_NAME = 'fk_ticket_coordinator'
);
SET @_fk_sql = IF(@_fk_exists = 0,
  'ALTER TABLE `tickets` ADD CONSTRAINT `fk_ticket_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinators` (`coordinator_id`) ON DELETE SET NULL',
  'SELECT "fk_ticket_coordinator already exists" AS _skip'
);
PREPARE _fk FROM @_fk_sql; EXECUTE _fk; DEALLOCATE PREPARE _fk;

-- =============================================================
-- Section 4: wip_transactions table
-- Ledger for WIP IN/OUT movements across RECEIVING, QC,
-- and DISPATCHING stages.
-- =============================================================
CREATE TABLE IF NOT EXISTS `wip_transactions` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `transaction_date` DATE          NOT NULL,
  `stage`            ENUM('RECEIVING','QC','DISPATCHING') NOT NULL,
  `direction`        ENUM('IN','OUT') NOT NULL,
  `qty`              INT           NOT NULL DEFAULT 0,
  `ctrl_number`      VARCHAR(30)   NULL,
  `ticket_number`    VARCHAR(30)   NULL,
  `dr_number`        VARCHAR(30)   NULL,
  `subcon_name`      VARCHAR(100)  NULL,
  `collection_name`  VARCHAR(100)  NULL,
  `style_code`       VARCHAR(50)   NULL,
  `po_number`        VARCHAR(100)  NULL,
  `local_export`     ENUM('LOCAL','EXPORT') NULL,
  `remarks`          VARCHAR(200)  NULL,
  `created_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_wip_date_stage` (`transaction_date`, `stage`),
  INDEX `idx_wip_stage_dir`  (`stage`, `direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Section 5: wip_period_snapshots table
-- One row per (snapshot_date, stage) for opening/closing EWIP.
-- UNIQUE KEY prevents accidental duplicates.
-- =============================================================
CREATE TABLE IF NOT EXISTS `wip_period_snapshots` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `snapshot_date` DATE          NOT NULL,
  `stage`         ENUM('RECEIVING','QC','DISPATCHING') NOT NULL,
  `ewip_qty`      INT           NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_snapshot` (`snapshot_date`, `stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Section 6: dispatching_priority table
-- Tracks dispatch queue per ctrl number with destination qty
-- breakdown. qty_total is a generated stored column.
-- =============================================================
CREATE TABLE IF NOT EXISTS `dispatching_priority` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `gatepass_order`   INT           NULL,
  `local_export`     ENUM('LOCAL','EXPORT') NULL,
  `ctrl_number`      VARCHAR(30)   NULL,
  `gatepass_created` ENUM('DONE','PENDING') NOT NULL DEFAULT 'PENDING',
  `received_at_dc`   ENUM('Y','N') NULL,
  `gatepass_qty`     INT           NULL DEFAULT 0,
  `status`           ENUM('NEW','DONE','CANCELLED') NOT NULL DEFAULT 'NEW',
  `remarks`          ENUM('GOODS','RSS') NULL,
  `ticket_number`    VARCHAR(30)   NULL,
  `subcon_name`      VARCHAR(100)  NULL,
  `collection_name`  VARCHAR(100)  NULL,
  `qty_local`        INT           NOT NULL DEFAULT 0,
  `qty_sg`           INT           NOT NULL DEFAULT 0,
  `qty_malaysia`     INT           NOT NULL DEFAULT 0,
  `qty_indo`         INT           NOT NULL DEFAULT 0,
  `qty_sm_baliwag`   INT           NOT NULL DEFAULT 0,
  `qty_export_full`  INT           NOT NULL DEFAULT 0,
  `qty_total`        INT GENERATED ALWAYS AS
                       (`qty_local` + `qty_sg` + `qty_malaysia` + `qty_indo` + `qty_sm_baliwag`)
                       STORED,
  `priority_rank`    INT           NULL,
  `created_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_dp_status`   (`status`),
  INDEX `idx_dp_priority` (`priority_rank`),
  INDEX `idx_dp_ctrl`     (`ctrl_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now that dispatching_priority exists, wire the FK on tickets.
SET @_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME    = 'tickets'
    AND CONSTRAINT_NAME = 'fk_ticket_dispatch_priority'
);
SET @_fk_sql = IF(@_fk_exists = 0,
  'ALTER TABLE `tickets` ADD CONSTRAINT `fk_ticket_dispatch_priority` FOREIGN KEY (`dispatching_priority_id`) REFERENCES `dispatching_priority` (`id`) ON DELETE SET NULL',
  'SELECT "fk_ticket_dispatch_priority already exists" AS _skip'
);
PREPARE _fk FROM @_fk_sql; EXECUTE _fk; DEALLOCATE PREPARE _fk;

-- =============================================================
-- Section 7: po_destination_allocations table
-- One allocation row per PO (UNIQUE KEY uq_po_alloc).
-- qty_total is a generated stored column (excludes export_full).
-- =============================================================
CREATE TABLE IF NOT EXISTS `po_destination_allocations` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `po_id`            INT UNSIGNED  NOT NULL,
  `qty_local`        INT           NOT NULL DEFAULT 0,
  `qty_sg`           INT           NOT NULL DEFAULT 0,
  `qty_malaysia`     INT           NOT NULL DEFAULT 0,
  `qty_indo`         INT           NOT NULL DEFAULT 0,
  `qty_other_export` INT           NOT NULL DEFAULT 0,
  `qty_sm_baliwag`   INT           NOT NULL DEFAULT 0,
  `qty_export_full`  INT           NOT NULL DEFAULT 0,
  `qty_total`        INT GENERATED ALWAYS AS
                       (`qty_local` + `qty_sg` + `qty_malaysia` + `qty_indo` + `qty_other_export` + `qty_sm_baliwag`)
                       STORED,
  `notes`            VARCHAR(255)  NULL,
  `created_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_po_alloc` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Section 8: Permissions for Phase 3 modules
-- All INSERTs use INSERT IGNORE — safe to re-run.
-- =============================================================
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('dbased',                 'view',    'View DBASED'),
  ('dbased',                 'create',  'Create DBASED Entry'),
  ('dbased',                 'edit',    'Edit DBASED Entry'),
  ('wip',                    'view',    'View WIP Matrix'),
  ('wip',                    'create',  'Create WIP Entry'),
  ('dispatching_priority',   'view',    'View Dispatching Priority'),
  ('dispatching_priority',   'create',  'Create Dispatching Priority'),
  ('dispatching_priority',   'edit',    'Edit Dispatching Priority'),
  ('destination_allocation', 'view',    'View Destination Allocation'),
  ('destination_allocation', 'create',  'Create Destination Allocation');

-- Grant all Phase 3 permissions to Admin (role_id=1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 1, `permission_id` FROM `permissions`
  WHERE `module_key` IN ('dbased','wip','dispatching_priority','destination_allocation');

-- Grant all Phase 3 permissions to Warehouse Manager (role_id=2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 2, `permission_id` FROM `permissions`
  WHERE `module_key` IN ('dbased','wip','dispatching_priority','destination_allocation');

-- =============================================================
-- Section 9: Cleanup — drop the helper procedure
-- =============================================================
DROP PROCEDURE IF EXISTS _AddCol;

-- =============================================================
-- End of Phase 3 Migration
-- =============================================================
