<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('users', 'view');

$db = getDB();
$step = (int)($_REQUEST['step'] ?? 1);

// ── helpers ──────────────────────────────────────────────────────────────
function getImportableTables(): array {
    return [
        'subcons'        => 'Subcontractors',
        'items'          => 'Items / SKU Master',
        'purchase_orders'=> 'Purchase Orders',
        'parties'        => 'Parties / Destinations',
    ];
}

function describeTable(PDO $db, string $table): array {
    $rows = $db->query("DESCRIBE `{$table}`")->fetchAll();
    $cols = [];
    foreach ($rows as $r) {
        $cols[] = [
            'field'   => $r['Field'],
            'type'    => $r['Type'],
            'null'    => $r['Null'],
            'key'     => $r['Key'],
            'default' => $r['Default'],
        ];
    }
    return $cols;
}

function parseCsvHeaders(string $path): array {
    $fh = fopen($path, 'r');
    $row = fgetcsv($fh);
    fclose($fh);
    return $row ?: [];
}

function parseCsvRows(string $path, int $limit = 5): array {
    $fh   = fopen($path, 'r');
    $head = fgetcsv($fh);           // skip header
    $rows = [];
    $i    = 0;
    while ($i < $limit && ($row = fgetcsv($fh)) !== false) {
        $rows[] = array_combine($head, $row);
        $i++;
    }
    fclose($fh);
    return $rows;
}

function countCsvRows(string $path): int {
    $fh    = fopen($path, 'r');
    fgetcsv($fh);   // skip header
    $count = 0;
    while (fgetcsv($fh) !== false) { $count++; }
    fclose($fh);
    return $count;
}

