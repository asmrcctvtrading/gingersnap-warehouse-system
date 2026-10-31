# =============================================================================
# generate_warehouse_sql.ps1  — Gingersnaps Warehouse SQL Import Generator
# PS 5.1 compatible. Run from any directory.
# =============================================================================
$ErrorActionPreference = 'Stop'
$dir   = "C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions\data_import"
# NOTE: variable names chosen to avoid PS case-insensitive collisions.
# Inner loop slices use $grp; outer size stored as $rowN and $fileN.
$rowN  = 200    # INSERT rows per block
$fileN = 5000   # rows per file before splitting

function EscSQL([string]$s) {
    if ($null -eq $s) { return '' }
    return $s.Replace("'", "''")
}
function ToSQL_D([string]$s) {
    if ([string]::IsNullOrWhiteSpace($s)) { return 'NULL' }
    try { return "'" + [DateTime]::Parse($s.Trim()).ToString('yyyy-MM-dd') + "'" } catch { return 'NULL' }
}
function ToSQL_DT([string]$s) {
    if ([string]::IsNullOrWhiteSpace($s)) { return 'NULL' }
    try { return "'" + [DateTime]::Parse($s.Trim()).ToString('yyyy-MM-dd HH:mm:ss') + "'" } catch { return 'NULL' }
}
function MapPOStatus([string]$s) {
    switch ($s.Trim().ToUpper()) {
        'COMPLETE'  { return 'COMPLETED' }
        'COMPLETED' { return 'COMPLETED' }
        'WIP'       { return 'WIP' }
        'CANCELLED' { return 'CANCELLED' }
        'CANCELED'  { return 'CANCELLED' }
        default     { return 'NEW' }
    }
}
function MapGPType([string]$s) {
    if ($s.Trim().ToUpper() -eq 'INTERNAL_CONSUMPTION') { return 'INTERNAL_CONSUMPTION' }
    return 'STOCK_TRANSFER'
}
function MapGPStatus([string]$s) {
    if ($s.Trim().ToUpper() -eq 'CLOSED') { return 'CLOSED' }
    return 'CREATED'
}

# Write string array to UTF-8 (no BOM) file and report stats
function SaveSQL([string]$fpath, [string[]]$content) {
    [System.IO.File]::WriteAllLines($fpath, $content, (New-Object System.Text.UTF8Encoding $false))
    $nm = Split-Path $fpath -Leaf
    $lc = $content.Count
    $kb = [Math]::Round((Get-Item $fpath).Length / 1KB, 1)
    Write-Host ("  {0,-50}  {1,6} lines  {2,7} KB" -f $nm, $lc, $kb)
}

# Build one INSERT IGNORE block; returns string[] of lines to append
function MakeIns([string]$tbl, [string]$cols, [string[]]$valueRows) {
    if ($valueRows.Count -eq 0) { return [string[]]@() }
    $joinedSep = "," + [System.Environment]::NewLine
    $joined    = [string]::Join($joinedSep, $valueRows)
    return [string[]]@(
        "INSERT IGNORE INTO ``$tbl`` ($cols) VALUES",
        ($joined + ";"),
        ""
    )
}

function MakeInsDup([string]$tbl, [string]$cols, [string]$ondup, [string[]]$valueRows) {
    if ($valueRows.Count -eq 0) { return [string[]]@() }
    $joinedSep = "," + [System.Environment]::NewLine
    $joined    = [string]::Join($joinedSep, $valueRows)
    return [string[]]@(
        "INSERT INTO ``$tbl`` ($cols) VALUES",
        $joined,
        ("  ON DUPLICATE KEY UPDATE " + $ondup + ";"),
        ""
    )
}

function SQLHeader([string]$fname, [string]$desc) {
    return [string[]]@(
        "-- ============================================================",
        "-- $fname",
        "-- $desc",
        "-- Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm')",
        "-- Run in phpMyAdmin. Safe to re-run (INSERT IGNORE).",
        "-- ============================================================",
        "SET FOREIGN_KEY_CHECKS=0;",
        "SET NAMES utf8mb4;",
        ""
    )
}

# Accumulate lines into System.Collections.ArrayList; returns nothing
function AppendLines([System.Collections.ArrayList]$list, [string[]]$more) {
    foreach ($ln in $more) { $null = $list.Add($ln) }
}

