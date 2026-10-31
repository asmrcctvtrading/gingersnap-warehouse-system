-- ============================================================
-- import_04_locations.sql
-- Unique shelf codes from putaway + gatepass
-- Generated: 2026-10-19 15:20
-- Run in phpMyAdmin. Safe to re-run (INSERT IGNORE).
-- ============================================================
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

INSERT IGNORE INTO `warehouse_locations` (`location_code`,`zone`,`description`,`status`) VALUES
  ('RRO','R','RRO','active'),
  ('SEW','S','SEW','active'),
  ('DDF','D','DDF','active'),
  ('INHOUSEQC-03','I','INHOUSEQC-03','active'),
  ('EXP','E','EXP','active'),
  ('WAS','W','WAS','active'),
  ('INH','I','INH','active'),
  ('PRD','P','PRD','active'),
  ('EXPORT','E','EXPORT','active'),
  ('DEFAULT','D','DEFAULT','active'),
  ('SB','S','SB','active'),
  ('LOC','L','LOC','active');

SET FOREIGN_KEY_CHECKS=1;
