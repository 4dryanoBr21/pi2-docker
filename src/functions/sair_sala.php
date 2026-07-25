<?php
require("conexao.php");
session_start();

if (!isset($_POST['id_participante'])) {
    echo "erro";
    exit;
}

$id_participante = intval($_POST['id_participante']);

// se o participante que está saindo era quem estava com a palavra, libera a vez
$stmt0 = $mysqli->prepare("
    UPDATE sala
    SET fk_participante_falando = NULL, fala_inicio = NULL
    WHERE fk_participante_falando = ?
");
$stmt0->bind_param("i", $id_participante);
$stmt0->execute();
$stmt0->close();

// apaga somente o registro deste participante — a sala e os demais continuam
$stmt1 = $mysqli->prepare("DELETE FROM participante WHERE id_participante = ?");
$stmt1->bind_param("i", $id_participante);
$stmt1->execute();
$stmt1->close();

if (isset($_SESSION['id_participante'])) {
    unset($_SESSION['id_participante']);
}
if (isset($_SESSION['nome'])) {
    unset($_SESSION['nome']);
}
if (isset($_SESSION['codigo'])) {
    unset($_SESSION['codigo']);
}

echo "ok";
?>
