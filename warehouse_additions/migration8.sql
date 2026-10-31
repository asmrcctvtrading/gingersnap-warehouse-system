-- migration8.sql: Subcontractors + Items module permissions
-- Run once against the warehouse database.

INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('subcontractors', 'view',   'View Subcontractors'),
  ('subcontractors', 'create', 'Create Subcontractors'),
  ('subcontractors', 'edit',   'Edit Subcontractors'),
  ('subcontractors', 'delete', 'Delete Subcontractors');

-- Roles 1-2 (admin / manager): all 4 permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2)
    AND p.module_key = 'subcontractors';

-- Roles 3-5 (staff-level): view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (3, 4, 5)
    AND p.module_key = 'subcontractors'
    AND p.action_key = 'view';

-- Items / SKU permissions
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('items', 'view',   'View Items'),
  ('items', 'create', 'Create Items'),
  ('items', 'edit',   'Edit Items'),
  ('items', 'delete', 'Delete Items');

-- Roles 1-2: all 4
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2)
    AND p.module_key = 'items';

-- Roles 3-5: view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (3, 4, 5)
    AND p.module_key = 'items'
    AND p.action_key = 'view';
