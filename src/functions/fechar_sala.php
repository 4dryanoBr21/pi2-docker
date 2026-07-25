<?php
require("conexao.php");

if (!isset($_POST['id_sala'])) {
    echo "erro";
    exit;
}

$id_sala = intval($_POST['id_sala']);

$stmt = $mysqli->prepare("
    UPDATE sala 
    SET encerrada = 1,
        data_fim = NOW()
    WHERE id_sala = ?
");
$stmt->bind_param("i", $id_sala);
$stmt->execute();

$stmt2 = $mysqli->prepare("
    UPDATE participante 
    SET fk_sala_atual = NULL, data_hora_solicitacao = NULL
    WHERE fk_sala_atual = ?
");

$stmt2->bind_param("i", $id_sala);
$stmt2->execute();

echo "ok";
?>