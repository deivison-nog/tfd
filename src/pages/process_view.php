<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('
    SELECT
        p.*,
        pa.name AS patient_name,
        pa.cpf AS patient_cpf,
        u.name AS professional_opinion_author
    FROM tfd_processes p
    JOIN patients pa ON pa.id = p.patient_id
    LEFT JOIN users u ON u.id = p.professional_opinion_by
    WHERE p.id = :id
');
$stmt->execute(['id' => $id]);
$process = $stmt->fetch();

if (!$process) {
    flash('error', 'Processo não encontrado.');
    redirect('/index.php?page=processes');
}

$companions = $pdo->prepare('SELECT * FROM companions WHERE process_id = :process_id ORDER BY id DESC');
$companions->execute(['process_id' => $id]);
$companionsRows = $companions->fetchAll();

$documents = $pdo->prepare('SELECT * FROM documents WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY id DESC');
$documents->execute(['entity_type' => 'process', 'entity_id' => $id]);
$documentRows = $documents->fetchAll();

$history = $pdo->prepare('SELECT h.*, u.username FROM status_history h LEFT JOIN users u ON u.id = h.changed_by WHERE h.process_id = :id ORDER BY h.id DESC');
$history->execute(['id' => $id]);
$historyRows = $history->fetchAll();
?>
<section>
    <div class="section-head">
        <h2>Processo <?= e($process['process_number']) ?></h2>
        <a class="btn" href="index.php?page=process_form&id=<?= (int) $process['id'] ?>">Editar processo</a>
    </div>

    <div class="panel details-grid">
        <div><strong>Paciente:</strong> <?= e($process['patient_name']) ?> (CPF <?= e($process['patient_cpf']) ?>)</div>
        <div><strong>Status:</strong> <span class="badge"><?= e($process['status']) ?></span></div>
        <div><strong>Ano abertura:</strong> <?= (int) $process['opening_year'] ?></div>
        <div><strong>Ano atualização:</strong> <?= (int) $process['update_year'] ?></div>
        <div><strong>Local tratamento:</strong> <?= e($process['treatment_location']) ?></div>
        <div><strong>CID:</strong> <?= e($process['cid']) ?></div>
        <div><strong>Especialidade:</strong> <?= e($process['specialty']) ?></div>
        <div><strong>Município destino:</strong> <?= e($process['destination_city']) ?></div>
        <div><strong>Solicitação:</strong> <?= e($process['request_date']) ?></div>
        <div><strong>Prioridade:</strong> <?= e($process['priority']) ?></div>
        <div><strong>Acompanhante:</strong> <?= (int) $process['companion_required'] === 1 ? 'Sim' : 'Não' ?></div>
        <div class="full"><strong>Observações:</strong> <?= nl2br(e($process['notes'])) ?></div>
        <div class="full">
            <strong>Parecer profissional:</strong>
            <?= nl2br(e((string) ($process['professional_opinion'] ?? ''))) ?: 'Não informado.' ?>
            <?php if (!empty($process['professional_opinion_author'])): ?>
                <br><small>Por: <?= e((string) $process['professional_opinion_author']) ?> em <?= e((string) $process['professional_opinion_at']) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <div class="split-panels">
        <div class="panel">
            <h3>Acompanhantes</h3>
            <a class="btn secondary" href="index.php?page=companion_form&process_id=<?= (int) $id ?>">Novo acompanhante</a>
            <ul>
                <?php if (!$companionsRows): ?>
                    <li>Sem acompanhantes cadastrados.</li>
                <?php else: foreach ($companionsRows as $companion): ?>
                    <li><?= e($companion['name']) ?> - <?= e($companion['relationship']) ?></li>
                <?php endforeach; endif; ?>
            </ul>
        </div>

        <div class="panel">
            <h3>Documentos (opcionais)</h3>
            <a class="btn secondary" href="index.php?page=documents&entity_type=process&entity_id=<?= (int) $id ?>">Gerenciar documentos</a>
            <ul>
                <?php if (!$documentRows): ?>
                    <li>Nenhum documento registrado.</li>
                <?php else: foreach ($documentRows as $document): ?>
                    <li><?= e($document['document_type']) ?> - <?= e($document['upload_status']) ?></li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>

    <div class="panel">
        <h3>Histórico de status</h3>
        <table>
            <thead><tr><th>Data</th><th>De</th><th>Para</th><th>Nota</th><th>Usuário</th></tr></thead>
            <tbody>
            <?php if (!$historyRows): ?>
                <tr><td colspan="5">Sem histórico.</td></tr>
            <?php else: foreach ($historyRows as $row): ?>
                <tr>
                    <td><?= e($row['changed_at']) ?></td>
                    <td><?= e((string) $row['previous_status']) ?></td>
                    <td><?= e($row['new_status']) ?></td>
                    <td><?= e((string) $row['note']) ?></td>
                    <td><?= e((string) $row['username']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
