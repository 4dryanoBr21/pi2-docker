<?php
require("conexao.php");
session_start();

if (!isset($_POST['id_participante'])) {
    echo "erro";
    exit;
}

$id_participante = intval($_POST['id_participante']);

$stmt1 = $mysqli->prepare("UPDATE participante SET fk_sala_atual = NULL WHERE id_participante = ?");
$stmt1->bind_param("i", $id_participante);
$stmt1->execute();

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