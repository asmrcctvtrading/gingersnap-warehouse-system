# Phase 3 Post-Deployment Verification
**Deployed:** 2026-10-24 19:20 (UTC+8)  
**Status:** Testing Required

## Quick Test Checklist

### ✅ Test 1: DBASED List Page (CRITICAL)
**URL:** https://asmrcctvtechnologies.com/warehouse/modules/dbased/list.php

**Test Steps:**
1. Open the page
2. Check coordinator filter dropdown
3. Try filtering by a coordinator
4. Export CSV

**Expected Results:**
- [ ] Page loads without errors
- [ ] Coordinator dropdown shows: JUDITH, GLENDA, VIVIAN, BETH, ROPHA
- [ ] Coordinator badges display in table rows
- [ ] Destination columns visible (LOCAL/SG/MY/INDO/SM BAL)
- [ ] CSV export includes coordinator + destination data
- [ ] No PHP errors in browser console

**Bug Fix Verification:**
- [ ] Coordinator names appear in dropdown (was broken before fix)

---

### ✅ Test 2: DBASED View Page
**URL:** https://asmrcctvtechnologies.com/warehouse/modules/dbased/view.php?id=1

**Test Steps:**
1. Open any PO detail page
2. Check "PO Identification" section
3. Check "Destination Allocation" card
4. Check "TAT Calculation" section

**Expected Results:**
- [ ] Coordinator badge displays correctly
- [ ] Destination allocation shows 7 columns
- [ ] TAT shows days calculation
- [ ] Edit DBASED button works

---

### ✅ Test 3: DBASED Form/Edit
**URL:** https://asmrcctvtechnologies.com/warehouse/modules/dbased/form.php?id=1

**Test Steps:**
1. Click "Edit DBASED" on any PO
2. Check "Details" tab
3. Switch to "Allocation" tab
4. Try saving a change

**Expected Results:**
- [ ] Coordinator dropdown populated
- [ ] Allocation tab accessible
- [ ] 7 destination fields visible
- [ ] Save works without errors
- [ ] Data persists after save

---

### ✅ Test 4: Tickets Print (NEW FILE)
**URL:** https://asmrcctvtechnologies.com/warehouse/modules/tickets/print.php?id=1

**Test Steps:**
1. Open the print URL with any valid ticket ID
2. Click print button

**Expected Results:**
- [ ] Page loads (no 404 error)
- [ ] Ticket details display
- [ ] Print button functional

**Previously:** This page returned 404  
**Now:** Should render correctly

---

### ✅ Test 5: Gatepass Auto-Insert
**URL:** https://asmrcctvtechnologies.com/warehouse/modules/gatepass/action.php

**Test Steps:**
1. Create or confirm a new gatepass
2. Ensure the gatepass has a ticket_no linked to a PO
3. Check dispatching_priority table

**Database Verification Query:**
```sql
SELECT dp.*, po.po_number, po.style_no 
FROM dispatching_priority dp
LEFT JOIN purchase_orders po ON po.ticket_no = dp.ctrl_number
ORDER BY dp.created_at DESC 
LIMIT 5;
```

**Expected Results:**
- [ ] New record created in `dispatching_priority`
- [ ] `ctrl_number` = ticket_no from gatepass
- [ ] `po_number`, `style_no` populated from PO
- [ ] Destination quantities copied from `po_destination_allocations`
- [ ] `status` = 'NEW'
- [ ] No duplicate records on re-confirm

---

### ✅ Test 6: Purchase Orders Coordinator
**URL:** https://asmrcctvtechnologies.com/warehouse/modules/purchase_orders/form.php

**Test Steps:**
1. Create or edit a PO
2. Select a coordinator
3. Save
4. View the PO

**Expected Results:**
- [ ] Coordinator dropdown shows 5 coordinators
- [ ] Selected coordinator saves correctly
- [ ] Coordinator preserved in quick-actions on view page

---

## 🔍 Error Checking

