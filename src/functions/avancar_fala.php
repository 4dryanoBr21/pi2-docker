<?php
session_start();
include('conexao.php');
require('csrf.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_criador']) || !isset($_SESSION['session_token'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'não autenticado']);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['erro' => 'token inválido']);
    exit;
}

$stmt_token = $mysqli->prepare("SELECT session_token FROM criador WHERE id_criador = ?");
$stmt_token->bind_param("i", $_SESSION['id_criador']);
$stmt_token->execute();
$row_token = $stmt_token->get_result()->fetch_assoc();
$stmt_token->close();

if (!$row_token || !hash_equals((string) $row_token['session_token'], (string) $_SESSION['session_token'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'sessão invalidada']);
    exit;
}

if (!isset($_POST['id_sala'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'id_sala não informado']);
    exit;
}

$id_sala = intval($_POST['id_sala']);

$stmt_dono = $mysqli->prepare("SELECT 1 FROM criador WHERE id_criador = ? AND fk_sala_criada = ?");
$stmt_dono->bind_param("ii", $_SESSION['id_criador'], $id_sala);
$stmt_dono->execute();
$eh_dono = $stmt_dono->get_result()->num_rows > 0;
$stmt_dono->close();

if (!$eh_dono) {
    http_response_code(403);
    echo json_encode(['erro' => 'sem permissão para esta sala']);
    exit;
}

$stmt_touch = $mysqli->prepare("UPDATE criador SET session_last_activity = NOW() WHERE id_criador = ?");
$stmt_touch->bind_param("i", $_SESSION['id_criador']);
$stmt_touch->execute();
$stmt_touch->close();

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

$stmt3 = $mysqli->prepare("UPDATE participante SET data_hora_solicitacao = NULL WHERE id_participante = ?");
$stmt3->bind_param("i", $id_participante);
$stmt3->execute();
$stmt3->close();

echo json_encode(['ok' => true, 'proximo' => $id_participante]);
