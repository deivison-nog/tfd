<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    ensure_database_initialized($pdo);

    return $pdo;
}

function ensure_database_initialized(PDO $pdo): void
{
    $hasUsers = (bool) $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();

    if ($hasUsers) {
        return;
    }

    $sql = file_get_contents(__DIR__ . '/../database/init.sql');
    if ($sql === false) {
        throw new RuntimeException('Não foi possível carregar database/init.sql.');
    }

    $pdo->exec($sql);

    $seedUser = $pdo->prepare('INSERT INTO users (username, name, password_hash, role, active, created_at) VALUES (:username, :name, :password_hash, :role, 1, :created_at)');
    $seedUser->execute([
        'username' => 'tfdcolares',
        'name' => 'Usuário Demonstração',
        'password_hash' => password_hash('tfd123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
