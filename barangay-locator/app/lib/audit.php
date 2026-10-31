<?php
declare(strict_types=1);

function logAudit(string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    try {
        $u = $_SESSION['user'] ?? null;
        $st = db()->prepare('INSERT INTO audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address)
                             VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $u['user_id'] ?? null,
            $u['username'] ?? null,
            $action,
            $entityType,
            $entityId,
            $details !== null ? mb_substr($details, 0, 2000, 'UTF-8') : null,
            client_ip(),
        ]);
    } catch (Throwable $e) {
        error_log('audit failed: ' . $e->getMessage());
    }
}
