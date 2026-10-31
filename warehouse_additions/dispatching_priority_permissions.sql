-- Dispatching Priority module permissions
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('dispatching_priority', 'view', 'View Dispatching Priority'),
  ('dispatching_priority', 'create', 'Create Dispatching Priority'),
  ('dispatching_priority', 'edit', 'Edit Dispatching Priority'),
  ('dispatching_priority', 'delete', 'Delete Dispatching Priority');

-- Grant to Admin (role_id=1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 1, `permission_id` FROM `permissions` WHERE `module_key` = 'dispatching_priority';

-- Grant to Warehouse Manager (role_id=2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 2, `permission_id` FROM `permissions` WHERE `module_key` = 'dispatching_priority';
