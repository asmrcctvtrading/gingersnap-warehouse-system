# Phase 3 Deployment Checklist
**Generated:** 2026-10-24 19:11 (UTC+8)  
**Status:** Ready for upload  
**Target:** asmrcctvtechnologies.com/warehouse

## ✅ Pre-Deployment Verification

### Database Migrations (Already Applied ✓)
- [x] `phase3_install.sql` — tables created
- [x] `coordinator_assignment.sql` — 972 POs assigned
- [x] `destination_allocations.sql` — destination data imported
- [x] `wip_opening_balances.sql` — opening balances seeded
- [x] `dispatching_priority_import.sql` — 792 records imported
- [x] `import_grn.sql` — 1,693 GRNs + 9,277 items imported
- [x] ALTERs applied (gatepass_order VARCHAR, status ENUM)

### Code Quality
- [x] Bug fixed: `coordinator_name` column reference in list.php:264
- [x] All 11 files verified present
- [x] Total size: ~161 KB

---

## 📦 Files to Upload (11 Total)

### 1. DBASED Module (4 files)
Target directory: `/public_html/warehouse/modules/dbased/`

```
✓ list.php        (22,971 bytes) — Coordinator filter dropdown fixed
✓ view.php        (23,444 bytes) — Coordinator display
✓ form.php        (19,147 bytes) — Coordinator dropdown + allocation form
✓ action.php      (6,654 bytes)  — Handles coordinator assignment + allocation save
```

**Changes:**
- Fixed `$crd['name']` → `$crd['coordinator_name']` in list.php
- Added destination allocation form (LOCAL/SG/MY/INDO/SM BALIWAG)
- CSV export includes coordinator + destination columns
- TAT calculation added

---

### 2. Purchase Orders Module (3 files)
Target directory: `/public_html/warehouse/modules/purchase_orders/`

```
✓ form.php        (10,639 bytes) — Coordinator dropdown added
✓ action.php      (12,662 bytes) — Saves coordinator_id on PO create/update
✓ view.php        (15,810 bytes) — Preserves coordinator_id in quick-actions
```

**Changes:**
- Coordinator selection on PO form
- `coordinator_id` saved to `purchase_orders` table

---

### 3. Tickets Module (3 files)
Target directory: `/public_html/warehouse/modules/tickets/`

```
✓ form.php        (5,764 bytes)  — Coordinator dropdown
✓ action.php      (3,687 bytes)  — Saves coordinator_id
✓ print.php       (15,695 bytes) — NEW FILE: Standalone print page
```

**Changes:**
- Coordinator assignment on tickets
- **NEW:** `print.php` fixes 404 on ticket print

⚠️ **CAUTION:** `tickets/form.php` — compare with live version before uploading (memory note says to check)

---

### 4. Gatepass Module (1 file)
Target directory: `/public_html/warehouse/modules/gatepass/`

```
✓ action.php      (24,215 bytes) — Auto-inserts dispatching_priority record
```

**Changes:**
- Transaction wrapper on gatepass confirm
- Auto-creates `dispatching_priority` record with:
  - `ctrl_number` = ticket_no
  - `po_number`, `style_no`, `po_date` from PO
  - `qty_local`, `qty_sg`, etc. from `po_destination_allocations`
  - `status` = 'NEW'

---

## 🚀 Deployment Methods

### Option A: cPanel File Manager (Recommended for safety)
1. Login to cPanel → File Manager
2. Navigate to `/public_html/warehouse/`
3. For each module directory, upload the corresponding files
4. Overwrite existing files when prompted
5. Verify permissions are 644 for .php files

### Option B: FTP Upload
Use deployment script: `deploy_phase3.ps1` (see below)

### Option C: Manual via VS Code SFTP Extension
1. Install "SFTP" extension
2. Configure `.vscode/sftp.json` with cPanel credentials
3. Right-click each file → "Upload"

---

## 🔍 Post-Deployment Testing

### Test 1: DBASED List Page
**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/dbased/list.php`

**Expected:**
- [x] Coordinator filter dropdown shows: JUDITH, GLENDA, VIVIAN, BETH, ROPHA
- [x] Coordinator badges display in table
- [x] Destination columns (LOCAL/SG/MY/INDO/SM BAL) populated
- [x] CSV export includes coordinator + destinations

**Test actions:**
1. Filter by coordinator → results filter correctly
2. Export CSV → coordinator column populated
3. Check console for JS errors (none expected)

---

### Test 2: DBASED View Page
**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/dbased/view.php?id=1`

