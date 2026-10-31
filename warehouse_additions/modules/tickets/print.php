<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('tickets', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/tickets/list.php'); }

// ── Main query (confirmed tables only) ───────────────────────────────────────
$stmt = $db->prepare(
    "SELECT t.ticket_id,
            t.ticket_no,
            t.status,
            t.priority_no,
            t.qty,
            t.handoff_time,
            t.notes,
            t.po_id,
            t.created_at,
            po.po_number,
            po.style_no,
            po.po_qty,
            po.facility,
            po.collection_id
     FROM tickets t
     LEFT JOIN purchase_orders po ON po.po_id = t.po_id
     WHERE t.ticket_id = ?"
);
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t) { redirect('/modules/tickets/list.php'); }

// ── Subcontractor (via purchase_orders.subcon_id) ────────────────────────────
$subconName = null;
if ($t['po_id']) {
    $subconStmt = $db->prepare(
        "SELECT s.subcon_name
         FROM purchase_orders po
         LEFT JOIN subcons s ON s.subcon_id = po.subcon_id
         WHERE po.po_id = ?"
    );
    $subconStmt->execute([$t['po_id']]);
    $subconRow  = $subconStmt->fetch();
    $subconName = $subconRow['subcon_name'] ?? null;
}

// ── Optional: collection name ─────────────────────────────────────────────────
$collectionName = null;
if ($t['collection_id']) {
    try {
        $colStmt = $db->prepare(
            "SELECT collection_name FROM collections WHERE collection_id = ? LIMIT 1"
        );
        $colStmt->execute([$t['collection_id']]);
        $colRow         = $colStmt->fetch();
        $collectionName = $colRow['collection_name'] ?? null;
    } catch (Throwable $e) {
        // collections table may not exist yet; fall through
    }
}

// ── Optional: dispatching priority ───────────────────────────────────────────
$dispatchPriority = null;
try {
    $dpStmt = $db->prepare(
        "SELECT priority_rank
         FROM dispatching_priority
         WHERE ctrl_number = ?
         LIMIT 1"
    );
    $dpStmt->execute([$t['ticket_no']]);
    $dpRow            = $dpStmt->fetch();
    $dispatchPriority = $dpRow['priority_rank'] ?? null;
} catch (Throwable $e) {
    // dispatching_priority table may not exist yet; fall through
}

// ── Optional: coordinator name ────────────────────────────────────────────────
$coordinatorName = null;
try {
    $coordStmt = $db->prepare(
        "SELECT c.coordinator_name AS coordinator_name
         FROM tickets t2
         JOIN coordinators c ON c.coordinator_id = t2.coordinator_id
         WHERE t2.ticket_id = ?
         LIMIT 1"
    );
    $coordStmt->execute([$id]);
    $coordRow        = $coordStmt->fetch();
    $coordinatorName = $coordRow['coordinator_name'] ?? null;
} catch (Throwable $e) {
    // coordinators / coordinator_id may not exist yet; fall through
}

// ── Optional: po_placeholder ─────────────────────────────────────────────────
$poPlaceholder = null;
try {
    $phStmt = $db->prepare(
        "SELECT po_placeholder FROM tickets WHERE ticket_id = ? LIMIT 1"
    );
    $phStmt->execute([$id]);
    $phRow         = $phStmt->fetch();
    $poPlaceholder = ($phRow && !empty($phRow['po_placeholder'])) ? $phRow['po_placeholder'] : null;
} catch (Throwable $e) {
    // column may not exist yet; fall through
}

// ── Helpers ───────────────────────────────────────────────────────────────────
$dash = function(?string $v): string {
    return ($v !== null && $v !== '') ? htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '&mdash;';
};

$statusColor = match($t['status']) {
    'OPEN'        => '#6c757d',
    'IN_PROGRESS' => '#e6a817',
    'COMPLETED'   => '#198754',
    'CANCELLED'   => '#dc3545',
    default       => '#6c757d',
};

$printedBy   = htmlspecialchars(
    $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown',
    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
);
$printedAt   = date('M j, Y H:i');

