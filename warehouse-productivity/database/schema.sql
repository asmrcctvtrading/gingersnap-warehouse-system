-- WPMS schema v1 (DRAFT - awaiting architect/user approval; NOT yet executed on any database)
-- Target: MariaDB 10.4+ / MySQL 8.0+, InnoDB, utf8mb4.
-- Safe to run only against the dedicated, EMPTY WPMS database. Contains no DROP statements.
-- Never run against the Warehouse Inventory System database.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version     VARCHAR(40)  NOT NULL PRIMARY KEY,
  applied_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key   VARCHAR(80)  NOT NULL PRIMARY KEY,
  setting_value TEXT         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS warehouses (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(20)  NOT NULL,
  name        VARCHAR(120) NOT NULL,
  address     VARCHAR(255) NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_warehouses_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employees (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  employee_code     VARCHAR(30)  NOT NULL,                 -- business ID; duplicates prevented
  full_name         VARCHAR(150) NOT NULL,
  email             VARCHAR(150) NULL,
  phone             VARCHAR(40)  NULL,
  uniware_username  VARCHAR(255) NULL,                     -- Uniware "User Name" (email-style); maps "Picked By" to this employee; unique when set
  department        VARCHAR(80)  NULL,
  warehouse_id      INT UNSIGNED NULL,
  employment_status ENUM('active','on_leave','inactive','terminated') NOT NULL DEFAULT 'active',
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_employees_code (employee_code),
  UNIQUE KEY uq_employees_uniware (uniware_username),
  KEY ix_employees_wh_status (warehouse_id, employment_status),
  KEY ix_employees_name (full_name),
  CONSTRAINT fk_employees_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username             VARCHAR(50)  NOT NULL,
  password_hash        VARCHAR(255) NOT NULL,             -- password_hash(PASSWORD_DEFAULT)
  role                 ENUM('admin','supervisor','employee') NOT NULL,
  employee_id          INT UNSIGNED NULL,                 -- required for role=employee (enforced in app)
  is_active            TINYINT(1)   NOT NULL DEFAULT 1,
  must_change_password TINYINT(1)   NOT NULL DEFAULT 1,
  failed_logins        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until         DATETIME     NULL,
  last_login_at        DATETIME     NULL,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_employee (employee_id),
  CONSTRAINT fk_users_emp FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warehouse scope for supervisors. Admins see all; no rows for a supervisor = no access.
CREATE TABLE IF NOT EXISTS user_warehouses (
  user_id      INT UNSIGNED NOT NULL,
  warehouse_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, warehouse_id),
  CONSTRAINT fk_uw_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_uw_wh   FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin-configurable task types. Comparisons are only valid within one activity + task type.
CREATE TABLE IF NOT EXISTS task_types (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(30)  NOT NULL,
  name           VARCHAR(100) NOT NULL,
  activity       ENUM('picking','packing') NOT NULL,
  target_seconds INT UNSIGNED NULL,                       -- optional expected duration per task (calculated-metric input)
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uq_task_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_batches (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  original_name    VARCHAR(255) NOT NULL,
  stored_name      VARCHAR(100) NOT NULL,                 -- random name under private storage/imports/
  file_sha256      CHAR(64)     NOT NULL,
  status           ENUM('previewed','committed','discarded') NOT NULL DEFAULT 'previewed',
  rows_total       INT UNSIGNED NOT NULL DEFAULT 0,
  rows_valid       INT UNSIGNED NOT NULL DEFAULT 0,
  rows_invalid     INT UNSIGNED NOT NULL DEFAULT 0,
  rows_duplicate   INT UNSIGNED NOT NULL DEFAULT 0,
  imported_by      INT UNSIGNED NOT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  committed_at     DATETIME     NULL,
  KEY ix_import_sha (file_sha256),
  KEY ix_import_status (status, created_at),
  CONSTRAINT fk_import_user FOREIGN KEY (imported_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_ref         VARCHAR(40)  NOT NULL,                 -- generated, human-readable reference
  activity         ENUM('picking','packing') NOT NULL,
  task_type_id     INT UNSIGNED NULL,
  warehouse_id     INT UNSIGNED NOT NULL,
  employee_id      INT UNSIGNED NULL,                     -- NULL = unassigned
  order_number     VARCHAR(60)  NULL,                     -- app requires it for manual tasks; NULL allowed because one Uniware picklist can span several shipments/orders (per-line values live in task_items)
  package_ref      VARCHAR(60)  NULL,                     -- packing
  picklist_code    VARCHAR(40)  NULL,                     -- Uniware picklist code (e.g. PT60185). One WPMS task = one picklist
  shipment_id      VARCHAR(60)  NULL,                     -- Uniware shipment code (e.g. LOCA23638) when the picklist has exactly one; else NULL
  uniware_type     VARCHAR(40)  NULL,                     -- Uniware picklist "Type", as exported
  generated_by     VARCHAR(100) NULL,                     -- Uniware "Generated By", as exported
  external_status  VARCHAR(30)  NULL,                     -- raw Uniware status (CREATED/PENDING/IN_PROGRESS/COMPLETED); kept for audit
  external_created_at DATETIME  NULL,                     -- Uniware created date. NOT the start of work (includes queue time)
  status           ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',  -- Uniware CREATED and PENDING both map to 'pending'
  due_at           DATETIME     NULL,
  started_at       DATETIME     NULL,                     -- MEASURED in WPMS (Start button). NULL for imported tasks unless the export has a true start time
  completed_at     DATETIME     NULL,                     -- MEASURED in WPMS, or taken from Uniware on import
  duration_seconds INT UNSIGNED NULL,                     -- completed_at - started_at only when started_at is a real start; NULL otherwise (derived, stored for speed)
  source           ENUM('manual','scan','import') NOT NULL DEFAULT 'manual',
  import_batch_id  INT UNSIGNED NULL,
  external_key     CHAR(64)     NULL,                     -- sha256(activity|picklist_code); UNIQUE blocks duplicate imports
  notes            VARCHAR(500) NULL,
  cancel_reason    VARCHAR(255) NULL,
  row_version      INT UNSIGNED NOT NULL DEFAULT 1,       -- optimistic locking
  created_by       INT UNSIGNED NOT NULL,
  updated_by       INT UNSIGNED NULL,
  cancelled_by     INT UNSIGNED NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tasks_ref (task_ref),
  UNIQUE KEY uq_tasks_external (external_key),
  KEY ix_tasks_emp_done   (employee_id, status, completed_at),
  KEY ix_tasks_wh_act_done(warehouse_id, activity, status, completed_at),
  KEY ix_tasks_order      (order_number),
  KEY ix_tasks_picklist   (picklist_code),
  KEY ix_tasks_shipment   (shipment_id),
  KEY ix_tasks_due        (status, due_at),
  KEY ix_tasks_batch      (import_batch_id),
  CONSTRAINT fk_tasks_type  FOREIGN KEY (task_type_id)    REFERENCES task_types(id),
  CONSTRAINT fk_tasks_wh    FOREIGN KEY (warehouse_id)    REFERENCES warehouses(id),
  CONSTRAINT fk_tasks_emp   FOREIGN KEY (employee_id)     REFERENCES employees(id),
  CONSTRAINT fk_tasks_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_tasks_cby   FOREIGN KEY (created_by)      REFERENCES users(id),
  CONSTRAINT fk_tasks_uby   FOREIGN KEY (updated_by)      REFERENCES users(id),
  CONSTRAINT fk_tasks_xby   FOREIGN KEY (cancelled_by)    REFERENCES users(id),
  CONSTRAINT ck_tasks_time  CHECK (completed_at IS NULL OR started_at IS NULL OR completed_at >= started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SKU lines. A picking task usually has 1..n lines; a packing task may list packed items.
CREATE TABLE IF NOT EXISTS task_items (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_id             BIGINT UNSIGNED NOT NULL,
  shipment_code       VARCHAR(60)  NULL,                   -- Uniware shipment (LOCA...) this line belongs to
  order_number        VARCHAR(60)  NULL,                   -- Uniware order number (e.g. 112191_WFR)
  sale_order_item_code VARCHAR(80) NULL,                   -- Uniware Sale_Order_Item_Code
  shelf_code          VARCHAR(60)  NULL,
  batch_code          VARCHAR(60)  NULL,
  ean                 VARCHAR(30)  NULL,
  sku                 VARCHAR(80)  NOT NULL,
  product_description VARCHAR(255) NULL,
  qty_expected        INT UNSIGNED NOT NULL,
  qty_done            INT UNSIGNED NULL,
  entry_method        ENUM('scan','manual') NULL,
  KEY ix_items_task (task_id),
  KEY ix_items_sku (sku),
  CONSTRAINT fk_items_task FOREIGN KEY (task_id) REFERENCES tasks(id),
  CONSTRAINT ck_items_qty  CHECK (qty_expected > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_exceptions (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_id        BIGINT UNSIGNED NOT NULL,
  task_item_id   BIGINT UNSIGNED NULL,
  exception_type ENUM('short_pick','wrong_item','incomplete','damaged','discrepancy','packing_error','rework','other') NOT NULL,
  is_error       TINYINT(1)   NOT NULL DEFAULT 1,         -- counts toward error rate
  is_rework      TINYINT(1)   NOT NULL DEFAULT 0,         -- counts toward rework count
  qty_affected   INT UNSIGNED NULL,
  description    VARCHAR(500) NULL,
  recorded_by    INT UNSIGNED NOT NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_exc_task (task_id),
  KEY ix_exc_type (exception_type, created_at),
  CONSTRAINT fk_exc_task FOREIGN KEY (task_id)      REFERENCES tasks(id),
  CONSTRAINT fk_exc_item FOREIGN KEY (task_item_id) REFERENCES task_items(id),
  CONSTRAINT fk_exc_user FOREIGN KEY (recorded_by)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only task history. App code never UPDATEs or DELETEs rows here.
-- Completed tasks can only change through a 'corrected' event carrying a mandatory reason.
CREATE TABLE IF NOT EXISTS task_events (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_id     BIGINT UNSIGNED NOT NULL,
  event_type  ENUM('created','assigned','reassigned','started','completed','cancelled','corrected','exception_added','imported') NOT NULL,
  from_status VARCHAR(20)  NULL,
  to_status   VARCHAR(20)  NULL,
  old_values  TEXT         NULL,                          -- JSON
  new_values  TEXT         NULL,                          -- JSON
  reason      VARCHAR(255) NULL,
  actor_id    INT UNSIGNED NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_events_task (task_id, created_at),
  CONSTRAINT fk_events_task  FOREIGN KEY (task_id)  REFERENCES tasks(id),
  CONSTRAINT fk_events_actor FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per CSV line, kept for the preview and the rejection report.
CREATE TABLE IF NOT EXISTS import_rows (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  batch_id     INT UNSIGNED NOT NULL,
  row_number   INT UNSIGNED NOT NULL,
  raw_data     TEXT         NOT NULL,                      -- JSON of the original columns
  status       ENUM('valid','invalid','duplicate') NOT NULL,
  errors       VARCHAR(1000) NULL,
  external_key CHAR(64)     NULL,
  task_id      BIGINT UNSIGNED NULL,                       -- set on commit
  KEY ix_irows_batch (batch_id, status),
  CONSTRAINT fk_irows_batch FOREIGN KEY (batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_irows_task  FOREIGN KEY (task_id)  REFERENCES tasks(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System-wide audit trail (logins, config, imports, record changes). Append-only.
CREATE TABLE IF NOT EXISTS audit_log (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  username    VARCHAR(50)  NULL,                          -- snapshot (also for failed logins)
  action      VARCHAR(50)  NOT NULL,                      -- e.g. login_success, login_failed, user_create, task_cancel, import_commit
  entity_type VARCHAR(40)  NULL,
  entity_id   VARCHAR(40)  NULL,
  details     TEXT         NULL,                          -- JSON; never passwords or tokens
  ip_address  VARCHAR(45)  NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_audit_time (created_at),
  KEY ix_audit_user (user_id, created_at),
  KEY ix_audit_entity (entity_type, entity_id),
  KEY ix_audit_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional cache of daily aggregates. DERIVED from tasks and fully rebuildable; reports must not depend on it for correctness.
CREATE TABLE IF NOT EXISTS productivity_daily (
  work_date        DATE         NOT NULL,
  employee_id      INT UNSIGNED NOT NULL,
  warehouse_id     INT UNSIGNED NOT NULL,
  activity         ENUM('picking','packing') NOT NULL,
  tasks_completed  INT UNSIGNED NOT NULL DEFAULT 0,
  total_seconds    BIGINT UNSIGNED NOT NULL DEFAULT 0,
  units_done       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  error_tasks      INT UNSIGNED NOT NULL DEFAULT 0,
  rework_count     INT UNSIGNED NOT NULL DEFAULT 0,
  rebuilt_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (work_date, employee_id, warehouse_id, activity),
  CONSTRAINT fk_pd_emp FOREIGN KEY (employee_id)  REFERENCES employees(id),
  CONSTRAINT fk_pd_wh  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PHASE 2 PLACEHOLDER (CCTV). Created empty so a later release needs no change to core tables.
-- No UI, no camera access, no video storage in release 1. Stores only a reference to
-- footage/incidents held in an authorised external system, with a documented purpose.
CREATE TABLE IF NOT EXISTS external_references (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_id       BIGINT UNSIGNED NOT NULL,
  ref_type      ENUM('cctv_clip','incident') NOT NULL,
  reference     VARCHAR(255) NOT NULL,
  time_from     DATETIME     NULL,
  time_to       DATETIME     NULL,
  purpose_note  VARCHAR(255) NOT NULL,                     -- why access was authorised
  authorised_by INT UNSIGNED NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_extref_task (task_id),
  CONSTRAINT fk_extref_task FOREIGN KEY (task_id)       REFERENCES tasks(id),
  CONSTRAINT fk_extref_user FOREIGN KEY (authorised_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('001_initial');
