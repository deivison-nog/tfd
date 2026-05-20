<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$processes = $pdo->query('SELECT id, process_number FROM tfd_processes ORDER BY id DESC')->fetchAll();

if (!$processes) {
    flash('error', 'Cadastre processo TFD antes de agendar viagem.');
    redirect('/index.php?page=processes');
}

$trip = [
    'process_id' => $processes[0]['id'],
    'departure_date' => '',
    'return_date' => '',
    'departure_time' => '',
    'return_time' => '',
    'transport_type' => '',
    'vehicle' => '',
    'driver_name' => '',
    'boarding_place' => '',
    'execution_status' => 'agendado',
    'notes' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $trip = $found;
    }
}

if (is_post()) {
    verify_csrf();

    $payload = [
        'process_id' => (int) ($_POST['process_id'] ?? 0),
        'departure_date' => trim((string) ($_POST['departure_date'] ?? '')),
        'return_date' => trim((string) ($_POST['return_date'] ?? '')),
        'departure_time' => trim((string) ($_POST['departure_time'] ?? '')),
        'return_time' => trim((string) ($_POST['return_time'] ?? '')),
        'transport_type' => trim((string) ($_POST['transport_type'] ?? '')),
        'vehicle' => trim((string) ($_POST['vehicle'] ?? '')),
        'driver_name' => trim((string) ($_POST['driver_name'] ?? '')),
        'boarding_place' => trim((string) ($_POST['boarding_place'] ?? '')),
        'execution_status' => trim((string) ($_POST['execution_status'] ?? 'agendado')),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    if ($payload['process_id'] <= 0 || $payload['departure_date'] === '') {
        flash('error', 'Processo e data de ida são obrigatórios para viagem.');
        redirect('/index.php?page=trip_form' . ($id ? "&id={$id}" : ''));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE trips SET process_id=:process_id, departure_date=:departure_date, return_date=:return_date, departure_time=:departure_time, return_time=:return_time, transport_type=:transport_type, vehicle=:vehicle, driver_name=:driver_name, boarding_place=:boarding_place, execution_status=:execution_status, notes=:notes, updated_at=:updated_at WHERE id=:id');
        $stmt->execute($payload + ['updated_at' => date('Y-m-d H:i:s'), 'id' => $id]);
        flash('success', 'Viagem atualizada.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO trips (process_id, departure_date, return_date, departure_time, return_time, transport_type, vehicle, driver_name, boarding_place, execution_status, notes, created_at) VALUES (:process_id, :departure_date, :return_date, :departure_time, :return_time, :transport_type, :vehicle, :driver_name, :boarding_place, :execution_status, :notes, :created_at)');
        $stmt->execute($payload + ['created_at' => date('Y-m-d H:i:s')]);
        flash('success', 'Viagem cadastrada com sucesso.');
    }

    redirect('/index.php?page=trips');
}
?>
<section>
    <div class="section-head"><h2><?= $id ? 'Editar viagem' : 'Nova viagem' ?></h2></div>
    <form method="post" class="grid-form panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Processo TFD *
            <select name="process_id" required>
                <?php foreach ($processes as $process): ?>
                    <option value="<?= (int) $process['id'] ?>" <?= (int) $trip['process_id'] === (int) $process['id'] ? 'selected' : '' ?>><?= e($process['process_number']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Data da ida *
            <input type="date" name="departure_date" required value="<?= e((string) $trip['departure_date']) ?>">
        </label>
        <label>Hora da ida
            <input type="time" name="departure_time" value="<?= e((string) $trip['departure_time']) ?>">
        </label>
        <label>Data da volta
            <input type="date" name="return_date" value="<?= e((string) $trip['return_date']) ?>">
        </label>
        <label>Hora da volta
            <input type="time" name="return_time" value="<?= e((string) $trip['return_time']) ?>">
        </label>
        <label>Tipo de transporte
            <input type="text" name="transport_type" value="<?= e((string) $trip['transport_type']) ?>" placeholder="Van, ambulância, ônibus...">
        </label>
        <label>Veículo
            <input type="text" name="vehicle" value="<?= e((string) $trip['vehicle']) ?>">
        </label>
        <label>Motorista
            <input type="text" name="driver_name" value="<?= e((string) $trip['driver_name']) ?>">
        </label>
        <label>Local de embarque
            <input type="text" name="boarding_place" value="<?= e((string) $trip['boarding_place']) ?>">
        </label>
        <label>Status de execução
            <select name="execution_status">
                <option value="agendado" <?= $trip['execution_status'] === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                <option value="em viagem" <?= $trip['execution_status'] === 'em viagem' ? 'selected' : '' ?>>Em viagem</option>
                <option value="retornado" <?= $trip['execution_status'] === 'retornado' ? 'selected' : '' ?>>Retornado</option>
                <option value="concluído" <?= $trip['execution_status'] === 'concluído' ? 'selected' : '' ?>>Concluído</option>
                <option value="cancelado" <?= $trip['execution_status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </label>
        <label class="full">Observações
            <textarea name="notes" rows="4"><?= e((string) $trip['notes']) ?></textarea>
        </label>
        <div class="full actions-row">
            <button type="submit">Salvar viagem</button>
            <a class="btn secondary" href="/index.php?page=trips">Cancelar</a>
        </div>
    </form>
</section>