// ── step 1 : upload form ─────────────────────────────────────────────────
if ($step === 1) {
    $pageTitle = 'Import CSV Data';
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="d-flex align-items-center mb-3 gap-2">
        <h5 class="mb-0">Import CSV Data</h5>
    </div>
    <?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-danger"><?= h($flash) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm mb-4" style="max-width:640px">
        <div class="card-body">
            <p class="text-muted small mb-3">
                Export any sheet from Google Sheets as <strong>CSV</strong>
                (File → Download → CSV), then upload it here.
            </p>
            <form method="post" action="import.php?step=2" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Target Table</label>
                    <select name="target_table" class="form-select" required>
                        <option value="">— choose table —</option>
                        <?php foreach (getImportableTables() as $tbl => $label): ?>
                        <option value="<?= h($tbl) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">CSV File</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                    <div class="form-text">Max 20 MB · UTF-8 or ANSI encoding</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">On duplicate key</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="on_dup" value="skip" id="dup_skip" checked>
                            <label class="form-check-label" for="dup_skip">Skip (keep existing)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="on_dup" value="update" id="dup_update">
                            <label class="form-check-label" for="dup_update">Update (overwrite)</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Next: Map Columns →</button>
            </form>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// ── step 2 : column mapping ──────────────────────────────────────────────
if ($step === 2) {
    verifyCsrf();

    $table  = $_POST['target_table'] ?? '';
    $on_dup = $_POST['on_dup'] ?? 'skip';

    $tables = getImportableTables();
    if (!isset($tables[$table])) {
        setFlash('error', 'Invalid target table.');
        redirect('/modules/admin/import.php');
    }

    if (empty($_FILES['csv_file']['tmp_name'])) {
        setFlash('error', 'No file uploaded.');
        redirect('/modules/admin/import.php');
    }

    // Save CSV to a temp path the session can reference
    $uploadDir = sys_get_temp_dir() . '/wh_import/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0700, true); }
    $tmpPath = $uploadDir . 'import_' . session_id() . '.csv';
    move_uploaded_file($_FILES['csv_file']['tmp_name'], $tmpPath);

    $_SESSION['import_csv']   = $tmpPath;
    $_SESSION['import_table'] = $table;
    $_SESSION['import_on_dup']= $on_dup;

    $dbCols   = describeTable($db, $table);
    $csvHead  = parseCsvHeaders($tmpPath);
    $preview  = parseCsvRows($tmpPath, 3);
    $totalRows= countCsvRows($tmpPath);

    // Auto-suggest mapping: case-insensitive substring match
    $autoMap = [];
    foreach ($dbCols as $col) {
        $autoMap[$col['field']] = '';
        foreach ($csvHead as $h) {
            $norm_db  = strtolower(preg_replace('/[^a-z0-9]/i', '', $col['field']));
            $norm_csv = strtolower(preg_replace('/[^a-z0-9]/i', '', $h));
            if ($norm_db === $norm_csv || strpos($norm_csv, $norm_db) !== false || strpos($norm_db, $norm_csv) !== false) {
                $autoMap[$col['field']] = $h;
                break;
            }
        }
    }

    $pageTitle = 'Map Columns';
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="d-flex align-items-center mb-3 gap-2">
        <a href="import.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0">Map Columns — <span class="text-muted fw-normal"><?= h($tables[$table]) ?></span></h5>
    </div>

    <div class="alert alert-info small">
        File has <strong><?= number_format($totalRows) ?> data rows</strong>.
        Map each database column to a CSV column. Leave blank to skip that field.
    </div>

    <!-- Preview -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold small" style="background:#f8fafc">CSV Preview (first 3 rows)</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 small">
                <thead class="table-light">
                    <tr><?php foreach ($csvHead as $h): ?><th><?= h($h) ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($preview as $row): ?>
                    <tr><?php foreach ($csvHead as $h): ?><td><?= h($row[$h] ?? '') ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mapping form -->
    <form method="post" action="import.php?step=3">
        <?= csrfField() ?>
        <input type="hidden" name="target_table" value="<?= h($table) ?>">
        <input type="hidden" name="on_dup" value="<?= h($on_dup) ?>">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header fw-semibold small" style="background:#f8fafc">Column Mapping</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-5 fw-semibold small text-muted">Database Column</div>
                    <div class="col-7 fw-semibold small text-muted">← CSV Column</div>
                </div>
                <hr class="mt-1 mb-2">
                <?php foreach ($dbCols as $col):
                    // Skip auto-increment PKs
                    if ($col['key'] === 'PRI' && str_contains(strtolower($col['type']), 'int')) continue;
                ?>
                <div class="row g-2 mb-1 align-items-center">
                    <div class="col-5">
                        <span class="font-monospace small"><?= h($col['field']) ?></span>
                        <?php if ($col['key'] === 'UNI'): ?>
                        <span class="badge bg-warning text-dark ms-1" style="font-size:10px">UNIQUE</span>
                        <?php elseif ($col['null'] === 'NO' && $col['default'] === null): ?>
                        <span class="badge bg-danger ms-1" style="font-size:10px">REQUIRED</span>
                        <?php endif; ?>
                        <div class="text-muted" style="font-size:11px"><?= h($col['type']) ?></div>
                    </div>
                    <div class="col-7">
                        <select name="map[<?= h($col['field']) ?>]" class="form-select form-select-sm">
                            <option value="">— skip —</option>
                            <?php foreach ($csvHead as $h): ?>
                            <option value="<?= h($h) ?>" <?= ($autoMap[$col['field']] === $h) ? 'selected' : '' ?>>
                                <?= h($h) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-dark">Preview Import →</button>
            <a href="import.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// ── step 3 : preview ─────────────────────────────────────────────────────
if ($step === 3) {
    verifyCsrf();

    $table   = $_POST['target_table'] ?? $_SESSION['import_table'] ?? '';
    $on_dup  = $_POST['on_dup'] ?? $_SESSION['import_on_dup'] ?? 'skip';
    $map     = $_POST['map'] ?? [];  // [db_col => csv_col]
    $tmpPath = $_SESSION['import_csv'] ?? '';

    $_SESSION['import_map']    = $map;
    $_SESSION['import_on_dup'] = $on_dup;

    if (!$table || !$tmpPath || !file_exists($tmpPath)) {
        setFlash('error', 'Session expired. Please start over.');
        redirect('/modules/admin/import.php');
    }

    $tables  = getImportableTables();
    $preview = parseCsvRows($tmpPath, 10);
    $total   = countCsvRows($tmpPath);

    $pageTitle = 'Preview Import';
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="d-flex align-items-center mb-3 gap-2">
        <h5 class="mb-0">Preview — <span class="text-muted fw-normal"><?= h($tables[$table] ?? $table) ?></span></h5>
    </div>

    <div class="alert alert-warning small">
        Showing first <strong>10 rows</strong> of <strong><?= number_format($total) ?></strong>.
        Confirm to run the full import.
        Duplicate mode: <strong><?= $on_dup === 'update' ? 'Update existing records' : 'Skip duplicates' ?></strong>.
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($map as $db => $csv): if (!$csv) continue; ?>
                        <th><?= h($db) ?><br><span class="text-muted fw-normal" style="font-size:10px"><?= h($csv) ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview as $row): ?>
                    <tr>
                        <?php foreach ($map as $db => $csv): if (!$csv) continue; ?>
                        <td><?= h($row[$csv] ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="import.php?step=4">
        <?= csrfField() ?>
        <input type="hidden" name="target_table" value="<?= h($table) ?>">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check2-circle me-1"></i>Run Import (<?= number_format($total) ?> rows)
            </button>
            <a href="import.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// ── step 4 : execute import ───────────────────────────────────────────────
if ($step === 4) {
    verifyCsrf();

    $table   = $_POST['target_table'] ?? $_SESSION['import_table'] ?? '';
    $map     = $_SESSION['import_map'] ?? [];
    $on_dup  = $_SESSION['import_on_dup'] ?? 'skip';
    $tmpPath = $_SESSION['import_csv'] ?? '';

    if (!$table || !$tmpPath || !file_exists($tmpPath) || empty($map)) {
        setFlash('error', 'Session expired. Please start over.');
        redirect('/modules/admin/import.php');
    }

    $validTables = array_keys(getImportableTables());
    if (!in_array($table, $validTables, true)) {
        setFlash('error', 'Invalid table.');
        redirect('/modules/admin/import.php');
    }

    // Build INSERT statement
    $dbCols  = array_keys(array_filter($map));    // only mapped cols
    $csvCols = array_filter($map);                 // [db => csv]

    if (empty($dbCols)) {
        setFlash('error', 'No columns mapped.');
        redirect('/modules/admin/import.php');
    }

    $colList    = implode(',', array_map(fn($c) => "`{$c}`", $dbCols));
    $paramList  = implode(',', array_fill(0, count($dbCols), '?'));
    $updateList = implode(',', array_map(fn($c) => "`{$c}`=VALUES(`{$c}`)", $dbCols));

    if ($on_dup === 'update') {
        $sql = "INSERT INTO `{$table}` ({$colList}) VALUES ({$paramList})
                ON DUPLICATE KEY UPDATE {$updateList}";
    } else {
        $sql = "INSERT IGNORE INTO `{$table}` ({$colList}) VALUES ({$paramList})";
    }

    $stmt = $db->prepare($sql);

    $fh      = fopen($tmpPath, 'r');
    $head    = fgetcsv($fh);     // header row
    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    $db->beginTransaction();
    try {
        while (($row = fgetcsv($fh)) !== false) {
            $record = array_combine($head, $row);
            $params = [];
            foreach ($dbCols as $dbCol) {
                $csvCol   = $csvCols[$dbCol];
                $val      = trim($record[$csvCol] ?? '');
                $params[] = ($val === '') ? null : $val;
            }
            try {
                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected > 0) { $inserted++; }
                else               { $skipped++;  }
            } catch (PDOException $e) {
                $errors[] = 'Row ' . ($inserted + $skipped + count($errors) + 1) . ': ' . $e->getMessage();
                if (count($errors) >= 20) { break; }  // stop after 20 errors
            }
        }
        fclose($fh);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        fclose($fh);
        setFlash('error', 'Import failed: ' . $e->getMessage());
        redirect('/modules/admin/import.php');
    }

    // Clean up session
    unset($_SESSION['import_csv'], $_SESSION['import_table'], $_SESSION['import_map'], $_SESSION['import_on_dup']);
    @unlink($tmpPath);

    logActivity('IMPORT', 'admin', null, "Imported {$inserted} rows into {$table}");

    $pageTitle = 'Import Complete';
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="d-flex align-items-center mb-3 gap-2">
        <h5 class="mb-0">Import Complete</h5>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="kpi-card" style="border-left-color:#2c7a4b">
                <div class="kpi-label">Rows Imported</div>
                <div class="kpi-value text-success"><?= number_format($inserted) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card" style="border-left-color:#64748b">
                <div class="kpi-label">Rows Skipped (duplicates)</div>
                <div class="kpi-value text-muted"><?= number_format($skipped) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card" style="border-left-color:#dc2626">
                <div class="kpi-label">Errors</div>
                <div class="kpi-value text-danger"><?= count($errors) ?></div>
            </div>
        </div>
    </div>

    <?php if ($errors): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold text-danger small" style="background:#fff5f5">Error Details</div>
        <div class="card-body">
            <ul class="mb-0 small">
                <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
                <?php endforeach; ?>
                <?php if (count($errors) >= 20): ?>
                <li class="text-muted">… stopped after 20 errors</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <a href="import.php" class="btn btn-dark">Import Another File</a>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary">Dashboard</a>
    </div>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Fallback
redirect('/modules/admin/import.php');
