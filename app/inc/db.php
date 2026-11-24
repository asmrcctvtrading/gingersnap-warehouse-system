<?php
defined('APP') || define('APP', 1);

function db_connect(array $c): PDO
{
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        return new PDO('sqlite:' . $c['sqlite_path'], null, null, $opts);
    }
    return new PDO("mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], $opts);
}

function db(): PDO
{
    static $pdo;
    global $CFG;
    return $pdo ??= db_connect($CFG['db']);
}

function q(string $sql, array $a = []): array { $s = db()->prepare($sql); $s->execute($a); return $s->fetchAll(); }
function one(string $sql, array $a = []): ?array { return q($sql, $a)[0] ?? null; }
function val(string $sql, array $a = []) { $r = one($sql, $a); return $r ? reset($r) : null; }
function run(string $sql, array $a = []): int { $s = db()->prepare($sql); $s->execute($a); return $s->rowCount(); }
function insert(string $sql, array $a = []): int { run($sql, $a); return (int) db()->lastInsertId(); }
