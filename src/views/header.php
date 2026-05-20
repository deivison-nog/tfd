<?php
$flash = get_flash();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar">
            <h1>TFD</h1>
            <p class="subtitle">Gestão Operacional</p>
            <nav>
                <a href="/index.php?page=dashboard">Dashboard</a>
                <a href="/index.php?page=patients">Pacientes</a>
                <a href="/index.php?page=processes">Processos TFD</a>
                <a href="/index.php?page=companions">Acompanhantes</a>
                <a href="/index.php?page=documents">Documentos</a>
                <a href="/index.php?page=trips">Viagens</a>
                <a href="/index.php?page=flow">Fluxo (SVG)</a>
                <a href="/index.php?page=logout">Sair</a>
            </nav>
            <small>Logado como <?= e($user['username']) ?></small>
        </aside>
    <?php endif; ?>
    <main class="main-content<?= $user ? '' : ' is-public' ?>">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
