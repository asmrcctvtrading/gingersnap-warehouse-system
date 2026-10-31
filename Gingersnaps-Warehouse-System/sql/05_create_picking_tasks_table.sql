-- WPMS Phase 1: Picking Tasks Table Migration
-- Purpose: Track individual picking operations and their completion status
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS picking_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    picklist_code VARCHAR(100) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    sku_code VARCHAR(100) NOT NULL,
    product_description VARCHAR(500),
    quantity_ordered INT UNSIGNED NOT NULL DEFAULT 1,
    quantity_picked INT UNSIGNED DEFAULT 0,
    shelf_code VARCHAR(100),
    batch_code VARCHAR(100),
    status ENUM('created', 'pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'created',
    task_start_time DATETIME NULL,
    task_completion_time DATETIME NULL,
    task_duration_seconds INT UNSIGNED NULL,
    has_error TINYINT(1) NOT NULL DEFAULT 0,
    exception_notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_employee_id (employee_id),
    INDEX idx_picklist_code (picklist_code),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_employee_status (employee_id, status),
    INDEX idx_employee_date (employee_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE picking_tasks COMMENT = 'Picking Task Records - Track picking operations and completion';
