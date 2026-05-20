<?php
$pdo = db();

$entityTypes = ['patient', 'companion', 'process'];
$entityType = trim((string) ($_GET['entity_type'] ?? 'process'));
if (!in_array($entityType, $entityTypes, true)) {
    $entityType = 'process';
}
$entityId = (int) ($_GET['entity_id'] ?? 0);

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM documents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash('success', 'Documento removido.');
        redirect('/index.php?page=documents');
    }

    if (($_POST['action'] ?? '') === 'save') {
        $payload = [
            'entity_type' => trim((string) ($_POST['entity_type'] ?? 'process')),
            'entity_id' => (int) ($_POST['entity_id'] ?? 0),
            'document_type' => trim((string) ($_POST['document_type'] ?? '')),
            'original_name' => trim((string) ($_POST['original_name'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if (!in_array($payload['entity_type'], $entityTypes, true) || $payload['entity_id'] <= 0 || $payload['document_type'] === '') {
            flash('error', 'Preencha os campos obrigatórios para documento.');
            redirect('/index.php?page=documents');
        }

        $stmt = $pdo->prepare('INSERT INTO documents (entity_type, entity_id, document_type, original_name, file_path, upload_status, notes, created_at) VALUES (:entity_type, :entity_id, :document_type, :original_name, :file_path, :upload_status, :notes, :created_at)');
        $stmt->execute($payload + [
            'file_path' => null,
            'upload_status' => 'estrutura pronta para upload',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        flash('success', 'Documento registrado (estrutura pronta para upload real).');
        redirect('/index.php?page=documents');
    }
}

$patients = $pdo->query('SELECT id, name FROM patients ORDER BY name')->fetchAll();
$processes = $pdo->query('SELECT id, process_number FROM tfd_processes ORDER BY id DESC')->fetchAll();
$companions = $pdo->query('SELECT id, name FROM companions ORDER BY name')->fetchAll();

$sql = 'SELECT * FROM documents WHERE 1=1';
$params = [];
if ($entityType !== '') {
    $sql .= ' AND entity_type = :entity_type';
    $params['entity_type'] = $entityType;
}
if ($entityId > 0) {
    $sql .= ' AND entity_id = :entity_id';
    $params['entity_id'] = $entityId;
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();
?>
<section>
    <div class="section-head"><h2>Documentos (opcionais)</h2></div>
    <div class="panel">
        <p>Modelagem pronta para upload. Nesta versão MVP, os metadados são registrados para preparar integração de anexos reais.</p>
        <form method="post" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">

            <label>Entidade *
                <select name="entity_type" id="entity_type">
                    <option value="patient">Paciente (laudo)</option>
                    <option value="companion">Acompanhante (comprovante/CPF/SUS/dados bancários)</option>
                    <option value="process" selected>Processo TFD</option>
                </select>
            </label>
            <label>ID da entidade *
                <input type="number" name="entity_id" min="1" required>
            </label>
            <label>Tipo do documento *
                <input type="text" name="document_type" required placeholder="Ex.: laudo, CPF, comprovante residência">
            </label>
            <label>Nome original do arquivo
                <input type="text" name="original_name" placeholder="Ex.: laudo_joao.pdf">
            </label>
            <label class="full">Observações
                <textarea name="notes" rows="3"></textarea>
            </label>
            <div class="full actions-row"><button type="submit">Registrar documento</button></div>
        </form>
    </div>

    <form method="get" class="toolbar grid-toolbar">
        <input type="hidden" name="page" value="documents">
        <select name="entity_type">
            <option value="">Todas as entidades</option>
            <?php foreach ($entityTypes as $type): ?>
                <option value="<?= e($type) ?>" <?= $entityType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="entity_id" min="0" value="<?= $entityId > 0 ? $entityId : '' ?>" placeholder="Filtrar por ID">
        <button type="submit">Filtrar</button>
        <a class="btn secondary" href="index.php?page=documents">Limpar</a>
    </form>

    <div class="panel">
        <table>
            <thead><tr><th>Entidade</th><th>ID</th><th>Tipo</th><th>Arquivo</th><th>Status Upload</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (!$documents): ?>
                <tr><td colspan="6">Nenhum documento registrado.</td></tr>
            <?php else: foreach ($documents as $document): ?>
                <tr>
                    <td><?= e($document['entity_type']) ?></td>
                    <td><?= (int) $document['entity_id'] ?></td>
                    <td><?= e($document['document_type']) ?></td>
                    <td><?= e((string) $document['original_name']) ?></td>
                    <td><?= e($document['upload_status']) ?></td>
                    <td class="actions">
                        <form method="post" onsubmit="return confirmDelete()">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $document['id'] ?>">
                            <button type="submit" class="link danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Referência rápida de IDs</h3>
        <div class="split-panels">
            <div>
                <h4>Pacientes</h4>
                <ul><?php foreach ($patients as $row): ?><li>#<?= (int) $row['id'] ?> - <?= e($row['name']) ?></li><?php endforeach; ?></ul>
            </div>
            <div>
                <h4>Processos</h4>
                <ul><?php foreach ($processes as $row): ?><li>#<?= (int) $row['id'] ?> - <?= e($row['process_number']) ?></li><?php endforeach; ?></ul>
            </div>
            <div>
                <h4>Acompanhantes</h4>
                <ul><?php foreach ($companions as $row): ?><li>#<?= (int) $row['id'] ?> - <?= e($row['name']) ?></li><?php endforeach; ?></ul>
            </div>
        </div>
    </div>
</section>
