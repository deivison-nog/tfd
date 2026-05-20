<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

if (file_exists(DB_PATH)) {
    unlink(DB_PATH);
}

db();

echo "Banco inicializado com sucesso em " . DB_PATH . PHP_EOL;
