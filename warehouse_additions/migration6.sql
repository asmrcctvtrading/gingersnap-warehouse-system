-- =======================================================
-- Gingersnaps Warehouse — Migration 6
-- Adds: warehouse_zones, warehouse_locations,
--       stock_positions, stock_movements
-- Adds permissions: locations (view/create/edit/delete),
--                   stock     (view/create/edit/delete)
-- Assigns all permissions to roles 1+2; view-only to 3,4,5
-- Run via cPanel phpMyAdmin
-- =======================================================

SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------
-- WAREHOUSE ZONES
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_zones` (
  `zone_id`    int(11)      NOT NULL AUTO_INCREMENT,
  `zone_code`  varchar(20)  NOT NULL,
  `zone_name`  varchar(100) NOT NULL,
  `zone_type`  enum('RECEIVING','STORAGE','QC','STAGING','DISPATCH') NOT NULL DEFAULT 'STORAGE',
  `notes`      varchar(255) DEFAULT NULL,
  `is_active`  tinyint(1)   NOT NULL DEFAULT 1,
  `created_by` int(11)      DEFAULT NULL,
  `created_at` timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`zone_id`),
  UNIQUE KEY `zone_code` (`zone_code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `wzones_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- WAREHOUSE LOCATIONS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_locations` (
  `location_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `zone_id`          int(11)      NOT NULL,
  `location_code`    varchar(50)  NOT NULL,
  `location_name`    varchar(100) DEFAULT NULL,
  `status`           enum('ACTIVE','INACTIVE','RESERVED') NOT NULL DEFAULT 'ACTIVE',
  `capacity_cartons` int(11)      DEFAULT NULL,
  `notes`            varchar(255) DEFAULT NULL,
  `created_by`       int(11)      DEFAULT NULL,
  `created_at`       timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `location_code` (`location_code`),
  KEY `zone_id` (`zone_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `wloc_ibfk_1` FOREIGN KEY (`zone_id`)    REFERENCES `warehouse_zones` (`zone_id`) ON DELETE CASCADE,
  CONSTRAINT `wloc_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- STOCK POSITIONS  (current snapshot per location+item+po)
--
-- NOTE ON NULL UNIQUE KEY:
--   MySQL treats NULL as distinct in composite unique indexes,
--   meaning (location_id, NULL, NULL) can be inserted multiple
--   times without triggering the duplicate-key constraint.
--   The application layer (action.php) handles uniqueness with
--   a SELECT-then-INSERT/UPDATE pattern using IS NULL checks,
--   so the UNIQUE KEY here provides fast lookups for non-null
--   combinations and serves as a safety net otherwise.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_positions` (
  `position_id`  int(11)      NOT NULL AUTO_INCREMENT,
  `location_id`  int(11)      NOT NULL,
  `item_id`      int(11)      DEFAULT NULL,
  `po_id`        int(11)      DEFAULT NULL,
  `qty_cartons`  int(11)      NOT NULL DEFAULT 0,
  `qty_pieces`   int(11)      NOT NULL DEFAULT 0,
  `notes`        varchar(255) DEFAULT NULL,
  `updated_by`   int(11)      DEFAULT NULL,
  `updated_at`   timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`position_id`),
  UNIQUE KEY `uq_position` (`location_id`, `item_id`, `po_id`),
  KEY `item_id` (`item_id`),
  KEY `po_id` (`po_id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `spos_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `warehouse_locations` (`location_id`) ON DELETE CASCADE,
  CONSTRAINT `spos_ibfk_2` FOREIGN KEY (`item_id`)     REFERENCES `items` (`item_id`)                   ON DELETE SET NULL,
  CONSTRAINT `spos_ibfk_3` FOREIGN KEY (`po_id`)       REFERENCES `purchase_orders` (`po_id`)           ON DELETE SET NULL,
  CONSTRAINT `spos_ibfk_4` FOREIGN KEY (`updated_by`)  REFERENCES `users` (`user_id`)                   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- STOCK MOVEMENTS  (full audit trail — never deleted by app)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `movement_type`    enum('PUTAWAY','RELOCATION','PICK','ADJUST','DISPATCH') NOT NULL,
  `from_location_id` int(11)      DEFAULT NULL,
  `to_location_id`   int(11)      DEFAULT NULL,
  `item_id`          int(11)      DEFAULT NULL,
  `po_id`            int(11)      DEFAULT NULL,
  `qty_cartons`      int(11)      NOT NULL DEFAULT 0,
  `qty_pieces`       int(11)      NOT NULL DEFAULT 0,
  `reference_doc`    varchar(100) DEFAULT NULL,
  `notes`            varchar(255) DEFAULT NULL,
  `created_by`       int(11)      DEFAULT NULL,
  `created_at`       timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`movement_id`),
  KEY `from_location_id` (`from_location_id`),
  KEY `to_location_id` (`to_location_id`),
  KEY `item_id` (`item_id`),
  KEY `po_id` (`po_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `smov_ibfk_1` FOREIGN KEY (`from_location_id`) REFERENCES `warehouse_locations` (`location_id`) ON DELETE SET NULL,
  CONSTRAINT `smov_ibfk_2` FOREIGN KEY (`to_location_id`)   REFERENCES `warehouse_locations` (`location_id`) ON DELETE SET NULL,
  CONSTRAINT `smov_ibfk_3` FOREIGN KEY (`item_id`)          REFERENCES `items` (`item_id`)                   ON DELETE SET NULL,
  CONSTRAINT `smov_ibfk_4` FOREIGN KEY (`po_id`)            REFERENCES `purchase_orders` (`po_id`)           ON DELETE SET NULL,
  CONSTRAINT `smov_ibfk_5` FOREIGN KEY (`created_by`)       REFERENCES `users` (`user_id`)                   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;

-- ---------------------------------------------------------
-- PERMISSIONS
-- ---------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `description`) VALUES
  ('locations', 'view',   'View Warehouse Locations'),
  ('locations', 'create', 'Create Warehouse Locations'),
  ('locations', 'edit',   'Edit Warehouse Locations'),
  ('locations', 'delete', 'Delete Warehouse Locations'),
  ('stock',     'view',   'View Stock Positions'),
  ('stock',     'create', 'Record Stock Movement'),
  ('stock',     'edit',   'Adjust Stock'),
  ('stock',     'delete', 'Delete Stock Movement');

-- All permissions to Admin (1) and Warehouse Manager (2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (1, 2)
    AND p.module_key IN ('locations', 'stock');

-- View-only to Coordinator (3), QC (4), Viewer (5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
  SELECT r.role_id, p.permission_id
  FROM `roles` r
  CROSS JOIN `permissions` p
  WHERE r.role_id IN (3, 4, 5)
    AND p.module_key IN ('locations', 'stock')
    AND p.action_key = 'view';
