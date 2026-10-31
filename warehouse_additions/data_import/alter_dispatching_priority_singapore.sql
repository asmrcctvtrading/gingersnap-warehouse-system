-- =============================================================
-- Gingersnaps WMS — Add qty_singapore to dispatching_priority
-- SINGAPORE column exists in Google Sheets DISPATCHING PRIORITY
-- tab (col P) but was missing from the DB schema.
-- SAFE TO RE-RUN — uses IF NOT EXISTS guard
-- =============================================================
-- HOW TO RUN:
--   cPanel phpMyAdmin → select yitrmcft_gingersnaps →
--   SQL tab → paste and run
-- =============================================================

ALTER TABLE `dispatching_priority`
    ADD COLUMN IF NOT EXISTS `qty_singapore` INT NOT NULL DEFAULT 0
    AFTER `qty_sg`;
