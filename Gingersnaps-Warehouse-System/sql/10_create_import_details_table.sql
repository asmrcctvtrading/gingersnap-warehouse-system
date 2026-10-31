-- WPMS Phase 1: Import Details Table Migration
-- Purpose: Map import records to database records and track errors
-- Date: 2026-10-24
-- Status: Ready for production

CREATE TABLE IF NOT EXISTS import_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id INT UNSIGNED NOT NULL,
    source_row_number INT UNSIGNED NOT NULL,
    source_data JSON NOT NULL,
    target_table VARCHAR(100),
    target_record_id INT UNSIGNED,
    status ENUM('success', 'duplicate', 'validation_error', 'skipped') NOT NULL DEFAULT 'success',
    error_message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,

    INDEX idx_import_id (import_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment for documentation
ALTER TABLE import_details COMMENT = 'Import Record Mapping - Map and track individual import records';
