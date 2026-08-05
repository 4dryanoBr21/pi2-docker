<?php
include('conexao.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id_sala'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'id_sala não informado']);
    exit;
}

$id_sala = intval($_GET['id_sala']);

$stmt = $mysqli->prepare("
    SELECT fk_participante_falando, fala_inicio, tempo_de_fala, data_inicio
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
        $inicio = new DateTime($sala['fala_inicio']);
        $agora = new DateTime();
        $decorrido = $agora->getTimestamp() - $inicio->getTimestamp();
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

echo json_encode([
    'duracao_segundos' => $duracao_segundos,
    'data_inicio' => $sala['data_inicio'],
    'falando' => $falando,
    'fila' => $fila,
]);
