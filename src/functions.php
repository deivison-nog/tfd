<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    return $basePath === '' || $basePath === '.' ? '' : $basePath;
}

function app_url(string $path = ''): string
{
    $basePath = app_base_path();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $basePath === '' ? '/' : $basePath . '/';
    }

    return ($basePath === '' ? '' : $basePath) . '/' . $path;
}

function asset_url(string $path): string
{
    $frontController = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    $rootController = realpath(__DIR__ . '/../index.php');
    $assetsPrefix = $frontController === $rootController ? 'public/assets/' : 'assets/';

    return app_url($assetsPrefix . ltrim($path, '/'));
}

function redirect(string $url): never
{
    if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
        $url = app_url($url);
    }

    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        echo 'Token CSRF inválido.';
        exit;
    }
}

function current_page(array $allowedPages): string
{
    $page = $_GET['page'] ?? 'home';

    return in_array($page, $allowedPages, true) ? $page : 'home';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function old(string $field, string $default = ''): string
{
    if (isset($_POST[$field])) {
        return trim((string) $_POST[$field]);
    }

    return $default;
}

function format_date(string $date): string
{
    if ($date === '') {
        return '';
    }

    $d = \DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));

    return $d ? $d->format('d/m/Y') : $date;
}

function format_datetime(string $datetime): string
{
    if ($datetime === '') {
        return '';
    }

    $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);

    return $d ? $d->format('d/m/Y H:i') : $datetime;
}

function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ]);
    $text = (string) preg_replace('/[^a-z0-9]+/', '_', $text);

    return trim($text, '_');
}

function safe_download_name(string $filename, string $fallback = 'documento'): string
{
    $filename = trim($filename);
    $filename = preg_replace('/[\x00-\x1F\x7F"\\\\\/:\|\*\?<>]+/', '', $filename) ?: $fallback;
    $filename = trim($filename, '. ');

    return $filename !== '' ? $filename : $fallback;
}

function ascii_download_name(string $filename, string $fallback = 'documento'): string
{
    $filename = safe_download_name($filename, $fallback);
    $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: $fallback;
    $ascii = trim($ascii, '. ');

    return $ascii !== '' ? $ascii : $fallback;
}
