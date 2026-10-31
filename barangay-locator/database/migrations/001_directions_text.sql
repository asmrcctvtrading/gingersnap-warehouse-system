-- Adds a staff-written "how to get there" text to each establishment.
-- Fresh installs already have this column (schema.sql). Run ONCE on an existing database, in phpMyAdmin → SQL.
-- Run it BEFORE uploading the updated PHP files, otherwise pages will error until the column exists.
ALTER TABLE establishments ADD COLUMN directions_text TEXT NULL AFTER landmark;
