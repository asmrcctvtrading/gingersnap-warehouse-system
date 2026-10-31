-- ============================================================
-- Gingersnaps Warehouse — Sprint 5 Migration
-- Gatepass Module: full CRUD + schema corrections
-- ============================================================
-- PURPOSE
--   1. Expand gatepasses.gp_type ENUM to include new
--      task-defined values alongside legacy values from import
--   2. Expand gatepasses.gp_status ENUM to include OPEN and
--      CANCELLED (original schema only had CREATED and CLOSED)
--   3. Expand gatepasses.gp_code to VARCHAR(50) so the new
--      GP-YYYYMMDD-NNNN format never hits the 20-char limit
--   4. Migrate existing CREATED records to OPEN status
--   5. Insert gatepass permissions (INSERT IGNORE — safe even
--      though migration9.sql already inserted them with slightly
--      different labels; duplicate (module_key, action_key)
--      rows are silently skipped)
--   6. Add performance indexes on gatepasses
--
-- NOTE ON EXISTING PERMISSIONS
--   migration9.sql already inserted gatepass permissions and
--   role_permissions. All INSERTs here use INSERT IGNORE so
--   they will be skipped if already present. Role 4 (QC) was
--   given 'create' permission in migration9.sql; the task
--   spec wants QC to have view-only. That cannot be corrected
--   with INSERT IGNORE — a manual DELETE is needed if desired:
--     DELETE rp FROM role_permissions rp
--     JOIN permissions p ON p.permission_id = rp.permission_id
--     WHERE rp.role_id = 4
--       AND p.module_key = 'gatepass'
--       AND p.action_key = 'create';
--
-- SAFE TO RE-RUN ON PRODUCTION
--   • ALTER TABLE MODIFY COLUMN: idempotent in effect — a
--     second run modifies the column definition to the same
--     target state; no data loss occurs.
--   • UPDATE … WHERE gp_status='CREATED': affects 0 rows on
--     a second run since records have already been migrated.
--   • INSERT IGNORE: silently skips duplicates.
--   • CREATE INDEX IF NOT EXISTS: skipped if already present.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- Step 1: Expand gp_code — current VARCHAR(20) is tight for
-- the new GP-YYYYMMDD-NNNN format (16 chars) and will overflow
-- if gp_id ever exceeds 9999.
-- ────────────────────────────────────────────────────────────
ALTER TABLE `gatepasses`
  MODIFY COLUMN `gp_code` VARCHAR(50) NOT NULL;

-- ────────────────────────────────────────────────────────────
-- Step 2: Expand gp_type ENUM.
-- Legacy import values: STOCK_TRANSFER, INTERNAL_CONSUMPTION
-- New task values:      OUTGOING, INCOMING, RETURN, TRANSFER
-- RETURNABLE kept for any records that may use it.
-- ────────────────────────────────────────────────────────────
ALTER TABLE `gatepasses`
  MODIFY COLUMN `gp_type`
    ENUM('OUTGOING','INCOMING','RETURN','TRANSFER',
         'STOCK_TRANSFER','INTERNAL_CONSUMPTION','RETURNABLE')
    NOT NULL DEFAULT 'OUTGOING';

-- ────────────────────────────────────────────────────────────
-- Step 3: Expand gp_status ENUM.
-- CREATED kept for backward compat; OPEN and CANCELLED added.
-- Default changed to OPEN (new records created by Sprint 5
-- PHP will use OPEN status).
-- ────────────────────────────────────────────────────────────
ALTER TABLE `gatepasses`
  MODIFY COLUMN `gp_status`
    ENUM('OPEN','CLOSED','CANCELLED','CREATED')
    NOT NULL DEFAULT 'OPEN';

-- ────────────────────────────────────────────────────────────
-- Step 4: Migrate legacy CREATED records to OPEN.
-- All imported data has CLOSED status so this only affects
-- any records created via the old PHP UI (if any).
-- ────────────────────────────────────────────────────────────
UPDATE `gatepasses`
  SET `gp_status` = 'OPEN'
  WHERE `gp_status` = 'CREATED';

-- ────────────────────────────────────────────────────────────
-- Step 5: Ensure permission rows exist
-- (migration9.sql already inserted these; INSERT IGNORE is safe)
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('gatepass', 'view',   'View Gatepasses'),
  ('gatepass', 'create', 'Create Gatepass'),
  ('gatepass', 'edit',   'Edit Gatepass'),
  ('gatepass', 'close',  'Close/Cancel Gatepass');

-- Step 5a: Full access — Admin (1) and Warehouse Manager (2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (1, 2)
    AND  p.module_key = 'gatepass';

-- Step 5b: View + Create — Coordinator (3)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 3
    AND  p.module_key = 'gatepass'
    AND  p.action_key IN ('view', 'create');

-- Step 5c: View-only — QC (4) and Viewer (5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (4, 5)
    AND  p.module_key = 'gatepass'
    AND  p.action_key = 'view';

-- ────────────────────────────────────────────────────────────
-- Step 6: Performance indexes
-- ────────────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_gp_status  ON `gatepasses` (`gp_status`);
CREATE INDEX IF NOT EXISTS idx_gp_type    ON `gatepasses` (`gp_type`);
CREATE INDEX IF NOT EXISTS idx_gp_created ON `gatepasses` (`created_at`);
CREATE INDEX IF NOT EXISTS idx_gp_closed  ON `gatepasses` (`closed_at`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- End of Sprint 5 Migration
-- ============================================================
