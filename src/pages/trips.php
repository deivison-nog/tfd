<?php
$pdo = db();

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM trips WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash('success', 'Viagem removida.');
        redirect('/index.php?page=trips');
    }
}

$status = trim((string) ($_GET['status'] ?? ''));
$sql = 'SELECT t.*, p.process_number, pa.name AS patient_name FROM trips t JOIN tfd_processes p ON p.id = t.process_id JOIN patients pa ON pa.id = p.patient_id WHERE 1=1';
$params = [];
if ($status !== '') {
    $sql .= ' AND t.execution_status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY t.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
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
            <option value="agendado" <?= $status === 'agendado' ? 'selected' : '' ?>>Agendado</option>
            <option value="em viagem" <?= $status === 'em viagem' ? 'selected' : '' ?>>Em viagem</option>
            <option value="retornado" <?= $status === 'retornado' ? 'selected' : '' ?>>Retornado</option>
            <option value="concluído" <?= $status === 'concluído' ? 'selected' : '' ?>>Concluído</option>
            <option value="cancelado" <?= $status === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
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
                    <td><span class="badge"><?= e($row['execution_status']) ?></span></td>
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
</section>
