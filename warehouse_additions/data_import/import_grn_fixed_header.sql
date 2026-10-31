-- ============================================================
-- import_grn.sql - WITH DATABASE SELECTION
-- GRN header records and line items — Gingersnaps WMS
-- Generated : 2026-10-24
-- Source    : grn.csv  (9277 item rows, 1693 unique GRNs)
-- Safe to re-run  (INSERT IGNORE + unique-key idempotency guard)
--
-- COLUMN NOTE: grn_items uses `qty_cartons` for received quantity
--   (NOT `qty`).  If the live table still has the Sprint-2 column
--   name `qty_received`, rename it first:
--     ALTER TABLE grn_items RENAME COLUMN qty_received TO qty_cartons;
--
-- HOW TO APPLY
--   1. In phpMyAdmin, this SQL will auto-select the database
--   2. Click Import tab → choose this file → Go
--   3. Wait for completion (may take 1-2 minutes)
-- ============================================================

-- SELECT THE DATABASE FIRST
USE `yitrmcft_gingersnaps`;

SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

-- ============================================================
-- Step 0: Idempotency guard for grn_items
-- Unique index on (grn_id, sku_code) ensures INSERT IGNORE skips
-- duplicate rows on every subsequent run.
-- ============================================================
ALTER TABLE `grn_items`
  ADD UNIQUE INDEX IF NOT EXISTS `uq_grni_grn_sku` (`grn_id`, `sku_code`(100));
