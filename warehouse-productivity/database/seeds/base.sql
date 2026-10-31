-- Reference data loaded by the installer after schema.sql. Idempotent (INSERT IGNORE); no deletes.
-- Default task types are placeholders: rename/add real ones in Admin once it exists.
INSERT IGNORE INTO task_types (code, name, activity, target_seconds, is_active) VALUES
  ('PICK_STD', 'Standard order picking', 'picking', NULL, 1),
  ('PACK_STD', 'Standard order packing', 'packing', NULL, 1);

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
  ('idle_timeout_minutes',   '30'),
  ('absolute_session_hours', '8'),
  ('min_sample',             '10'),
  ('max_task_seconds',       '14400'),
  ('import_allowed_types',   ''),
  ('import_max_rows',        '5000'),
  ('import_max_bytes',       '2097152');
