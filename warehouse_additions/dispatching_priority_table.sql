-- Dispatching Priority table
-- Tracks dispatch queue per ctrl number with destination qty breakdown

CREATE TABLE IF NOT EXISTS `dispatching_priority` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `gatepass_order`   INT           NULL,
  `local_export`     ENUM('LOCAL','EXPORT') NULL,
  `ctrl_number`      VARCHAR(30)   NULL,
  `gatepass_created` ENUM('DONE','PENDING') NOT NULL DEFAULT 'PENDING',
  `received_at_dc`   ENUM('Y','N') NULL,
  `gatepass_qty`     INT           NULL DEFAULT 0,
  `status`           ENUM('NEW','DONE','CANCELLED') NOT NULL DEFAULT 'NEW',
  `remarks`          ENUM('GOODS','RSS') NULL,
  `ticket_number`    VARCHAR(30)   NULL,
  `subcon_name`      VARCHAR(100)  NULL,
  `collection_name`  VARCHAR(100)  NULL,
  `qty_local`        INT           NOT NULL DEFAULT 0,
  `qty_sg`           INT           NOT NULL DEFAULT 0,
  `qty_malaysia`     INT           NOT NULL DEFAULT 0,
  `qty_indo`         INT           NOT NULL DEFAULT 0,
  `qty_sm_baliwag`   INT           NOT NULL DEFAULT 0,
  `qty_export_full`  INT           NOT NULL DEFAULT 0,
  `qty_total`        INT GENERATED ALWAYS AS
                       (`qty_local` + `qty_sg` + `qty_malaysia` + `qty_indo` + `qty_sm_baliwag`)
                       STORED,
  `priority_rank`    INT           NULL,
  `created_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_dp_status`   (`status`),
  INDEX `idx_dp_priority` (`priority_rank`),
  INDEX `idx_dp_ctrl`     (`ctrl_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
