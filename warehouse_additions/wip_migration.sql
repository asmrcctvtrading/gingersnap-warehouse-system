-- ─────────────────────────────────────────────────────────────────────────────
-- WIP Matrix module migration
-- Run once against yitrmcft_gingersnaps (or local XAMPP equivalent)
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. WIP transaction ledger
CREATE TABLE IF NOT EXISTS wip_transactions (
    id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    transaction_date DATE             NOT NULL,
    stage            ENUM('RECEIVING','QC','DISPATCHING') NOT NULL,
    direction        ENUM('IN','OUT') NOT NULL,
    qty              INT              NOT NULL,
    ctrl_number      VARCHAR(100)     DEFAULT NULL,
    ticket_number    VARCHAR(100)     DEFAULT NULL,
    dr_number        VARCHAR(100)     DEFAULT NULL,
    subcon_name      VARCHAR(150)     DEFAULT NULL,
    collection_name  VARCHAR(150)     DEFAULT NULL,
    style_code       VARCHAR(100)     DEFAULT NULL,
    po_number        VARCHAR(100)     DEFAULT NULL,
    local_export     ENUM('LOCAL','EXPORT') DEFAULT NULL,
    remarks          TEXT             DEFAULT NULL,
    created_by       INT              DEFAULT NULL,
    created_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_wip_stage_date  (stage, transaction_date),
    KEY idx_wip_date        (transaction_date),
    KEY idx_wip_created_by  (created_by),
    CONSTRAINT fk_wip_tx_user
        FOREIGN KEY (created_by) REFERENCES users (user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. End-of-period EWIP snapshots (used as BWIP seed for the next period)
CREATE TABLE IF NOT EXISTS wip_period_snapshots (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    snapshot_date DATE         NOT NULL,
    stage         ENUM('RECEIVING','QC','DISPATCHING') NOT NULL,
    ewip_qty      INT          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_snapshot (snapshot_date, stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Register module permissions (wip.view and wip.create)
--    Skip if the permissions table does not exist or rows already exist.
INSERT IGNORE INTO permissions (module_key, action_key, description)
VALUES
    ('wip', 'view',   'View WIP Matrix dashboard and transaction log'),
    ('wip', 'create', 'Log, edit, delete WIP entries and save snapshots');

-- 4. Grant wip.view and wip.create to the admin role (role_id = 1).
--    Adjust role_id if your admin role has a different ID.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id
FROM permissions
WHERE module_key = 'wip';
