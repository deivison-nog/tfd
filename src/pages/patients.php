<?php
$pdo = db();

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM patients WHERE id = :id');
        try {
            $stmt->execute(['id' => $id]);
            flash('success', 'Paciente removido com sucesso.');
        } catch (Throwable $e) {
            flash('error', 'Não foi possível excluir paciente vinculado a processo.');
        }
        redirect('/index.php?page=patients');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE name LIKE :q OR cpf LIKE :q OR cns LIKE :q ORDER BY id DESC');
    $stmt->execute(['q' => "%{$q}%"]);
    $patients = $stmt->fetchAll();
} else {
    $patients = $pdo->query('SELECT * FROM patients ORDER BY id DESC')->fetchAll();
}
?>
<section>
    <div class="section-head">
        <h2>Pacientes</h2>
        <a class="btn" href="/index.php?page=patient_form">Novo paciente</a>
    </div>
    <form method="get" class="toolbar">
        <input type="hidden" name="page" value="patients">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome, CPF ou CNS">
        <button type="submit">Buscar</button>
    </form>
    <div class="panel">
        <table>
            <thead><tr><th>Nome</th><th>CPF</th><th>CNS</th><th>Telefone</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$patients): ?>
                <tr><td colspan="5">Nenhum paciente encontrado.</td></tr>
            <?php else: foreach ($patients as $patient): ?>
                <tr>
                    <td><?= e($patient['name']) ?></td>
                    <td><?= e($patient['cpf']) ?></td>
                    <td><?= e($patient['cns']) ?></td>
                    <td><?= e($patient['phone']) ?></td>
                    <td class="actions">
                        <a href="/index.php?page=patient_view&id=<?= (int) $patient['id'] ?>">Ver</a>
                        <a href="/index.php?page=patient_form&id=<?= (int) $patient['id'] ?>">Editar</a>
                        <form method="post" onsubmit="return confirmDelete()">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $patient['id'] ?>">
                            <button type="submit" class="link danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
