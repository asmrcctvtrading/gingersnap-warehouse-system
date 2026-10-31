-- WPMS Phase 1: Packing Tasks Table Migration
-- Purpose: Track packing operations and package completion
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS packing_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(100) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    shipment_id VARCHAR(100) NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    quantity_items INT UNSIGNED NOT NULL DEFAULT 1,
    package_status ENUM('created', 'pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'created',
    task_start_time DATETIME NULL,
    task_completion_time DATETIME NULL,
    task_duration_seconds INT UNSIGNED NULL,
    has_error TINYINT(1) NOT NULL DEFAULT 0,
    has_quality_issue TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_employee_id (employee_id),
    INDEX idx_package_code (package_code),
    INDEX idx_package_status (package_status),
    INDEX idx_created_at (created_at),
    INDEX idx_employee_status (employee_id, package_status),
    INDEX idx_employee_date (employee_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE packing_tasks COMMENT = 'Packing Task Records - Track packing operations and package completion';
