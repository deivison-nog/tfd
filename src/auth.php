<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const ROLE_LABELS = [
    'admin' => 'Administrativo',
    'assistente_social' => 'Assistente Social',
    'medico' => 'Médico',
    'auxiliar_administrativo' => 'Auxiliar Administrativo',
];

const MENU_ITEMS = [
    'dashboard' => ['label' => 'Dashboard', 'url' => 'index.php?page=dashboard'],
    'patients' => ['label' => 'Pacientes', 'url' => 'index.php?page=patients'],
    'processes' => ['label' => 'Processos TFD', 'url' => 'index.php?page=processes'],
    'companions' => ['label' => 'Acompanhantes', 'url' => 'index.php?page=companions'],
    'documents' => ['label' => 'Documentos', 'url' => 'index.php?page=documents'],
    'trips' => ['label' => 'Viagens', 'url' => 'index.php?page=trips'],
    'flow' => ['label' => 'Fluxo (SVG)', 'url' => 'index.php?page=flow'],
    'settings' => ['label' => 'Configuração', 'url' => 'index.php?page=settings'],
];

const PAGE_PERMISSION_MAP = [
    'dashboard' => 'dashboard',
    'patients' => 'patients',
    'patient_form' => 'patients',
    'patient_view' => 'patients',
    'processes' => 'processes',
    'process_form' => 'processes',
    'process_view' => 'processes',
    'companions' => 'companions',
    'companion_form' => 'companions',
    'documents' => 'documents',
    'trips' => 'trips',
    'trip_form' => 'trips',
    'flow' => 'flow',
    'settings' => 'settings',
];

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

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function role_permissions(string $role): array
{
    if ($role === 'admin') {
        return array_fill_keys(array_keys(MENU_ITEMS), true);
    }

    $stmt = db()->prepare('SELECT menu_key, allowed FROM role_permissions WHERE role = :role');
    $stmt->execute(['role' => $role]);
    $rows = $stmt->fetchAll();

    $permissions = array_fill_keys(array_keys(MENU_ITEMS), false);
    foreach ($rows as $row) {
        $menuKey = (string) $row['menu_key'];
        if (array_key_exists($menuKey, $permissions)) {
            $permissions[$menuKey] = (int) $row['allowed'] === 1;
        }
    }

    return $permissions;
}

function user_permissions(?array $user = null): array
{
    $user ??= current_user();
    if (!$user) {
        return array_fill_keys(array_keys(MENU_ITEMS), false);
    }

    return role_permissions((string) $user['role']);
}

function current_menu_items(): array
{
    $permissions = user_permissions();
    $allowed = [];

    foreach (MENU_ITEMS as $key => $item) {
        if (!empty($permissions[$key])) {
            $allowed[$key] = $item;
        }
    }

    return $allowed;
}

function can_access_page(string $page): bool
{
    $permissionKey = PAGE_PERMISSION_MAP[$page] ?? null;
    if ($permissionKey === null) {
        return true;
    }

    $permissions = user_permissions();

    return !empty($permissions[$permissionKey]);
}

function require_page_access(string $page): void
{
    if (!can_access_page($page)) {
        flash('error', 'Seu perfil não tem acesso a esta área.');
        redirect(user_home_url());
    }
}

function available_roles(): array
{
    return ROLE_LABELS;
}

function user_home_url(): string
{
    $menuItems = current_menu_items();
    $firstItem = reset($menuItems);

    if ($firstItem && !empty($firstItem['url'])) {
        return (string) $firstItem['url'];
    }

    return 'index.php?page=home';
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
