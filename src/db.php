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

    if (!$hasUsers) {
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

    ensure_access_control_schema($pdo);
    ensure_tfd_processes_schema($pdo);
    ensure_process_opinions_schema($pdo);
    ensure_default_role_users($pdo);
}

function ensure_access_control_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS role_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT NOT NULL,
            menu_key TEXT NOT NULL,
            allowed INTEGER NOT NULL DEFAULT 1,
            updated_at TEXT,
            UNIQUE (role, menu_key)
        )'
    );

    $defaultPermissions = [
        'admin' => ['dashboard', 'patients', 'processes', 'companions', 'documents', 'trips', 'flow', 'settings', 'professional_opinion'],
        'assistente_social' => ['dashboard', 'patients', 'processes', 'companions', 'documents', 'flow', 'professional_opinion'],
        'medico' => ['dashboard', 'patients', 'processes', 'documents', 'flow', 'professional_opinion'],
        'auxiliar_administrativo' => ['dashboard', 'patients', 'processes', 'companions', 'documents', 'trips', 'flow', 'professional_opinion'],
    ];

    $insertStmt = $pdo->prepare(
        'INSERT OR IGNORE INTO role_permissions (role, menu_key, allowed, updated_at)
         VALUES (:role, :menu_key, :allowed, :updated_at)'
    );

    $now = date('Y-m-d H:i:s');

    foreach ($defaultPermissions as $role => $menuKeys) {
        foreach ($menuKeys as $menuKey) {
            $insertStmt->execute([
                'role' => $role,
                'menu_key' => $menuKey,
                'allowed' => 1,
                'updated_at' => $now,
            ]);
        }
    }
}

function ensure_process_opinions_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS process_professional_opinions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            process_id INTEGER NOT NULL,
            opinion_text TEXT NOT NULL,
            created_by INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT,
            FOREIGN KEY (process_id) REFERENCES tfd_processes(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_process_professional_opinions_process ON process_professional_opinions(process_id)');
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_process_professional_opinions_legacy_unique ON process_professional_opinions(process_id, opinion_text, created_by, created_at)');

    $legacyOpinionRows = $pdo->query("
        SELECT id, professional_opinion, professional_opinion_by, professional_opinion_at
        FROM tfd_processes
        WHERE TRIM(COALESCE(professional_opinion, '')) <> ''
    ")->fetchAll();

    if (!$legacyOpinionRows) {
        return;
    }

    $insertLegacy = $pdo->prepare('
        INSERT OR IGNORE INTO process_professional_opinions (
            process_id,
            opinion_text,
            created_by,
            created_at,
            updated_at
        ) VALUES (
            :process_id,
            :opinion_text,
            :created_by,
            :created_at,
            NULL
        )
    ');

    foreach ($legacyOpinionRows as $row) {
        $createdBy = (int) ($row['professional_opinion_by'] ?? 0);
        if ($createdBy <= 0) {
            continue;
        }

        $insertLegacy->execute([
            'process_id' => (int) $row['id'],
            'opinion_text' => trim((string) $row['professional_opinion']),
            'created_by' => $createdBy,
            'created_at' => (string) ($row['professional_opinion_at'] ?: date('Y-m-d H:i:s')),
        ]);
    }
}

function ensure_tfd_processes_schema(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(tfd_processes)')->fetchAll();
    $columnNames = array_column($columns, 'name');

    if (!in_array('professional_opinion', $columnNames, true)) {
        $pdo->exec('ALTER TABLE tfd_processes ADD COLUMN professional_opinion TEXT');
    }

    if (!in_array('professional_opinion_by', $columnNames, true)) {
        $pdo->exec('ALTER TABLE tfd_processes ADD COLUMN professional_opinion_by INTEGER');
    }

    if (!in_array('professional_opinion_at', $columnNames, true)) {
        $pdo->exec('ALTER TABLE tfd_processes ADD COLUMN professional_opinion_at TEXT');
    }
}

function ensure_default_role_users(PDO $pdo): void
{
    $seedUsers = [
        ['username' => 'assistente.social', 'name' => 'Assistente Social', 'role' => 'assistente_social'],
        ['username' => 'medico.tfd', 'name' => 'Médico TFD', 'role' => 'medico'],
        ['username' => 'auxiliar.adm', 'name' => 'Auxiliar Administrativo', 'role' => 'auxiliar_administrativo'],
    ];

    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO users (username, name, password_hash, role, active, created_at)
         VALUES (:username, :name, :password_hash, :role, 1, :created_at)'
    );

    foreach ($seedUsers as $seedUser) {
        $stmt->execute([
            'username' => $seedUser['username'],
            'name' => $seedUser['name'],
            'password_hash' => password_hash('tfd123', PASSWORD_DEFAULT),
            'role' => $seedUser['role'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
