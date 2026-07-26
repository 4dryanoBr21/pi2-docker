<?php
session_start();
require("conexao.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_criador']) || !isset($_SESSION['session_token'])) {
    echo json_encode(['valido' => false]);
    exit;
}

$stmt = $mysqli->prepare("SELECT session_token FROM criador WHERE id_criador = ?");
$stmt->bind_param("i", $_SESSION['id_criador']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$valido = $row && hash_equals((string) $row['session_token'], (string) $_SESSION['session_token']);

if ($valido) {
    // renova a marca de atividade — enquanto a página do criador estiver
    // aberta e este polling rodando, a sessão nunca fica "parada" o
    // suficiente para liberar um login em outro lugar
    $stmt2 = $mysqli->prepare("UPDATE criador SET session_last_activity = NOW() WHERE id_criador = ?");
    $stmt2->bind_param("i", $_SESSION['id_criador']);
    $stmt2->execute();
    $stmt2->close();
}

echo json_encode(['valido' => $valido]);
