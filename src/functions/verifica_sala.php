<?php
require("conexao.php");

if (!isset($_GET['id_sala'])) {
    echo "0";
    exit;
}

$id_sala = intval($_GET['id_sala']);

$stmt = $mysqli->prepare("SELECT encerrada FROM sala WHERE id_sala = ?");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "1";
    exit;
}

$row = $result->fetch_assoc();

echo ($row['encerrada'] == 1) ? "1" : "0";
?>