# ---------------------------------------------------------------------------
Write-Host "`nReading CSV files..."
$csvPO  = Import-Csv "$dir\po.csv"
$csvGRN = Import-Csv "$dir\grn.csv"
$csvPUT = Import-Csv "$dir\putaway.csv"
$csvGP  = Import-Csv "$dir\gatepass.csv"
Write-Host ("  po.csv       {0} rows" -f $csvPO.Count)
Write-Host ("  grn.csv      {0} rows" -f $csvGRN.Count)
Write-Host ("  putaway.csv  {0} rows" -f $csvPUT.Count)
Write-Host ("  gatepass.csv {0} rows" -f $csvGP.Count)

# ===========================================================================
# 01 — items
# ===========================================================================
Write-Host "`nBuilding items..."
$itemDict = @{}
foreach ($row in $csvGRN) {
    $sk = $row.'Item SkuCode'.Trim()
    if ([string]::IsNullOrWhiteSpace($sk) -or $sk -eq '0') { continue }
    if (-not $itemDict.ContainsKey($sk)) {
        $itemDict[$sk] = @{ sk=$sk; nm=$row.'Item Type Name'.Trim(); cl=$row.'Item Type Color'.Trim(); sz=$row.'Item Type Size'.Trim(); br=$row.'Item Type Brand'.Trim(); hn='' }
    }
}
foreach ($row in $csvPO) {
    $sk = $row.'Item SkuCode'.Trim()
    if ([string]::IsNullOrWhiteSpace($sk) -or $sk -eq '0') { continue }
    $hn = $row.'HSN Code'.Trim()
    if (-not $itemDict.ContainsKey($sk)) {
        $itemDict[$sk] = @{ sk=$sk; nm=$row.'Item Type Name'.Trim(); cl=''; sz=''; br=''; hn=$hn }
    } elseif ([string]::IsNullOrWhiteSpace($itemDict[$sk].hn) -and -not [string]::IsNullOrWhiteSpace($hn)) {
        $itemDict[$sk].hn = $hn
    }
}
foreach ($row in $csvPUT) {
    $sk = $row.'Item Type skuCode'.Trim()
    if ([string]::IsNullOrWhiteSpace($sk) -or $sk -eq '0') { continue }
    if (-not $itemDict.ContainsKey($sk)) {
        $itemDict[$sk] = @{ sk=$sk; nm=$row.'Name'.Trim(); cl=''; sz=''; br=''; hn='' }
    }
}
foreach ($row in $csvGP) {
    $sk = $row.'Item SkuCode'.Trim()
    if ([string]::IsNullOrWhiteSpace($sk) -or $sk -eq '0') { continue }
    if (-not $itemDict.ContainsKey($sk)) {
        $itemDict[$sk] = @{ sk=$sk; nm=$row.'Item Name'.Trim(); cl=$row.'Color'.Trim(); sz=$row.'Size'.Trim(); br=$row.'Brand'.Trim(); hn=$row.'HSN Code'.Trim() }
    }
}
$itemList = @($itemDict.GetEnumerator() | ForEach-Object { $_.Value })
Write-Host ("  Unique items: {0}" -f $itemList.Count)

$acc = [System.Collections.ArrayList]::new()
AppendLines $acc (SQLHeader 'import_01_items.sql' 'All unique items from GRN + PO + Putaway + Gatepass')
$iCols = "``sku_code``,``item_name``,``color``,``size``,``brand``,``hsn_code``,``status``"
$idx = 0
while ($idx -lt $itemList.Count) {
    $stop = [Math]::Min($idx + $rowN - 1, $itemList.Count - 1)
    $grp  = @($itemList[$idx..$stop])   # NOTE: $grp != $rowN (different name)
    $vr   = [string[]]($grp | ForEach-Object {
        "  ('$(EscSQL $_.sk)','$(EscSQL $_.nm)','$(EscSQL $_.cl)','$(EscSQL $_.sz)','$(EscSQL $_.br)','$(EscSQL $_.hn)','active')"
    })
    AppendLines $acc (MakeIns 'items' $iCols $vr)
    $idx += $rowN
}
$null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
SaveSQL "$dir\import_01_items.sql" ([string[]]$acc.ToArray())

# ===========================================================================
# 02 — subcons
# ===========================================================================
Write-Host "`nBuilding subcons..."
$subDict = @{}
foreach ($row in $csvPO) {
    $v = $row.'Vendor Name'.Trim()
    if (-not [string]::IsNullOrWhiteSpace($v)) { $subDict[$v] = 1 }
}
$subList = @($subDict.Keys)
Write-Host ("  Unique subcons: {0}" -f $subList.Count)

