<?php
$pdo = db();
$tripStatuses = TRIP_EXECUTION_STATUSES;

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM trips WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash('success', 'Viagem removida.');
        redirect('/index.php?page=trips');
    }
    if ($action === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $executionStatus = trim((string) ($_POST['execution_status'] ?? ''));
        if (!isset($tripStatuses[$executionStatus])) {
            flash('error', 'Status de viagem inválido.');
            redirect('/index.php?page=trips');
        }
        $stmt = $pdo->prepare('UPDATE trips SET execution_status = :execution_status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'execution_status' => $executionStatus,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
        flash('success', 'Status da viagem atualizado.');
        redirect('/index.php?page=trips');
    }
}

$status = trim((string) ($_GET['status'] ?? ''));
$perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));

$baseSql = ' FROM trips t JOIN tfd_processes p ON p.id = t.process_id JOIN patients pa ON pa.id = p.patient_id WHERE 1=1';
$params = [];
if ($status !== '') {
    $baseSql .= ' AND t.execution_status = :status';
    $params['status'] = $status;
}

$countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql);
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$stmt = $pdo->prepare('SELECT t.*, p.process_number, pa.name AS patient_name' . $baseSql . ' ORDER BY t.id DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$paginationQuery = [];
if ($status !== '') {
    $paginationQuery['status'] = $status;
}
?>
<section>
    <div class="section-head">
        <h2>Agendamento de viagens</h2>
        <a class="btn" href="index.php?page=trip_form">Nova viagem</a>
    </div>

    <form method="get" class="toolbar grid-toolbar">
        <input type="hidden" name="page" value="trips">
        <select name="status">
            <option value="">Todos os status</option>
            <?php foreach ($tripStatuses as $statusValue => $statusLabel): ?>
                <option value="<?= e($statusValue) ?>" <?= $status === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
        <a class="btn secondary" href="index.php?page=trips">Limpar</a>
    </form>

    <div class="panel">
        <table>
            <thead><tr><th>Processo</th><th>Paciente</th><th>Ida</th><th>Volta</th><th>Transporte</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">Nenhuma viagem cadastrada.</td></tr>
            <?php else: foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['process_number']) ?></td>
                    <td><?= e($row['patient_name']) ?></td>
                    <td><?= e(format_date((string) $row['departure_date'])) ?> <?= e((string) $row['departure_time']) ?></td>
                    <td><?= e(format_date((string) $row['return_date'])) ?> <?= e((string) $row['return_time']) ?></td>
                    <td><?= e((string) $row['transport_type']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <select name="execution_status">
                                <?php foreach ($tripStatuses as $statusValue => $statusLabel): ?>
                                    <option value="<?= e($statusValue) ?>" <?= (string) $row['execution_status'] === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="link">Atualizar</button>
                        </form>
                    </td>
                    <td class="actions">
                        <a href="index.php?page=trip_form&id=<?= (int) $row['id'] ?>">Editar</a>
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
    <?= render_pagination('trips', $currentPage, $totalPages, $paginationQuery) ?>
</section>
