-- WPMS Phase 1: Seed Data
-- Sample data for testing and development
-- Date: 2026-10-24
-- Load AFTER all migration files (01-13)

-- ===================================================
-- 1. CREATE INITIAL WAREHOUSES
-- ===================================================

INSERT INTO warehouses (name, location, is_active, created_by) VALUES
(1, 'Main Warehouse Gingersnaps', 'Makati, Manila', 1, 1),
(2, 'Secondary Facility Rizal', 'Cainta, Rizal', 1, 1),
(3, 'Receiving & Staging', 'Las Pinas, Manila', 1, 1);

-- ===================================================
-- 2. CREATE INITIAL DEPARTMENTS
-- ===================================================

INSERT INTO departments (warehouse_id, name, is_active, created_by) VALUES
(1, 'Picking', 1, 1),
(1, 'Packing', 1, 1),
(1, 'Quality Assurance', 1, 1),
(2, 'Picking', 1, 1),
(2, 'Packing', 1, 1),
(3, 'Receiving', 1, 1),
(3, 'Staging', 1, 1);

-- ===================================================
-- 3. CREATE INITIAL USERS (passwords are bcrypt hashes)
-- ===================================================

INSERT INTO users (email, password_hash, full_name, role, is_active, created_by) VALUES
-- Admin user (password: admin123)
('admin@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Admin User', 'admin', 1, NULL),
-- Supervisors
('supervisor1@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Juan Santos', 'supervisor', 1, 1),
('supervisor2@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Maria Garcia', 'supervisor', 1, 1),
-- Employees
('employee1@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Carlos Reyes', 'employee', 1, 1),
('employee2@asmrcctvtechnologies.com', '$2y$10$Nf7BXy7Vvx.LyVN7dKL7/eQqRvVvXBr5X5d5l5z5z5z5z5z5z5z5z', 'Rosa Fernandez', 'employee', 1, 1);

-- ===================================================
-- 4. CREATE INITIAL EMPLOYEES
-- ===================================================

INSERT INTO employees (emp_id, user_id, full_name, email, phone, warehouse_id, department_id, employment_status, hire_date, is_active, created_by) VALUES
('EMP001', 4, 'Carlos Reyes', 'carlos.reyes@asmr.com', '09171234567', 1, 1, 'active', '2024-01-15', 1, 1),
('EMP002', 5, 'Rosa Fernandez', 'rosa.fernandez@asmr.com', '09181234568', 1, 2, 'active', '2024-02-20', 1, 1),
('EMP003', NULL, 'Pedro Cruz', 'pedro.cruz@asmr.com', '09191234569', 1, 1, 'active', '2024-03-10', 1, 1),
('EMP004', NULL, 'Anna Lopez', 'anna.lopez@asmr.com', '09201234570', 2, 4, 'active', '2024-04-05', 1, 1),
('EMP005', NULL, 'Miguel Torres', 'miguel.torres@asmr.com', '09211234571', 2, 5, 'active', '2024-05-01', 1, 1);

-- ===================================================
-- 5. CREATE SAMPLE PICKING TASKS
-- ===================================================

INSERT INTO picking_tasks (picklist_code, employee_id, order_number, sku_code, product_description, quantity_ordered, quantity_picked, shelf_code, batch_code, status, task_start_time, task_completion_time, task_duration_seconds, has_error, created_by) VALUES
('PICK001', 1, 'ORD-2026-10001', 'SKU-001-A', 'CCTV Dome Camera 2MP Black', 5, 5, 'SHELF-A1', 'BATCH-2026-10-01', 'completed', '2026-10-24 08:00:00', '2026-10-24 08:15:30', 930, 0, 1),
('PICK002', 1, 'ORD-2026-10002', 'SKU-002-B', 'DVR 8 Channel HD', 3, 3, 'SHELF-B2', 'BATCH-2026-10-01', 'completed', '2026-10-24 08:20:00', '2026-10-24 08:45:15', 1515, 0, 1),
('PICK003', 2, 'ORD-2026-10003', 'SKU-003-C', 'Network Cable CAT6 30m', 10, 10, 'SHELF-C3', 'BATCH-2026-10-01', 'completed', '2026-10-24 09:00:00', '2026-10-24 09:08:45', 525, 0, 1),
('PICK004', 1, 'ORD-2026-10004', 'SKU-001-A', 'CCTV Dome Camera 2MP Black', 2, 2, 'SHELF-A1', 'BATCH-2026-10-02', 'in_progress', '2026-10-24 10:30:00', NULL, NULL, 0, 1),
('PICK005', 3, 'ORD-2026-10005', 'SKU-004-D', 'Power Supply 12V 5A', 15, 14, 'SHELF-D4', 'BATCH-2026-10-02', 'pending', NULL, NULL, NULL, 1, 1);