$acc = [System.Collections.ArrayList]::new()
AppendLines $acc (SQLHeader 'import_02_subcons.sql' 'Unique vendor names from po.csv')
$idx = 0
while ($idx -lt $subList.Count) {
    $stop = [Math]::Min($idx + $rowN - 1, $subList.Count - 1)
    $grp  = @($subList[$idx..$stop])
    $vr   = [string[]]($grp | ForEach-Object { "  ('$(EscSQL $_)')" })
    AppendLines $acc (MakeIns 'subcons' "``subcon_name``" $vr)
    $idx += $rowN
}
$null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
SaveSQL "$dir\import_02_subcons.sql" ([string[]]$acc.ToArray())

# ===========================================================================
# 03 — purchase_orders
# ===========================================================================
Write-Host "`nBuilding purchase orders..."
$poDict = @{}
foreach ($row in $csvPO) {
    $poc = $row.'PO Code'.Trim()
    if ([string]::IsNullOrWhiteSpace($poc)) { continue }
    $qty = 0; [int]::TryParse($row.'Order Quantity'.Trim(), [ref]$qty) | Out-Null
    if (-not $poDict.ContainsKey($poc)) {
        $sty  = if ($poc -match '^([^_]+)_') { $Matches[1] } else { $poc }
        $stat = MapPOStatus $row.'Purchase Order Status'
        $poDict[$poc] = @{
            pn = $poc; sn = $sty; vn = $row.'Vendor Name'.Trim()
            pd = ToSQL_D $row.'Created'; dd = ToSQL_D $row.'Delivery Date'
            fc = $row.'Facility'.Trim(); qt = $qty; st = $stat
            ap = if ($stat -eq 'COMPLETED') { 'approved' } else { 'pending' }
        }
    } else {
        $poDict[$poc].qt += $qty
    }
}
$poList  = @($poDict.GetEnumerator() | ForEach-Object { $_.Value })
Write-Host ("  Unique POs: {0}" -f $poList.Count)

$acc    = [System.Collections.ArrayList]::new()
AppendLines $acc (SQLHeader 'import_03_purchase_orders.sql' 'Unique POs grouped by PO Code')
$poCols = "``po_number``,``style_no``,``subcon_id``,``facility``,``po_qty``,``po_date``,``delivery_date``,``status``,``approval_status``"
$idx = 0
while ($idx -lt $poList.Count) {
    $stop = [Math]::Min($idx + $rowN - 1, $poList.Count - 1)
    $grp  = @($poList[$idx..$stop])
    $vr   = [string[]]($grp | ForEach-Object {
        $xpn = EscSQL $_.pn; $xsn = EscSQL $_.sn; $xvn = EscSQL $_.vn; $xfc = EscSQL $_.fc
        "  ('$xpn','$xsn',(SELECT subcon_id FROM ``subcons`` WHERE subcon_name='$xvn' LIMIT 1),'$xfc',$($_.qt),$($_.pd),$($_.dd),'$($_.st)','$($_.ap)')"
    })
    AppendLines $acc (MakeIns 'purchase_orders' $poCols $vr)
    $idx += $rowN
}
$null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
SaveSQL "$dir\import_03_purchase_orders.sql" ([string[]]$acc.ToArray())

# ===========================================================================
# 04 — warehouse_locations
# ===========================================================================
Write-Host "`nBuilding locations..."
$locDict = @{}
foreach ($row in $csvPUT) {
    $lc = $row.'Shelf Code'.Trim().ToUpper()
    if (-not [string]::IsNullOrWhiteSpace($lc)) { $locDict[$lc] = 1 }
}
foreach ($row in $csvGP) {
    $lc = $row.'Shelf'.Trim().ToUpper()
    if (-not [string]::IsNullOrWhiteSpace($lc)) { $locDict[$lc] = 1 }
}
$locList = @($locDict.Keys)
Write-Host ("  Unique locations: {0}" -f $locList.Count)

$acc    = [System.Collections.ArrayList]::new()
AppendLines $acc (SQLHeader 'import_04_locations.sql' 'Unique shelf codes from putaway + gatepass')
$lCols  = "``location_code``,``zone``,``description``,``status``"
$idx = 0
while ($idx -lt $locList.Count) {
    $stop = [Math]::Min($idx + $rowN - 1, $locList.Count - 1)
    $grp  = @($locList[$idx..$stop])
    $vr   = [string[]]($grp | ForEach-Object {
        $xlc  = EscSQL $_
        $zone = if ($_.Length -gt 0) { EscSQL $_.Substring(0,1) } else { '' }
        "  ('$xlc','$zone','$xlc','active')"
    })
    AppendLines $acc (MakeIns 'warehouse_locations' $lCols $vr)
    $idx += $rowN
}
$null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
SaveSQL "$dir\import_04_locations.sql" ([string[]]$acc.ToArray())

