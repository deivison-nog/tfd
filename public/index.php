<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$allowedPages = [
    'login',
    'logout',
    'dashboard',
    'patients',
    'patient_form',
    'patient_view',
    'processes',
    'process_form',
    'process_view',
    'companions',
    'companion_form',
    'documents',
    'trips',
    'trip_form',
    'flow',
];

$page = current_page($allowedPages);

if ($page === 'logout') {
    logout();
    flash('success', 'Sessão encerrada com sucesso.');
    redirect('/index.php?page=login');
}

if ($page !== 'login') {
    require_login();
}

require __DIR__ . '/../src/views/header.php';
require __DIR__ . '/../src/pages/' . $page . '.php';
require __DIR__ . '/../src/views/footer.php';
