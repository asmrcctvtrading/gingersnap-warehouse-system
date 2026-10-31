-- WPMS Database Deployment Script (FIXED)
-- All migrations + seed data in one file
-- Execute in phpMyAdmin or via MySQL client

-- ===================================================
-- DISABLE FOREIGN KEY CHECKS (allow drops)
-- ===================================================
SET FOREIGN_KEY_CHECKS=0;

-- ===================================================
-- DROP EXISTING TABLES (in reverse dependency order)
-- ===================================================
DROP TABLE IF EXISTS `productivity_summaries`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `import_details`;
DROP TABLE IF EXISTS `imports`;
DROP TABLE IF EXISTS `packing_errors`;
DROP TABLE IF EXISTS `packing_tasks`;
DROP TABLE IF EXISTS `picking_errors`;
DROP TABLE IF EXISTS `picking_tasks`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `warehouses`;
DROP TABLE IF EXISTS `users`;

-- ===================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ===================================================
SET FOREIGN_KEY_CHECKS=1;

-- ===================================================
-- TABLE 01: Users
-- ===================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'employee') NOT NULL DEFAULT 'employee',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 02: Warehouses
-- ===================================================
CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(500),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_is_active (is_active),
    UNIQUE KEY uk_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 03: Departments
-- ===================================================
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_is_active (is_active),
    UNIQUE KEY uk_warehouse_name (warehouse_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 04: Employees
-- ===================================================
CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emp_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT UNSIGNED,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    warehouse_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    employment_status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active',
    hire_date DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_emp_id (emp_id),
    INDEX idx_user_id (user_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_department_id (department_id),
    INDEX idx_employment_status (employment_status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 05: Picking Tasks
-- ===================================================
CREATE TABLE IF NOT EXISTS picking_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    picklist_code VARCHAR(100) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    sku_code VARCHAR(100) NOT NULL,
    product_description VARCHAR(500),
    quantity_ordered INT UNSIGNED NOT NULL,
    quantity_picked INT UNSIGNED,
    shelf_code VARCHAR(100),
    batch_code VARCHAR(100),
    status ENUM('created', 'pending', 'in_progress', 'completed', 'on_hold', 'cancelled') NOT NULL DEFAULT 'created',
    task_start_time DATETIME,
    task_completion_time DATETIME,
    task_duration_seconds INT UNSIGNED,
    has_error TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_picklist_code (picklist_code),
    INDEX idx_employee_id (employee_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_batch_code (batch_code),
    INDEX idx_has_error (has_error),
    INDEX idx_task_completion_time (task_completion_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 06: Picking Errors
-- ===================================================
CREATE TABLE IF NOT EXISTS picking_errors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    picking_task_id INT UNSIGNED NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    error_severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    description TEXT,
    resolution TEXT,
    requires_rework TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (picking_task_id) REFERENCES picking_tasks(id) ON DELETE CASCADE,
    INDEX idx_picking_task_id (picking_task_id),
    INDEX idx_error_type (error_type),
    INDEX idx_error_severity (error_severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 07: Packing Tasks
-- ===================================================
CREATE TABLE IF NOT EXISTS packing_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(100) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    shipment_id VARCHAR(100) NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    quantity_items INT UNSIGNED NOT NULL,
    package_status ENUM('created', 'pending', 'in_progress', 'completed', 'on_hold', 'cancelled') NOT NULL DEFAULT 'created',
    task_start_time DATETIME,
    task_completion_time DATETIME,
    task_duration_seconds INT UNSIGNED,
    has_error TINYINT(1) DEFAULT 0,
    has_quality_issue TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_package_code (package_code),
    INDEX idx_employee_id (employee_id),
    INDEX idx_shipment_id (shipment_id),
    INDEX idx_package_status (package_status),
    INDEX idx_has_error (has_error),
    INDEX idx_has_quality_issue (has_quality_issue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 08: Packing Errors
-- ===================================================
CREATE TABLE IF NOT EXISTS packing_errors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    packing_task_id INT UNSIGNED NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    quantity_affected INT UNSIGNED,
    description TEXT,
    action_taken TEXT,
    rework_required TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (packing_task_id) REFERENCES packing_tasks(id) ON DELETE CASCADE,
    INDEX idx_packing_task_id (packing_task_id),
    INDEX idx_error_type (error_type),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 09: Imports
-- ===================================================
CREATE TABLE IF NOT EXISTS imports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255),
    file_size INT UNSIGNED,
    total_records INT UNSIGNED,
    successful_records INT UNSIGNED,
    failed_records INT UNSIGNED,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    import_started_at DATETIME,
    import_completed_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_import_type (import_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 10: Import Details
-- ===================================================
CREATE TABLE IF NOT EXISTS import_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id INT UNSIGNED NOT NULL,
    source_row_number INT UNSIGNED,
    source_data JSON,
    target_table VARCHAR(100),
    target_record_id INT UNSIGNED,
    status ENUM('success', 'validation_error', 'duplicate', 'failed') NOT NULL DEFAULT 'success',
    error_message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    INDEX idx_import_id (import_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 11: Audit Logs
-- ===================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    change_summary TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 12: System Settings
-- ===================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
    description VARCHAR(500),
    is_editable TINYINT(1) DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_setting_key (setting_key),
    INDEX idx_setting_type (setting_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TABLE 13: Productivity Summaries
-- ===================================================
CREATE TABLE IF NOT EXISTS productivity_summaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly') NOT NULL,
    period_date DATE NOT NULL,
    tasks_picked INT UNSIGNED DEFAULT 0,
    tasks_packed INT UNSIGNED DEFAULT 0,
    total_tasks INT UNSIGNED DEFAULT 0,
    total_duration_seconds BIGINT UNSIGNED DEFAULT 0,
    avg_task_duration_seconds INT UNSIGNED,
    error_count INT UNSIGNED DEFAULT 0,
    error_rate_percent DECIMAL(5, 2) DEFAULT 0,
    rework_count INT UNSIGNED DEFAULT 0,
    quality_issue_count INT UNSIGNED DEFAULT 0,
    tasks_completed_on_time INT UNSIGNED DEFAULT 0,
    on_time_percent DECIMAL(5, 2) DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_period_type_date (period_type, period_date),
    INDEX idx_employee_period (employee_id, period_type, period_date),
    UNIQUE KEY uk_employee_period (employee_id, period_type, period_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- SEED DATA (correct order: no FK violations)
-- ===================================================

-- INSERT USERS FIRST (no dependencies)
INSERT INTO users (email, password_hash, full_name, role, is_active, created_by) VALUES
('admin@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Admin User', 'admin', 1, NULL),
('supervisor1@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Juan Santos', 'supervisor', 1, 1),
('supervisor2@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Maria Garcia', 'supervisor', 1, 1),
('employee1@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Carlos Reyes', 'employee', 1, 1),
('employee2@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Rosa Fernandez', 'employee', 1, 1);

-- INSERT WAREHOUSES (depends on users)
INSERT INTO warehouses (name, location, is_active, created_by) VALUES
('Main Warehouse Gingersnaps', 'Makati, Manila', 1, 1),
('Secondary Facility Rizal', 'Cainta, Rizal', 1, 1),
('Receiving & Staging', 'Las Pinas, Manila', 1, 1);

-- INSERT DEPARTMENTS (depends on warehouses and users)
INSERT INTO departments (warehouse_id, name, is_active, created_by) VALUES
(1, 'Picking', 1, 1),
(1, 'Packing', 1, 1),
(1, 'Quality Assurance', 1, 1),
(2, 'Picking', 1, 1),
(2, 'Packing', 1, 1),
(3, 'Receiving', 1, 1),
(3, 'Staging', 1, 1);

-- INSERT EMPLOYEES (depends on users, warehouses, departments)
INSERT INTO employees (emp_id, user_id, full_name, email, phone, warehouse_id, department_id, employment_status, hire_date, is_active, created_by) VALUES
('EMP001', 4, 'Carlos Reyes', 'carlos.reyes@asmr.com', '09171234567', 1, 1, 'active', '2024-01-15', 1, 1),
('EMP002', 5, 'Rosa Fernandez', 'rosa.fernandez@asmr.com', '09181234568', 1, 2, 'active', '2024-02-20', 1, 1),
('EMP003', NULL, 'Pedro Cruz', 'pedro.cruz@asmr.com', '09191234569', 1, 1, 'active', '2024-03-10', 1, 1),
('EMP004', NULL, 'Anna Lopez', 'anna.lopez@asmr.com', '09201234570', 2, 4, 'active', '2024-04-05', 1, 1),
('EMP005', NULL, 'Miguel Torres', 'miguel.torres@asmr.com', '09211234571', 2, 5, 'active', '2024-05-01', 1, 1);

-- INSERT PICKING TASKS (depends on employees and users)
INSERT INTO picking_tasks (picklist_code, employee_id, order_number, sku_code, product_description, quantity_ordered, quantity_picked, shelf_code, batch_code, status, task_start_time, task_completion_time, task_duration_seconds, has_error, created_by) VALUES
('PICK001', 1, 'ORD-2026-10001', 'SKU-001-A', 'CCTV Dome Camera 2MP Black', 5, 5, 'SHELF-A1', 'BATCH-2026-10-01', 'completed', '2026-10-24 08:00:00', '2026-10-24 08:15:30', 930, 0, 1),
('PICK002', 1, 'ORD-2026-10002', 'SKU-002-B', 'DVR 8 Channel HD', 3, 3, 'SHELF-B2', 'BATCH-2026-10-01', 'completed', '2026-10-24 08:20:00', '2026-10-24 08:45:15', 1515, 0, 1),
('PICK003', 2, 'ORD-2026-10003', 'SKU-003-C', 'Network Cable CAT6 30m', 10, 10, 'SHELF-C3', 'BATCH-2026-10-01', 'completed', '2026-10-24 09:00:00', '2026-10-24 09:08:45', 525, 0, 1),
('PICK004', 1, 'ORD-2026-10004', 'SKU-001-A', 'CCTV Dome Camera 2MP Black', 2, 2, 'SHELF-A1', 'BATCH-2026-10-02', 'in_progress', '2026-10-24 10:30:00', NULL, NULL, 0, 1),
('PICK005', 3, 'ORD-2026-10005', 'SKU-004-D', 'Power Supply 12V 5A', 15, 14, 'SHELF-D4', 'BATCH-2026-10-02', 'pending', NULL, NULL, NULL, 1, 1);

-- INSERT PICKING ERRORS (depends on picking_tasks)
INSERT INTO picking_errors (picking_task_id, error_type, error_severity, description, resolution, requires_rework) VALUES
(5, 'WRONG_QUANTITY', 'medium', 'Ordered 15 units but only picked 14', 'One unit was damaged in inventory', 0);

-- INSERT PACKING TASKS (depends on employees and users)
INSERT INTO packing_tasks (package_code, employee_id, shipment_id, order_number, quantity_items, package_status, task_start_time, task_completion_time, task_duration_seconds, has_error, has_quality_issue, created_by) VALUES
('PKG001', 2, 'SHIP-2026-10-001', 'ORD-2026-10001', 5, 'completed', '2026-10-24 08:20:00', '2026-10-24 08:35:20', 915, 0, 0, 1),
('PKG002', 2, 'SHIP-2026-10-002', 'ORD-2026-10002', 3, 'completed', '2026-10-24 08:40:00', '2026-10-24 09:05:15', 1515, 0, 0, 1),
('PKG003', 5, 'SHIP-2026-10-003', 'ORD-2026-10003', 10, 'in_progress', '2026-10-24 09:15:00', NULL, NULL, 0, 0, 1),
('PKG004', 2, 'SHIP-2026-10-004', 'ORD-2026-10-004', 2, 'pending', NULL, NULL, NULL, 0, 0, 1),
('PKG005', 5, 'SHIP-2026-10-005', 'ORD-2026-10-005', 15, 'pending', NULL, NULL, NULL, 0, 0, 1);

-- INSERT PACKING ERRORS (depends on packing_tasks)
INSERT INTO packing_errors (packing_task_id, error_type, severity, quantity_affected, description, action_taken, rework_required) VALUES
(3, 'DAMAGED_PACKAGE', 'high', 1, 'Corner of box was crushed during packing', 'Repacked item in new box', 1);

-- INSERT IMPORTS (depends on users)
INSERT INTO imports (import_type, file_name, file_size, total_records, successful_records, failed_records, status, import_started_at, import_completed_at, created_by) VALUES
('PICKLIST_IMPORT', 'unicommerce_picklists_2026-10-24.csv', 125000, 100, 98, 2, 'completed', '2026-10-24 06:00:00', '2026-10-24 06:15:30', 1);

-- INSERT IMPORT DETAILS (depends on imports)
INSERT INTO import_details (import_id, source_row_number, source_data, target_table, target_record_id, status, error_message) VALUES
(1, 1, '{"order_number":"ORD-2026-10001","sku_code":"SKU-001-A","qty":5}', 'picking_tasks', 1, 'success', NULL),
(1, 2, '{"order_number":"ORD-2026-10002","sku_code":"SKU-002-B","qty":3}', 'picking_tasks', 2, 'success', NULL),
(1, 3, '{"order_number":"ORD-2026-10003","sku_code":"SKU-003-C","qty":10}', 'picking_tasks', 3, 'success', NULL),
(1, 99, '{"order_number":"ORD-2026-10099","sku_code":"INVALID-SKU"}', 'picking_tasks', NULL, 'validation_error', 'SKU code does not exist in inventory'),
(1, 100, '{"order_number":"ORD-2026-10100","sku_code":"SKU-001-A","qty":2}', 'picking_tasks', NULL, 'duplicate', 'Order already exists in system');

-- INSERT AUDIT LOGS (depends on users)
INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, change_summary, ip_address) VALUES
(1, 'LOGIN', 'user', 1, NULL, '{"login_time":"2026-10-24 07:00:00"}', 'Admin user logged in', '192.168.1.100'),
(1, 'CREATE_EMPLOYEE', 'employee', 1, NULL, '{"emp_id":"EMP001","full_name":"Carlos Reyes","department_id":1}', 'Created new employee: Carlos Reyes', '192.168.1.100'),
(1, 'CREATE_TASK', 'picking_task', 1, NULL, '{"picklist_code":"PICK001","emp_id":1,"status":"created"}', 'Created picking task PICK001', '192.168.1.100'),
(4, 'UPDATE_TASK', 'picking_task', 1, '{"status":"pending"}', '{"status":"in_progress","task_start_time":"2026-10-24 08:00:00"}', 'Carlos started picking task PICK001', '192.168.1.101'),
(4, 'COMPLETE_TASK', 'picking_task', 1, '{"status":"in_progress"}', '{"status":"completed","task_completion_time":"2026-10-24 08:15:30"}', 'Carlos completed picking task PICK001', '192.168.1.101'),
(1, 'IMPORT_START', 'import', 1, NULL, '{"import_type":"PICKLIST_IMPORT","file_name":"unicommerce_picklists_2026-10-24.csv","status":"processing"}', 'Started importing picklists from Unicommerce', '192.168.1.100'),
(1, 'IMPORT_COMPLETE', 'import', 1, '{"status":"processing"}', '{"status":"completed","successful_records":98,"failed_records":2}', 'Completed import: 98 success, 2 failed', '192.168.1.100');

-- INSERT SYSTEM SETTINGS (no dependencies)
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_editable) VALUES
('session_timeout_minutes', '120', 'integer', 'Session inactivity timeout in minutes', 1),
('items_per_page', '50', 'integer', 'Default pagination size for lists', 1),
('retention_days_audit_logs', '90', 'integer', 'Number of days to retain audit logs', 1),
('error_notification_email', 'admin@asmrcctvtechnologies.com', 'string', 'Email address for critical error notifications', 1),
('warehouse_name_default', 'Main Warehouse Gingersnaps', 'string', 'Default warehouse for new assignments', 1),
('enable_barcode_scanning', 'true', 'boolean', 'Enable barcode/QR scanning in picking module', 1),
('task_status_workflow', '["created","pending","in_progress","completed"]', 'json', 'Allowed task status transitions', 0),
('app_version', '1.0.0', 'string', 'Application version', 0);

-- INSERT PRODUCTIVITY SUMMARIES (depends on employees)
INSERT INTO productivity_summaries (employee_id, period_type, period_date, tasks_picked, tasks_packed, total_tasks, total_duration_seconds, avg_task_duration_seconds, error_count, error_rate_percent, rework_count, quality_issue_count, tasks_completed_on_time, on_time_percent) VALUES
(1, 'daily', '2026-10-24', 2, 0, 2, 2445, 1222, 0, 0.00, 0, 0, 2, 100.00),
(2, 'daily', '2026-10-24', 0, 2, 2, 2430, 1215, 0, 0.00, 0, 0, 2, 100.00),
(3, 'daily', '2026-10-24', 1, 0, 1, 525, 525, 1, 100.00, 0, 0, 1, 100.00),
(4, 'daily', '2026-10-24', 0, 1, 1, 1440, 1440, 0, 0.00, 0, 1, 1, 100.00),
(5, 'daily', '2026-10-24', 0, 1, 1, 1800, 1800, 0, 0.00, 0, 0, 1, 100.00);

-- ===================================================
-- DEPLOYMENT COMPLETE
-- ===================================================
-- All 13 tables created with proper indexes
-- Sample data loaded in dependency order
-- Foreign key constraints are active