# ===========================================================================
# 05 — stock_positions
# ===========================================================================
Write-Host "`nBuilding stock positions..."
$spDict = @{}
foreach ($row in $csvPUT) {
    if ($row.'Status Code'.Trim().ToUpper() -ne 'COMPLETE') { continue }
    $lc  = $row.'Shelf Code'.Trim().ToUpper()
    $sk  = $row.'Item Type skuCode'.Trim()
    if ([string]::IsNullOrWhiteSpace($lc) -or [string]::IsNullOrWhiteSpace($sk) -or $sk -eq '0') { continue }
    $qt  = 0; [int]::TryParse($row.'Putaway Quantity'.Trim(), [ref]$qt) | Out-Null
    if ($qt -le 0) { continue }
    $key = "${lc}|${sk}"
    if (-not $spDict.ContainsKey($key)) { $spDict[$key] = @{ lc=$lc; sk=$sk; qt=$qt } }
    else { $spDict[$key].qt += $qt }
}
$spList   = @($spDict.GetEnumerator() | ForEach-Object { $_.Value })
Write-Host ("  Unique stock positions: {0}" -f $spList.Count)

$needSplit = $spList.Count -gt $fileN
$fileIdx   = 1
$spCols    = "``location_id``,``item_id``,``po_id``,``qty_cartons``,``qty_pieces``"
$spDup     = "``qty_cartons`` = ``qty_cartons`` + VALUES(``qty_cartons``)"
$fstart    = 0
while ($fstart -lt $spList.Count) {
    $fstop   = [Math]::Min($fstart + $fileN - 1, $spList.Count - 1)
    $fileChk = @($spList[$fstart..$fstop])
    $sfx     = if ($needSplit) { [char]([int][char]'a' + $fileIdx - 1) } else { '' }
    $fname   = "import_05${sfx}_stock_positions.sql"

    $acc = [System.Collections.ArrayList]::new()
    AppendLines $acc (SQLHeader $fname "Stock positions from completed putaway (part $fileIdx)")
    $idx = 0
    while ($idx -lt $fileChk.Count) {
        $stop = [Math]::Min($idx + $rowN - 1, $fileChk.Count - 1)
        $grp  = @($fileChk[$idx..$stop])
        $vr   = [string[]]($grp | ForEach-Object {
            $xlc = EscSQL $_.lc; $xsk = EscSQL $_.sk
            "  ((SELECT location_id FROM ``warehouse_locations`` WHERE location_code='$xlc' LIMIT 1),(SELECT item_id FROM ``items`` WHERE sku_code='$xsk' LIMIT 1),NULL,$($_.qt),0)"
        })
        AppendLines $acc (MakeInsDup 'stock_positions' $spCols $spDup $vr)
        $idx += $rowN
    }
    $null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
    SaveSQL "$dir\$fname" ([string[]]$acc.ToArray())
    $fstart  += $fileN
    $fileIdx++
}

# ===========================================================================
# 06 — gatepasses
# ===========================================================================
Write-Host "`nBuilding gatepasses..."
$gpDict = @{}
foreach ($row in $csvGP) {
    $gc = $row.'Gatepass Code'.Trim()
    if ([string]::IsNullOrWhiteSpace($gc)) { continue }
    if ($gc.Length -gt 20) { $gc = $gc.Substring(0,20) }
    if (-not $gpDict.ContainsKey($gc)) {
        $st = MapGPStatus $row.'Gatepass Status'
        $gpDict[$gc] = @{
            gc = $gc; gt = MapGPType $row.'Type'; gs = $st
            ca = ToSQL_DT $row.'Gatepass Created At'
            tp = $row.'To Party'.Trim(); pu = $row.'Purpose'.Trim(); rf = $row.'Reference'.Trim()
            cl = if ($st -eq 'CLOSED') { ToSQL_DT $row.'Gatepass Updated At' } else { 'NULL' }
        }
    }
}
$gpList  = @($gpDict.GetEnumerator() | ForEach-Object { $_.Value })
Write-Host ("  Unique gatepasses: {0}" -f $gpList.Count)

