<?php

declare(strict_types=1);

const APP_NAME = 'Sistema TFD';
const APP_TIMEZONE = 'America/Belem';
const DB_PATH = __DIR__ . '/../data/tfd.sqlite';

const TFD_STATUSES = [
    'cadastrado',
    'aguardando análise',
    'pendente de documentos',
    'autorizado',
    'agendado',
    'em viagem',
    'retornado',
    'concluído',
    'negado',
    'cancelado',
];

const TFD_PRIORITIES = [
    'baixa',
    'média',
    'alta',
    'urgente',
];

const TRIP_EXECUTION_STATUSES = [
    'agendado' => 'Agendado',
    'em viagem' => 'Em viagem',
    'retornado' => 'Retornado',
    'concluído' => 'Concluído',
    'cancelado' => 'Cancelado',
];
