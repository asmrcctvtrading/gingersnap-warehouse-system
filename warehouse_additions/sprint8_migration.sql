-- =============================================================
-- Gingersnaps Warehouse — Sprint 8 Migration
-- Production Polish: Audit Log, Subcon Enhancements, Permissions
-- =============================================================
-- SAFE TO RE-RUN ON PRODUCTION
--   • CREATE TABLE IF NOT EXISTS — silently skipped if exists
--   • ALTER TABLE ... ADD COLUMN IF NOT EXISTS — idempotent
--   • INSERT IGNORE — silently skips duplicate permission rows
--   • SET FOREIGN_KEY_CHECKS wraps DDL section only
-- HOW TO APPLY
--   cPanel phpMyAdmin → select gingersnaps_db →
--   Import tab → choose this file → Go
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1a. Audit Log Table
-- Stores who did what, when, from where.
-- Never truncated or deleted by the application.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `log_id`      BIGINT       NOT NULL AUTO_INCREMENT,
  `user_id`     INT          DEFAULT NULL,
  `username`    VARCHAR(100) DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `module`      VARCHAR(100) NOT NULL,
  `record_id`   INT          DEFAULT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user`    (`user_id`),
  KEY `idx_audit_module`  (`module`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 1b. Enhance subcons table with contact / status columns.
-- Existing 73 rows will default to status='active'.
-- ADD COLUMN IF NOT EXISTS is safe to re-run.
-- -------------------------------------------------------------
ALTER TABLE `subcons`
  ADD COLUMN IF NOT EXISTS `contact_person` VARCHAR(255) DEFAULT NULL     AFTER `subcon_name`,
  ADD COLUMN IF NOT EXISTS `phone`          VARCHAR(50)  DEFAULT NULL     AFTER `contact_person`,
  ADD COLUMN IF NOT EXISTS `address`        TEXT         DEFAULT NULL     AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `status`         ENUM('active','inactive')
                                            NOT NULL DEFAULT 'active'     AFTER `address`,
  ADD COLUMN IF NOT EXISTS `created_at`     TIMESTAMP
                                            NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- 1c. Permissions for Sprint 8 modules
--     All INSERTs use INSERT IGNORE — safe to re-run.
-- =============================================================

-- subcons module (new module key — separate from the existing
-- 'subcontractors' key that was added in migration8.sql)
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('subcons', 'view',   'View Subcontractors'),
  ('subcons', 'create', 'Create Subcontractor'),
  ('subcons', 'edit',   'Edit Subcontractor');

-- items module (already in migration8.sql; INSERT IGNORE skips duplicates)
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('items', 'view',   'View Items/SKUs'),
  ('items', 'create', 'Create Item/SKU'),
  ('items', 'edit',   'Edit Item/SKU');

-- audit module (new in Sprint 8)
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('audit', 'view', 'View Audit Log');

-- profile module (new in Sprint 8)
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('profile', 'view', 'View Own Profile'),
  ('profile', 'edit', 'Edit Own Profile');

-- =============================================================
-- Role assignments
-- =============================================================

-- Roles 1 (Admin) + 2 (Warehouse Manager): full access to
-- subcons, items, and audit modules
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2)
    AND p.module_key IN ('subcons', 'items', 'audit');

-- Role 3 (Coordinator): view-only on subcons + items
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id = 3
    AND p.module_key IN ('subcons', 'items')
    AND p.action_key = 'view';

-- Roles 4 (QC) + 5 (Viewer): items view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (4, 5)
    AND p.module_key = 'items'
    AND p.action_key = 'view';

-- All roles (1–5): profile.view + profile.edit
-- Every user can manage their own profile
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2, 3, 4, 5)
    AND p.module_key = 'profile';

-- =============================================================
-- End of Sprint 8 Migration
-- =============================================================