$acc    = [System.Collections.ArrayList]::new()
AppendLines $acc (SQLHeader 'import_06_gatepasses.sql' 'Gatepass headers grouped by Gatepass Code')
$gpCols = "``gp_code``,``gp_type``,``gp_status``,``created_at``,``to_party``,``purpose``,``reference``,``closed_at``"
$idx = 0
while ($idx -lt $gpList.Count) {
    $stop = [Math]::Min($idx + $rowN - 1, $gpList.Count - 1)
    $grp  = @($gpList[$idx..$stop])
    $vr   = [string[]]($grp | ForEach-Object {
        "  ('$(EscSQL $_.gc)','$($_.gt)','$($_.gs)',$($_.ca),'$(EscSQL $_.tp)','$(EscSQL $_.pu)','$(EscSQL $_.rf)',$($_.cl))"
    })
    AppendLines $acc (MakeIns 'gatepasses' $gpCols $vr)
    $idx += $rowN
}
$null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
SaveSQL "$dir\import_06_gatepasses.sql" ([string[]]$acc.ToArray())

# ===========================================================================
# 07 — gatepass_items
# ===========================================================================
Write-Host "`nBuilding gatepass items..."
$giList = [System.Collections.ArrayList]::new()
foreach ($row in $csvGP) {
    $gc = $row.'Gatepass Code'.Trim()
    if ([string]::IsNullOrWhiteSpace($gc)) { continue }
    if ($gc.Length -gt 20) { $gc = $gc.Substring(0,20) }
    $sk = $row.'Item SkuCode'.Trim()
    if ([string]::IsNullOrWhiteSpace($sk) -or $sk -eq '0') { continue }
    $qt = 0; [int]::TryParse($row.'Quantity'.Trim(), [ref]$qt) | Out-Null
    $null = $giList.Add(@{ gc=$gc; sk=$sk; sh=$row.'Shelf'.Trim().ToUpper(); qt=$qt })
}
$giArr     = @($giList.ToArray())
Write-Host ("  Gatepass item rows: {0}" -f $giArr.Count)

$needSplit = $giArr.Count -gt $fileN
$fileIdx   = 1
$giCols    = "``gp_id``,``item_id``,``from_location_id``,``qty_cartons``"
$fstart    = 0
while ($fstart -lt $giArr.Count) {
    $fstop   = [Math]::Min($fstart + $fileN - 1, $giArr.Count - 1)
    $fileChk = @($giArr[$fstart..$fstop])
    $sfx     = if ($needSplit) { [char]([int][char]'a' + $fileIdx - 1) } else { '' }
    $fname   = "import_07${sfx}_gatepass_items.sql"

    $acc = [System.Collections.ArrayList]::new()
    AppendLines $acc (SQLHeader $fname "Gatepass line items (part $fileIdx)")
    $idx = 0
    while ($idx -lt $fileChk.Count) {
        $stop = [Math]::Min($idx + $rowN - 1, $fileChk.Count - 1)
        $grp  = @($fileChk[$idx..$stop])
        $vr   = [string[]]($grp | ForEach-Object {
            $xgc = EscSQL $_.gc; $xsk = EscSQL $_.sk; $xsh = EscSQL $_.sh
            $lsub = if (-not [string]::IsNullOrWhiteSpace($_.sh)) {
                "(SELECT location_id FROM ``warehouse_locations`` WHERE location_code='$xsh' LIMIT 1)"
            } else { 'NULL' }
            "  ((SELECT gp_id FROM ``gatepasses`` WHERE gp_code='$xgc' LIMIT 1),(SELECT item_id FROM ``items`` WHERE sku_code='$xsk' LIMIT 1),$lsub,$($_.qt))"
        })
        AppendLines $acc (MakeIns 'gatepass_items' $giCols $vr)
        $idx += $rowN
    }
    $null = $acc.Add("SET FOREIGN_KEY_CHECKS=1;")
    SaveSQL "$dir\$fname" ([string[]]$acc.ToArray())
    $fstart  += $fileN
    $fileIdx++
}

# ===========================================================================
Write-Host "`n============================================================"
Write-Host " Done. Files written to: $dir"
Write-Host "============================================================"
Write-Host "`nSQL files:"
Get-ChildItem "$dir\import_0*.sql" | Sort-Object Name | ForEach-Object {
    $lc = (Get-Content $_.FullName).Count
    Write-Host ("  {0,-52}  {1,6} lines  {2,7} KB" -f $_.Name, $lc, [Math]::Round($_.Length/1KB,1))
}
Write-Host "`nImport order in phpMyAdmin (01 → 07):"
Write-Host "  1. import_01_items.sql"
Write-Host "  2. import_02_subcons.sql"
Write-Host "  3. import_03_purchase_orders.sql"
Write-Host "  4. import_04_locations.sql"
Write-Host "  5. import_05_stock_positions.sql (or 05a/05b if split)"
Write-Host "  6. import_06_gatepasses.sql"
Write-Host "  7. import_07_gatepass_items.sql (or 07a/07b/07c if split)"
