-- Barangay Establishment Locator — schema v1
-- MySQL 8.x / MariaDB 10.x, InnoDB, utf8mb4.
-- Idempotent: safe to run on an empty database. Use migrations/ for later changes.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
  role_id     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_key    VARCHAR(30)  NOT NULL,
  label       VARCHAR(60)  NOT NULL,
  PRIMARY KEY (role_id),
  UNIQUE KEY uq_roles_key (role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS barangays (
  barangay_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name               VARCHAR(120) NOT NULL,
  municipality       VARCHAR(120) NULL,
  province           VARCHAR(120) NULL,
  region             VARCHAR(120) NULL,
  center_lat         DECIMAL(10,7) NULL,
  center_lng         DECIMAL(10,7) NULL,
  default_zoom       TINYINT UNSIGNED NOT NULL DEFAULT 16,
  boundary_geojson   MEDIUMTEXT NULL,            -- only set from a supplied, sourced file
  boundary_source    VARCHAR(255) NULL,          -- required when boundary_geojson is set
  boundary_verified  TINYINT(1) NOT NULL DEFAULT 0,
  hall_address       VARCHAR(255) NULL,
  hall_phone         VARCHAR(60)  NULL,
  is_active          TINYINT(1) NOT NULL DEFAULT 1,
  is_sample          TINYINT(1) NOT NULL DEFAULT 0,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (barangay_id),
  UNIQUE KEY uq_barangay_place (name, municipality, province),
  CONSTRAINT chk_brgy_boundary_src CHECK (boundary_geojson IS NULL OR boundary_source IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(50)  NOT NULL,
  full_name      VARCHAR(120) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  role_id        TINYINT UNSIGNED NOT NULL,
  barangay_id    INT UNSIGNED NULL,               -- NULL = all barangays
  status         ENUM('active','disabled') NOT NULL DEFAULT 'active',
  failed_logins  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until   DATETIME NULL,
  last_login_at  DATETIME NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_username (username),
  KEY ix_users_role (role_id),
  CONSTRAINT fk_users_role  FOREIGN KEY (role_id)     REFERENCES roles(role_id),
  CONSTRAINT fk_users_brgy  FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  category_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(60)  NOT NULL,
  name_en       VARCHAR(120) NOT NULL,
  name_tl       VARCHAR(120) NULL,
  icon          VARCHAR(40)  NOT NULL DEFAULT 'pin',   -- key into assets/js icon map
  color         CHAR(7)      NOT NULL DEFAULT '#2563eb',
  sort_order    INT NOT NULL DEFAULT 0,
  is_essential  TINYINT(1) NOT NULL DEFAULT 0,          -- shown under "Essential services"
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY ix_categories_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS establishments (
  establishment_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  barangay_id          INT UNSIGNED NOT NULL,
  name                 VARCHAR(160) NOT NULL,
  description          TEXT NULL,
  primary_category_id  INT UNSIGNED NOT NULL,
  street_address       VARCHAR(255) NULL,
  landmark             VARCHAR(255) NULL,
  directions_text      TEXT NULL,                       -- staff-written "how to get there"
  zone                 VARCHAR(80)  NULL,               -- purok / sitio / zone
  latitude             DECIMAL(10,7) NULL,
  longitude            DECIMAL(10,7) NULL,
  coords_verified      TINYINT(1) NOT NULL DEFAULT 0,   -- 1 only after a site/map check by staff
  coords_source        VARCHAR(255) NULL,               -- e.g. "GPS reading by encoder", "OSM node 123"
  classification       ENUM('public','private') NOT NULL DEFAULT 'private',
  status               ENUM('pending_verification','verified','needs_review','inactive')
                         NOT NULL DEFAULT 'pending_verification',
  data_source          VARCHAR(255) NULL,               -- where the information came from
  last_verified_at     DATETIME NULL,
  verified_by          INT UNSIGNED NULL,
  hours_note           VARCHAR(255) NULL,
  photo_path           VARCHAR(255) NULL,
  is_sample            TINYINT(1) NOT NULL DEFAULT 0,
  osm_ref              VARCHAR(30) NULL,                -- e.g. "node/123" when imported from OpenStreetMap
  created_by           INT UNSIGNED NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (establishment_id),
  UNIQUE KEY uq_est_osm (osm_ref),
  KEY ix_est_brgy_status (barangay_id, status),
  KEY ix_est_category (primary_category_id),
  KEY ix_est_name (name),
  KEY ix_est_coords (latitude, longitude),
  CONSTRAINT fk_est_brgy     FOREIGN KEY (barangay_id)         REFERENCES barangays(barangay_id),
  CONSTRAINT fk_est_cat      FOREIGN KEY (primary_category_id) REFERENCES categories(category_id),
  CONSTRAINT fk_est_verifier FOREIGN KEY (verified_by)         REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT fk_est_creator  FOREIGN KEY (created_by)          REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT chk_est_lat     CHECK (latitude  IS NULL OR (latitude  BETWEEN -90  AND 90)),
  CONSTRAINT chk_est_lng     CHECK (longitude IS NULL OR (longitude BETWEEN -180 AND 180)),
  CONSTRAINT chk_est_pair    CHECK ((latitude IS NULL) = (longitude IS NULL)),
  CONSTRAINT chk_est_verif_coords CHECK (coords_verified = 0 OR (latitude IS NOT NULL AND longitude IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS establishment_categories (
  establishment_id INT UNSIGNED NOT NULL,
  category_id      INT UNSIGNED NOT NULL,
  PRIMARY KEY (establishment_id, category_id),
  KEY ix_ec_category (category_id),
  CONSTRAINT fk_ec_est FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
  CONSTRAINT fk_ec_cat FOREIGN KEY (category_id)      REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS establishment_contacts (
  contact_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establishment_id INT UNSIGNED NOT NULL,
  contact_type     ENUM('phone','mobile','email','website','facebook') NOT NULL,
  contact_value    VARCHAR(190) NOT NULL,
  is_public        TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (contact_id),
  KEY ix_contacts_est (establishment_id),
  CONSTRAINT fk_contacts_est FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS operating_hours (
  hours_id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establishment_id INT UNSIGNED NOT NULL,
  day_of_week      TINYINT UNSIGNED NOT NULL,        -- 0 = Sunday … 6 = Saturday
  opens_at         TIME NULL,
  closes_at        TIME NULL,
  is_closed        TINYINT(1) NOT NULL DEFAULT 0,
  is_24h           TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (hours_id),
  UNIQUE KEY uq_hours_day (establishment_id, day_of_week),
  CONSTRAINT fk_hours_est FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
  CONSTRAINT chk_hours_dow CHECK (day_of_week BETWEEN 0 AND 6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS facilities (
  facility_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establishment_id     INT UNSIGNED NOT NULL,
  facility_type        ENUM('government','health','safety','education','evacuation','transport','utility','other')
                         NOT NULL DEFAULT 'other',
  is_emergency         TINYINT(1) NOT NULL DEFAULT 0,
  emergency_verified_at DATETIME NULL,               -- public only when set
  emergency_verified_by INT UNSIGNED NULL,
  services_offered     TEXT NULL,
  notes                VARCHAR(255) NULL,
  PRIMARY KEY (facility_id),
  UNIQUE KEY uq_facility_est (establishment_id),
  KEY ix_facility_type (facility_type),
  CONSTRAINT fk_fac_est FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
  CONSTRAINT fk_fac_ver FOREIGN KEY (emergency_verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS verification_records (
  record_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establishment_id INT UNSIGNED NOT NULL,
  action           ENUM('verify','flag_review','deactivate','reactivate','coords_verified','emergency_verified','created','edited')
                     NOT NULL,
  from_status      VARCHAR(30) NULL,
  to_status        VARCHAR(30) NULL,
  evidence         VARCHAR(255) NULL,               -- how it was checked
  note             TEXT NULL,
  user_id          INT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (record_id),
  KEY ix_ver_est (establishment_id, created_at),
  CONSTRAINT fk_ver_est  FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
  CONSTRAINT fk_ver_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS establishment_change_requests (
  request_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  establishment_id  INT UNSIGNED NULL,               -- NULL = proposal for a new establishment
  barangay_id       INT UNSIGNED NOT NULL,
  request_type      ENUM('new','correction','report_incorrect') NOT NULL,
  payload           MEDIUMTEXT NOT NULL,             -- JSON of submitted fields (validated, size-capped)
  submitter_name    VARCHAR(120) NULL,
  submitter_contact VARCHAR(190) NULL,
  status            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by       INT UNSIGNED NULL,
  reviewed_at       DATETIME NULL,
  review_note       VARCHAR(255) NULL,
  ip_hash           CHAR(64) NULL,                   -- sha256(ip + app salt); for rate limiting only
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (request_id),
  KEY ix_cr_status (status, created_at),
  KEY ix_cr_ip (ip_hash, created_at),
  CONSTRAINT fk_cr_est  FOREIGN KEY (establishment_id) REFERENCES establishments(establishment_id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_brgy FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id),
  CONSTRAINT fk_cr_user FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  log_id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NULL,
  username     VARCHAR(50) NULL,
  action       VARCHAR(60) NOT NULL,
  entity_type  VARCHAR(40) NOT NULL,
  entity_id    INT UNSIGNED NULL,
  details      TEXT NULL,
  ip_address   VARCHAR(45) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY ix_audit_entity (entity_type, entity_id),
  KEY ix_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key    VARCHAR(60) NOT NULL,
  setting_value  TEXT NULL,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
