<?php
declare(strict_types=1);

/** Keys that must never be written to the audit log. */
function audit_scrub(array $details): array
{
    foreach ($details as $k => $v) {
        if (preg_match('/pass|token|secret|csrf|hash|cookie/i', (string)$k) === 1) {
            $details[$k] = '[redacted]';
        } elseif (is_array($v)) {
            $details[$k] = audit_scrub($v);
        }
    }
    return $details;
}

/**
 * Append one audit row. Never throws: a failed audit write is logged to the PHP error log.
 * $usernameOverride is for events with no session user (e.g. a failed login).
 */
function logAudit(string $action, ?string $entityType = null, int|string|null $entityId = null,
                  ?array $details = null, ?string $usernameOverride = null, ?int $userIdOverride = null): void
{
    try {
        $u = currentUserCached();
        $json = $details !== null
            ? mb_substr((string)json_encode(audit_scrub($details), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), 0, 4000, 'UTF-8')
            : null;
        $st = db()->prepare('INSERT INTO audit_log (user_id, username, action, entity_type, entity_id, details, ip_address, user_agent)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $userIdOverride ?? ($u['id'] ?? null),
            $usernameOverride !== null ? mb_substr($usernameOverride, 0, 50, 'UTF-8') : ($u['username'] ?? null),
            $action,
            $entityType,
            $entityId !== null ? (string)$entityId : null,
            $json,
            client_ip(),
            client_agent(),
        ]);
    } catch (Throwable $e) {
        error_log('audit failed: ' . $e->getMessage());
    }
}
