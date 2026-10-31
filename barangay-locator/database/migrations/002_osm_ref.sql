-- Supports admin/osm_import.php: remembers the OpenStreetMap object an establishment was imported from,
-- so importing the same area twice does not create duplicates.
-- Fresh installs already have this (schema.sql). Run ONCE on an existing database, in phpMyAdmin → SQL,
-- BEFORE uploading the updated PHP files.
ALTER TABLE establishments
  ADD COLUMN osm_ref VARCHAR(30) NULL AFTER is_sample,
  ADD UNIQUE KEY uq_est_osm (osm_ref);
