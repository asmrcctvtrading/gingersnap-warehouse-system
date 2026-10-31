-- =============================================================
-- Gingersnaps Warehouse — Sprint 7 Migration
-- =============================================================
-- PURPOSE
--   1. Adds email column to users table (safe if already present)
--   2. Adds indexes on users.role_id and users.status
--   3. Inserts permissions for users and roles modules
--   4. Assigns permissions to roles:
--        Admin (1)             — all users + roles permissions
--        Warehouse Manager (2) — users.view only
--        Roles 3, 4, 5         — no user management access
--
-- SAFE TO RE-RUN ON PRODUCTION
--   All INSERT statements use INSERT IGNORE.
--   Column and index additions use INFORMATION_SCHEMA checks
--   and are compatible with MySQL 5.7 and 8.x.
--
-- HOW TO APPLY
--   cPanel phpMyAdmin -> select gingersnaps_db ->
--   Import tab -> choose this file -> Go
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─── Step 1: Add email column to users if not present ─────────
SET @dbname = DATABASE();

SET @has_email = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'email'
);
SET @sql_email = IF(
    @has_email > 0,
    'SELECT 1 -- email column already exists',
    'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(150) DEFAULT NULL AFTER `full_name`'
);
PREPARE stmt_email FROM @sql_email;
EXECUTE stmt_email;
DEALLOCATE PREPARE stmt_email;

-- ─── Step 2: Add index on users.role_id if not present ────────
SET @has_idx_role = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME   = 'users'
      AND INDEX_NAME   = 'idx_users_role'
);
SET @sql_idx_role = IF(
    @has_idx_role > 0,
    'SELECT 1 -- idx_users_role already exists',
    'ALTER TABLE `users` ADD INDEX `idx_users_role` (`role_id`)'
);
PREPARE stmt_idx_role FROM @sql_idx_role;
EXECUTE stmt_idx_role;
DEALLOCATE PREPARE stmt_idx_role;

-- ─── Step 3: Add index on users.status if not present ─────────
SET @has_idx_status = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME   = 'users'
      AND INDEX_NAME   = 'idx_users_status'
);
SET @sql_idx_status = IF(
    @has_idx_status > 0,
    'SELECT 1 -- idx_users_status already exists',
    'ALTER TABLE `users` ADD INDEX `idx_users_status` (`status`)'
);
PREPARE stmt_idx_status FROM @sql_idx_status;
EXECUTE stmt_idx_status;
DEALLOCATE PREPARE stmt_idx_status;

SET FOREIGN_KEY_CHECKS = 1;

-- ─── Step 4: Permissions for user management modules ──────────
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('users', 'view',   'View Users'),
  ('users', 'create', 'Create User'),
  ('users', 'edit',   'Edit User'),
  ('users', 'delete', 'Deactivate User'),
  ('roles', 'view',   'View Roles & Permissions'),
  ('roles', 'edit',   'Edit Role Permissions');

-- ─── Step 5: Admin (1) — ALL users + roles permissions ────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 1
    AND  p.module_key IN ('users', 'roles');

-- ─── Step 6: Warehouse Manager (2) — users.view only ──────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 2
    AND  p.module_key = 'users'
    AND  p.action_key = 'view';

-- Roles 3 (Coordinator), 4 (QC), 5 (Viewer): no user management access.
-- No INSERT needed for those roles.

-- =============================================================
-- End of Sprint 7 Migration
-- =============================================================
