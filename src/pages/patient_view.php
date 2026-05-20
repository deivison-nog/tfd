<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id');
$stmt->execute(['id' => $id]);
$patient = $stmt->fetch();

if (!$patient) {
    flash('error', 'Paciente não encontrado.');
    redirect('/index.php?page=patients');
}

$processesStmt = $pdo->prepare('SELECT id, process_number, status FROM tfd_processes WHERE patient_id = :id ORDER BY id DESC');
$processesStmt->execute(['id' => $id]);
$processes = $processesStmt->fetchAll();
?>
<section>
    <div class="section-head">
        <h2>Paciente: <?= e($patient['name']) ?></h2>
        <a class="btn" href="index.php?page=patient_form&id=<?= $id ?>">Editar</a>
    </div>
    <div class="panel details-grid">
        <div><strong>CPF:</strong> <?= e($patient['cpf']) ?></div>
        <div><strong>CNS:</strong> <?= e($patient['cns']) ?></div>
        <div><strong>Telefone:</strong> <?= e($patient['phone']) ?></div>
        <div><strong>Endereço:</strong> <?= e($patient['address']) ?></div>
        <div class="full"><strong>Observações:</strong> <?= nl2br(e($patient['notes'])) ?></div>
    </div>

    <div class="panel">
        <h3>Processos vinculados</h3>
        <ul>
            <?php if (!$processes): ?>
                <li>Sem processos vinculados.</li>
            <?php else: foreach ($processes as $process): ?>
                <li><a href="index.php?page=process_view&id=<?= (int) $process['id'] ?>"><?= e($process['process_number']) ?></a> - <?= e($process['status']) ?></li>
            <?php endforeach; endif; ?>
        </ul>
    </div>
</section>
