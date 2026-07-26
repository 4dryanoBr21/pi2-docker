<?php
session_start();
require("conexao.php");

if (!isset($_SESSION['id_criador']) || !isset($_SESSION['session_token'])) {
    http_response_code(403);
    echo "erro";
    exit;
}

// confirma que o token desta sessão ainda é o mais recente (evita que um
// dispositivo já deslogado por um novo login em outro lugar continue agindo)
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

// confirma que a sala pertence ao criador da sessão atual antes de apagar qualquer coisa
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

// remove a referência de "quem está falando" antes de apagar os participantes
$stmt0 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = NULL WHERE id_sala = ?");
$stmt0->bind_param("i", $id_sala);
$stmt0->execute();
$stmt0->close();

// desvincula o criador da sala (a conta do criador continua existindo)
$stmt1 = $mysqli->prepare("UPDATE criador SET fk_sala_criada = NULL WHERE fk_sala_criada = ?");
$stmt1->bind_param("i", $id_sala);
$stmt1->execute();
$stmt1->close();

// apaga todos os participantes da sala
$stmt2 = $mysqli->prepare("DELETE FROM participante WHERE fk_sala_atual = ?");
$stmt2->bind_param("i", $id_sala);
$stmt2->execute();
$stmt2->close();

// apaga a sala em si
$stmt3 = $mysqli->prepare("DELETE FROM sala WHERE id_sala = ?");
$stmt3->bind_param("i", $id_sala);
$stmt3->execute();
$stmt3->close();

echo "ok";
?>