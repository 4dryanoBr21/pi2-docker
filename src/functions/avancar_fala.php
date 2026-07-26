<?php
include('conexao.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['id_sala'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'id_sala não informado']);
    exit;
}

$id_sala = intval($_POST['id_sala']);

$stmt0 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = NULL, fala_inicio = NULL WHERE id_sala = ?");
$stmt0->bind_param("i", $id_sala);
$stmt0->execute();
$stmt0->close();

$stmt = $mysqli->prepare("
    SELECT id_participante
    FROM participante
    WHERE fk_sala_atual = ? AND data_hora_solicitacao IS NOT NULL
    ORDER BY data_hora_solicitacao ASC
    LIMIT 1
");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['ok' => true, 'proximo' => null]);
    exit;
}

$proximo = $result->fetch_assoc();
$stmt->close();
$id_participante = (int) $proximo['id_participante'];

$stmt2 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = ?, fala_inicio = NOW() WHERE id_sala = ?");
$stmt2->bind_param("ii", $id_participante, $id_sala);
$stmt2->execute();
$stmt2->close();

// quem começa a falar sai da fila de "mão levantada"
$stmt3 = $mysqli->prepare("UPDATE participante SET data_hora_solicitacao = NULL WHERE id_participante = ?");
$stmt3->bind_param("i", $id_participante);
$stmt3->execute();
$stmt3->close();

echo json_encode(['ok' => true, 'proximo' => $id_participante]);
