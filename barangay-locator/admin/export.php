<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('export.run');

/** Neutralise spreadsheet formula injection: cells starting with = + - @ tab or CR get a leading apostrophe. */
function csv_safe($v): string
{
    $s = (string)($v ?? '');
    return ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) ? "'" . $s : $s;
}

if (($_GET['download'] ?? '') === '1') {
    $scope = userBarangayScope();
    $sql = 'SELECT e.establishment_id, e.name, b.name AS barangay, c.name_en AS category, e.street_address, e.landmark, e.zone,
                   e.latitude, e.longitude, e.coords_verified, e.classification, e.status, e.last_verified_at, e.data_source, e.is_sample
            FROM establishments e JOIN barangays b ON b.barangay_id = e.barangay_id JOIN categories c ON c.category_id = e.primary_category_id';
    $params = [];
    if ($scope !== null) { $sql .= ' WHERE e.barangay_id = ?'; $params[] = $scope; }
    $st = db()->prepare($sql . ' ORDER BY b.name, e.name');
    $st->execute($params);
    logAudit('export', 'establishment', null, 'csv');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="establishments-' . date('Ymd-His') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $first = true;
    while ($r = $st->fetch()) {
        if ($first) { fputcsv($out, array_keys($r)); $first = false; }
        fputcsv($out, array_map('csv_safe', $r));
    }
    if ($first) { fputcsv($out, ['no records']); }
    fclose($out);
    exit;
}

admin_header('Export', 'export');
?>
<h1>Export</h1>
<p>Download all establishments you can access as CSV (opens in Excel). The export includes records of every status, so treat the file as internal.</p>
<p><a class="btn" href="?download=1">Download CSV</a></p>
<?php admin_footer(); ?>
