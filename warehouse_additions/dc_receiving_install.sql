CREATE TABLE IF NOT EXISTS dc_receivings (
  dc_id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  grn_id           INT UNSIGNED    NULL,
  po_id            INT UNSIGNED    NULL,
  receiving_date   DATE            NOT NULL,
  receiving_time   TIME            NULL,
  receiving_person VARCHAR(255)    NULL,
  dc_type          VARCHAR(30)     NOT NULL DEFAULT 'LOCAL',
  total_qty        INT UNSIGNED    NOT NULL DEFAULT 0,
  defective_qty    INT UNSIGNED    NOT NULL DEFAULT 0,
  status           VARCHAR(20)     NOT NULL DEFAULT 'RECEIVED',
  notes            TEXT            NULL,
  created_by       INT UNSIGNED    NULL,
  created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (dc_id),
  UNIQUE KEY uq_dc_grn (grn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
