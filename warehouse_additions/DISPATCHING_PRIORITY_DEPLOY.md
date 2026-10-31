# Dispatching Priority Module Deployment
**Created:** 2026-10-24 19:33 UTC+8

## Files to Upload (4 total - 21 KB)

Upload to: `/public_html/warehouse/modules/dispatching_priority/`

1. `list.php` (6.42 KB)
2. `form.php` (8.26 KB)
3. `action.php` (6.3 KB)
4. `index.php` (0.04 KB)

---

## ⚠️ Database Requirement

This module requires the `dispatching_priority` table, which doesn't exist yet.

### Option A: Upload Files First, Fix Database Later
- Files will upload successfully
- Page will load but may show "table not found" errors
- Fix database afterwards

### Option B: Fix Database First, Then Upload Files
- Create the table before uploading
- Module will work immediately after upload

---

## Database Setup (Run in phpMyAdmin)

```sql
-- Create dispatching_priority table
CREATE TABLE IF NOT EXISTS `dispatching_priority` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ctrl_number` VARCHAR(50) NOT NULL,
  `po_number` VARCHAR(100) NULL,
  `style_no` VARCHAR(100) NULL,
  `po_date` DATE NULL,
  `priority_rank` INT UNSIGNED NULL,
  `gatepass_order` VARCHAR(30) NULL COMMENT 'Text codes not integers',
  `gatepass_qty` INT NULL,
  `qty_local` INT NULL,
  `qty_sg` INT NULL,
  `qty_malaysia` INT NULL,
  `qty_indo` INT NULL,
  `qty_sm_baliwag` INT NULL,
  `status` ENUM('NEW','DONE','CANCELLED','WIP') NOT NULL DEFAULT 'NEW',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ctrl_number` (`ctrl_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Quick Deploy Instructions

### Method 1: Manual Upload via cPanel
1. Login to cPanel File Manager
2. Navigate to `/public_html/warehouse/modules/`
3. Create folder: `dispatching_priority`
4. Upload the 4 PHP files into that folder
5. Run the database SQL above
6. Access: `https://asmrcctvtechnologies.com/warehouse/modules/dispatching_priority/list.php`

### Method 2: Run Deployment Script
See `deploy_dispatching_priority.ps1` (created below)

---

## Testing After Upload

**URL:** `https://asmrcctvtechnologies.com/warehouse/modules/dispatching_priority/list.php`

**Expected (if database exists):**
- Page loads with empty list or existing records
- "Add New" button visible
- Filters working

**Expected (if database missing):**
- PHP error: "Table 'yitrmcft_gingersnaps.dispatching_priority' doesn't exist"
- Run the CREATE TABLE SQL to fix

---

## Notes

- This module was NOT part of the original 11-file Phase 3 deployment
- Database table may not exist (full phase3_install.sql failed earlier)
- Module can be uploaded independently of other Phase 3 features
- Works standalone once table is created
