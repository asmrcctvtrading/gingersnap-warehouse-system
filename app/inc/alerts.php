<?php
defined('APP') || exit;

/** Builds the alert text from current data and the notification settings. Returns '' when there is nothing to report. */
function build_alerts(): string
{
    $out = [];
    if (setting('alert_low_stock', '1') === '1') {
        $low = q('SELECT p.name, p.sku, p.reorder_level, COALESCE(SUM(s.qty),0) AS total FROM products p LEFT JOIN stock s ON s.product_id = p.id
                  WHERE p.active = 1 GROUP BY p.id, p.name, p.sku, p.reorder_level HAVING COALESCE(SUM(s.qty),0) <= p.reorder_level ORDER BY p.name');
        if ($low) {
            $out[] = "LOW STOCK:";
            foreach ($low as $r) $out[] = "  - {$r['name']} ({$r['sku']}): {$r['total']} left, reorder at {$r['reorder_level']}";
        }
    }
    if (setting('alert_expiry', '1') === '1') {
        $days = max(1, (int) setting('expiry_days', '30'));
        $until = date('Y-m-d', strtotime("+$days days"));
        $exp = q("SELECT b.lot_no, b.qty, b.expiry_date, p.name FROM batches b JOIN products p ON p.id = b.product_id
                  WHERE b.status = 'released' AND b.expiry_date IS NOT NULL AND b.expiry_date <= ? ORDER BY b.expiry_date", [$until]);
        if ($exp) {
            $out[] = ($out ? "\n" : '') . "EXPIRING WITHIN $days DAYS (or already expired):";
            foreach ($exp as $r) $out[] = "  - {$r['name']} lot {$r['lot_no']}: {$r['qty']} units, expires {$r['expiry_date']}";
        }
    }
    return implode("\n", $out);
}

function send_alerts(): string
{
    $to = trim(setting('alert_email'));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return 'No valid alert email is set.';
    $body = build_alerts();
    if ($body === '') return 'Nothing to report. No email sent.';
    global $CFG;
    $ok = mail($to, '[' . ($CFG['app_name'] ?? 'Gingersnap') . '] Inventory alerts', $body, 'Content-Type: text/plain; charset=UTF-8');
    return $ok ? "Alert email sent to $to." : 'The server refused to send the email. Check your hosting mail settings.';
}
