<?php
include('conexao.php');
session_start();

if (!isset($_POST['id_participante'])) {
    die("ID inválido");
}

$id_participante = intval($_POST['id_participante']);

$stmt = $mysqli->prepare("
    SELECT data_hora_solicitacao 
    FROM participante 
    WHERE id_participante = ?
");
$stmt->bind_param("i", $id_participante);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$valorAtual = $row['data_hora_solicitacao'];
$stmt->close();

if ($valorAtual === null) {
    $novoValor = date("Y-m-d H:i:s");
} else {
    $novoValor = null;
}

$stmt2 = $mysqli->prepare("
    UPDATE participante 
    SET data_hora_solicitacao = ?
    WHERE id_participante = ?
");

$stmt2->bind_param("si", $novoValor, $id_participante);
$stmt2->execute();

if ($stmt2->affected_rows >= 0) {
    echo $novoValor === null ? "null" : "hora";
} else {
    echo "erro";
}

$stmt2->close();
?>