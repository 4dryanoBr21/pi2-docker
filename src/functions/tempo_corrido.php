<?php
// tempo_corrido.php
// Supõe que $mysqli e $id_sala já existem (fornecidos pelo arquivo que inclui este)

$sql = "SELECT data_inicio FROM sala WHERE id_sala = $id_sala";
$res = $mysqli->query($sql);

if (!$res) {
    error_log("tempo_corrido.php SELECT erro: " . $mysqli->error);
    return;
}

$row = $res->fetch_assoc();

if ($row === null || $row['data_inicio'] === null) {
    $update = $mysqli->query("UPDATE sala SET data_inicio = NOW() WHERE id_sala = $id_sala");
    if (!$update) {
        error_log("tempo_corrido.php UPDATE erro: " . $mysqli->error);
    }
    $res2 = $mysqli->query("SELECT data_inicio FROM sala WHERE id_sala = $id_sala");
    if ($res2) {
        $row2 = $res2->fetch_assoc();
        $inicio_sala = $row2['data_inicio'];
    }
} else {
    $inicio_sala = $row['data_inicio'];
}
?>