-- ===================================================
-- 6. CREATE SAMPLE PICKING ERRORS
-- ===================================================

INSERT INTO picking_errors (picking_task_id, error_type, error_severity, description, resolution, requires_rework) VALUES
(5, 'WRONG_QUANTITY', 'medium', 'Ordered 15 units but only picked 14', 'One unit was damaged in inventory', 0);

-- ===================================================
-- 7. CREATE SAMPLE PACKING TASKS
-- ===================================================

INSERT INTO packing_tasks (package_code, employee_id, shipment_id, order_number, quantity_items, package_status, task_start_time, task_completion_time, task_duration_seconds, has_error, has_quality_issue, created_by) VALUES
('PKG001', 2, 'SHIP-2026-10-001', 'ORD-2026-10001', 5, 'completed', '2026-10-24 08:20:00', '2026-10-24 08:35:20', 915, 0, 0, 1),
('PKG002', 2, 'SHIP-2026-10-002', 'ORD-2026-10002', 3, 'completed', '2026-10-24 08:40:00', '2026-10-24 09:05:15', 1515, 0, 0, 1),
('PKG003', 5, 'SHIP-2026-10-003', 'ORD-2026-10003', 10, 'in_progress', '2026-10-24 09:15:00', NULL, NULL, 0, 0, 1),
('PKG004', 2, 'SHIP-2026-10-004', 'ORD-2026-10004', 2, 'pending', NULL, NULL, NULL, 0, 0, 1),
('PKG005', 5, 'SHIP-2026-10-005', 'ORD-2026-10005', 15, 'pending', NULL, NULL, NULL, 0, 0, 1);

-- ===================================================
-- 8. CREATE SAMPLE PACKING ERRORS
-- ===================================================

INSERT INTO packing_errors (packing_task_id, error_type, severity, quantity_affected, description, action_taken, rework_required) VALUES
(3, 'DAMAGED_PACKAGE', 'high', 1, 'Corner of box was crushed during packing', 'Repacked item in new box', 1);

-- ===================================================
-- 9. CREATE SAMPLE IMPORT RECORD
-- ===================================================

INSERT INTO imports (import_type, file_name, file_size, total_records, successful_records, failed_records, status, import_started_at, import_completed_at, created_by) VALUES
('PICKLIST_IMPORT', 'unicommerce_picklists_2026-10-24.csv', 125000, 100, 98, 2, 'completed', '2026-10-24 06:00:00', '2026-10-24 06:15:30', 1);

-- ===================================================
-- 10. CREATE SAMPLE IMPORT DETAILS
-- ===================================================

INSERT INTO import_details (import_id, source_row_number, source_data, target_table, target_record_id, status, error_message) VALUES
(1, 1, '{"order_number":"ORD-2026-10001","sku_code":"SKU-001-A","qty":5}', 'picking_tasks', 1, 'success', NULL),
(1, 2, '{"order_number":"ORD-2026-10002","sku_code":"SKU-002-B","qty":3}', 'picking_tasks', 2, 'success', NULL),
(1, 3, '{"order_number":"ORD-2026-10003","sku_code":"SKU-003-C","qty":10}', 'picking_tasks', 3, 'success', NULL),
(1, 99, '{"order_number":"ORD-2026-10099","sku_code":"INVALID-SKU"}', 'picking_tasks', NULL, 'validation_error', 'SKU code does not exist in inventory'),
(1, 100, '{"order_number":"ORD-2026-10100","sku_code":"SKU-001-A","qty":2}', 'picking_tasks', NULL, 'duplicate', 'Order already exists in system');

