-- =============================================================
-- Gingersnaps WMS — WIP Module Tables
-- Creates wip_transactions table and adds 'wip' permission rows
-- SAFE TO RE-RUN — uses CREATE TABLE IF NOT EXISTS + INSERT IGNORE
-- =============================================================
-- HOW TO RUN:
--   cPanel phpMyAdmin → select yitrmcft_gingersnaps →
--   Import tab → choose this file → Go
-- =============================================================

-- ── wip_transactions: one row per WIP movement entry ──────────────────────────
CREATE TABLE IF NOT EXISTS `wip_transactions` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `transaction_date` DATE            NOT NULL,
    `stage`            ENUM('RECEIVING','QC','DISPATCHING') NOT NULL,
    `direction`        ENUM('IN','OUT') NOT NULL,
    `qty`              INT UNSIGNED    NOT NULL,
    `ctrl_number`      VARCHAR(100)    NULL DEFAULT NULL,
    `ticket_number`    VARCHAR(100)    NULL DEFAULT NULL,
    `dr_number`        VARCHAR(100)    NULL DEFAULT NULL,
    `subcon_name`      VARCHAR(150)    NULL DEFAULT NULL,
    `collection_name`  VARCHAR(150)    NULL DEFAULT NULL,
    `style_code`       VARCHAR(100)    NULL DEFAULT NULL,
    `po_number`        VARCHAR(100)    NULL DEFAULT NULL,
    `local_export`     ENUM('LOCAL','EXPORT') NULL DEFAULT NULL,
    `remarks`          TEXT            NULL DEFAULT NULL,
    `created_by`       INT UNSIGNED    NULL DEFAULT NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_stage_date` (`stage`, `transaction_date`),
    INDEX `idx_date`        (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Add 'wip' permission rows for all 5 roles ────────────────────────────────
-- Role IDs (from original install): 1=Admin, 2=Manager, 3=Staff, 4=Viewer, 5=QC
-- Adjust role IDs if they differ in your installation.
INSERT IGNORE INTO `permissions` (`role_id`, `module`, `action`)
VALUES
    (1, 'wip', 'view'),   (1, 'wip', 'create'), (1, 'wip', 'edit'), (1, 'wip', 'delete'),
    (2, 'wip', 'view'),   (2, 'wip', 'create'), (2, 'wip', 'edit'),
    (3, 'wip', 'view'),   (3, 'wip', 'create'),
    (4, 'wip', 'view'),
    (5, 'wip', 'view'),   (5, 'wip', 'create');
