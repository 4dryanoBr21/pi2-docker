<?php
require("conexao.php");

if (!isset($_POST['id_sala'])) {
    echo "erro";
    exit;
}

$id_sala = intval($_POST['id_sala']);

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