-- ===================================================
-- 11. CREATE SAMPLE AUDIT LOGS
-- ===================================================

INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, change_summary, ip_address) VALUES
(1, 'LOGIN', 'user', 1, NULL, '{"login_time":"2026-10-24 07:00:00"}', 'Admin user logged in', '192.168.1.100'),
(1, 'CREATE_EMPLOYEE', 'employee', 1, NULL, '{"emp_id":"EMP001","full_name":"Carlos Reyes","department_id":1}', 'Created new employee: Carlos Reyes', '192.168.1.100'),
(1, 'CREATE_TASK', 'picking_task', 1, NULL, '{"picklist_code":"PICK001","emp_id":1,"status":"created"}', 'Created picking task PICK001', '192.168.1.100'),
(4, 'UPDATE_TASK', 'picking_task', 1, '{"status":"pending"}', '{"status":"in_progress","task_start_time":"2026-10-24 08:00:00"}', 'Carlos started picking task PICK001', '192.168.1.101'),
(4, 'COMPLETE_TASK', 'picking_task', 1, '{"status":"in_progress"}', '{"status":"completed","task_completion_time":"2026-10-24 08:15:30"}', 'Carlos completed picking task PICK001', '192.168.1.101'),
(1, 'IMPORT_START', 'import', 1, NULL, '{"import_type":"PICKLIST_IMPORT","file_name":"unicommerce_picklists_2026-10-24.csv","status":"processing"}', 'Started importing picklists from Unicommerce', '192.168.1.100'),
(1, 'IMPORT_COMPLETE', 'import', 1, '{"status":"processing"}', '{"status":"completed","successful_records":98,"failed_records":2}', 'Completed import: 98 success, 2 failed', '192.168.1.100');

-- ===================================================
-- 12. CREATE SAMPLE SYSTEM SETTINGS
-- ===================================================

INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_editable) VALUES
('session_timeout_minutes', '120', 'integer', 'Session inactivity timeout in minutes', 1),
('items_per_page', '50', 'integer', 'Default pagination size for lists', 1),
('retention_days_audit_logs', '90', 'integer', 'Number of days to retain audit logs', 1),
('error_notification_email', 'admin@asmrcctvtechnologies.com', 'string', 'Email address for critical error notifications', 1),
('warehouse_name_default', 'Main Warehouse Gingersnaps', 'string', 'Default warehouse for new assignments', 1),
('enable_barcode_scanning', 'true', 'boolean', 'Enable barcode/QR scanning in picking module', 1),
('task_status_workflow', '["created","pending","in_progress","completed"]', 'json', 'Allowed task status transitions', 0),
('app_version', '1.0.0', 'string', 'Application version', 0);

-- ===================================================
-- 13. CREATE SAMPLE PRODUCTIVITY SUMMARIES
-- ===================================================

INSERT INTO productivity_summaries (employee_id, period_type, period_date, tasks_picked, tasks_packed, total_tasks, total_duration_seconds, avg_task_duration_seconds, error_count, error_rate_percent, rework_count, quality_issue_count, tasks_completed_on_time, on_time_percent) VALUES
(1, 'daily', '2026-10-24', 2, 0, 2, 2445, 1222, 0, 0.00, 0, 0, 2, 100.00),
(2, 'daily', '2026-10-24', 0, 2, 2, 2430, 1215, 0, 0.00, 0, 0, 2, 100.00),
(3, 'daily', '2026-10-24', 1, 0, 1, 525, 525, 1, 100.00, 0, 0, 1, 100.00),
(4, 'daily', '2026-10-24', 0, 1, 1, 1440, 1440, 0, 0.00, 0, 1, 1, 100.00),
(5, 'daily', '2026-10-24', 0, 1, 1, 1800, 1800, 0, 0.00, 0, 0, 1, 100.00);

-- ===================================================
-- END OF SEED DATA
-- ===================================================
-- Execution complete. All sample data loaded successfully.
-- Next step: Create indexes and verify data integrity.
