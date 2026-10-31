-- =======================================================
-- Gingersnaps Warehouse — Migration 7 (revised)
-- The purchase_orders table already exists on the server.
-- This migration ONLY inserts permissions and role grants.
--
-- Permissions: purchase_orders/view, create, edit, delete, receive
-- Role assignments:
--   1 Admin, 2 Warehouse Manager — all 5 permissions
--   3 Coordinator, 4 QC          — view + receive only
--   5 Viewer                     — view only
-- =======================================================

-- ---------------------------------------------------------
-- PERMISSIONS
-- Column name confirmed as `description` (matches migration6.sql).
-- ---------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('purchase_orders', 'view',    'View Purchase Orders'),
  ('purchase_orders', 'create',  'Create Purchase Orders'),
  ('purchase_orders', 'edit',    'Edit Purchase Orders'),
  ('purchase_orders', 'delete',  'Delete Purchase Orders'),
  ('purchase_orders', 'receive', 'Receive Against Purchase Orders');

-- Admin (1) and Warehouse Manager (2): all 5 permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2)
    AND p.module_key = 'purchase_orders';

-- Coordinator (3) and QC (4): view + receive only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (3, 4)
    AND p.module_key = 'purchase_orders'
    AND p.action_key IN ('view', 'receive');

-- Viewer (5): view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 5, p.permission_id
  FROM `permissions` p
  WHERE p.module_key = 'purchase_orders'
    AND p.action_key = 'view';
