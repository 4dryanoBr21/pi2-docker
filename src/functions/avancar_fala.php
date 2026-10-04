<?php
// Criador passa a palavra ao próximo da fila (botão "Iniciar próximo" /
// "Passar a vez", ou automaticamente quando o cronômetro zera).

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth_criador.php';
require_once __DIR__ . '/sala_helpers.php';

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

criador_registrar_atividade($mysqli);

// id do orador que o cliente acha que está falando (0 = ninguém). O servidor
// só avança se isso bater com a realidade — veja avancar_fala_sala().
$esperado = isset($_POST['falando_atual']) ? max(0, (int) $_POST['falando_atual']) : null;

session_write_close();

$resultado = avancar_fala_sala($mysqli, $id_sala, $esperado);

responder_json(200, [
    'ok' => true,
    'avancou' => $resultado['avancou'],
    'proximo' => $resultado['proximo'],
]);
