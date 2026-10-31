-- migration9.sql: Gatepass tables + permissions
-- Run once against the warehouse database.

CREATE TABLE IF NOT EXISTS `gatepasses` (
  `gp_id`      int(11) NOT NULL AUTO_INCREMENT,
  `gp_code`    varchar(20) NOT NULL,
  `gp_type`    enum('INTERNAL_CONSUMPTION','STOCK_TRANSFER','RETURNABLE') NOT NULL DEFAULT 'STOCK_TRANSFER',
  `gp_status`  enum('CREATED','CLOSED') NOT NULL DEFAULT 'CREATED',
  `to_party`   varchar(255) DEFAULT NULL,
  `purpose`    text DEFAULT NULL,
  `reference`  varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at`  datetime DEFAULT NULL,
  PRIMARY KEY (`gp_id`),
  UNIQUE KEY `uq_gp_code` (`gp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `gatepass_items` (
  `gpi_id`          int(11) NOT NULL AUTO_INCREMENT,
  `gp_id`           int(11) NOT NULL,
  `item_id`         int(11) DEFAULT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `qty_cartons`     int(11) NOT NULL DEFAULT 1,
  `notes`           varchar(255) DEFAULT NULL,
  PRIMARY KEY (`gpi_id`),
  KEY `idx_gp_id` (`gp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Gatepass permissions
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('gatepass', 'view',   'View Gatepasses'),
  ('gatepass', 'create', 'Create Gatepasses'),
  ('gatepass', 'edit',   'Edit Gatepasses'),
  ('gatepass', 'close',  'Close Gatepasses');

-- Roles 1-2: all 4 permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2) AND p.module_key = 'gatepass';

-- Roles 3-4: view + create
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r CROSS JOIN `permissions` p
  WHERE r.role_id IN (3, 4) AND p.module_key = 'gatepass'
    AND p.action_key IN ('view', 'create');

-- Role 5: view only
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r CROSS JOIN `permissions` p
  WHERE r.role_id = 5 AND p.module_key = 'gatepass'
    AND p.action_key = 'view';
