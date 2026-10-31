# Menu Link Audit - All Navigation Links
**Date:** 2026-10-24 19:45 UTC+8

## Issues Found

### 🔴 CRITICAL: Duplicate "Dispatching Priority" Links
- **Line 67:** First occurrence (WRONG - should be removed)
- **Line 97:** Second occurrence (CORRECT - keep this one)

The line 67 entry was supposed to be **removed entirely**, not just updated!

---

## All Menu Links Audit

### Operations Menu (Icon: Gear)

#### Column 1
1. ❌ **Line 67:** Dispatching Priority (DUPLICATE - DELETE THIS LINE)
2. ✅ **Line 68:** Purchase Orders → `/modules/purchase_orders/list.php`
3. ✅ **Line 69:** GRN / Receiving → `/modules/grn/list.php`
4. ✅ **Line 70:** QC Inspection → `/modules/qc/list.php`

#### Column 2
5. ⚠️ **Line 73:** Putaway → `/modules/putaway/list.php` (check if exists)
6. ✅ **Line 74:** Gatepass → `/modules/gatepass/list.php`
7. ⚠️ **Line 76:** DC Receiving → `/modules/dc_receiving/list.php` (check if exists)
8. ⚠️ **Line 81:** DC Putaway → `/modules/dc_putaway/list.php` (check if exists)
9. ✅ **Line 86:** DBASED → `/modules/dbased/list.php` (deployed Phase 3)
10. ⚠️ **Line 90:** RSS / Other-Process → `/modules/rss/list.php` (check if exists)
11. ⚠️ **Line 91:** Subcontractors → `/modules/subcons/list.php` (check if exists)

#### Column 3
12. ⚠️ **Line 94:** Tickets → `/modules/tickets/list.php` (check if exists)
13. ⚠️ **Line 95:** SMART Buys → `/modules/smart_buys/list.php` (check if exists)
14. ⚠️ **Line 96:** WIP Matrix → `/modules/wip/list.php` (NOT deployed - will 404)
15. ✅ **Line 97:** Dispatching Priority → `/modules/dispatching_priority/list.php` (deployed Phase 3) ✅
16. ⚠️ **Line 98:** Output Records → `/modules/output/list.php` (check if exists)
17. ⚠️ **Line 99:** FOB Shipment → `/modules/fob/list.php` (check if exists)

#### Column 4
18. ⚠️ **Line 102:** Locations → `/modules/locations/list.php` (check if exists)
19. ⚠️ **Line 103:** Stock Ledger → `/modules/stock/list.php` (check if exists)
20. ⚠️ **Line 104:** Movement Ledger → `/modules/stock/ledger.php` (check if exists)
21. ⚠️ **Line 105:** Record Movement → `/modules/stock/move.php` (check if exists)

### Reports Menu
22. ✅ **Line 119:** All Reports → `/modules/reports/list.php`
23. ✅ **Line 120:** WIP Status → `/modules/reports/wip_status.php`
24. ✅ **Line 121:** GRN Report → `/modules/reports/grn_report.php`
25. ✅ **Line 122:** Stock Report → `/modules/reports/stock_report.php`
26. ✅ **Line 123:** Stock Summary → `/modules/reports/stock_summary.php`
27. ✅ **Line 124:** PO Aging → `/modules/reports/po_aging.php`
28. ✅ **Line 125:** Movement Summary → `/modules/reports/movement_summary.php`

### Catalog Menu
29. ⚠️ **Line 139:** Items / SKU Master → `/modules/items/list.php` (check if exists)
30. ⚠️ **Line 140:** Parties / Destinations → `/modules/parties/list.php` (check if exists)

### Administration Menu
31. ⚠️ **Line 154:** User Management → `/modules/users/list.php` (check if exists)
32. ⚠️ **Line 155:** Roles & Permissions → `/modules/roles/list.php` (check if exists)
33. ⚠️ **Line 156:** Alerts → `/modules/alerts/list.php` (check if exists)
34. ⚠️ **Line 157:** Backup & Restore → `/modules/admin/backup.php` (check if exists)
35. ⚠️ **Line 158:** Performance Criteria → `/modules/admin/criteria.php` (check if exists)
36. ⚠️ **Line 159:** API Tokens → `/modules/admin/api_tokens.php` (check if exists)
37. ⚠️ **Line 160:** Import CSV → `/modules/admin/import.php` (check if exists)
38. ⚠️ **Line 161:** Subcontractors → `/modules/subcontractors/list.php` (check if exists)
39. ⚠️ **Line 162:** Audit Log → `/modules/audit/list.php` (check if exists)

---

## 🔧 Required Fix

### Delete Line 67 Entirely

The correct fix is to **REMOVE** line 67 completely, not update it.

**Line 67 should be deleted:**
```php
<?php if (can('dispatching_priority','view')): ?><a href="<?= BASE_URL ?>/modules/dispatching_priority/list.php" class="<?= navActive('dispatching_priority','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-list-ol me-2"></i>Dispatching Priority</a><?php endif; ?>
```

Line 97 already has the correct Dispatching Priority link in the right place (Column 3).

---

## 📝 What Happened

The original line 67 was:
```php
Priority Queues → /modules/priorities/list.php
```

I incorrectly updated it to:
```php
Dispatching Priority → /modules/dispatching_priority/list.php
```

But there was **already** a correct Dispatching Priority link on line 97!

So now there are TWO "Dispatching Priority" menu items, and the first one (line 67) shouldn't exist at all.

---

## ✅ Correct Solution

**Remove line 67 entirely** so the menu structure becomes:

**Column 1:**
- Purchase Orders
- GRN / Receiving
- QC Inspection

**Column 3 (existing):**
- Tickets
- SMART Buys
- WIP Matrix
- **Dispatching Priority** ← Only one, on line 97
- Output Records
- FOB Shipment

---

## 🚀 Action Required

Re-upload `header.php` with line 67 **deleted**.

I'll create the corrected version now.
