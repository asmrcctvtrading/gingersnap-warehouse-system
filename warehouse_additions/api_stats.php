<?php
/**
 * Gingersnaps WMS — Public Stats API
 * GET /warehouse/api_stats.php?token=YOUR_TOKEN
 * Returns JSON with live dashboard stats.
 * CORS-enabled for embedding anywhere.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-Token');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Token auth ───────────────────────────────────────────────────────────
define('WIDGET_TOKEN', 'gs_widget_2026');   // change this to a secret value

$supplied = $_GET['token']
    ?? (preg_match('/^Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $m) ? $m[1] : null)
    ?? ltrim($_SERVER['HTTP_X_TOKEN'] ?? '');

if (!hash_equals(WIDGET_TOKEN, (string) $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ─── DB ──────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';

function q(string $sql, array $params = []): array {
    $st = getDB()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}
function scalar(string $sql, array $params = []): mixed {
    $st = getDB()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

try {
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');

    // PO counts by status
    $poRows = q("SELECT status, COUNT(*) AS cnt FROM purchase_orders GROUP BY status");
    $po = ['NEW' => 0, 'WIP' => 0, 'COMPLETED' => 0, 'CANCELLED' => 0, 'total' => 0];
    foreach ($poRows as $r) {
        $po[$r['status']] = (int) $r['cnt'];
        $po['total'] += (int) $r['cnt'];
    }

    // GRN counts
    $grnTotal   = (int) scalar("SELECT COUNT(*) FROM grn");
    $grnToday   = (int) scalar("SELECT COUNT(*) FROM grn WHERE DATE(created_at) = ?", [$today]);
    $grnMonth   = (int) scalar("SELECT COUNT(*) FROM grn WHERE created_at >= ?", [$monthStart]);

    // Gatepasses
    $gpOpen     = (int) scalar("SELECT COUNT(*) FROM gatepasses WHERE status NOT IN ('DISPATCHED','CANCELLED')");
    $gpTotal    = (int) scalar("SELECT COUNT(*) FROM gatepasses");

    // QC
    $qcPending  = (int) scalar("SELECT COUNT(*) FROM qc_inspections WHERE status NOT IN ('PASSED','FAILED','CANCELLED')");

    // Stock
    $stockQty   = (int) scalar("SELECT COALESCE(SUM(qty_cartons),0) FROM stock_ledger WHERE type='in'")
                - (int) scalar("SELECT COALESCE(SUM(qty_cartons),0) FROM stock_ledger WHERE type='out'");

    // Subcons active
    $subconsActive = (int) scalar("SELECT COUNT(*) FROM subcons WHERE status='active'");

    // SKUs
    $skus = (int) scalar("SELECT COUNT(*) FROM items");

    // Tickets open
    $ticketsOpen = (int) scalar("SELECT COUNT(*) FROM tickets WHERE status IN ('OPEN','IN_PROGRESS')");

    // WIP latest snapshot
    $wip = q("SELECT period_label, qc_wip, dispatching_wip, receiving_wip
               FROM wip_snapshots ORDER BY snapshot_date DESC, snapshot_id DESC LIMIT 1");
    $wipRow = $wip[0] ?? null;

    echo json_encode([
        'generated_at' => date('c'),
        'purchase_orders' => $po,
        'grn' => [
            'total'       => $grnTotal,
            'today'       => $grnToday,
            'this_month'  => $grnMonth,
        ],
        'gatepasses' => [
            'open'  => $gpOpen,
            'total' => $gpTotal,
        ],
        'qc_pending'      => $qcPending,
        'stock_cartons'   => max(0, $stockQty),
        'subcons_active'  => $subconsActive,
        'skus'            => $skus,
        'tickets_open'    => $ticketsOpen,
        'wip'             => $wipRow,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