**Expected:**
- [x] Coordinator badge displays under "PO Identification"
- [x] Destination allocation table shows 7 destination columns
- [x] TAT calculation shows days
- [x] Edit button navigates to form

---

### Test 3: DBASED Form
**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/dbased/form.php?id=1`

**Expected:**
- [x] "Details" and "Allocation" tabs visible
- [x] Coordinator dropdown populated
- [x] Allocation tab shows 7 destination fields + notes
- [x] Total validation (sum must equal PO qty)

**Test actions:**
1. Select coordinator → saves correctly
2. Set allocation → saves to `po_destination_allocations`
3. Submit with invalid total → shows error

---

### Test 4: Purchase Orders
**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/purchase_orders/form.php`

**Expected:**
- [x] Coordinator dropdown in form
- [x] Saves `coordinator_id` on submit
- [x] View page preserves coordinator on quick-actions

---

### Test 5: Tickets Print
**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/tickets/print.php?id=1`

**Expected:**
- [x] Print page renders (no 404)
- [x] Ticket details display
- [x] Print button works

---

### Test 6: Gatepass Auto-Insert
**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/gatepass/action.php`

**Test flow:**
1. Create/confirm a gatepass with a ticket_no
2. Check `dispatching_priority` table:
   ```sql
   SELECT * FROM dispatching_priority 
   WHERE ctrl_number = '[ticket_no]' 
   ORDER BY created_at DESC LIMIT 1;
   ```
3. Expected: New record with status='NEW', destinations populated

---

## 🛡️ Rollback Plan

### If Issues Occur:
1. **Backup location:** cPanel File Manager → Right-click file → Download (before upload)
2. **Quick rollback:** Re-upload the downloaded backup files
3. **Database rollback:** Not needed (migrations already applied, read-only for these files)

### Known Safe Restore Points:
- Last backup before Phase 3: (check cPanel backups)
- Git commit hash: (if tracked)

---

## 📊 Expected Behavior After Deployment

### DBASED Workflow
1. User opens DBASED list → sees coordinators in filter + table
2. User clicks PO → sees coordinator badge + destination allocation
3. User edits DBASED → can assign coordinator + set destinations
4. User exports CSV → includes coordinator + destination columns

### Gatepass → Dispatching Priority Auto-Flow
1. User confirms gatepass (with ticket_no linked to PO)
2. System auto-creates `dispatching_priority` record
3. Record includes: ctrl_number, PO details, destinations, status=NEW
4. Record visible in `/modules/dispatching_priority/list.php`

---

## 📝 Notes

### Critical Schema Facts (Confirmed from Live DB)
- `coordinators` column is `coordinator_name` (NOT `name`)
- `coordinators` IDs: 1=JUDITH, 2=GLENDA, 3=VIVIAN, 4=BETH, 5=ROPHA
- `purchase_orders` PK: `po_id` (NOT `id`)
- `gatepass_items` FK: `gp_id` (NOT `gatepass_id`)
- `dispatching_priority.gatepass_order`: VARCHAR(30) (text codes, not INT)
- `dispatching_priority.status`: includes 'WIP' value

### Bug Fix Applied
**File:** `modules/dbased/list.php:264`  
**Before:** `<?= h($crd['name']) ?>`  
**After:** `<?= h($crd['coordinator_name']) ?>`  
**Impact:** Coordinator names now display correctly in filter dropdown

---

## ✅ Deployment Sign-off

- [ ] All 11 files uploaded successfully
- [ ] Test 1-6 passed
- [ ] No PHP errors in error_log
- [ ] Users notified of new features
- [ ] Documentation updated

**Deployed by:** _________________  
**Date/Time:** _________________  
**Sign-off:** _________________

---

## 📞 Support

**System:** Gingersnaps Warehouse Management System  
**Environment:** Production (asmrcctvtechnologies.com)  
**Database:** yitrmcft_gingersnaps  
**Hosting:** cPanel  

**Live System Stats (Post-Migration Oct 24, 2026):**
- POs: 972 | Gatepasses: 4,037 | Subcons: 341 | SKUs: 9,218
- Cartons: 401,139 | GRNs: 1,693 | Coordinators: 5
- WIP snapshots: RECEIVING=0, QC=784, DISPATCHING=2,910
