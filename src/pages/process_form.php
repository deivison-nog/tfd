<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$patients = $pdo->query('SELECT id, name, cpf FROM patients ORDER BY name')->fetchAll();

if (!$patients) {
    flash('error', 'Cadastre ao menos um paciente antes de abrir processo TFD.');
    redirect('/index.php?page=patients');
}

$process = [
    'process_number' => '',
    'opening_year' => date('Y'),
    'update_year' => date('Y'),
    'treatment_location' => '',
    'patient_id' => $patients[0]['id'],
    'cid' => '',
    'specialty' => '',
    'destination_city' => '',
    'request_date' => date('Y-m-d'),
    'priority' => 'média',
    'status' => 'cadastrado',
    'companion_required' => 0,
    'notes' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM tfd_processes WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $process = $found;
    }
}

if (is_post()) {
    verify_csrf();

    $payload = [
        'process_number' => trim((string) ($_POST['process_number'] ?? '')),
        'opening_year' => (int) ($_POST['opening_year'] ?? 0),
        'update_year' => (int) ($_POST['update_year'] ?? 0),
        'treatment_location' => trim((string) ($_POST['treatment_location'] ?? '')),
        'patient_id' => (int) ($_POST['patient_id'] ?? 0),
        'cid' => trim((string) ($_POST['cid'] ?? '')),
        'specialty' => trim((string) ($_POST['specialty'] ?? '')),
        'destination_city' => trim((string) ($_POST['destination_city'] ?? '')),
        'request_date' => trim((string) ($_POST['request_date'] ?? '')),
        'priority' => trim((string) ($_POST['priority'] ?? 'média')),
        'status' => trim((string) ($_POST['status'] ?? 'cadastrado')),
        'companion_required' => isset($_POST['companion_required']) ? 1 : 0,
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    if ($payload['process_number'] === '' || $payload['opening_year'] < 2000 || $payload['update_year'] < 2000 || $payload['treatment_location'] === '' || $payload['patient_id'] <= 0) {
        flash('error', 'Preencha os campos obrigatórios do processo TFD.');
        redirect('/index.php?page=process_form' . ($id ? "&id={$id}" : ''));
    }

    try {
        if ($id > 0) {
            $currentStatusStmt = $pdo->prepare('SELECT status FROM tfd_processes WHERE id=:id');
            $currentStatusStmt->execute(['id' => $id]);
            $previousStatus = (string) $currentStatusStmt->fetchColumn();

            $stmt = $pdo->prepare('UPDATE tfd_processes SET process_number=:process_number, opening_year=:opening_year, update_year=:update_year, treatment_location=:treatment_location, patient_id=:patient_id, cid=:cid, specialty=:specialty, destination_city=:destination_city, request_date=:request_date, priority=:priority, status=:status, companion_required=:companion_required, notes=:notes, updated_at=:updated_at WHERE id=:id');
            $stmt->execute($payload + ['updated_at' => date('Y-m-d H:i:s'), 'id' => $id]);

            if ($previousStatus !== $payload['status']) {
                $historyStmt = $pdo->prepare('INSERT INTO status_history (process_id, previous_status, new_status, note, changed_by, changed_at) VALUES (:process_id, :previous_status, :new_status, :note, :changed_by, :changed_at)');
                $historyStmt->execute([
                    'process_id' => $id,
                    'previous_status' => $previousStatus,
                    'new_status' => $payload['status'],
                    'note' => 'Alteração via edição do processo',
                    'changed_by' => current_user()['id'] ?? null,
                    'changed_at' => date('Y-m-d H:i:s'),
                ]);
            }

            flash('success', 'Processo atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO tfd_processes (process_number, opening_year, update_year, treatment_location, patient_id, cid, specialty, destination_city, request_date, priority, status, companion_required, notes, created_at) VALUES (:process_number, :opening_year, :update_year, :treatment_location, :patient_id, :cid, :specialty, :destination_city, :request_date, :priority, :status, :companion_required, :notes, :created_at)');
            $stmt->execute($payload + ['created_at' => date('Y-m-d H:i:s')]);
            $processId = (int) $pdo->lastInsertId();

            $historyStmt = $pdo->prepare('INSERT INTO status_history (process_id, previous_status, new_status, note, changed_by, changed_at) VALUES (:process_id, :previous_status, :new_status, :note, :changed_by, :changed_at)');
            $historyStmt->execute([
                'process_id' => $processId,
                'previous_status' => null,
                'new_status' => $payload['status'],
                'note' => 'Abertura do processo',
                'changed_by' => current_user()['id'] ?? null,
                'changed_at' => date('Y-m-d H:i:s'),
            ]);

            flash('success', 'Processo cadastrado com sucesso.');
        }

        redirect('/index.php?page=processes');
    } catch (Throwable $e) {
        flash('error', 'Erro ao salvar processo. Verifique campos obrigatórios e nº de processo duplicado.');
        redirect('/index.php?page=process_form' . ($id ? "&id={$id}" : ''));
    }
}
?>
<section>
    <div class="section-head"><h2><?= $id ? 'Editar processo TFD' : 'Novo processo TFD' ?></h2></div>
    <form method="post" class="grid-form panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <label>Nº do processo *
            <input type="text" name="process_number" required value="<?= e((string) $process['process_number']) ?>">
        </label>
        <label>Ano de abertura *
            <input type="number" name="opening_year" min="2000" max="2100" required value="<?= e((string) $process['opening_year']) ?>">
        </label>
        <label>Ano de atualização *
            <input type="number" name="update_year" min="2000" max="2100" required value="<?= e((string) $process['update_year']) ?>">
        </label>
        <label>Local de tratamento *
            <input type="text" name="treatment_location" required value="<?= e((string) $process['treatment_location']) ?>">
        </label>
        <label>Paciente *
            <select name="patient_id" required>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= (int) $patient['id'] ?>" <?= (int) $process['patient_id'] === (int) $patient['id'] ? 'selected' : '' ?>><?= e($patient['name'] . ' - CPF ' . $patient['cpf']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>CID
            <input type="text" name="cid" value="<?= e((string) $process['cid']) ?>">
        </label>
        <label>Especialidade
            <input type="text" name="specialty" value="<?= e((string) $process['specialty']) ?>">
        </label>
        <label>Município destino
            <input type="text" name="destination_city" value="<?= e((string) $process['destination_city']) ?>">
        </label>
        <label>Data da solicitação
            <input type="date" name="request_date" value="<?= e((string) $process['request_date']) ?>">
        </label>
        <label>Prioridade
            <select name="priority">
                <?php foreach (TFD_PRIORITIES as $priority): ?>
                    <option value="<?= e($priority) ?>" <?= $process['priority'] === $priority ? 'selected' : '' ?>><?= e(ucfirst($priority)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status
            <select name="status">
                <?php foreach (TFD_STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= $process['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="checkbox-line">
            <input type="checkbox" name="companion_required" <?= (int) $process['companion_required'] === 1 ? 'checked' : '' ?>> Exige acompanhante?
        </label>
        <label class="full">Observações
            <textarea name="notes" rows="4"><?= e((string) $process['notes']) ?></textarea>
        </label>
        <div class="full actions-row">
            <button type="submit">Salvar processo</button>
            <a class="btn secondary" href="index.php?page=processes">Cancelar</a>
        </div>
    </form>
</section>
