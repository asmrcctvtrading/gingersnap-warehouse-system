-- WPMS Phase 1: Picking Errors Table Migration
-- Purpose: Record errors, exceptions, and quality issues in picking operations
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS picking_errors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    picking_task_id INT UNSIGNED NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    error_severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    description TEXT,
    resolution TEXT,
    requires_rework TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,

    FOREIGN KEY (picking_task_id) REFERENCES picking_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_picking_task_id (picking_task_id),
    INDEX idx_error_type (error_type),
    INDEX idx_error_severity (error_severity),
    INDEX idx_requires_rework (requires_rework)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE picking_errors COMMENT = 'Pick Exceptions & Defects - Quality issues and errors in picking tasks';