// Collection display: name if available, else raw ID
$collectionDisplay = $collectionName ?? ($t['collection_id'] ? 'ID: ' . (int)$t['collection_id'] : null);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ticket <?= htmlspecialchars($t['ticket_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #111;
        background: #fff;
        padding: 24px;
    }

    /* ── no-print controls ── */
    .no-print {
        margin-bottom: 16px;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .btn {
        display: inline-block;
        padding: 6px 14px;
        font-size: 10pt;
        font-family: Arial, sans-serif;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        border: 1px solid #ccc;
        background: #f5f5f5;
        color: #333;
    }
    .btn-primary {
        background: #1a1a2e;
        color: #fff;
        border-color: #1a1a2e;
    }

    /* ── ticket wrapper ── */
    .ticket-wrapper {
        max-width: 700px;
        margin: 0 auto;
        border: 2px solid #222;
        page-break-inside: avoid;
    }

    /* ── header band ── */
    .ticket-header {
        background: #1a1a2e;
        color: #fff;
        padding: 10px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ticket-header .brand { font-size: 13pt; font-weight: bold; letter-spacing: 1px; }
    .ticket-header .doc-type { font-size: 10pt; letter-spacing: 2px; opacity: 0.85; }

    /* ── ctrl / status row ── */
    .ctrl-row {
        background: #f2f2f2;
        padding: 8px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ccc;
    }
    .ctrl-no { font-size: 15pt; font-weight: bold; letter-spacing: 1px; }
    .status-badge {
        font-size: 9pt;
        font-weight: bold;
        padding: 3px 10px;
        border-radius: 3px;
        color: #fff;
        letter-spacing: 1px;
    }

    /* ── placeholder warning ── */
    .placeholder-banner {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 6px 16px;
        font-size: 9pt;
        font-weight: bold;
    }

    /* ── priority band ── */
    .priority-row {
        display: flex;
        border-bottom: 1px solid #ccc;
    }
    .priority-cell {
        flex: 1;
        padding: 8px 16px;
        text-align: center;
        border-right: 1px solid #ccc;
    }
    .priority-cell:last-child { border-right: none; }
    .priority-label {
        font-size: 8pt;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .priority-value {
        font-size: 18pt;
        font-weight: bold;
        color: #1a1a2e;
    }

    /* ── detail section ── */
    .detail-section {
        padding: 8px 16px;
        border-bottom: 1px solid #ccc;
    }
    .detail-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 0;
    }
    .detail-item {
        width: 50%;
        display: flex;
        gap: 6px;
        padding: 2px 0;
    }
    .detail-item.full-width { width: 100%; }
    .detail-label {
        font-size: 8pt;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
        min-width: 90px;
    }
    .detail-value {
        font-size: 9.5pt;
        font-weight: 600;
        color: #111;
        word-break: break-word;
    }

    /* ── wip / handoff band ── */
    .wip-row {
        display: flex;
        border-bottom: 1px solid #ccc;
    }
    .wip-cell {
        flex: 1;
        padding: 8px 16px;
        border-right: 1px solid #ccc;
    }
    .wip-cell:last-child { border-right: none; }
    .wip-label {
        font-size: 8pt;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
    }
    .wip-value {
        font-size: 14pt;
        font-weight: bold;
        color: #1a1a2e;
    }

    /* ── PO table ── */
    .po-section {
        padding: 8px 16px;
        border-bottom: 1px solid #ccc;
    }
    .po-section .section-heading {
        font-size: 8pt;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .po-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }
    .po-table th {
        background: #f0f0f0;
        border: 1px solid #ccc;
        padding: 4px 8px;
        text-align: left;
        font-weight: bold;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .po-table td {
        border: 1px solid #ccc;
        padding: 4px 8px;
    }
    .po-table td.text-right { text-align: right; }

    /* ── notes band ── */
    .notes-row {
        padding: 6px 16px;
        border-bottom: 1px solid #ccc;
        font-size: 9pt;
        color: #444;
    }
    .notes-label {
        font-size: 8pt;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-right: 6px;
    }

    /* ── footer band ── */
    .ticket-footer {
        padding: 6px 16px;
        background: #f9f9f9;
        border-top: 1px solid #ccc;
        font-size: 8pt;
        color: #666;
        display: flex;
        justify-content: space-between;
    }

    /* ── print rules ── */
    @media print {
        body { margin: 0; padding: 0; }
        .no-print { display: none !important; }
        .ticket-wrapper { page-break-inside: avoid; border: 2px solid #222; }
    }
</style>
</head>
<body>

<!-- ── Controls (hidden when printing) ────────────────────────────────────── -->
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">&#128438; Print</button>
    <a href="view.php?id=<?= (int)$id ?>" class="btn">&#8592; Back</a>
</div>

<!-- ── Ticket ──────────────────────────────────────────────────────────────── -->
<div class="ticket-wrapper">

    <!-- Header band -->
    <div class="ticket-header">
        <div>
            <div class="brand">GINGERSNAPS WAREHOUSE</div>
            <div class="doc-type">WORK TICKET</div>
        </div>
    </div>

    <!-- PO Placeholder warning -->
    <?php if ($poPlaceholder): ?>
    <div class="placeholder-banner">
        &#9888; PO PLACEHOLDER: <?= htmlspecialchars($poPlaceholder, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <!-- CTRL No. / Status -->
    <div class="ctrl-row">
        <div class="ctrl-no"><?= $dash($t['ticket_no']) ?></div>
        <div>
            <span class="status-badge" style="background:<?= $statusColor ?>;">
                <?= htmlspecialchars($t['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <!-- Priority band -->
    <div class="priority-row">
        <div class="priority-cell">
            <div class="priority-label">Receiving Priority</div>
            <div class="priority-value"><?= ($t['priority_no'] !== null && $t['priority_no'] !== '') ? (int)$t['priority_no'] : '&mdash;' ?></div>
        </div>
        <div class="priority-cell">
            <div class="priority-label">Dispatching Priority</div>
            <div class="priority-value"><?= ($dispatchPriority !== null) ? htmlspecialchars($dispatchPriority, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '&mdash;' ?></div>
        </div>
    </div>

    <!-- Collection / Subcon / Facility / Coordinator -->
    <div class="detail-section">
        <div class="detail-grid">
            <div class="detail-item full-width">
                <span class="detail-label">Collection</span>
                <span class="detail-value"><?= $dash($collectionDisplay) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Subcon</span>
                <span class="detail-value"><?= $dash($subconName) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Facility</span>
                <span class="detail-value"><?= $dash($t['facility']) ?></span>
            </div>
            <?php if ($coordinatorName !== null): ?>
            <div class="detail-item">
                <span class="detail-label">Coordinator</span>
                <span class="detail-value"><?= htmlspecialchars($coordinatorName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- WIP Qty / Handoff Time -->
    <div class="wip-row">
        <div class="wip-cell">
            <div class="wip-label">WIP Qty</div>
            <div class="wip-value"><?= ($t['qty'] !== null) ? number_format((int)$t['qty']) : '&mdash;' ?></div>
        </div>
        <div class="wip-cell">
            <div class="wip-label">Handoff Time</div>
            <div class="wip-value"><?= $dash($t['handoff_time']) ?></div>
        </div>
    </div>

    <!-- PO Details table -->
    <div class="po-section">
        <div class="section-heading">PO Details</div>
        <?php if ($t['po_id']): ?>
        <table class="po-table">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Style No</th>
                    <th style="text-align:right;">PO Qty</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $dash($t['po_number']) ?></td>
                    <td><?= $dash($t['style_no']) ?></td>
                    <td class="text-right"><?= ($t['po_qty'] !== null) ? number_format((int)$t['po_qty']) : '&mdash;' ?></td>
                </tr>
            </tbody>
        </table>
        <?php else: ?>
        <p style="font-size:9pt;color:#888;">&mdash; No PO linked &mdash;</p>
        <?php endif; ?>
    </div>

    <!-- Notes (shown only if not empty) -->
    <?php if (!empty($t['notes'])): ?>
    <div class="notes-row">
        <span class="notes-label">Notes:</span>
        <?= htmlspecialchars($t['notes'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="ticket-footer">
        <span>Date Created: <?= $dash(substr($t['created_at'] ?? '', 0, 10)) ?></span>
        <span>Printed: <?= htmlspecialchars($printedAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> &nbsp;|&nbsp; By: <?= $printedBy ?></span>
    </div>

</div><!-- /.ticket-wrapper -->

</body>
</html>
