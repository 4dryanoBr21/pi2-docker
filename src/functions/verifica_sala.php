<?php
require("conexao.php");
require("sala_helpers.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id_sala'])) {
    echo "0";
    exit;
}

$id_sala = intval($_GET['id_sala']);

if (isset($_SESSION['id_participante'])) {
    $id_participante_ativo = intval($_SESSION['id_participante']);

    // checa existência com SELECT, não com affected_rows do UPDATE (ver
    // explicação em estado_sala.php)
    $stmt_check = $mysqli->prepare("SELECT 1 FROM participante WHERE id_participante = ?");
    $stmt_check->bind_param("i", $id_participante_ativo);
    $stmt_check->execute();
    $ainda_existe = $stmt_check->get_result()->num_rows > 0;
    $stmt_check->close();

    // participante já foi removido — trata como "sala encerrada" pra este
    // cliente específico, reaproveitando o redirecionamento já existente
    if (!$ainda_existe) {
        echo "1";
        exit;
    }

    $stmt_touch = $mysqli->prepare("UPDATE participante SET ultima_atividade = NOW() WHERE id_participante = ?");
    $stmt_touch->bind_param("i", $id_participante_ativo);
    $stmt_touch->execute();
    $stmt_touch->close();
}

if (!limpar_sala_se_abandonada($mysqli, $id_sala)) {
    echo "1";
    exit;
}

// como fechar a sala significa apagá-la, "sala não existe" já é
// sinônimo de "sala encerrada"
$stmt = $mysqli->prepare("SELECT 1 FROM sala WHERE id_sala = ?");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

echo ($result->num_rows === 0) ? "1" : "0";
?>
