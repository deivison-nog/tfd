<?php
$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    flash('error', 'Apenas o perfil administrativo pode alterar configurações.');
    redirect(user_home_url());
}

$roles = available_roles();
$permissionItems = permission_items();
$now = date('Y-m-d H:i:s');

if (is_post()) {
    verify_csrf();

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $upsert = $pdo->prepare(
            'INSERT INTO role_permissions (role, menu_key, allowed, updated_at)
             VALUES (:role, :menu_key, :allowed, :updated_at)
             ON CONFLICT(role, menu_key) DO UPDATE SET allowed = excluded.allowed, updated_at = excluded.updated_at'
        );

        foreach ($roles as $role => $label) {
            foreach ($permissionItems as $menuKey => $menuItem) {
                $allowed = $role === 'admin'
                    ? 1
                    : (isset($_POST['permissions'][$role][$menuKey]) ? 1 : 0);

                $upsert->execute([
                    'role' => $role,
                    'menu_key' => $menuKey,
                    'allowed' => $allowed,
                    'updated_at' => $now,
                ]);
            }
        }

        $pdo->commit();
        flash('success', 'Permissões atualizadas com sucesso.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Não foi possível atualizar as permissões.');
    }

    redirect('/index.php?page=settings');
}

$rolePermissions = [];
foreach ($roles as $role => $label) {
    $rolePermissions[$role] = role_permissions($role);
}
?>
<section>
    <div class="section-head"><h2>Configuração</h2></div>
    <p>Marque os itens de menu que cada perfil pode acessar.</p>

    <form method="post" class="panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <table>
            <thead>
            <tr>
                <th>Perfil</th>
                <?php foreach ($permissionItems as $menuItem): ?>
                    <th><?= e($menuItem['label']) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $role => $label): ?>
                <tr>
                    <td><strong><?= e($label) ?></strong></td>
                    <?php foreach ($permissionItems as $menuKey => $menuItem): ?>
                        <td style="text-align: center;">
                            <input
                                type="checkbox"
                                name="permissions[<?= e($role) ?>][<?= e($menuKey) ?>]"
                                <?= !empty($rolePermissions[$role][$menuKey]) ? 'checked' : '' ?>
                                <?= $role === 'admin' ? 'disabled' : '' ?>
                                aria-label="Permissão <?= e($label) ?> para <?= e($menuItem['label']) ?>"
                                title="<?= $role === 'admin' ? 'Perfil administrativo possui acesso total fixo.' : '' ?>"
                            >
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="actions-row" style="margin-top: 1rem;">
            <button type="submit">Salvar permissões</button>
        </div>
    </form>
</section>
