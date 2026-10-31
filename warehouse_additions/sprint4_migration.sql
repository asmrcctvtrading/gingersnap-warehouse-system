-- =======================================================
-- Gingersnaps Warehouse — Sprint 4 Migration
-- Stock Movements module: additional performance indexes
--
-- Safe to re-run (IF NOT EXISTS / IGNORE).
-- Run via cPanel phpMyAdmin before deploying Sprint 4 files.
-- =======================================================

SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------
-- stock_movements: add index on created_at
-- migration6.sql already indexed: from_location_id,
-- to_location_id, item_id, po_id, created_by.
-- created_at is NOT indexed there — needed for the
-- date-range filtering on the Stock Ledger page.
-- ---------------------------------------------------------
ALTER TABLE `stock_movements`
    ADD INDEX IF NOT EXISTS `idx_smov_created_at` (`created_at`);

-- ---------------------------------------------------------
-- stock_positions: confirm indexes
-- migration6.sql already has:
--   UNIQUE KEY uq_position (location_id, item_id, po_id)
--   KEY item_id (item_id)
--   KEY po_id   (po_id)
--   KEY updated_by (updated_by)
-- location_id is covered by the UNIQUE key prefix.
-- No additional indexes needed for stock_positions.
-- ---------------------------------------------------------

-- ---------------------------------------------------------
-- No new tables needed for Sprint 4.
-- No new permissions needed — stock (view/create/edit/delete)
-- were inserted in migration6.sql.
-- ---------------------------------------------------------

SET FOREIGN_KEY_CHECKS=1;
