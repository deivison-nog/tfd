<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    static $user;

    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare('SELECT id, username, name, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE username = :username AND active = 1 LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user_id'] = (int) $user['id'];
    session_regenerate_id(true);

    return true;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/index.php?page=login');
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
