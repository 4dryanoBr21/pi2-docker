<?php
// Criador altera os limites da SUA sala já criada (participantes e pautas).
// 0 = sem limite. Reduzir um limite não remove ninguém: ele só vale para
// novas entradas e novas mãos levantadas.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth_criador.php';
require_once __DIR__ . '/sala_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(405, ['erro' => 'método não permitido']);
}

if (!criador_autenticado($mysqli)) {
    responder_json(403, ['erro' => 'não autenticado']);
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    responder_json(403, ['erro' => 'token inválido']);
}

$id_sala = (int) ($_POST['id_sala'] ?? 0);
if ($id_sala <= 0) {
    responder_json(400, ['erro' => 'id_sala não informado']);
}

if (!criador_eh_dono_da_sala($mysqli, $id_sala)) {
    responder_json(403, ['erro' => 'sem permissão para esta sala']);
}

// Campo vazio = sem limite (0). Texto, negativo ou acima do máximo = inválido.
$max_participantes = normalizar_limite($_POST['max_participantes'] ?? '');
$max_fila = normalizar_limite($_POST['max_fila'] ?? '');

if ($max_participantes === null || $max_fila === null) {
    responder_json(400, ['erro' => 'limite_invalido']);
}

criador_registrar_atividade($mysqli);
session_write_close();

db_exec(
    $mysqli,
    "UPDATE sala SET max_participantes = ?, max_fila = ? WHERE id_sala = ?",
    "iii",
    $max_participantes,
    $max_fila,
    $id_sala
);

responder_json(200, [
    'ok' => true,
    'max_participantes' => $max_participantes,
    'max_fila' => $max_fila,
]);
