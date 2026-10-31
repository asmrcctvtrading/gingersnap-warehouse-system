<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    if (!is_array($c)) {
        throw new RuntimeException('Database is not configured.');
    }
    $dsn = 'mysql:host=' . $c['host'] . ';dbname=' . $c['name'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    // Keep NOW()/CURRENT_TIMESTAMP in the app timezone so stored times match PHP's.
    $offset = (new DateTimeImmutable('now', new DateTimeZone((string)config('timezone', 'Asia/Manila'))))->format('P');
    if (preg_match('/^[+-]\d{2}:\d{2}$/', $offset) === 1) {
        $pdo->exec("SET time_zone = '" . $offset . "'");
    }
    return $pdo;
}

/** Read one app_settings value (cached per request). */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM app_settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) && $cache[$key] !== null ? $cache[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    $st = db()->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute([$key, $value]);
}