### Check PHP Error Log
**cPanel Path:** `/home/yitrmcft/public_html/warehouse/error_log`

**Commands to check for errors:**
```bash
# Via cPanel File Manager: View error_log file
# Look for entries timestamped after deployment (2026-10-24 11:20 UTC)
```

**What to look for:**
- [ ] No "Undefined index: name" errors
- [ ] No "Undefined index: coordinator_name" errors
- [ ] No SQL errors related to new columns
- [ ] No missing file warnings

---

## 📊 Database Verification Queries

### 1. Check Coordinator Assignments
```sql
SELECT 
    c.coordinator_name,
    COUNT(po.po_id) as po_count
FROM coordinators c
LEFT JOIN purchase_orders po ON po.coordinator_id = c.coordinator_id
GROUP BY c.coordinator_id, c.coordinator_name
ORDER BY po_count DESC;
```

**Expected:** All 5 coordinators listed, counts match imported data

---

### 2. Check Destination Allocations
```sql
SELECT COUNT(*) as allocated_count
FROM po_destination_allocations;
```

**Expected:** Should match the number of POs with destinations set

---

### 3. Check Dispatching Priority Records
```sql
SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT ctrl_number) as unique_ctrl,
    MAX(created_at) as latest_created
FROM dispatching_priority;
```

**Expected:** 
- Total records: 792+ (792 imported + any new ones)
- Latest_created: Should show today's date if Test 5 passed

---

### 4. Verify No Orphaned Records
```sql
-- Check POs with invalid coordinator_id
SELECT po_id, po_number, coordinator_id
FROM purchase_orders
WHERE coordinator_id IS NOT NULL 
  AND coordinator_id NOT IN (SELECT coordinator_id FROM coordinators);
```

**Expected:** 0 rows (no orphaned records)

---

## 🎯 Success Criteria

### All Tests Pass If:
- [x] All 6 test URLs load without 404/500 errors
- [x] Coordinator dropdown displays correctly (bug fix verified)
- [x] DBASED CSV export includes new columns
- [x] Tickets print page accessible (was 404 before)
- [x] Gatepass auto-insert creates dispatching_priority record
- [x] No PHP errors in error_log since deployment
- [x] Database queries return expected data

---

## 🚨 Rollback Triggers

### Rollback immediately if:
- [ ] Critical 500 errors on DBASED list page
- [ ] Users cannot access any DBASED functions
- [ ] Data corruption detected in database
- [ ] Multiple PHP fatal errors in error_log

### Rollback Steps:
1. Restore the 11 files from pre-deployment backup
2. Clear opcache if enabled: `rm -rf /home/yitrmcft/public_html/warehouse/cache/*`
3. Test DBASED list page loads
4. Investigate root cause before redeploying

---

## ✅ Deployment Verified By

**Tester:** _________________  
**Date/Time:** _________________  
**All Tests Passed:** [ ] Yes [ ] No  
**Issues Found:** _________________  
**Notes:** _________________

---

## 📝 Known Issues (Post-Deployment)

### Issue Log
| # | Issue | Severity | Status | Notes |
|---|-------|----------|--------|-------|
| 1 |       |          |        |       |
| 2 |       |          |        |       |

---

## 📞 Next Steps

### If All Tests Pass:
1. ✅ Mark Phase 3 as **COMPLETE**
2. ✅ Update project_warehouse.md memory
3. ✅ Notify users of new features:
   - Coordinator assignment
   - Destination allocation
   - Enhanced DBASED reports
   - Tickets print page
   - Auto dispatching priority

### If Issues Found:
1. Document in "Known Issues" table above
2. Assess severity (Critical/High/Medium/Low)
3. Create fix plan
4. Test fix in staging/local first
5. Deploy patch

---

**Deployment Package:** phase3_deployment_20261024_191502.zip  
**Files Deployed:** 11 PHP files  
**Database Changes:** None (migrations already applied)  
**Rollback Available:** Yes (pre-deployment backups)
