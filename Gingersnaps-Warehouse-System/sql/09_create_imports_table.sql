-- WPMS Phase 1: Imports Table Migration
-- Purpose: Track all bulk data imports (Unicommerce, employee data, etc.)
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS imports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED,
    total_records INT UNSIGNED,
    successful_records INT UNSIGNED,
    failed_records INT UNSIGNED,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    error_summary TEXT,
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

-- Add comment for documentation
ALTER TABLE imports COMMENT = 'Import Records - Track bulk data imports from external sources';
