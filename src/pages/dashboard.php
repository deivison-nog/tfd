<?php
$pdo = db();
$totals = [
    'patients' => (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
    'processes' => (int) $pdo->query('SELECT COUNT(*) FROM tfd_processes')->fetchColumn(),
    'companions' => (int) $pdo->query('SELECT COUNT(*) FROM companions')->fetchColumn(),
    'trips' => (int) $pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn(),
];

$statusRows = $pdo->query('SELECT status, COUNT(*) total FROM tfd_processes GROUP BY status ORDER BY total DESC')->fetchAll();
?>
<section>
    <h2>Dashboard</h2>
    <p>Visão inicial de operação do Transporte Fora do Domicílio.</p>

    <div class="cards">
        <article class="card"><h3><?= $totals['patients'] ?></h3><p>Pacientes</p></article>
        <article class="card"><h3><?= $totals['processes'] ?></h3><p>Processos TFD</p></article>
        <article class="card"><h3><?= $totals['companions'] ?></h3><p>Acompanhantes</p></article>
        <article class="card"><h3><?= $totals['trips'] ?></h3><p>Viagens</p></article>
    </div>

    <div class="panel">
        <h3>Status dos processos</h3>
        <table>
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
            <?php if (!$statusRows): ?>
                <tr><td colspan="2">Nenhum processo cadastrado.</td></tr>
            <?php else: ?>
                <?php foreach ($statusRows as $row): ?>
                    <tr><td><?= e($row['status']) ?></td><td><?= (int) $row['total'] ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
