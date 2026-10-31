# Menu Link Fix - Dispatching Priority

## Issue
Navigation menu had wrong link:
- **Wrong:** `/modules/priorities/list.php` (404 error)
- **Correct:** `/modules/dispatching_priority/list.php`

## Fix Applied
**File:** `includes/header.php` (line 67)

**Changed:**
```php
// OLD - Wrong permission and URL
<?php if (can('priorities','view')): ?>
  <a href="<?= BASE_URL ?>/modules/priorities/list.php">Priority Queues</a>
<?php endif; ?>

// NEW - Correct permission and URL
<?php if (can('dispatching_priority','view')): ?>
  <a href="<?= BASE_URL ?>/modules/dispatching_priority/list.php">Dispatching Priority</a>
<?php endif; ?>
```

## Upload Instructions

**File to upload:** `includes/header.php`
**Server path:** `/public_html/warehouse/includes/header.php`

**Steps:**
1. Login to cPanel File Manager
2. Navigate to `/public_html/warehouse/includes/`
3. Upload `header.php` (overwrite existing)
4. Refresh any warehouse page
5. Click Operations menu → "Dispatching Priority" link should work

## Verification
After upload, the Operations menu should show:
- "Dispatching Priority" (instead of "Priority Queues")
- Clicking it goes to the working page (not 404)

---

**Fixed:** 2026-10-24 19:42 UTC+8
**File location:** `C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions\includes\header.php`
