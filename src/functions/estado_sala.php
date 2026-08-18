<?php
include('conexao.php');
require('sala_helpers.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id_sala'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'id_sala não informado']);
    exit;
}

$id_sala = intval($_GET['id_sala']);

if (!limpar_sala_se_abandonada($mysqli, $id_sala)) {
    http_response_code(404);
    echo json_encode(['erro' => 'sala não encontrada']);
    exit;
}

// TIMESTAMPDIFF roda dentro do próprio MySQL: fala_inicio e "agora" vêm da
// mesma fonte de tempo, então não há risco de descompasso de fuso/relógio
// entre o container do PHP e o do banco (causa do bug do cronômetro).
$stmt = $mysqli->prepare("
    SELECT fk_participante_falando, tempo_de_fala, data_inicio,
           TIMESTAMPDIFF(SECOND, fala_inicio, NOW()) AS decorrido_fala
    FROM sala
    WHERE id_sala = ?
");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$sala = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sala) {
    http_response_code(404);
    echo json_encode(['erro' => 'sala não encontrada']);
    exit;
}

list($h, $m, $s) = explode(':', $sala['tempo_de_fala']);
$duracao_segundos = ((int) $h * 3600) + ((int) $m * 60) + (int) $s;

$falando = null;

if ($sala['fk_participante_falando']) {
    $stmt = $mysqli->prepare("SELECT id_participante, nome_participante FROM participante WHERE id_participante = ?");
    $stmt->bind_param("i", $sala['fk_participante_falando']);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($p) {
        $decorrido = max(0, (int) $sala['decorrido_fala']);
        $restante = max(0, $duracao_segundos - $decorrido);

        $falando = [
            'id_participante' => (int) $p['id_participante'],
            'nome' => $p['nome_participante'],
            'restante_segundos' => $restante,
        ];
    }
}

$falando_id = $falando['id_participante'] ?? 0;

$stmt = $mysqli->prepare("
    SELECT id_participante, nome_participante
    FROM participante
    WHERE fk_sala_atual = ?
      AND data_hora_solicitacao IS NOT NULL
      AND id_participante != ?
    ORDER BY data_hora_solicitacao ASC
");
$stmt->bind_param("ii", $id_sala, $falando_id);
$stmt->execute();
$result = $stmt->get_result();

$fila = [];
while ($row = $result->fetch_assoc()) {
    $fila[] = ['id_participante' => (int) $row['id_participante'], 'nome' => $row['nome_participante']];
}
$stmt->close();

// lista de TODOS os participantes presentes na sala (independente de fila/fala)
$stmt = $mysqli->prepare("
    SELECT id_participante, nome_participante
    FROM participante
    WHERE fk_sala_atual = ?
    ORDER BY id_participante ASC
");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

$presentes = [];
while ($row = $result->fetch_assoc()) {
    $presentes[] = ['id_participante' => (int) $row['id_participante'], 'nome' => $row['nome_participante']];
}
$stmt->close();

echo json_encode([
    'duracao_segundos' => $duracao_segundos,
    'data_inicio' => $sala['data_inicio'],
    'falando' => $falando,
    'fila' => $fila,
    'presentes' => $presentes,
]);
