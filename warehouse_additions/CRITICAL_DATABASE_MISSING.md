# 🚨 CRITICAL: Phase 3 Database Not Installed

## Problem
The PHP files were uploaded, but the **database migrations were never run**.

The `coordinators` table doesn't exist, which means:
- ❌ `coordinators` table missing
- ❌ `po_destination_allocations` table missing  
- ❌ `wip_period_snapshots` table missing
- ❌ `dispatching_priority` table missing (or incomplete)
- ❌ `purchase_orders.coordinator_id` column missing
- ❌ Other Phase 3 columns missing

## 🔧 IMMEDIATE FIX REQUIRED

### Step 1: Run Phase 3 Installation SQL
**File:** `phase3_install.sql`  
**Location:** `C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions\phase3_install.sql`

**How to apply:**
1. Open phpMyAdmin for database `yitrmcft_gingersnaps`
2. Click **Import** tab
3. Choose file: `phase3_install.sql`
4. Click **Go**
5. Wait for "Import has been successfully finished"

**What it creates:**
- `coordinators` table (5 coordinators seeded)
- `po_destination_allocations` table
- `wip_period_snapshots` table  
- `dispatching_priority` table
- Adds columns to `purchase_orders`: `coordinator_id`, `receiving_priority`, `inspection_priority`, `po_number_alias`
- Foreign keys and indexes

---

### Step 2: AFTER phase3_install.sql succeeds, run data imports

#### 2a. Coordinator Assignments
**File:** `data_import/coordinator_assignment.sql`
- Sets `coordinator_id` on existing POs based on legacy DBASED sheet

#### 2b. Destination Allocations  
**File:** `data_import/destination_allocations.sql`
- Populates `po_destination_allocations` with LOCAL/SG/MY/INDO/SM_BALIWAG quantities

#### 2c. WIP Opening Balances
**File:** `data_import/wip_opening_balances.sql`  
- Seeds WIP period snapshots (QC=784, DISPATCHING=2,910)

#### 2d. Dispatching Priority
**File:** `data_import/dispatching_priority_import.sql`
- Imports 792 dispatching priority records from legacy sheet
- **Run the ALTER TABLE commands at the top FIRST**

#### 2e. GRN Import
**File:** `data_import/import_grn.sql`
- Imports 1,693 GRNs + 9,277 GRN items

---

### Step 3: Verify Installation

Run this query to confirm tables exist:
```sql
-- Check Phase 3 tables
SELECT 
    'coordinators' as table_name, COUNT(*) as row_count FROM coordinators
UNION ALL
SELECT 'po_destination_allocations', COUNT(*) FROM po_destination_allocations
UNION ALL
SELECT 'wip_period_snapshots', COUNT(*) FROM wip_period_snapshots
UNION ALL
SELECT 'dispatching_priority', COUNT(*) FROM dispatching_priority;
```

**Expected results:**
- coordinators: 5 rows
- po_destination_allocations: ~972 rows (or whatever was imported)
- wip_period_snapshots: 3 rows
- dispatching_priority: 792+ rows

---

### Step 4: Fix the Schema Mismatch (IMPORTANT)

The `phase3_install.sql` creates the column as `name`, but all PHP files expect `coordinator_name`.

**After Step 1 completes, run this:**
```sql
ALTER TABLE coordinators 
CHANGE COLUMN `name` `coordinator_name` VARCHAR(100) NOT NULL;
```

Then verify:
```sql
SELECT coordinator_id, coordinator_name 
FROM coordinators 
WHERE is_active = 1 
ORDER BY coordinator_name;
```

Should show:
```
1  JUDITH
2  GLENDA
3  VIVIAN
4  BETH
5  ROPHA
```

---

### Step 5: Test DBASED Page Again

After all SQL is run:
1. Clear browser cache (Ctrl+Shift+R)
2. Open: https://asmrcctvtechnologies.com/warehouse/modules/dbased/list.php
3. Check coordinator dropdown
4. Should now show all 5 coordinators

---

## 📂 File Locations (on your PC)

All SQL files are in:  
`C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions\`

```
phase3_install.sql                              ← Run this FIRST
data_import/coordinator_assignment.sql          ← Run after install
data_import/destination_allocations.sql         ← Run after install
data_import/wip_opening_balances.sql           ← Run after install
data_import/dispatching_priority_import.sql    ← Run after install (includes ALTERs)
data_import/import_grn.sql                     ← Run after install
```

---

## ⚠️ Current State

- ✅ PHP files uploaded (11 files deployed)
- ❌ Database not set up (tables missing)
- ❌ DBASED page will not work until database is installed
- ❌ Coordinator dropdown empty because table doesn't exist

---

## 🎯 Action Plan

1. **NOW:** Run `phase3_install.sql` in phpMyAdmin
2. **THEN:** Run the ALTER to rename `name` → `coordinator_name`
3. **OPTIONAL:** Run the 5 data import SQLs if you want the legacy data
4. **FINALLY:** Test DBASED page

Without Step 1, the uploaded PHP files cannot function.

---

## 📞 Need Help?

If you encounter errors during SQL import, save the error message and I'll help troubleshoot.

Common issues:
- Foreign key conflicts → Check if old test data needs cleanup
- Duplicate key errors → Safe to ignore (INSERT IGNORE protection)
- Syntax errors → Check MySQL version compatibility
