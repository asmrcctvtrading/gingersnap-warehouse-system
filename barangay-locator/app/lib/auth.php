<?php
declare(strict_types=1);

const MAX_FAILED_LOGINS = 5;
const LOCK_MINUTES      = 15;

/** Permissions are code constants: role_key => list of permission keys. */
const ROLE_PERMISSIONS = [
    'admin' => ['*'],
    'verifier' => [
        'establishments.view', 'establishments.edit', 'establishments.verify',
        'requests.review', 'audit.view', 'export.run',
    ],
    'encoder' => [
        'establishments.view', 'establishments.create', 'establishments.edit',
    ],
];

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function can(string $permission): bool
{
    $u = currentUser();
    if (!$u) {
        return false;
    }
    $perms = ROLE_PERMISSIONS[$u['role_key']] ?? [];
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('admin/login.php');
    }
}

function requirePermission(string $permission): void
{
    requireLogin();
    if (!can($permission)) {
        http_response_code(403);
        exit('You do not have permission to do this.');
    }
}

/** Barangay scope for the logged-in user: null = all barangays. */
function userBarangayScope(): ?int
{
    $u = currentUser();
    return $u && $u['barangay_id'] !== null ? (int)$u['barangay_id'] : null;
}

/** True if the user may touch a record belonging to $barangayId. */
function canAccessBarangay(int $barangayId): bool
{
    $s = userBarangayScope();
    return $s === null || $s === $barangayId;
}

/**
 * Attempt login. Returns [bool ok, string message]. Same message for unknown user / wrong password.
 */
function attemptLogin(string $username, string $password): array
{
    $generic = 'Incorrect username or password.';
    $st = db()->prepare('SELECT u.*, r.role_key FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.username = ?');
    $st->execute([$username]);
    $u = $st->fetch();

    if (!$u) {
        // Burn comparable time so unknown usernames are not distinguishable by speed.
        password_verify($password, password_hash('dummy-not-a-real-password', PASSWORD_DEFAULT));
        return [false, $generic];
    }
    if ($u['status'] !== 'active') {
        return [false, 'This account is disabled. Contact the administrator.'];
    }
    if ($u['locked_until'] !== null && strtotime((string)$u['locked_until']) > time()) {
        return [false, 'Too many failed attempts. Try again in a few minutes.'];
    }
    if (!password_verify($password, $u['password_hash'])) {
        $fails = (int)$u['failed_logins'] + 1;
        if ($fails >= MAX_FAILED_LOGINS) {
            db()->prepare('UPDATE users SET failed_logins = 0, locked_until = DATE_ADD(NOW(), INTERVAL ' . LOCK_MINUTES . ' MINUTE) WHERE user_id = ?')
                ->execute([$u['user_id']]);
        } else {
            db()->prepare('UPDATE users SET failed_logins = ? WHERE user_id = ?')->execute([$fails, $u['user_id']]);
        }
        logAudit('login_failed', 'user', (int)$u['user_id'], 'username=' . $username);
        return [false, $generic];
    }

    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $u['user_id']]);
    }
    db()->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE user_id = ?')
        ->execute([$u['user_id']]);

    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['user'] = [
        'user_id'     => (int)$u['user_id'],
        'username'    => $u['username'],
        'full_name'   => $u['full_name'],
        'role_key'    => $u['role_key'],
        'barangay_id' => $u['barangay_id'] !== null ? (int)$u['barangay_id'] : null,
    ];
    logAudit('login', 'user', (int)$u['user_id']);
    return [true, 'ok'];
}

function logout(): void
{
    logAudit('logout', 'user', currentUser()['user_id'] ?? null);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Password policy for admin-created accounts. Returns error string or null. */
function password_policy_error(string $pw): ?string
{
    if (strlen($pw) < 10) {
        return 'Password must be at least 10 characters.';
    }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
        return 'Password must contain letters and numbers.';
    }
    return null;
}
