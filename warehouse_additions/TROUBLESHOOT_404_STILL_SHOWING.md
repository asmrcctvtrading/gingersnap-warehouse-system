# Troubleshooting: 404 Still Appearing

## Problem
The URL `https://asmrcctvtechnologies.com/warehouse/modules/priorities/list.php` still shows 404 even after fixing the menu.

## Root Causes

### 1. Browser Cache (Most Likely)
Your browser cached the old menu HTML and is still showing the wrong link.

### 2. File Not Uploaded
The fixed `header.php` file wasn't uploaded to the server yet.

### 3. Server PHP Cache
The server has opcode cache (OPcache) enabled and cached the old PHP file.

---

## Solution Steps

### Step 1: Verify File Was Uploaded

Check if you actually uploaded the fixed `header.php` file to:
`/public_html/warehouse/includes/header.php`

**If NOT uploaded yet:**
- Upload it now from: `C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions\includes\header.php`
- Make sure it overwrites the existing file

---

### Step 2: Clear Browser Cache COMPLETELY

**Option A: Hard Refresh (Quick)**
1. Go to any warehouse page
2. Press `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
3. This forces reload without cache

**Option B: Clear All Cache (Thorough)**
1. Press `Ctrl + Shift + Delete`
2. Select "Cached images and files"
3. Select "All time"
4. Click "Clear data"
5. Close and reopen browser

**Option C: Use Incognito/Private Mode**
1. Open a new Incognito window (`Ctrl + Shift + N`)
2. Navigate to the warehouse
3. Test the menu link
4. If it works in Incognito → it's a cache issue

---

### Step 3: Clear Server PHP Cache (If Steps 1-2 Don't Work)

**Method A: Via cPanel (if available)**
1. Login to cPanel
2. Search for "PHP Selector" or "Select PHP Version"
3. Look for "Reset OPcache" or "Clear Cache" button
4. Click it

**Method B: Touch the file**
Upload a tiny whitespace change to force PHP to reload it:
1. Edit `header.php` on the server
2. Add a blank line at the end
3. Save
4. This updates the file timestamp and forces PHP to reload

**Method C: Create a cache-clear script**
Upload this file as `clear_cache.php` to the warehouse root:

```php
<?php
// Clear PHP opcode cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!";
} elseif (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "APC cache cleared successfully!";
} else {
    echo "No opcode cache detected or not accessible.";
}

// Also clear PHP file stat cache
clearstatcache(true);
echo "\nFile stat cache cleared!";
?>
```

Access it once: `https://asmrcctvtechnologies.com/warehouse/clear_cache.php`
Then delete it for security.

---

### Step 4: Verify the Fix

**Test in order:**

1. **Open Incognito window**
2. **Navigate to:** `https://asmrcctvtechnologies.com/warehouse/`
3. **Login**
4. **Click Operations icon** (the gear icon)
5. **Look at the menu**

**What you should see:**
- Column 1: Purchase Orders, GRN/Receiving, QC Inspection (NO "Dispatching Priority" here)
- Column 3: Tickets, SMART Buys, WIP Matrix, **Dispatching Priority** ← ONE entry only

6. **Click "Dispatching Priority"** in Column 3
7. **Should navigate to:** `/modules/dispatching_priority/list.php`
8. **Should show:** The working page with 1,584 records

**If still 404:**
- The old URL is bookmarked or typed manually
- Use the menu link, don't type the URL

---

## Quick Debug: Check What's Actually on Server

**Option 1: View Source**
1. Open any warehouse page
2. Right-click → View Page Source
3. Press `Ctrl + F` and search for: `modules/priorities/list.php`
4. If found → file not uploaded or cache not cleared
5. If NOT found → browser cache issue only

**Option 2: Check File Directly via cPanel**
1. Open cPanel File Manager
2. Navigate to `/public_html/warehouse/includes/`
3. Right-click `header.php` → Edit
4. Press `Ctrl + F` and search for: `priorities/list.php`
5. If found on line 67 → file wasn't uploaded correctly
6. If NOT found → file is correct, clear caches

---

## Summary Checklist

- [ ] Fixed `header.php` file uploaded to server
- [ ] Browser cache cleared (try Incognito mode first)
- [ ] Server PHP cache cleared (if available)
- [ ] Tested in Incognito window
- [ ] Clicked the menu link (not typing URL manually)
- [ ] Verified page loads at `/modules/dispatching_priority/list.php`

---

## Still Not Working?

If after ALL these steps the menu still shows the wrong link:

**Check the live file on server:**
1. cPanel File Manager → `/public_html/warehouse/includes/header.php`
2. Download it
3. Send me the first 100 lines
4. I'll verify what's actually deployed

---

**Most likely cause:** Browser cache. Try Incognito mode first before anything else.
