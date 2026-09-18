<?php
session_start();
require("conexao.php");
require("csrf.php");
require("sala_helpers.php");

if (!isset($_SESSION['id_criador']) || !isset($_SESSION['session_token'])) {
    http_response_code(403);
    echo "erro";
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo "erro";
    exit;
}

$stmt_token = $mysqli->prepare("SELECT session_token FROM criador WHERE id_criador = ?");
$stmt_token->bind_param("i", $_SESSION['id_criador']);
$stmt_token->execute();
$row_token = $stmt_token->get_result()->fetch_assoc();
$stmt_token->close();

if (!$row_token || !hash_equals((string) $row_token['session_token'], (string) $_SESSION['session_token'])) {
    http_response_code(403);
    echo "erro";
    exit;
}

if (!isset($_POST['id_sala'])) {
    echo "erro";
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
    echo "erro";
    exit;
}

apagar_sala($mysqli, $id_sala);

echo "ok";
?>
