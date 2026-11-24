<?php
defined('APP') || exit;

function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float) $v, 2); }
function now(): string { return date('Y-m-d H:i:s'); }
function today(): string { return date('Y-m-d'); }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function redirect(string $url): void { header('Location: ' . $url); exit; }

function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function post_guard(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid or expired form. Go back, reload and try again.');
    }
}

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!current_user()) redirect('login.php'); }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_admin(): void { if (!is_admin()) { http_response_code(403); exit('Admins only.'); } }

function setting(string $k, string $default = ''): string
{
    $v = val('SELECT sval FROM settings WHERE skey = ?', [$k]);
    return $v === null ? $default : (string) $v;
}
function set_setting(string $k, string $v): void
{
    if (one('SELECT skey FROM settings WHERE skey = ?', [$k])) run('UPDATE settings SET sval = ? WHERE skey = ?', [$v, $k]);
    else run('INSERT INTO settings (skey, sval) VALUES (?, ?)', [$k, $v]);
}

/** Change stock at a location. Call inside a transaction. Throws if it would go negative. */
function move_stock(int $pid, int $loc, int $delta, string $reason, string $ref = ''): void
{
    $row = one('SELECT id, qty FROM stock WHERE product_id = ? AND location_id = ?', [$pid, $loc]);
    $cur = $row ? (int) $row['qty'] : 0;
    if ($cur + $delta < 0) {
        $name = val('SELECT name FROM products WHERE id = ?', [$pid]);
        throw new RuntimeException("Not enough stock of {$name} (have {$cur}, need " . abs($delta) . ').');
    }
    if ($row) run('UPDATE stock SET qty = qty + ? WHERE id = ?', [$delta, $row['id']]);
    else run('INSERT INTO stock (product_id, location_id, qty) VALUES (?, ?, ?)', [$pid, $loc, $delta]);
    run('INSERT INTO stock_moves (product_id, location_id, delta, reason, ref, user_id, created_at) VALUES (?,?,?,?,?,?,?)',
        [$pid, $loc, $delta, $reason, $ref, current_user()['id'] ?? null, now()]);
}

/** Run $fn in a transaction; on failure roll back and flash the error. Returns true on success. */
function atomic(callable $fn): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $fn();
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($ex instanceof RuntimeException ? $ex->getMessage() : 'Something went wrong. Nothing was saved.', 'err');
        return false;
    }
}

function doc_no(string $table, string $col, string $prefix, int $id): void
{
    run("UPDATE $table SET $col = ? WHERE id = ?", [$prefix . str_pad((string) $id, 5, '0', STR_PAD_LEFT), $id]);
}

function options(array $rows, $selected = null, string $label = 'name'): string
{
    $h = '';
    foreach ($rows as $r) {
        $h .= '<option value="' . e($r['id']) . '"' . ((string) $selected === (string) $r['id'] ? ' selected' : '') . '>' . e($r[$label]) . '</option>';
    }
    return $h;
}
function badge(string $s): string { return '<span class="badge b-' . e($s) . '">' . e(ucfirst($s)) . '</span>'; }
function locations(): array { return q('SELECT * FROM locations ORDER BY name'); }
function active_products(): array { return q('SELECT id, sku, name FROM products WHERE active = 1 ORDER BY name'); }
function stock_total(int $pid): int { return (int) val('SELECT COALESCE(SUM(qty),0) FROM stock WHERE product_id = ?', [$pid]); }
