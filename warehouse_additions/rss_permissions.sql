-- RSS module permissions
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('rss', 'view',   'View RSS / Other-Process'),
  ('rss', 'create', 'Create RSS Record'),
  ('rss', 'edit',   'Edit RSS Record'),
  ('rss', 'delete', 'Delete RSS Record');

-- Grant to Admin (role_id=1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 1, `permission_id` FROM `permissions` WHERE `module_key` = 'rss';

-- Grant to Warehouse Manager (role_id=2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT 2, `permission_id` FROM `permissions` WHERE `module_key` = 'rss';
