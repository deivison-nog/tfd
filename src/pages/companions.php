<?php
$pdo = db();

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM companions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash('success', 'Acompanhante removido.');
        redirect('/index.php?page=companions');
    }
}

$perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));

$countStmt = $pdo->query('SELECT COUNT(*) FROM companions c JOIN tfd_processes p ON p.id = c.process_id');
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$stmt = $pdo->prepare('SELECT c.*, p.process_number FROM companions c JOIN tfd_processes p ON p.id = c.process_id ORDER BY c.id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<section>
    <div class="section-head">
        <h2>Acompanhantes</h2>
        <a class="btn" href="index.php?page=companion_form">Novo acompanhante</a>
    </div>
    <div class="panel">
        <table>
            <thead><tr><th>Nome</th><th>CPF</th><th>Parentesco</th><th>Telefone</th><th>Processo</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">Nenhum acompanhante cadastrado.</td></tr>
            <?php else: foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['cpf']) ?></td>
                    <td><?= e($row['relationship']) ?></td>
                    <td><?= e(format_phone((string) $row['phone'])) ?></td>
                    <td><a href="index.php?page=process_view&id=<?= (int) $row['process_id'] ?>"><?= e($row['process_number']) ?></a></td>
                    <td class="actions">
                        <a href="index.php?page=companion_form&id=<?= (int) $row['id'] ?>">Editar</a>
                        <form method="post" onsubmit="return confirmDelete()">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="link danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination('companions', $currentPage, $totalPages) ?>
</section>
