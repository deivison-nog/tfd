<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$patient = [
    'name' => '',
    'cpf' => '',
    'cns' => '',
    'phone' => '',
    'address' => '',
    'notes' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $patient = $found;
    }
}

if (is_post()) {
    verify_csrf();

    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'cpf' => trim((string) ($_POST['cpf'] ?? '')),
        'cns' => trim((string) ($_POST['cns'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    if ($payload['name'] === '' || $payload['cpf'] === '') {
        flash('error', 'Nome e CPF são obrigatórios.');
        redirect('/index.php?page=patient_form' . ($id ? "&id={$id}" : ''));
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE patients SET name=:name, cpf=:cpf, cns=:cns, phone=:phone, address=:address, notes=:notes, updated_at=:updated_at WHERE id=:id');
            $stmt->execute($payload + ['updated_at' => date('Y-m-d H:i:s'), 'id' => $id]);
            flash('success', 'Paciente atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO patients (name, cpf, cns, phone, address, notes, created_at) VALUES (:name,:cpf,:cns,:phone,:address,:notes,:created_at)');
            $stmt->execute($payload + ['created_at' => date('Y-m-d H:i:s')]);
            flash('success', 'Paciente cadastrado com sucesso.');
        }
        redirect('/index.php?page=patients');
    } catch (Throwable $e) {
        flash('error', 'Erro ao salvar paciente. Verifique se o CPF já está cadastrado.');
        redirect('/index.php?page=patient_form' . ($id ? "&id={$id}" : ''));
    }
}
?>
<section>
    <div class="section-head"><h2><?= $id ? 'Editar paciente' : 'Novo paciente' ?></h2></div>
    <form method="post" class="grid-form panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Nome *
            <input type="text" name="name" required value="<?= e($patient['name']) ?>">
        </label>
        <label>CPF *
            <input type="text" name="cpf" required value="<?= e($patient['cpf']) ?>" data-mask="cpf" placeholder="000.000.000-00">
        </label>
        <label>CNS / Cartão SUS
            <input type="text" name="cns" value="<?= e($patient['cns']) ?>">
        </label>
        <label>Telefone
            <input type="text" name="phone" value="<?= e($patient['phone']) ?>" data-mask="phone" placeholder="(91)99999-9999">
        </label>
        <label class="full">Endereço
            <input type="text" name="address" value="<?= e($patient['address']) ?>">
        </label>
        <label class="full">Observações
            <textarea name="notes" rows="4"><?= e($patient['notes']) ?></textarea>
        </label>
        <div class="full actions-row">
            <button type="submit">Salvar</button>
            <a class="btn secondary" href="index.php?page=patients">Cancelar</a>
        </div>
    </form>
</section>
