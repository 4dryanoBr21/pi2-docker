<?php
// tempo_corrido.php
// Supõe que $mysqli e $id_sala já existem (fornecidos pelo arquivo que inclui este)

$sql = "SELECT inicio_sala FROM sala WHERE id_sala = $id_sala";
$res = $mysqli->query($sql);

if (!$res) {
    // erro no SQL: útil pra debug
    error_log("tempo_corrido.php SELECT erro: " . $mysqli->error);
    return;
}

$row = $res->fetch_assoc();

if ($row === null || $row['inicio_sala'] === null) {
    $update = $mysqli->query("UPDATE sala SET inicio_sala = NOW() WHERE id_sala = $id_sala");
    if (!$update) {
        error_log("tempo_corrido.php UPDATE erro: " . $mysqli->error);
    }
    // opcional: atualizar $row para refletir o novo valor imediatamente
    $res2 = $mysqli->query("SELECT inicio_sala FROM sala WHERE id_sala = $id_sala");
    if ($res2) {
        $row2 = $res2->fetch_assoc();
        // para que o arquivo que incluiu possa usar $inicio_sala imediatamente,
        // podemos definir a variável global aqui (é visível no escopo do script que incluiu).
        $inicio_sala = $row2['inicio_sala'];
    }
}
?>
