<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$processes = $pdo->query('SELECT id, process_number FROM tfd_processes ORDER BY id DESC')->fetchAll();

if (!$processes) {
    flash('error', 'Cadastre um processo antes de incluir acompanhante.');
    redirect('/index.php?page=processes');
}

$processIdFromQuery = (int) ($_GET['process_id'] ?? 0);
$companion = [
    'process_id' => $processIdFromQuery > 0 ? $processIdFromQuery : $processes[0]['id'],
    'name' => '',
    'cpf' => '',
    'relationship' => '',
    'phone' => '',
    'notes' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM companions WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $companion = $found;
    }
}

if (is_post()) {
    verify_csrf();
    $payload = [
        'process_id' => (int) ($_POST['process_id'] ?? 0),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'cpf' => trim((string) ($_POST['cpf'] ?? '')),
        'relationship' => trim((string) ($_POST['relationship'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    if ($payload['name'] === '' || $payload['process_id'] <= 0) {
        flash('error', 'Nome e processo são obrigatórios para acompanhante.');
        redirect('/index.php?page=companion_form' . ($id ? "&id={$id}" : ''));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE companions SET process_id=:process_id, name=:name, cpf=:cpf, relationship=:relationship, phone=:phone, notes=:notes, updated_at=:updated_at WHERE id=:id');
        $stmt->execute($payload + ['updated_at' => date('Y-m-d H:i:s'), 'id' => $id]);
        flash('success', 'Acompanhante atualizado.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO companions (process_id, name, cpf, relationship, phone, notes, created_at) VALUES (:process_id, :name, :cpf, :relationship, :phone, :notes, :created_at)');
        $stmt->execute($payload + ['created_at' => date('Y-m-d H:i:s')]);
        flash('success', 'Acompanhante cadastrado.');
    }

    redirect('/index.php?page=companions');
}
?>
<section>
    <div class="section-head"><h2><?= $id ? 'Editar acompanhante' : 'Novo acompanhante' ?></h2></div>
    <form method="post" class="grid-form panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Processo TFD *
            <select name="process_id" required>
                <?php foreach ($processes as $process): ?>
                    <option value="<?= (int) $process['id'] ?>" <?= (int) $companion['process_id'] === (int) $process['id'] ? 'selected' : '' ?>><?= e($process['process_number']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nome *
            <input type="text" name="name" required value="<?= e((string) $companion['name']) ?>">
        </label>
        <label>CPF
            <input type="text" name="cpf" value="<?= e((string) $companion['cpf']) ?>" data-mask="cpf" placeholder="000.000.000-00">
        </label>
        <label>Parentesco
            <input type="text" name="relationship" value="<?= e((string) $companion['relationship']) ?>">
        </label>
        <label>Telefone
            <input type="text" name="phone" value="<?= e((string) $companion['phone']) ?>">
        </label>
        <label class="full">Observações
            <textarea name="notes" rows="4"><?= e((string) $companion['notes']) ?></textarea>
        </label>
        <div class="full actions-row">
            <button type="submit">Salvar acompanhante</button>
            <a class="btn secondary" href="index.php?page=companions">Cancelar</a>
        </div>
    </form>
</section>
