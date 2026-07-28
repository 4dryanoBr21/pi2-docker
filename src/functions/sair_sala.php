<?php
require("conexao.php");
require("csrf.php");
session_start();

if (!isset($_POST['id_participante'])) {
    echo "erro";
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo "erro";
    exit;
}

$id_participante = intval($_POST['id_participante']);

// só permite que o participante mexa no próprio registro, nunca no de outra pessoa
if (!isset($_SESSION['id_participante']) || (int) $_SESSION['id_participante'] !== $id_participante) {
    http_response_code(403);
    echo "erro";
    exit;
}

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
