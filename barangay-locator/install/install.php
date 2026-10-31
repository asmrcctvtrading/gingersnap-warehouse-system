<?php
declare(strict_types=1);

/**
 * Web installer. Creates tables, loads reference data, creates the first admin, writes app/config.local.php,
 * then locks itself with storage/installed.lock. Delete the whole /install folder afterwards.
 * Self-contained: it does not use app/bootstrap.php because no config exists yet.
 */
$root = dirname(__DIR__);
$lock = $root . '/storage/installed.lock';
session_name('BRGYINSTALL');
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

if (is_file($lock)) {
    http_response_code(403);
    exit('<!doctype html><meta charset="utf-8"><title>Installed</title><p>Already installed. Delete the <code>install</code> folder from the server.</p>');
}

/** Split a SQL script into statements, respecting quoted strings and -- comments. */
function split_sql(string $sql): array
{
    $out = []; $buf = ''; $len = strlen($sql); $q = null;
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($q !== null) {
            $buf .= $c;
            if ($c === '\\' && $i + 1 < $len) { $buf .= $sql[++$i]; continue; }
            if ($c === $q) {
                if ($i + 1 < $len && $sql[$i + 1] === $q) { $buf .= $sql[++$i]; continue; }
                $q = null;
            }
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $buf .= $c; continue; }
        if ($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") { $i++; }
            $buf .= "\n";
            continue;
        }
        if ($c === ';') {
            if (trim($buf) !== '') { $out[] = trim($buf); }
            $buf = '';
            continue;
        }
        $buf .= $c;
    }
    if (trim($buf) !== '') { $out[] = trim($buf); }
    return $out;
}

function run_sql_file(PDO $pdo, string $file): int
{
    $n = 0;
    foreach (split_sql((string)file_get_contents($file)) as $stmt) {
        $pdo->exec($stmt);
        $n++;
    }
    return $n;
}

$checks = [
    'PHP 8.0 or newer'            => version_compare(PHP_VERSION, '8.0.0', '>='),
    'pdo_mysql extension'         => extension_loaded('pdo_mysql'),
    'mbstring extension'          => extension_loaded('mbstring'),
    'fileinfo extension'          => extension_loaded('fileinfo'),
    'app/ folder writable'        => is_writable($root . '/app'),
    'storage/ folder writable'    => is_writable($root . '/storage'),
    'uploads/ folder writable'    => is_writable($root . '/uploads'),
];
$allOk = !in_array(false, $checks, true);

if (empty($_SESSION['itok'])) { $_SESSION['itok'] = bin2hex(random_bytes(24)); }

