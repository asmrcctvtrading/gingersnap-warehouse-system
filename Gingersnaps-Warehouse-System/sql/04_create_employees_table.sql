-- WPMS Phase 1: Employees Table Migration
-- Purpose: Store employee information and assignment details
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emp_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    warehouse_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    employment_status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active',
    hire_date DATE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_emp_id (emp_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_department_id (department_id),
    INDEX idx_is_active (is_active),
    INDEX idx_employment_status (employment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE employees COMMENT = 'Warehouse Staff Profiles - Employee records and assignments';
