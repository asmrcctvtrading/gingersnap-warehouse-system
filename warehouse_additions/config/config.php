<?php
// ─── Database credentials ─────────────────────────────────────────────────
// FILL IN DB_PASS before uploading to the server.
// Find it in: cPanel → MySQL Databases → Users & Databases.

define('DB_HOST', 'localhost');
define('DB_NAME', 'yitrmcft_gingersnaps');
define('DB_USER', 'yitrmcft_gingersnaps');
define('DB_PASS', 'User13919');

// ─── App constants ────────────────────────────────────────────────────────
define('BASE_URL', '/warehouse');

// ─── Session start ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Database connection ─────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ─── Auth ────────────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requirePermission(string $module, string $action): void {
    if (!can($module, $action)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body><h3>Access denied.</h3>'
            . '<a href="' . BASE_URL . '/modules/dashboard/index.php">Back to Dashboard</a>'
            . '</body></html>';
        exit;
    }
}

function can(string $module, string $action): bool {
    $perms = $_SESSION['permissions'] ?? [];
    return in_array($module . '.' . $action, $perms, true);
}

function attemptLogin(string $username, string $password): bool {
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT u.user_id, u.username, u.full_name, u.password_hash,
                    u.role_id, u.status, r.role_name
             FROM users u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Load permissions for this role as "module.action" strings
        $pstmt = $db->prepare(
            "SELECT p.module_key, p.action_key
             FROM role_permissions rp
             JOIN permissions p ON p.permission_id = rp.permission_id
             WHERE rp.role_id = ?"
        );
        $pstmt->execute([$user['role_id']]);
        $perms = [];
        foreach ($pstmt->fetchAll() as $row) {
            $perms[] = $row['module_key'] . '.' . $row['action_key'];
        }

        session_regenerate_id(true);

        $_SESSION['user_id']     = (int) $user['user_id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['full_name']   = $user['full_name'];
        $_SESSION['role_id']     = (int) $user['role_id'];
        $_SESSION['role_name']   = (string) $user['role_name'];
        $_SESSION['permissions'] = $perms;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// ─── Output helpers ──────────────────────────────────────────────────────
function h(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fmtNum($n, int $decimals = 0): string {
    return number_format((float) $n, $decimals, '.', ',');
}

function fmtDate(?string $d): string {
    if (!$d || $d === '0000-00-00') { return ''; }
    $ts = strtotime($d);
    return $ts !== false ? date('d M Y', $ts) : '';
}

// ─── Redirect ────────────────────────────────────────────────────────────
function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

// ─── Flash messages ──────────────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $flash;
}

// ─── CSRF protection ─────────────────────────────────────────────────────
function csrfField(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="_csrf" value="' . h($_SESSION['_csrf']) . '">';
}

function verifyCsrf(): void {
    $token    = $_SESSION['_csrf'] ?? '';
    $supplied = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($token, $supplied)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ─── URL / request helpers ────────────────────────────────────────────────
function getDateParam(string $key, string $default = ''): string {
    $val = trim($_GET[$key] ?? '');
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) ? $val : $default;
}

// ─── App settings ────────────────────────────────────────────────────────
function setting(string $key, string $default = ''): string {
    static $cache = [];
    if (array_key_exists($key, $cache)) { return $cache[$key]; }
    try {
        $stmt = getDB()->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row  = $stmt->fetch();
        $cache[$key] = $row ? (string) $row['setting_value'] : $default;
    } catch (Throwable $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

// ─── Audit / activity logging ─────────────────────────────────────────────
if (!function_exists('logAudit')) {
    function logAudit(PDO $db, string $action, string $module, ?int $recordId = null, ?string $description = null): void {
        $userId   = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;
        $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
        try {
            $db->prepare(
                "INSERT INTO audit_log (user_id, username, action, module, record_id, description, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$userId, $username, $action, $module, $recordId, $description, $ip]);
        } catch (Throwable $e) {
            // silently fail — audit_log may not exist yet on older installs
        }
    }
}

function logActivity(string $action, string $module, ?int $recordId = null, ?string $description = null): void {
    try {
        logAudit(getDB(), $action, $module, $recordId, $description);
    } catch (Throwable $e) {
        // silently fail
    }
}
