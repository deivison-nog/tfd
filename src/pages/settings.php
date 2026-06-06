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

    // ── Create role ────────────────────────────────────────────────────────────
    if ($action === 'create_role') {
        $pdo = db();
        $newLabel = trim((string) ($_POST['role_label'] ?? ''));

        if ($newLabel === '') {
            flash('error', 'Informe um nome para o perfil.');
            redirect('/index.php?page=settings#perfis');
        }

        $roleKey = slugify($newLabel);

        if ($roleKey === 'admin') {
            flash('error', 'Não é possível criar um segundo perfil Administrativo.');
            redirect('/index.php?page=settings#perfis');
        }

        if ($roleKey === '') {
            flash('error', 'Nome de perfil inválido.');
            redirect('/index.php?page=settings#perfis');
        }

        // Ensure uniqueness
        $check = $pdo->prepare('SELECT id FROM roles WHERE role_key = :key');
        $check->execute(['key' => $roleKey]);
        if ($check->fetch()) {
            $roleKey = $roleKey . '_' . time();
        }

        try {
            $pdo->prepare('INSERT INTO roles (role_key, label, active, created_at) VALUES (:key, :label, 1, :now)')
                ->execute(['key' => $roleKey, 'label' => $newLabel, 'now' => $now]);
            flash('success', 'Perfil "' . $newLabel . '" criado com sucesso.');
        } catch (Throwable $e) {
            flash('error', 'Não foi possível criar o perfil.');
        }

        redirect('/index.php?page=settings#perfis');
    }

    // ── Edit role label ────────────────────────────────────────────────────────
    if ($action === 'edit_role') {
        $pdo = db();
        $roleId   = (int) ($_POST['role_id'] ?? 0);
        $newLabel = trim((string) ($_POST['role_label'] ?? ''));

        if ($roleId <= 0 || $newLabel === '') {
            flash('error', 'Dados inválidos para editar o perfil.');
            redirect('/index.php?page=settings#perfis');
        }

        try {
            $pdo->prepare('UPDATE roles SET label = :label WHERE id = :id')
                ->execute(['label' => $newLabel, 'id' => $roleId]);
            flash('success', 'Perfil atualizado com sucesso.');
        } catch (Throwable $e) {
            flash('error', 'Não foi possível atualizar o perfil.');
        }

        redirect('/index.php?page=settings#perfis');
    }

    // ── Toggle role active ─────────────────────────────────────────────────────
    if ($action === 'toggle_role') {
        $pdo    = db();
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $row = $pdo->prepare('SELECT role_key, active FROM roles WHERE id = :id');
        $row->execute(['id' => $roleId]);
        $roleData = $row->fetch();

        if (!$roleData) {
            flash('error', 'Perfil não encontrado.');
            redirect('/index.php?page=settings#perfis');
        }

        if ((string) $roleData['role_key'] === 'admin') {
            flash('error', 'O perfil Administrativo não pode ser desativado.');
            redirect('/index.php?page=settings#perfis');
        }

        $newActive = (int) $roleData['active'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE roles SET active = :active WHERE id = :id')
            ->execute(['active' => $newActive, 'id' => $roleId]);

        flash('success', $newActive ? 'Perfil ativado com sucesso.' : 'Perfil desativado com sucesso.');
        redirect('/index.php?page=settings#perfis');
    }

    // ── Create user ────────────────────────────────────────────────────────────
    if ($action === 'create_user') {
        $pdo = db();

        $newName            = trim((string) ($_POST['new_name'] ?? ''));
        $newUsername        = trim((string) ($_POST['new_username'] ?? ''));
        $newPassword        = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');
        $newRole            = trim((string) ($_POST['new_role'] ?? ''));

        if ($newName === '' || $newUsername === '' || $newPassword === '' || $newRole === '') {
            flash('error', 'Preencha todos os campos obrigatórios para criar o usuário.');
            redirect('/index.php?page=settings#novo-usuario');
        }

        if ($newRole === 'admin') {
            flash('error', 'Não é possível criar um segundo perfil Administrativo.');
            redirect('/index.php?page=settings#novo-usuario');
        }

        if (!array_key_exists($newRole, available_roles())) {
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
            $pdo->prepare(
                'INSERT INTO users (username, name, password_hash, role, active, created_at)
                 VALUES (:username, :name, :password_hash, :role, 1, :created_at)'
            )->execute([
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

    // ── Save permissions (default) ─────────────────────────────────────────────
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
                    'role'       => $role,
                    'menu_key'   => $menuKey,
                    'allowed'    => $allowed,
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

$pdo       = db();
$allUsers  = $pdo->query('SELECT id, username, name, role, active FROM users ORDER BY id DESC')->fetchAll();
$allRoles  = all_roles_list();
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

<section id="perfis">
    <div class="section-head"><h2>Gerenciar Perfis</h2></div>

    <div class="panel">
        <table>
            <thead>
            <tr>
                <th>Perfil</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($allRoles as $r): ?>
                <tr>
                    <td><strong><?= e((string) $r['label']) ?></strong></td>
                    <td>
                        <?php if ((int) $r['active'] === 1): ?>
                            <span class="badge" style="background:#dcf7e8;color:#175c3f;">Ativo</span>
                        <?php else: ?>
                            <span class="badge" style="background:#ffe6e3;color:#8d1f18;">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <button
                            type="button"
                            class="link js-open-role-modal"
                            data-role-id="<?= (int) $r['id'] ?>"
                            data-role-label="<?= e((string) $r['label']) ?>"
                        >
                            Editar
                        </button>
                        <?php if ((string) $r['role_key'] !== 'admin'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="role_id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="link <?= (int) $r['active'] === 1 ? 'danger' : '' ?>">
                                    <?= (int) $r['active'] === 1 ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color:#aaa;font-size:.85rem;">Protegido</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" data-role-modal>
        <div class="modal-card">
            <h3>Editar perfil</h3>
            <form method="post" class="grid-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="role_id" value="" data-role-id-input>
                <label>Nome do perfil *
                    <input type="text" name="role_label" required value="" data-role-label-input>
                </label>
                <div class="full actions-row">
                    <button type="submit">Salvar alteração</button>
                    <button type="button" class="btn secondary" data-close-role-modal>Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <h3>Incluir Novo Perfil</h3>
        <form method="post" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_role">
            <label>Nome do perfil *
                <input type="text" name="role_label" required placeholder="Ex: Enfermeiro">
            </label>
            <div class="full actions-row">
                <button type="submit">Criar perfil</button>
            </div>
        </form>
    </div>
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
                    <?php if ($roleKey === 'admin'): continue; endif; ?>
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
                        <td><?= e(get_role_label((string) $u['role'])) ?></td>
                        <td><?= (int) $u['active'] === 1 ? 'Sim' : 'Não' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
