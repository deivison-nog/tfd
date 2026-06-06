<?php
$flash = get_flash();
$user = current_user();
$menuItems = current_menu_items();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('style.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar">
            <h1>TFD</h1>
            <p class="subtitle">Gestão de Saúde</p>
            <nav>
                <?php foreach ($menuItems as $item): ?>
                    <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
                <a href="index.php?page=logout">Sair</a>
            </nav>
            <small>
                Logado como <?= e($user['username']) ?>
                (<?= e(get_role_label((string) $user['role'])) ?>)
            </small>
        </aside>
    <?php endif; ?>
    <main class="main-content<?= $user ? '' : ' is-public' ?>">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
