<?php
declare(strict_types=1);

function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $want = $_GET['lang'] ?? ($_COOKIE['brgy_lang'] ?? null);
    if (!in_array($want, ['en', 'tl'], true)) {
        $want = setting('default_language', 'en');
        if (!in_array($want, ['en', 'tl'], true)) {
            $want = 'en';
        }
    } elseif (isset($_GET['lang']) && !headers_sent()) {
        setcookie('brgy_lang', $want, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true]);
    }
    return $lang = $want;
}

function t(string $key, array $vars = []): string
{
    static $dict = [];
    $lang = current_lang();
    if (!isset($dict[$lang])) {
        $f = APP_DIR . '/lang/' . $lang . '.php';
        $dict[$lang] = is_file($f) ? require $f : [];
        if ($lang !== 'en') {
            $dict['en'] = $dict['en'] ?? require APP_DIR . '/lang/en.php';
        }
    }
    $s = $dict[$lang][$key] ?? ($dict['en'][$key] ?? $key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}

/** Localised category name from a row containing name_en / name_tl. */
function cat_name(array $row): string
{
    if (current_lang() === 'tl' && !empty($row['name_tl'])) {
        return $row['name_tl'];
    }
    return $row['name_en'] ?? '';
}
