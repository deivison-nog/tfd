<?php
$pdo = db();

$entityTypes = ['patient', 'companion', 'process'];
$entityType = trim((string) ($_GET['entity_type'] ?? ''));
if ($entityType !== '' && !in_array($entityType, $entityTypes, true)) {
    $entityType = '';
}
$entityId = (int) ($_GET['entity_id'] ?? 0);
$formEntityType = $entityType !== '' ? $entityType : 'process';
$projectRoot = dirname(__DIR__, 2);
$allowedExtensions = [
    'pdf' => ['application/pdf'],
    'png' => ['image/png'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'doc' => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
];
$entityTables = [
    'patient' => 'patients',
    'companion' => 'companions',
    'process' => 'tfd_processes',
];

function documents_redirect_url(string $entityType, int $entityId): string
{
    $query = ['page' => 'documents'];
    if ($entityType !== '') {
        $query['entity_type'] = $entityType;
    }
    if ($entityId > 0) {
        $query['entity_id'] = (string) $entityId;
    }

    return '/index.php?' . http_build_query($query);
}

function document_exists(PDO $pdo, array $entityTables, string $entityType, int $entityId): bool
{
    if (!isset($entityTables[$entityType]) || $entityId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM ' . $entityTables[$entityType] . ' WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $entityId]);

    return (bool) $stmt->fetchColumn();
}

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, entity_type, entity_id, file_path FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $document = $stmt->fetch();

        if (!$document) {
            flash('error', 'Documento não encontrado.');
            redirect('/index.php?page=documents');
        }

        $redirectUrl = documents_redirect_url((string) $document['entity_type'], (int) $document['entity_id']);
        $filePath = trim((string) ($document['file_path'] ?? ''));
        $absolutePath = str_starts_with($filePath, 'data/uploads/documents/')
            ? $projectRoot . '/' . ltrim($filePath, '/')
            : '';

        if ($absolutePath !== '' && is_file($absolutePath) && !unlink($absolutePath)) {
            flash('error', 'Não foi possível remover o arquivo enviado.');
            redirect($redirectUrl);
        }

        $stmt = $pdo->prepare('DELETE FROM documents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash('success', 'Documento removido.');
        redirect($redirectUrl);
    }

    if (($_POST['action'] ?? '') === 'save') {
        $payload = [
            'entity_type' => trim((string) ($_POST['entity_type'] ?? 'process')),
            'entity_id' => (int) ($_POST['entity_id'] ?? 0),
            'document_type' => trim((string) ($_POST['document_type'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
        $redirectUrl = documents_redirect_url($payload['entity_type'], $payload['entity_id']);
        $uploadedFile = $_FILES['document_file'] ?? null;

        if (!in_array($payload['entity_type'], $entityTypes, true) || $payload['entity_id'] <= 0 || $payload['document_type'] === '') {
            flash('error', 'Preencha os campos obrigatórios para documento.');
            redirect($redirectUrl);
        }

        if (!document_exists($pdo, $entityTables, $payload['entity_type'], $payload['entity_id'])) {
            flash('error', 'A entidade informada não foi encontrada.');
            redirect($redirectUrl);
        }

        if (!is_array($uploadedFile) || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Selecione um arquivo para upload.');
            redirect($redirectUrl);
        }

        if (($uploadedFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            flash('error', 'O upload falhou. Tente novamente.');
            redirect($redirectUrl);
        }

        if (($uploadedFile['size'] ?? 0) > 10 * 1024 * 1024) {
            flash('error', 'O arquivo deve ter no máximo 10 MB.');
            redirect($redirectUrl);
        }

        $originalName = trim((string) ($uploadedFile['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset($allowedExtensions[$extension])) {
            flash('error', 'Formato inválido. Envie PDF, PNG, JPG, JPEG, DOC ou DOCX.');
            redirect($redirectUrl);
        }

        $detectedMimeType = '';
        $tmpName = (string) ($uploadedFile['tmp_name'] ?? '');
        if ($tmpName !== '' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMimeType = (string) finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            }
        }

        if ($detectedMimeType !== '' && !in_array($detectedMimeType, $allowedExtensions[$extension], true)) {
            flash('error', 'O arquivo enviado não corresponde ao formato informado.');
            redirect($redirectUrl);
        }

        $storageDir = $projectRoot . '/data/uploads/documents/' . $payload['entity_type'] . '/' . $payload['entity_id'];
        if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
            flash('error', 'Não foi possível preparar a pasta de upload.');
            redirect($redirectUrl);
        }

        $safeDocumentType = slugify($payload['document_type']);
        $storedFilename = bin2hex(random_bytes(16)) . ($safeDocumentType !== '' ? '_' . $safeDocumentType : '') . '.' . $extension;
        $relativePath = 'data/uploads/documents/' . $payload['entity_type'] . '/' . $payload['entity_id'] . '/' . $storedFilename;
        $destinationPath = $projectRoot . '/' . $relativePath;

        if (!move_uploaded_file($tmpName, $destinationPath)) {
            flash('error', 'Não foi possível salvar o arquivo enviado.');
            redirect($redirectUrl);
        }

        $stmt = $pdo->prepare('INSERT INTO documents (entity_type, entity_id, document_type, original_name, file_path, upload_status, notes, created_at) VALUES (:entity_type, :entity_id, :document_type, :original_name, :file_path, :upload_status, :notes, :created_at)');
        try {
            $stmt->execute($payload + [
                'original_name' => $originalName,
                'file_path' => $relativePath,
                'upload_status' => 'upload concluído',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            if (is_file($destinationPath)) {
                unlink($destinationPath);
            }

            throw $exception;
        }

        flash('success', 'Documento enviado com sucesso.');
        redirect($redirectUrl);
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
        <p>Envie anexos reais para paciente, acompanhante ou processo. Formatos aceitos: PDF, PNG, JPG, JPEG, DOC e DOCX (até 10 MB).</p>
        <form method="post" class="grid-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">

            <label>Entidade *
                <select name="entity_type" id="entity_type">
                    <option value="patient" <?= $formEntityType === 'patient' ? 'selected' : '' ?>>Paciente (laudo)</option>
                    <option value="companion" <?= $formEntityType === 'companion' ? 'selected' : '' ?>>Acompanhante (comprovante/CPF/SUS/dados bancários)</option>
                    <option value="process" <?= $formEntityType === 'process' ? 'selected' : '' ?>>Processo TFD</option>
                </select>
            </label>
            <label>ID da entidade *
                <input type="number" name="entity_id" min="1" required value="<?= $entityId > 0 ? $entityId : '' ?>">
            </label>
            <label>Tipo do documento *
                <input type="text" name="document_type" required placeholder="Ex.: laudo, CPF, comprovante residência">
            </label>
            <label>Arquivo *
                <input type="file" name="document_file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" required>
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
                    <td>
                        <?php if (trim((string) ($document['file_path'] ?? '')) !== ''): ?>
                            <a class="link" href="index.php?page=document_download&id=<?= (int) $document['id'] ?>"><?= e((string) $document['original_name']) ?></a>
                        <?php else: ?>
                            <?= e((string) $document['original_name']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($document['upload_status']) ?></td>
                    <td class="actions">
                        <?php if (trim((string) ($document['file_path'] ?? '')) !== ''): ?>
                            <a class="link" href="index.php?page=document_download&id=<?= (int) $document['id'] ?>">Baixar</a>
                        <?php endif; ?>
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
