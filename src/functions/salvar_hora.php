<?php
// Botão da mão do participante. Comportamento conforme a situação:
//   - mão abaixada  -> levanta (entra na fila, marcando a hora com milissegundos)
//   - mão levantada -> abaixa (sai da fila)
//   - está FALANDO  -> encerra a própria fala e passa a palavra ao próximo
//                      (antes, clicar no ❌ durante a fala recolocava a pessoa
//                      na fila em vez de encerrar)

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/sala_helpers.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_POST['id_participante'])) {
    http_response_code(400);
    echo "erro";
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo "erro";
    exit;
}

$id_participante = (int) $_POST['id_participante'];

if (!isset($_SESSION['id_participante']) || (int) $_SESSION['id_participante'] !== $id_participante) {
    http_response_code(403);
    echo "erro";
    exit;
}

session_write_close();

$participante = db_fetch_one(
    $mysqli,
    "SELECT fk_sala_atual FROM participante WHERE id_participante = ?",
    "i",
    $id_participante
);

if ($participante === null) {
    http_response_code(404);
    echo "erro";
    exit;
}

$id_sala = (int) $participante['fk_sala_atual'];

// Está falando agora? Então o clique significa "terminei".
$falando_agora = db_fetch_one(
    $mysqli,
    "SELECT 1 AS ok FROM sala WHERE id_sala = ? AND fk_participante_falando = ?",
    "ii",
    $id_sala,
    $id_participante
);

if ($falando_agora !== null) {
    avancar_fala_sala($mysqli, $id_sala, $id_participante);
    echo "fim";
    exit;
}

// Alterna a mão em UM único UPDATE (atômico): dois cliques simultâneos não
// conseguem se atropelar. A hora vem do relógio do banco, com milissegundos,
// a mesma fonte usada para ordenar a fila.
db_exec(
    $mysqli,
    "UPDATE participante
        SET data_hora_solicitacao = IF(data_hora_solicitacao IS NULL, NOW(3), NULL),
            ultima_atividade = NOW()
      WHERE id_participante = ?",
    "i",
    $id_participante
);

$atual = db_fetch_one(
    $mysqli,
    "SELECT data_hora_solicitacao FROM participante WHERE id_participante = ?",
    "i",
    $id_participante
);

echo ($atual !== null && $atual['data_hora_solicitacao'] !== null) ? "hora" : "null";
