<?php
$pdo = db();

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM tfd_processes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash('success', 'Processo removido com sucesso.');
        redirect('/index.php?page=processes');
    }
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'priority' => trim((string) ($_GET['priority'] ?? '')),
];

$sql = 'SELECT p.*, pa.name AS patient_name FROM tfd_processes p JOIN patients pa ON pa.id = p.patient_id WHERE 1=1';
$params = [];

if ($filters['q'] !== '') {
    $sql .= ' AND (p.process_number LIKE :q OR pa.name LIKE :q OR p.cid LIKE :q)';
    $params['q'] = '%' . $filters['q'] . '%';
}
if ($filters['status'] !== '') {
    $sql .= ' AND p.status = :status';
    $params['status'] = $filters['status'];
}
if ($filters['priority'] !== '') {
    $sql .= ' AND p.priority = :priority';
    $params['priority'] = $filters['priority'];
}
$sql .= ' ORDER BY p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$processes = $stmt->fetchAll();
?>
<section>
    <div class="section-head">
        <h2>Processos TFD</h2>
        <a class="btn" href="index.php?page=process_form">Novo processo</a>
    </div>
    <form method="get" class="toolbar grid-toolbar">
        <input type="hidden" name="page" value="processes">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar por processo, paciente ou CID">
        <select name="status">
            <option value="">Todos os status</option>
            <?php foreach (TFD_STATUSES as $status): ?>
                <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority">
            <option value="">Todas as prioridades</option>
            <?php foreach (TFD_PRIORITIES as $priority): ?>
                <option value="<?= e($priority) ?>" <?= $filters['priority'] === $priority ? 'selected' : '' ?>><?= e(ucfirst($priority)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
        <a class="btn secondary" href="index.php?page=processes">Limpar</a>
    </form>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Nº Processo</th>
                    <th>Paciente</th>
                    <th>Tratamento</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$processes): ?>
                <tr><td colspan="6">Nenhum processo encontrado.</td></tr>
            <?php else: foreach ($processes as $process): ?>
                <tr>
                    <td><?= e($process['process_number']) ?></td>
                    <td><?= e($process['patient_name']) ?></td>
                    <td><?= e($process['treatment_location']) ?></td>
                    <td><span class="badge"><?= e($process['status']) ?></span></td>
                    <td><?= e($process['priority']) ?></td>
                    <td class="actions">
                        <a href="index.php?page=process_view&id=<?= (int) $process['id'] ?>">Ver</a>
                        <a href="index.php?page=process_form&id=<?= (int) $process['id'] ?>">Editar</a>
                        <form method="post" onsubmit="return confirmDelete()">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $process['id'] ?>">
                            <button type="submit" class="link danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
