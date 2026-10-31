-- =============================================================
-- Gingersnaps Warehouse — Sprint 3 Migration
-- =============================================================
-- PURPOSE
--   1. Ensures qc_inspection has all columns needed by the
--      QC Inspection module (Sprint 3).
--      (Sprint 1 stub already defines these; ADD COLUMN IF NOT
--      EXISTS is a no-op when columns already exist.)
--   2. Cleans up Sprint 1 anonymous FK names and replaces them
--      with explicit names used by Sprint 3 module code.
--   3. Adds FK: qci_ibfk_grn_item  (grn_item_id → grn_items)
--   4. Adds UNIQUE key on grn_item_id to support upsert in
--      qc/action.php via INSERT ... ON DUPLICATE KEY UPDATE.
--   5. Adds indexes on inspection_date and status.
--   6. Inserts RBAC permissions for the `qc` module.
--
-- SAFE TO RE-RUN
--   • ADD COLUMN IF NOT EXISTS  — no-op if column already exists
--   • DROP FOREIGN KEY IF EXISTS — no-op if FK not present
--   • DROP INDEX IF EXISTS        — no-op if index not present
--   • CREATE INDEX IF NOT EXISTS  — no-op if index already exists
--   • INSERT IGNORE               — skips duplicate rows
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Step 1: Ensure all required columns exist
-- (Sprint 1 stub already created these; this is a safety net
--  for any deployment where Sprint 1 was skipped or partial.)
-- -------------------------------------------------------------
ALTER TABLE `qc_inspection`
  ADD COLUMN IF NOT EXISTS `grn_item_id`     INT          DEFAULT NULL              AFTER `grn_id`,
  ADD COLUMN IF NOT EXISTS `item_id`         INT          DEFAULT NULL              AFTER `grn_item_id`,
  ADD COLUMN IF NOT EXISTS `inspection_date` DATE         NOT NULL DEFAULT (CURDATE()) AFTER `item_id`,
  ADD COLUMN IF NOT EXISTS `inspected_qty`   INT          NOT NULL DEFAULT 0        AFTER `inspection_date`,
  ADD COLUMN IF NOT EXISTS `passed_qty`      INT          NOT NULL DEFAULT 0        AFTER `inspected_qty`,
  ADD COLUMN IF NOT EXISTS `failed_qty`      INT          NOT NULL DEFAULT 0        AFTER `passed_qty`,
  ADD COLUMN IF NOT EXISTS `qc_notes`        TEXT         DEFAULT NULL              AFTER `failed_qty`,
  ADD COLUMN IF NOT EXISTS `inspected_by`    INT          NOT NULL DEFAULT 1        AFTER `qc_notes`,
  ADD COLUMN IF NOT EXISTS `status`          VARCHAR(20)  NOT NULL DEFAULT 'PENDING' AFTER `inspected_by`;

-- -------------------------------------------------------------
-- Step 2: UNIQUE key on grn_item_id to enable upserts
-- (DROP first makes this step idempotent on re-run)
-- -------------------------------------------------------------
ALTER TABLE `qc_inspection` DROP INDEX IF EXISTS `uq_qci_grn_item`;
ALTER TABLE `qc_inspection`
  ADD UNIQUE KEY `uq_qci_grn_item` (`grn_item_id`);

-- -------------------------------------------------------------
-- Step 3: Replace Sprint 1 anonymous FK names with explicit ones
-- Sprint 1 created qci_ibfk_1 (item_id) and qci_ibfk_2 (inspected_by).
-- Drop them so we can re-add with canonical names.
-- -------------------------------------------------------------
ALTER TABLE `qc_inspection` DROP FOREIGN KEY IF EXISTS `qci_ibfk_1`;
ALTER TABLE `qc_inspection` DROP FOREIGN KEY IF EXISTS `qci_ibfk_2`;

-- item_id → items
ALTER TABLE `qc_inspection` DROP FOREIGN KEY IF EXISTS `qci_ibfk_item`;
ALTER TABLE `qc_inspection`
  ADD CONSTRAINT `qci_ibfk_item`
  FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE SET NULL;

-- grn_item_id → grn_items  (new in Sprint 3)
ALTER TABLE `qc_inspection` DROP FOREIGN KEY IF EXISTS `qci_ibfk_grn_item`;
ALTER TABLE `qc_inspection`
  ADD CONSTRAINT `qci_ibfk_grn_item`
  FOREIGN KEY (`grn_item_id`) REFERENCES `grn_items` (`grn_item_id`) ON DELETE SET NULL;

-- inspected_by → users
ALTER TABLE `qc_inspection` DROP FOREIGN KEY IF EXISTS `qci_ibfk_inspected_by`;
ALTER TABLE `qc_inspection`
  ADD CONSTRAINT `qci_ibfk_inspected_by`
  FOREIGN KEY (`inspected_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT;

-- -------------------------------------------------------------
-- Step 4: Indexes (IF NOT EXISTS is MariaDB/MySQL 8 syntax)
-- -------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_qci_grn`    ON `qc_inspection` (`grn_id`);
CREATE INDEX IF NOT EXISTS `idx_qci_date`   ON `qc_inspection` (`inspection_date`);
CREATE INDEX IF NOT EXISTS `idx_qci_status` ON `qc_inspection` (`status`);

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- Step 5: RBAC — permissions for the `qc` module
-- -------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('qc', 'view',    'View QC Inspections'),
  ('qc', 'inspect', 'Perform QC Inspection'),
  ('qc', 'approve', 'Approve/Reject QC Results');

-- Admin (1) and Warehouse Manager (2): all 3 qc permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (1, 2)
    AND  p.module_key = 'qc';

-- QC Inspector (4): view + inspect
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 4
    AND  p.module_key = 'qc'
    AND  p.action_key IN ('view', 'inspect');

-- Coordinator (3) and Viewer (5): view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (3, 5)
    AND  p.module_key = 'qc'
    AND  p.action_key = 'view';

-- =============================================================
-- End of Sprint 3 Migration
-- =============================================================
