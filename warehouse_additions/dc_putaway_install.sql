CREATE TABLE IF NOT EXISTS dc_putaways (
  dcp_id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  dc_id           INT UNSIGNED    NULL,
  gp_id           INT UNSIGNED    NULL,
  gp_code         VARCHAR(50)     NULL,
  putaway_date    DATE            NOT NULL,
  putaway_time    TIME            NULL,
  putaway_person  VARCHAR(255)    NULL,
  warehouse_type  VARCHAR(30)     NOT NULL DEFAULT 'LOCAL',
  total_qty       INT UNSIGNED    NOT NULL DEFAULT 0,
  status          VARCHAR(20)     NOT NULL DEFAULT 'PENDING',
  notes           TEXT            NULL,
  created_by      INT UNSIGNED    NULL,
  created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (dcp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dc_putaway_items (
  dcpi_id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  dcp_id          INT UNSIGNED    NOT NULL,
  location_id     INT UNSIGNED    NULL,
  item_id         INT UNSIGNED    NULL,
  po_id           INT UNSIGNED    NULL,
  sku_code        VARCHAR(100)    NULL,
  item_name       VARCHAR(255)    NULL,
  qty_cartons     INT UNSIGNED    NOT NULL DEFAULT 0,
  notes           VARCHAR(255)    NULL,
  PRIMARY KEY (dcpi_id),
  KEY idx_dcpi_dcp (dcp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
