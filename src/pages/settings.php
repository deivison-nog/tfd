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

    $action = trim((string) ($_POST['action'] ?? 'save_permissions'));

    if ($action === 'create_user') {
        $pdo = db();

        $newName     = trim((string) ($_POST['new_name'] ?? ''));
        $newUsername = trim((string) ($_POST['new_username'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');
        $newRole     = trim((string) ($_POST['new_role'] ?? ''));

        if ($newName === '' || $newUsername === '' || $newPassword === '' || $newRole === '') {
            flash('error', 'Preencha todos os campos obrigatórios para criar o usuário.');
            redirect('/index.php?page=settings#novo-usuario');
        }

        if (!array_key_exists($newRole, ROLE_LABELS)) {
            flash('error', 'Perfil inválido selecionado.');
            redirect('/index.php?page=settings#novo-usuario');
        }

        if ($newPassword !== $newPasswordConfirm) {
            flash('error', 'As senhas não conferem.');
            redirect('/index.php?page=settings#novo-usuario');
        }

        if (strlen($newPassword) < 6) {
            flash('error', 'A senha deve ter pelo menos 6 caracteres.');
            redirect('/index.php?page=settings#novo-usuario');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, name, password_hash, role, active, created_at)
                 VALUES (:username, :name, :password_hash, :role, 1, :created_at)'
            );
            $stmt->execute([
                'username'      => $newUsername,
                'name'          => $newName,
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'role'          => $newRole,
                'created_at'    => $now,
            ]);
            flash('success', 'Usuário criado com sucesso.');
        } catch (Throwable $e) {
            flash('error', 'Não foi possível criar o usuário. Verifique se o login já está em uso.');
        }

        redirect('/index.php?page=settings#novo-usuario');
    }

    // Default: save permissions
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

$pdo = db();
$allUsers = $pdo->query('SELECT id, username, name, role, active FROM users ORDER BY id DESC')->fetchAll();
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

<section id="novo-usuario">
    <div class="section-head"><h2>Incluir Novo Usuário</h2></div>
    <p>Crie um novo acesso ao sistema informando os dados abaixo.</p>

    <form method="post" class="grid-form panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create_user">
        <label>Nome completo *
            <input type="text" name="new_name" required>
        </label>
        <label>Login *
            <input type="text" name="new_username" required autocomplete="off">
        </label>
        <label>Perfil *
            <select name="new_role" required>
                <?php foreach ($roles as $roleKey => $roleLabel): ?>
                    <option value="<?= e($roleKey) ?>"><?= e($roleLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Senha * (mínimo 6 caracteres)
            <input type="password" name="new_password" required minlength="6" autocomplete="new-password">
        </label>
        <label>Confirmar senha *
            <input type="password" name="new_password_confirm" required minlength="6" autocomplete="new-password">
        </label>
        <div class="full actions-row">
            <button type="submit">Criar usuário</button>
        </div>
    </form>

    <?php if ($allUsers): ?>
        <div class="panel" style="margin-top: 1rem;">
            <h3>Usuários cadastrados</h3>
            <table>
                <thead>
                <tr>
                    <th>Login</th>
                    <th>Nome</th>
                    <th>Perfil</th>
                    <th>Ativo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($allUsers as $u): ?>
                    <tr>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e(ROLE_LABELS[$u['role']] ?? $u['role']) ?></td>
                        <td><?= (int) $u['active'] === 1 ? 'Sim' : 'Não' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
