-- WPMS Phase 1: Packing Errors Table Migration
-- Purpose: Record quality defects, damages, and discrepancies in packing
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS packing_errors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    packing_task_id INT UNSIGNED NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    quantity_affected INT UNSIGNED,
    description TEXT,
    action_taken TEXT,
    rework_required TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,

    FOREIGN KEY (packing_task_id) REFERENCES packing_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_packing_task_id (packing_task_id),
    INDEX idx_error_type (error_type),
    INDEX idx_rework_required (rework_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE packing_errors COMMENT = 'Packing Quality Issues - Quality defects and discrepancies in packing';
