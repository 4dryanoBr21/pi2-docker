<?php
include('conexao.php');

if (!isset($_GET['id_sala'])) {
    die("ID inválido");
}

$id_sala = intval($_GET['id_sala']);

$stmt = $mysqli->prepare("
    SELECT nome_participante, data_hora_solicitacao 
    FROM participante 
    WHERE fk_sala_atual = ?
    ORDER BY 
        (data_hora_solicitacao IS NULL) ASC,
        data_hora_solicitacao ASC
");

$stmt->bind_param("i", $id_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Nenhum participante na sala ainda.</p>";
} else {
    while ($row = $result->fetch_assoc()) {

        $nome = htmlspecialchars($row['nome_participante']);

        if (!empty($row['data_hora_solicitacao'])) {
            echo "<p><strong>{$nome} 🤚 00:00</strong></p>";
        } else {
            echo "<p>{$nome}</p>";
        }
    }
}

$stmt->close();
?>