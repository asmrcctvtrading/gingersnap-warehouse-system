<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valid(?string $given): bool
{
    return is_string($given) && $given !== '' && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $given);
}

/** Verify POST token (form field `csrf` or header X-CSRF-Token). Aborts with 403 on failure. */
function verifyCsrf(): void
{
    $given = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!csrf_valid(is_string($given) ? $given : null)) {
        http_response_code(403);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}
