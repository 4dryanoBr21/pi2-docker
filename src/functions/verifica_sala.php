<?php
require("conexao.php");
require("sala_helpers.php");

if (!isset($_GET['id_sala'])) {
    echo "0";
    exit;
}

$id_sala = intval($_GET['id_sala']);

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
