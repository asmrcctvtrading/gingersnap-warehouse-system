-- =============================================================
-- Gingersnaps Warehouse — Sprint 6 Migration
-- =============================================================
-- PURPOSE
--   Adds permissions for the dashboard and reports modules,
--   used by the new Management Dashboard and enhanced Reports
--   section introduced in Sprint 6.
--
-- SAFE TO RE-RUN ON PRODUCTION
--   All INSERT statements use INSERT IGNORE — duplicate rows
--   are silently skipped; no data is overwritten.
--   SET FOREIGN_KEY_CHECKS wrapper prevents FK errors during
--   the permission inserts.
--
-- HOW TO APPLY
--   cPanel phpMyAdmin → select gingersnaps_db →
--   Import tab → choose this file → Go
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─── Step 1: Ensure permission rows exist ────────────────────
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('dashboard', 'view',   'View Dashboard'),
  ('reports',   'view',   'View Reports'),
  ('reports',   'export', 'Export Reports');

-- ─── Step 2: Admin (1) + Warehouse Manager (2) ───────────────
--   Full access to all dashboard and reports permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id IN (1, 2)
    AND  p.module_key IN ('dashboard', 'reports');

-- ─── Step 3: Coordinator (3) ─────────────────────────────────
--   dashboard.view + reports.view only (no export)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 3
    AND  p.module_key IN ('dashboard', 'reports')
    AND  p.action_key = 'view';

-- ─── Step 4: QC Inspector (4) ────────────────────────────────
--   dashboard.view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 4
    AND  p.module_key = 'dashboard'
    AND  p.action_key = 'view';

-- ─── Step 5: Viewer (5) ──────────────────────────────────────
--   dashboard.view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM   `roles` r
  CROSS JOIN `permissions` p
  WHERE  r.role_id = 5
    AND  p.module_key = 'dashboard'
    AND  p.action_key = 'view';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- End of Sprint 6 Migration
-- =============================================================
