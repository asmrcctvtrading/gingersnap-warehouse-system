-- =============================================================
-- Gingersnaps WMS — WIP Opening Balance Seed
-- Seeds wip_period_snapshots with current EWIP values from
-- legacy Google Sheets WIP tab (as of Oct 22-23, 2026).
-- These become the BWIP for the current WIP period in the matrix.
-- SAFE TO RE-RUN — INSERT IGNORE skips duplicates on same date.
-- =============================================================
-- HOW TO RUN:
--   cPanel phpMyAdmin → select yitrmcft_gingersnaps →
--   Import tab → choose this file → Go
-- =============================================================

START TRANSACTION;

INSERT IGNORE INTO `wip_period_snapshots` (`snapshot_date`, `stage`, `ewip_qty`) VALUES
  ('2026-10-23', 'RECEIVING',   0),
  ('2026-10-23', 'QC',        784),
  ('2026-10-23', 'DISPATCHING', 2910);

COMMIT;

-- After running, the WIP Matrix will show:
--   RECEIVING:    BWIP = 0
--   QC:           BWIP = 784
--   DISPATCHING:  BWIP = 2,910
-- (NEW/OUTPUT rows will derive from wip_transactions entered going forward)
-- =============================================================
