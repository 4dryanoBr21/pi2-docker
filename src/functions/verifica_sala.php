<?php
require("conexao.php");

if (!isset($_GET['id_sala'])) {
    echo "0";
    exit;
}

$id_sala = intval($_GET['id_sala']);

// como fechar a sala agora significa apagá-la, "sala não existe" já é
// sinônimo de "sala encerrada" — não depende mais de uma coluna de status
$stmt = $mysqli->prepare("SELECT 1 FROM sala WHERE id_sala = ?");
$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

echo ($result->num_rows === 0) ? "1" : "0";
?>
