-- ============================================================================
-- Widget System - Phase 1 Migration (CORRECTED)
-- Database: yitrmcft_gingersnaps
-- Purpose: Create customizable dashboard widget system
-- Note: Uses dashboard_widgets table name (matches API code)
-- ============================================================================

-- 1. Dashboard Widgets Table - Store widget configurations per user
CREATE TABLE IF NOT EXISTS dashboard_widgets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    widget_name VARCHAR(255) NOT NULL,
    chart_type ENUM('donut', 'pie', 'bar', 'polar', 'line', 'doughnut') NOT NULL DEFAULT 'bar',
    data_source VARCHAR(100) NOT NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    layout_size ENUM('small', 'medium', 'large') NOT NULL DEFAULT 'medium',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    config JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_user_active (user_id, is_active),
    UNIQUE KEY uk_user_position (user_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Widget Data Cache - Optional caching for chart data
CREATE TABLE IF NOT EXISTS widget_data (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    widget_id INT UNSIGNED NOT NULL,
    data_json JSON NOT NULL,
    cache_valid_until DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (widget_id) REFERENCES dashboard_widgets(id) ON DELETE CASCADE,
    INDEX idx_widget_id (widget_id),
    INDEX idx_cache_valid_until (cache_valid_until),
    UNIQUE KEY uk_widget_id (widget_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Sample widgets for admin user (user_id = 1)
-- NOTE: Adjust user_id based on your actual admin user ID
INSERT IGNORE INTO dashboard_widgets (user_id, widget_name, chart_type, data_source, position, layout_size, is_active, config)
VALUES
    (1, 'Picking by Zone', 'donut', 'picking_by_zone', 0, 'medium', 1, '{"refresh_interval": 300}'),
    (1, 'Hourly Throughput', 'bar', 'hourly_throughput', 1, 'large', 1, '{"refresh_interval": 300}'),
    (1, 'Task Distribution', 'pie', 'task_distribution', 2, 'medium', 1, '{"refresh_interval": 600}'),
    (1, 'Top Performers', 'bar', 'top_performers', 3, 'large', 1, '{"refresh_interval": 900}');

-- 4. Sample widget data (initial cache)
INSERT IGNORE INTO widget_data (widget_id, data_json, cache_valid_until)
SELECT
    id,
    '{"labels": [], "data": []}',
    DATE_ADD(NOW(), INTERVAL 5 MINUTE)
FROM dashboard_widgets
WHERE is_active = 1;

-- ============================================================================
-- Reference: Data Sources
-- ============================================================================
-- picking_by_zone: GROUP BY zone, COUNT tasks
-- hourly_throughput: HOUR(completed_time), COUNT tasks for today
-- task_distribution: GROUP BY task_type, COUNT all
-- top_performers: GROUP BY assigned_to, COUNT completed tasks
