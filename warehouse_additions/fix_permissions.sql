-- ============================================================
-- Fix: Re-insert all module permissions + grant them to Admin
-- Safe to re-run (INSERT IGNORE skips duplicates)
-- Run in phpMyAdmin → yitrmcft_gingersnaps → Import
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── All module permissions ───────────────────────────────────
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('dashboard',       'view',   'View Dashboard'),
  ('grn',             'view',   'View GRNs'),
  ('grn',             'create', 'Create GRN'),
  ('grn',             'edit',   'Edit GRN'),
  ('grn',             'close',  'Close/Complete GRN'),
  ('qc',              'view',   'View QC Inspections'),
  ('qc',              'create', 'Create QC Inspection'),
  ('qc',              'edit',   'Edit QC Inspection'),
  ('stock',           'view',   'View Stock'),
  ('stock',           'create', 'Create Stock Movement'),
  ('stock',           'edit',   'Edit Stock'),
  ('putaway',         'view',   'View Putaway'),
  ('putaway',         'create', 'Create Putaway'),
  ('gatepass',        'view',   'View Gatepasses'),
  ('gatepass',        'create', 'Create Gatepass'),
  ('gatepass',        'edit',   'Edit Gatepass'),
  ('gatepass',        'close',  'Close Gatepass'),
  ('gatepass',        'cancel', 'Cancel Gatepass'),
  ('reports',         'view',   'View Reports'),
  ('users',           'view',   'View Users'),
  ('users',           'create', 'Create User'),
  ('users',           'edit',   'Edit User'),
  ('users',           'delete', 'Delete User'),
  ('roles',           'view',   'View Roles'),
  ('roles',           'edit',   'Edit Role Permissions'),
  ('subcons',         'view',   'View Subcontractors'),
  ('subcons',         'create', 'Create Subcontractor'),
  ('subcons',         'edit',   'Edit Subcontractor'),
  ('subcons',         'delete', 'Delete Subcontractor'),
  ('items',           'view',   'View Items'),
  ('items',           'create', 'Create Item'),
  ('items',           'edit',   'Edit Item'),
  ('audit',           'view',   'View Audit Log'),
  ('profile',         'view',   'View Profile'),
  ('profile',         'edit',   'Edit Profile'),
  ('receiving',       'view',   'View Receiving'),
  ('receiving',       'create', 'Create Receiving'),
  ('receiving',       'edit',   'Edit Receiving'),
  ('purchase_orders', 'view',   'View Purchase Orders'),
  ('purchase_orders', 'create', 'Create Purchase Order'),
  ('purchase_orders', 'edit',   'Edit Purchase Order'),
  ('locations',       'view',   'View Locations'),
  ('locations',       'create', 'Create Location'),
  ('locations',       'edit',   'Edit Location'),
  ('rss',             'view',   'View RSS'),
  ('alerts',          'view',   'View Alerts'),
  ('priorities',      'view',   'View Priorities');

-- ── Grant ALL permissions to Admin (role_id = 1) ────────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 1, `permission_id` FROM `permissions`;

-- ── Grant common permissions to Warehouse Manager (role_id = 2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 2, p.permission_id FROM `permissions` p
  WHERE p.module_key IN ('dashboard','grn','qc','stock','putaway','gatepass','receiving',
                         'purchase_orders','locations','subcons','items','reports','profile','alerts');

-- ── Grant view/create to Coordinator (role_id = 3) ──────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 3, p.permission_id FROM `permissions` p
  WHERE p.action_key IN ('view','create')
    AND p.module_key IN ('dashboard','grn','qc','gatepass','receiving','items','profile');

-- ── Grant view/create to QC (role_id = 4) ───────────────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 4, p.permission_id FROM `permissions` p
  WHERE p.action_key IN ('view','create')
    AND p.module_key IN ('dashboard','qc','grn','items','profile');

-- ── Grant view-only to Viewer (role_id = 5) ─────────────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 5, p.permission_id FROM `permissions` p
  WHERE p.action_key = 'view';

SET FOREIGN_KEY_CHECKS = 1;
