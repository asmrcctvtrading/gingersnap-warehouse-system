-- WPMS Phase 1: Productivity Summaries Table Migration
-- Purpose: Pre-calculated productivity metrics for reporting performance
-- Date: 2026-10-24
-- Status: Ready for production

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

-- Add comment for documentation
ALTER TABLE productivity_summaries COMMENT = 'Daily/Weekly/Monthly Metrics - Pre-calculated productivity metrics for reporting';
