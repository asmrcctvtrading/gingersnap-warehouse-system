<?php
declare(strict_types=1);

const MAX_FAILED_LOGINS = 5;
const LOCK_MINUTES      = 15;
const ROLES             = ['admin', 'supervisor', 'employee'];

/** Permissions are code constants: role => permission keys. '*' = everything. Record-level scope is checked separately. */
const ROLE_PERMISSIONS = [
    'admin' => ['*'],
    'supervisor' => [
        'employees.view', 'employees.edit',
        'tasks.view', 'tasks.create', 'tasks.assign', 'tasks.cancel', 'tasks.work', 'tasks.correct',
        'imports.run', 'reports.view', 'reports.export',
    ],
    'employee' => [
        'profile.view_own', 'tasks.view_own', 'tasks.work_own', 'reports.view_own',
    ],
];

/**
 * Logged-in user for this request, re-read from the database every request so that deactivation,
 * role changes and employee status changes take effect immediately. Enforces idle/absolute timeouts.
 * Returns null when nobody is logged in.
 *
 * @return array{id:int,username:string,role:string,employee_id:?int,full_name:string,must_change_password:bool,emp_warehouse_id:?int}|null
 */
function currentUser(): ?array
{
    if (array_key_exists('WPMS_USER', $GLOBALS)) {
        return $GLOBALS['WPMS_USER'];
    }
    $GLOBALS['WPMS_USER'] = null;

    $uid = $_SESSION['uid'] ?? null;
    if (!is_int($uid)) {
        return null;
    }

    $now  = time();
    $idle = max(1, (int)setting('idle_timeout_minutes', '30')) * 60;
    $abs  = max(1, (int)setting('absolute_session_hours', '8')) * 3600;
    if ($now - (int)($_SESSION['last_seen'] ?? 0) > $idle || $now - (int)($_SESSION['login_at'] ?? 0) > $abs) {
        logAudit('session_expired', 'user', $uid, null, null, $uid);
        clear_login_state();
        flash('warning', 'Your session expired. Please sign in again.');
        return null;
    }

    $st = db()->prepare('SELECT u.id, u.username, u.role, u.employee_id, u.is_active, u.must_change_password,
                                e.full_name, e.warehouse_id AS emp_warehouse_id, e.employment_status
                         FROM users u LEFT JOIN employees e ON e.id = u.employee_id
                         WHERE u.id = ?');
    $st->execute([$uid]);
    $r = $st->fetch();
    if (!$r || (int)$r['is_active'] !== 1
        || ($r['role'] === 'employee' && in_array($r['employment_status'], ['inactive', 'terminated'], true))) {
        clear_login_state();
        return null;
    }

    $_SESSION['last_seen'] = $now;
    $GLOBALS['WPMS_USER'] = [
        'id'                   => (int)$r['id'],
        'username'             => (string)$r['username'],
        'role'                 => (string)$r['role'],
        'employee_id'          => $r['employee_id'] !== null ? (int)$r['employee_id'] : null,
        'full_name'            => (string)($r['full_name'] ?? $r['username']),
        'must_change_password' => (int)$r['must_change_password'] === 1,
        'emp_warehouse_id'     => $r['emp_warehouse_id'] !== null ? (int)$r['emp_warehouse_id'] : null,
    ];
    return $GLOBALS['WPMS_USER'];
}

/** The user resolved earlier in this request, without touching the database (safe to call from audit code). */
function currentUserCached(): ?array
{
    return $GLOBALS['WPMS_USER'] ?? null;
}

function clear_login_state(): void
{
    unset($_SESSION['uid'], $_SESSION['last_seen'], $_SESSION['login_at']);
    $GLOBALS['WPMS_USER'] = null;
}

function can(string $permission): bool
{
    $u = currentUser();
    if ($u === null) {
        return false;
    }
    $perms = ROLE_PERMISSIONS[$u['role']] ?? [];
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

/** $allowPasswordChange = true only on the change-password page itself. */
function requireLogin(bool $allowPasswordChange = false): array
{
    $u = currentUser();
    if ($u === null) {
        redirect('login.php');
    }
    if ($u['must_change_password'] && !$allowPasswordChange) {
        redirect('change_password.php');
    }
    return $u;
}

function requireRole(string ...$roles): array
{
    $u = requireLogin();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to do this.');
    }
    return $u;
}

function requirePermission(string $permission): array
{
    $u = requireLogin();
    if (!can($permission)) {
        http_response_code(403);
        exit('You do not have permission to do this.');
    }
    return $u;
}

/**
 * Warehouse scope of the current user: null = all warehouses (admin);
 * list of ids otherwise (supervisor: user_warehouses; employee: own warehouse). Empty list = none.
 *
 * @return list<int>|null
 */
function userWarehouseScope(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $u = currentUser();
    if ($u === null) {
        return $cache = [];
    }
    if ($u['role'] === 'admin') {
        return $cache = null;
    }
    if ($u['role'] === 'employee') {
        return $cache = ($u['emp_warehouse_id'] !== null ? [$u['emp_warehouse_id']] : []);
    }
    $st = db()->prepare('SELECT warehouse_id FROM user_warehouses WHERE user_id = ?');
    $st->execute([$u['id']]);
    return $cache = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

function canAccessWarehouse(int $warehouseId): bool
{
    $s = userWarehouseScope();
    return $s === null || in_array($warehouseId, $s, true);
}

/**
 * Attempt login. Returns [bool ok, string message]. One generic message for every failure
 * (unknown user, wrong password, disabled, locked); the real reason goes to the audit log only.
 */
function attemptLogin(string $username, string $password): array
{
    $generic = 'Incorrect username or password, or the account is temporarily locked.';
    $st = db()->prepare('SELECT u.id, u.username, u.password_hash, u.is_active, u.failed_logins,
                                (u.locked_until IS NOT NULL AND u.locked_until > NOW()) AS is_locked,
                                u.role, e.employment_status
                         FROM users u LEFT JOIN employees e ON e.id = u.employee_id
                         WHERE u.username = ?');
    $st->execute([$username]);
    $u = $st->fetch();

    if (!$u) {
        // Spend comparable time so unknown usernames are not distinguishable by response speed.
        static $dummy = null;
        $dummy ??= password_hash('not-a-real-password', PASSWORD_DEFAULT);
        password_verify($password, $dummy);
        logAudit('login_failed', 'user', null, ['reason' => 'unknown_user'], $username);
        return [false, $generic];
    }

    $uid = (int)$u['id'];
    if ((int)$u['is_active'] !== 1
        || ($u['role'] === 'employee' && in_array($u['employment_status'], ['inactive', 'terminated'], true))) {
        password_verify($password, (string)$u['password_hash']);
        logAudit('login_failed', 'user', $uid, ['reason' => 'account_disabled'], $u['username'], $uid);
        return [false, $generic];
    }
    if ((int)$u['is_locked'] === 1) {
        password_verify($password, (string)$u['password_hash']);
        logAudit('login_failed', 'user', $uid, ['reason' => 'locked'], $u['username'], $uid);
        return [false, $generic];
    }

    if (!password_verify($password, (string)$u['password_hash'])) {
        $fails = (int)$u['failed_logins'] + 1;
        if ($fails >= MAX_FAILED_LOGINS) {
            db()->prepare('UPDATE users SET failed_logins = 0, locked_until = DATE_ADD(NOW(), INTERVAL ' . LOCK_MINUTES . ' MINUTE) WHERE id = ?')
                ->execute([$uid]);
            logAudit('account_locked', 'user', $uid, ['minutes' => LOCK_MINUTES], $u['username'], $uid);
        } else {
            db()->prepare('UPDATE users SET failed_logins = ? WHERE id = ?')->execute([$fails, $uid]);
        }
        logAudit('login_failed', 'user', $uid, ['reason' => 'bad_password', 'attempt' => $fails], $u['username'], $uid);
        return [false, $generic];
    }

    if (password_needs_rehash((string)$u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $uid]);
    }
    db()->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?')
        ->execute([$uid]);

    session_regenerate_id(true);
    $_SESSION['csrf']      = bin2hex(random_bytes(32));
    $_SESSION['uid']       = $uid;
    $_SESSION['login_at']  = time();
    $_SESSION['last_seen'] = time();
    unset($GLOBALS['WPMS_USER']);
    currentUser();
    logAudit('login_success', 'user', $uid);
    return [true, 'ok'];
}

function logout(): void
{
    $u = currentUserCached();
    logAudit('logout', 'user', $u['id'] ?? null);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }
    session_destroy();
    $GLOBALS['WPMS_USER'] = null;
}
