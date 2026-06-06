<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('
    SELECT
        p.*,
        pa.name AS patient_name,
        pa.cpf AS patient_cpf
    FROM tfd_processes p
    JOIN patients pa ON pa.id = p.patient_id
    WHERE p.id = :id
');
$stmt->execute(['id' => $id]);
$process = $stmt->fetch();

if (!$process) {
    flash('error', 'Processo não encontrado.');
    redirect('/index.php?page=processes');
}

$canViewProfessionalOpinion = has_permission('professional_opinion');
$currentUserId = (int) (current_user()['id'] ?? 0);

if (is_post()) {
    verify_csrf();

    if (!$canViewProfessionalOpinion) {
        flash('error', 'Seu perfil não possui permissão para gerenciar parecer profissional.');
        redirect('/index.php?page=process_view&id=' . $id);
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    $opinionText = trim((string) ($_POST['opinion_text'] ?? ''));

    if (!in_array($action, ['professional_opinion_create', 'professional_opinion_update'], true)) {
        flash('error', 'Ação inválida para parecer profissional.');
        redirect('/index.php?page=process_view&id=' . $id . '#professional-opinions');
    }

    if ($opinionText === '') {
        flash('error', 'Preencha o parecer profissional.');
        redirect('/index.php?page=process_view&id=' . $id . '#professional-opinions');
    }

    if ($action === 'professional_opinion_create') {
        $insertOpinionStmt = $pdo->prepare('
            INSERT INTO process_professional_opinions (process_id, opinion_text, created_by, created_at)
            VALUES (:process_id, :opinion_text, :created_by, :created_at)
        ');
        $insertOpinionStmt->execute([
            'process_id' => $id,
            'opinion_text' => $opinionText,
            'created_by' => $currentUserId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        flash('success', 'Parecer profissional registrado com sucesso.');
        redirect('/index.php?page=process_view&id=' . $id . '#professional-opinions');
    }

    $opinionId = (int) ($_POST['opinion_id'] ?? 0);
    $updateOpinionStmt = $pdo->prepare('
        UPDATE process_professional_opinions
        SET opinion_text = :opinion_text, updated_at = :updated_at
        WHERE id = :id
          AND process_id = :process_id
          AND created_by = :created_by
    ');
    $updateOpinionStmt->execute([
        'opinion_text' => $opinionText,
        'updated_at' => date('Y-m-d H:i:s'),
        'id' => $opinionId,
        'process_id' => $id,
        'created_by' => $currentUserId,
    ]);

    if ($updateOpinionStmt->rowCount() === 0) {
        flash('error', 'Você só pode editar pareceres criados por você.');
    } else {
        flash('success', 'Parecer profissional atualizado com sucesso.');
    }

    redirect('/index.php?page=process_view&id=' . $id . '#professional-opinions');
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

$opinionRows = [];
$opinionEditingRow = null;
$showOpinionForm = false;

if ($canViewProfessionalOpinion) {
    $opinionsStmt = $pdo->prepare('
        SELECT o.*, u.name AS author_name
        FROM process_professional_opinions o
        JOIN users u ON u.id = o.created_by
        WHERE o.process_id = :process_id
        ORDER BY o.created_at DESC, o.id DESC
    ');
    $opinionsStmt->execute(['process_id' => $id]);
    $opinionRows = $opinionsStmt->fetchAll();

    $editOpinionId = (int) ($_GET['edit_opinion'] ?? 0);
    if ($editOpinionId > 0) {
        foreach ($opinionRows as $opinionRow) {
            if ((int) $opinionRow['id'] === $editOpinionId && (int) $opinionRow['created_by'] === $currentUserId) {
                $opinionEditingRow = $opinionRow;
                $showOpinionForm = true;
                break;
            }
        }
    }

    if (isset($_GET['new_opinion'])) {
        $showOpinionForm = true;
    }
}
?>
<section>
    <div class="section-head">
        <h2>Processo <?= e($process['process_number']) ?></h2>
        <div class="actions-row">
            <?php if ($canViewProfessionalOpinion): ?>
                <a class="btn secondary" href="index.php?page=process_view&id=<?= (int) $process['id'] ?>&new_opinion=1#professional-opinions">Novo parecer profissional</a>
            <?php endif; ?>
            <a class="btn" href="index.php?page=process_form&id=<?= (int) $process['id'] ?>">Editar processo</a>
        </div>
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
        <div><strong>Solicitação:</strong> <?= e(format_date((string) $process['request_date'])) ?></div>
        <div><strong>Prioridade:</strong> <?= e($process['priority']) ?></div>
        <div><strong>Acompanhante:</strong> <?= (int) $process['companion_required'] === 1 ? 'Sim' : 'Não' ?></div>
        <div class="full"><strong>Observações:</strong> <?= nl2br(e($process['notes'])) ?></div>
    </div>

    <?php if ($canViewProfessionalOpinion): ?>
        <div class="panel" id="professional-opinions">
            <h3>Pareceres profissionais</h3>

            <?php if ($showOpinionForm): ?>
                <form method="post" class="grid-form" style="margin-bottom: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $opinionEditingRow ? 'professional_opinion_update' : 'professional_opinion_create' ?>">
                    <?php if ($opinionEditingRow): ?>
                        <input type="hidden" name="opinion_id" value="<?= (int) $opinionEditingRow['id'] ?>">
                    <?php endif; ?>
                    <label class="full">
                        <?= $opinionEditingRow ? 'Editar parecer profissional' : 'Novo parecer profissional' ?>
                        <textarea name="opinion_text" rows="4" required><?= e((string) ($opinionEditingRow['opinion_text'] ?? '')) ?></textarea>
                    </label>
                    <div class="full actions-row">
                        <button type="submit"><?= $opinionEditingRow ? 'Atualizar parecer' : 'Salvar parecer' ?></button>
                        <a class="btn secondary" href="index.php?page=process_view&id=<?= (int) $process['id'] ?>#professional-opinions">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!$opinionRows): ?>
                <p>Nenhum parecer profissional registrado.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($opinionRows as $opinion): ?>
                        <li>
                            <?= nl2br(e($opinion['opinion_text'])) ?><br>
                            <small>
                                Por: <?= e((string) $opinion['author_name']) ?>
                                em <?= e(format_datetime((string) $opinion['created_at'])) ?>
                                <?php if (!empty($opinion['updated_at'])): ?>
                                    (editado em <?= e(format_datetime((string) $opinion['updated_at'])) ?>)
                                <?php endif; ?>
                            </small>
                            <?php if ((int) $opinion['created_by'] === $currentUserId): ?>
                                <div class="actions-row">
                                    <a class="btn secondary" href="index.php?page=process_view&id=<?= (int) $process['id'] ?>&edit_opinion=<?= (int) $opinion['id'] ?>#professional-opinions">Editar meu parecer</a>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (trim((string) ($process['professional_opinion'] ?? '')) !== ''): ?>
                <p><small>Registro legado: <?= nl2br(e((string) $process['professional_opinion'])) ?></small></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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
                    <td><?= e(format_datetime((string) $row['changed_at'])) ?></td>
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
