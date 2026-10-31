-- =======================================================
-- Gingersnaps Warehouse — Missing Modules Migration
-- Adds: tickets, output_records, fob_shipments, fob_shipment_items
-- Adds permissions: tickets, output, fob_shipment
-- Run via cPanel phpMyAdmin
-- =======================================================

SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------
-- TICKETS  (production/dispatch work-order tickets)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tickets` (
  `ticket_id`    int(11)      NOT NULL AUTO_INCREMENT,
  `ticket_no`    varchar(100) NOT NULL,
  `po_id`        int(11)      DEFAULT NULL,
  `ticket_date`  date         DEFAULT NULL,
  `qty`          int(11)      NOT NULL DEFAULT 0,
  `status`       enum('OPEN','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  `notes`        varchar(255) DEFAULT NULL,
  `created_by`   int(11)      DEFAULT NULL,
  `created_at`   timestamp    NULL DEFAULT current_timestamp(),
  `updated_at`   timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `ticket_no` (`ticket_no`),
  KEY `po_id` (`po_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`po_id`)      REFERENCES `purchase_orders` (`po_id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- OUTPUT RECORDS  (daily production output per PO)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `output_records` (
  `output_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `po_id`          int(11)      NOT NULL,
  `output_date`    date         NOT NULL,
  `completed_qty`  int(11)      NOT NULL DEFAULT 0,
  `packed_qty`     int(11)      NOT NULL DEFAULT 0,
  `notes`          varchar(255) DEFAULT NULL,
  `created_by`     int(11)      DEFAULT NULL,
  `created_at`     timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`output_id`),
  KEY `po_id` (`po_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `output_records_ibfk_1` FOREIGN KEY (`po_id`)       REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `output_records_ibfk_2` FOREIGN KEY (`created_by`)  REFERENCES `users` (`user_id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- FOB SHIPMENTS  (export/outbound shipment header)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fob_shipments` (
  `fob_id`         int(11)      NOT NULL AUTO_INCREMENT,
  `booking_ref`    varchar(100) NOT NULL,
  `vessel_flight`  varchar(100) DEFAULT NULL,
  `etd`            date         DEFAULT NULL,
  `eta`            date         DEFAULT NULL,
  `destination_id` int(11)      DEFAULT NULL,
  `buyer`          varchar(150) DEFAULT NULL,
  `status`         enum('BOOKING','LOADED','IN_TRANSIT','ARRIVED','CANCELLED') NOT NULL DEFAULT 'BOOKING',
  `remarks`        varchar(255) DEFAULT NULL,
  `created_by`     int(11)      DEFAULT NULL,
  `created_at`     timestamp    NULL DEFAULT current_timestamp(),
  `updated_at`     timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`fob_id`),
  UNIQUE KEY `booking_ref` (`booking_ref`),
  KEY `destination_id` (`destination_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fob_shipments_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`destination_id`) ON DELETE SET NULL,
  CONSTRAINT `fob_shipments_ibfk_2` FOREIGN KEY (`created_by`)     REFERENCES `users` (`user_id`)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- FOB SHIPMENT ITEMS  (POs included in each shipment)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fob_shipment_items` (
  `fob_item_id`  int(11)      NOT NULL AUTO_INCREMENT,
  `fob_id`       int(11)      NOT NULL,
  `po_id`        int(11)      DEFAULT NULL,
  `qty_shipped`  int(11)      NOT NULL DEFAULT 0,
  `cartons`      int(11)               DEFAULT 0,
  `notes`        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`fob_item_id`),
  KEY `fob_id` (`fob_id`),
  KEY `po_id` (`po_id`),
  CONSTRAINT `fob_shipment_items_ibfk_1` FOREIGN KEY (`fob_id`) REFERENCES `fob_shipments` (`fob_id`) ON DELETE CASCADE,
  CONSTRAINT `fob_shipment_items_ibfk_2` FOREIGN KEY (`po_id`)  REFERENCES `purchase_orders` (`po_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------
-- PERMISSIONS  (new module keys)
-- ---------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module_key`, `action_key`, `label`) VALUES
  ('tickets',      'view',   'View Tickets'),
  ('tickets',      'create', 'Create Tickets'),
  ('tickets',      'edit',   'Edit Tickets'),
  ('tickets',      'delete', 'Delete Tickets'),
  ('output',       'view',   'View Output Records'),
  ('output',       'create', 'Create Output Records'),
  ('output',       'edit',   'Edit Output Records'),
  ('output',       'delete', 'Delete Output Records'),
  ('fob_shipment', 'view',   'View FOB Shipments'),
  ('fob_shipment', 'create', 'Create FOB Shipments'),
  ('fob_shipment', 'edit',   'Edit FOB Shipments'),
  ('fob_shipment', 'delete', 'Delete FOB Shipments');

-- Grant all new permissions to Administrator (1) and Warehouse Manager (2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM   `roles` r
CROSS JOIN `permissions` p
WHERE  r.role_id IN (1, 2)
AND    p.module_key IN ('tickets', 'output', 'fob_shipment');

-- Grant view-only to Coordinator (3) and QC Inspector (4)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM   `roles` r
CROSS JOIN `permissions` p
WHERE  r.role_id IN (3, 4)
AND    p.module_key IN ('tickets', 'output', 'fob_shipment')
AND    p.action_key = 'view';

-- Grant view-only to Viewer (5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, p.permission_id
FROM   `permissions` p
WHERE  p.module_key IN ('tickets', 'output', 'fob_shipment')
AND    p.action_key = 'view';

SET FOREIGN_KEY_CHECKS=1;
