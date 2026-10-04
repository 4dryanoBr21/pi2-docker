<?php
// "Encerrar sala": apaga a sala e todos os participantes. Só o dono.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth_criador.php';
require_once __DIR__ . '/sala_helpers.php';

header('Content-Type: text/plain; charset=utf-8');

if (!criador_autenticado($mysqli)) {
    http_response_code(403);
    echo "erro";
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo "erro";
    exit;
}

$id_sala = (int) ($_POST['id_sala'] ?? 0);
if ($id_sala <= 0) {
    http_response_code(400);
    echo "erro";
    exit;
}

if (!criador_eh_dono_da_sala($mysqli, $id_sala)) {
    http_response_code(403);
    echo "erro";
    exit;
}

apagar_sala($mysqli, $id_sala);

echo "ok";
