<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$allowedPages = [
    'home',
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
    'document_download',
    'trips',
    'trip_form',
    'flow',
    'settings',
];

$page = current_page($allowedPages);

if ($page === 'logout') {
    logout();
    flash('success', 'Sessão encerrada com sucesso.');
    redirect('/index.php?page=login');
}

$publicPages = ['home', 'login'];

if (!in_array($page, $publicPages, true)) {
    require_login();
    require_page_access($page);
}

// The home page manages its own full HTML layout
if ($page === 'home') {
    require __DIR__ . '/../src/pages/home.php';
    exit;
}

if ($page === 'document_download') {
    require __DIR__ . '/../src/pages/document_download.php';
    exit;
}

require __DIR__ . '/../src/views/header.php';
require __DIR__ . '/../src/pages/' . $page . '.php';
require __DIR__ . '/../src/views/footer.php';