$errors = [];
$done = null;
$in = [
    'db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'base_url' => '',
    'admin_user' => 'admin', 'admin_name' => '', 'sample' => '',
];
if ($in['base_url'] === '') {
    $dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/install.php'))), '/');
    $in['base_url'] = $dir === '.' ? '' : $dir;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['itok'], (string)($_POST['itok'] ?? ''))) {
        $errors[] = 'Form expired. Reload and try again.';
    }
    foreach (['db_host', 'db_name', 'db_user', 'base_url', 'admin_user', 'admin_name'] as $k) {
        $in[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $in['sample'] = !empty($_POST['sample']) ? '1' : '';
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $pw  = (string)($_POST['admin_pass'] ?? '');
    $pw2 = (string)($_POST['admin_pass2'] ?? '');

    if (!$allOk) { $errors[] = 'Fix the failed requirements first.'; }
    if ($in['db_name'] === '' || $in['db_user'] === '') { $errors[] = 'Database name and user are required.'; }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $in['admin_user'])) { $errors[] = 'Admin username: 3-50 letters, numbers, . _ -'; }
    if ($in['admin_name'] === '') { $errors[] = 'Admin full name is required.'; }
    if (strlen($pw) < 10 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) { $errors[] = 'Admin password needs 10+ characters with letters and numbers.'; }
    if ($pw !== $pw2) { $errors[] = 'Passwords do not match.'; }
    if ($in['base_url'] !== '' && !preg_match('#^/[A-Za-z0-9._/-]*$#', $in['base_url'])) { $errors[] = 'Base URL must look like /barangay (or be empty).'; }

    if (!$errors) {
        try {
            $pdo = new PDO('mysql:host=' . $in['db_host'] . ';dbname=' . $in['db_name'] . ';charset=utf8mb4', $in['db_user'], $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $n  = run_sql_file($pdo, $root . '/database/schema.sql');
            $n += run_sql_file($pdo, $root . '/database/seeds/base.sql');
            if ($in['sample'] === '1') {
                $n += run_sql_file($pdo, $root . '/database/seeds/sample_data.sql');
            }
            $roleId = (int)$pdo->query("SELECT role_id FROM roles WHERE role_key = 'admin'")->fetchColumn();
            $st = $pdo->prepare('SELECT user_id FROM users WHERE username = ?');
            $st->execute([$in['admin_user']]);
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            if ($st->fetchColumn()) {
                $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role_id = ?, status = \'active\' WHERE username = ?')
                    ->execute([$hash, $in['admin_name'], $roleId, $in['admin_user']]);
            } else {
                $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)')
                    ->execute([$in['admin_user'], $in['admin_name'], $hash, $roleId]);
            }

            $cfg = [
                'db' => ['host' => $in['db_host'], 'name' => $in['db_name'], 'user' => $in['db_user'], 'pass' => $dbPass],
                'base_url' => rtrim($in['base_url'], '/'),
                'app_salt' => bin2hex(random_bytes(24)),
                'debug' => false,
            ];
            $php = "<?php\n// Generated by install.php. Keep private; never commit.\nreturn " . var_export($cfg, true) . ";\n";
            if (file_put_contents($root . '/app/config.local.php', $php, LOCK_EX) === false) {
                throw new RuntimeException('Could not write app/config.local.php');
            }
            @chmod($root . '/app/config.local.php', 0640);
            file_put_contents($lock, 'installed ' . date('c') . "\n");
            $done = ['statements' => $n, 'sample' => $in['sample'] === '1', 'base' => rtrim($in['base_url'], '/')];
            unset($_SESSION['itok']);
        } catch (Throwable $ex) {
            $errors[] = 'Install failed: ' . $ex->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install - Barangay Establishment Locator</title>
<style>
body{font:16px/1.5 system-ui,Segoe UI,Arial,sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem;color:#0f172a}
label{display:block;margin:.8rem 0 .2rem;font-weight:600} input[type=text],input[type=password]{width:100%;padding:.6rem;border:1px solid #94a3b8;border-radius:6px;font-size:1rem}
button{margin-top:1.2rem;padding:.8rem 1.4rem;font-size:1rem;background:#1d4ed8;color:#fff;border:0;border-radius:6px;cursor:pointer}
.err{background:#fee2e2;border:1px solid #fca5a5;padding:.6rem 1rem;border-radius:6px;margin:.5rem 0}
.ok{color:#15803d}.bad{color:#b91c1c}.note{background:#fef9c3;border:1px solid #fde047;padding:.6rem 1rem;border-radius:6px}
</style></head><body>
<h1>Barangay Establishment Locator - Install</h1>
<?php if ($done): ?>
  <p class="ok"><strong>Installed.</strong> <?= (int)$done['statements'] ?> SQL statements ran.</p>
  <?php if ($done['sample']): ?><p class="note">Fictional demo data was loaded. It is labelled SAMPLE and can be removed from Admin → Settings.</p><?php endif; ?>
  <ol>
    <li><strong>Delete the <code>install</code> folder</strong> from the server now.</li>
    <li>Open <a href="<?= e($done['base']) ?>/admin/login.php">the admin login</a> and sign in with the account you just created.</li>
    <li>Add your real barangay under Admin → Barangays. Do not invent coordinates or boundaries.</li>
  </ol>
<?php else: ?>
  <h2>Requirements</h2>
  <ul><?php foreach ($checks as $label => $ok): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FAIL' ?> - <?= e($label) ?></li><?php endforeach; ?></ul>
  <p class="note">Run this installer right after uploading; until it finishes, anyone who can open this page could install. It locks itself when done.</p>
  <?php foreach ($errors as $m): ?><div class="err"><?= e($m) ?></div><?php endforeach; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="itok" value="<?= e($_SESSION['itok']) ?>">
    <h2>Database (create it first in cPanel → MySQL Databases)</h2>
    <label>Host</label><input type="text" name="db_host" value="<?= e($in['db_host']) ?>">
    <label>Database name</label><input type="text" name="db_name" value="<?= e($in['db_name']) ?>">
    <label>Database user</label><input type="text" name="db_user" value="<?= e($in['db_user']) ?>">
    <label>Database password</label><input type="password" name="db_pass">
    <h2>Site</h2>
    <label>Base URL path (e.g. /barangay, empty for domain root)</label><input type="text" name="base_url" value="<?= e($in['base_url']) ?>">
    <h2>First administrator</h2>
    <label>Username</label><input type="text" name="admin_user" value="<?= e($in['admin_user']) ?>">
    <label>Full name</label><input type="text" name="admin_name" value="<?= e($in['admin_name']) ?>">
    <label>Password (10+ chars, letters and numbers)</label><input type="password" name="admin_pass">
    <label>Repeat password</label><input type="password" name="admin_pass2">
    <label><input type="checkbox" name="sample" value="1" <?= $in['sample'] ? 'checked' : '' ?>> Also load FICTIONAL demo data (labelled SAMPLE; not for production)</label>
    <button type="submit" <?= $allOk ? '' : 'disabled' ?>>Install</button>
  </form>
<?php endif; ?>
</body></html>
