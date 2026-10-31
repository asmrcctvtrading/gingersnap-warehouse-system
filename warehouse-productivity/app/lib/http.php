<?php
declare(strict_types=1);

function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    return rtrim((string)config('base_url', ''), '/');
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    // Internal paths only: never redirect to a caller-supplied absolute URL.
    header('Location: ' . url($path));
    exit;
}

function client_ip(): string
{
    // REMOTE_ADDR only: forwarded headers are client-controlled and not trusted here.
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function client_agent(): ?string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    return is_string($ua) && $ua !== '' ? mb_substr($ua, 0, 255, 'UTF-8') : null;
}

/** Private storage path (imports, logs, backups). Prefer a folder above public_html via config storage_dir. */
function storage_path(string $sub = ''): string
{
    $base = (string)config('storage_dir', '');
    if ($base === '') {
        $base = APP_ROOT . '/storage';
    }
    return rtrim($base, '/\\') . ($sub !== '' ? '/' . trim($sub, '/\\') : '');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

/** @return list<array{type:string,msg:string}> */
function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($f) ? $f : [];
}

function json_out(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
