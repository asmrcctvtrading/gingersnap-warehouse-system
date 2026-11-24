<?php
// One-time installer. Delete this file after it succeeds.
define('APP', 1);
define('ROOT', __DIR__);
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/schema.php';
function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$done = false; $err = '';
if (is_file(ROOT . '/config.php')) { http_response_code(403); exit('Already installed. Delete install.php from the server.'); }
$v = $_POST + ['driver' => 'mysql', 'host' => 'localhost', 'name' => '', 'user' => '', 'pass' => '', 'company' => 'Gingersnap', 'admin_name' => '', 'admin_email' => '', 'admin_pass' => '', 'tz' => 'UTC'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL) || strlen($v['admin_pass']) < 8 || trim($v['admin_name']) === '') throw new Exception('Admin name, a valid email and a password of 8+ characters are required.');
        if (!in_array($v['tz'], timezone_identifiers_list(), true)) throw new Exception('Unknown timezone.');
        $driver = $v['driver'] === 'sqlite' ? 'sqlite' : 'mysql';
        $dbc = ['driver' => $driver, 'host' => trim($v['host']), 'name' => trim($v['name']), 'user' => trim($v['user']), 'pass' => $v['pass'], 'sqlite_path' => ROOT . '/data/app.sqlite'];
        $pdo = db_connect($dbc);
        foreach (schema_sql($driver) as $sql) $pdo->exec($sql);
        $n = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($n > 0) throw new Exception('This database already has users. Use an empty database.');
        $st = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)');
        $st->execute([trim($v['admin_name']), strtolower(trim($v['admin_email'])), password_hash($v['admin_pass'], PASSWORD_DEFAULT), 'admin', date('Y-m-d H:i:s')]);
        foreach ([['Main Warehouse', 'warehouse'], ['Lab', 'lab'], ['Shop', 'shop']] as $l) $pdo->prepare('INSERT INTO locations (name,type) VALUES (?,?)')->execute($l);
        $pdo->prepare('INSERT INTO settings (skey,sval) VALUES (?,?)')->execute(['company', trim($v['company']) ?: 'Gingersnap']);
        $cfg = ['app_name' => trim($v['company']) ?: 'Gingersnap', 'timezone' => $v['tz'], 'db' => $dbc];
        if (file_put_contents(ROOT . '/config.php', "<?php\nreturn " . var_export($cfg, true) . ";\n") === false) throw new Exception('Could not write config.php. Make this folder writable and retry.');
        @chmod(ROOT . '/config.php', 0640);
        $done = true;
    } catch (Throwable $ex) { $err = $ex instanceof PDOException ? 'Database error: ' . $ex->getMessage() : $ex->getMessage(); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Install</title>
<link rel="stylesheet" href="assets/app.css"></head><body class="authbody">
<?php if ($done): ?>
<div class="card auth"><h2>Installed</h2><div class="alert a-ok">The system is ready.</div>
<p><b>Now delete <code>install.php</code> from your server</b>, then sign in.</p><a class="btn primary" href="login.php">Go to sign in</a></div>
<?php else: ?>
<form method="post" class="card auth" style="max-width:480px"><h2>Install Gingersnap Warehouse</h2>
<?php if ($err): ?><div class="alert a-err"><?= h($err) ?></div><?php endif; ?>
<label>Database type<select name="driver"><option value="mysql" <?= $v['driver'] === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB (cPanel)</option><option value="sqlite" <?= $v['driver'] === 'sqlite' ? 'selected' : '' ?>>SQLite (testing only)</option></select></label>
<label>DB host<input name="host" value="<?= h($v['host']) ?>"></label><label>DB name<input name="name" value="<?= h($v['name']) ?>"></label>
<label>DB user<input name="user" value="<?= h($v['user']) ?>"></label><label>DB password<input type="password" name="pass"></label>
<hr><label>Company name<input name="company" value="<?= h($v['company']) ?>"></label><label>Timezone<input name="tz" value="<?= h($v['tz']) ?>" placeholder="e.g. America/New_York"></label>
<label>Your name<input name="admin_name" value="<?= h($v['admin_name']) ?>" required></label><label>Admin email<input type="email" name="admin_email" value="<?= h($v['admin_email']) ?>" required></label>
<label>Admin password (8+ characters)<input type="password" name="admin_pass" minlength="8" required></label>
<button class="btn primary">Install</button></form>
<?php endif; ?></body></html>
