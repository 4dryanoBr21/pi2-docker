<?php
require("conexao.php");

if (!isset($_GET['id_sala'])) {
    echo "0";
    exit;
}

$id_sala = intval($_GET['id_sala']);

$stmt = $mysqli->prepare("SELECT 1 FROM sala WHERE id_sala = ?");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

echo ($result->num_rows === 0) ? "1" : "0";
?>
