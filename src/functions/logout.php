<?php
session_start();
require_once(__DIR__ . '/conexao.php');

// se havia um criador logado, invalida o token/atividade e apaga a sala
// dele, se tiver uma aberta (mesmo procedimento do botão "Encerrar sala")
if (isset($_SESSION['id_criador'])) {
    $id_criador = intval($_SESSION['id_criador']);

    $stmt_sala = $mysqli->prepare("SELECT fk_sala_criada FROM criador WHERE id_criador = ?");
    $stmt_sala->bind_param("i", $id_criador);
    $stmt_sala->execute();
    $row_sala = $stmt_sala->get_result()->fetch_assoc();
    $stmt_sala->close();

    $id_sala = $row_sala['fk_sala_criada'] ?? null;

    if ($id_sala) {
        // remove a referência de "quem está falando" antes de apagar os participantes
        $stmt0 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = NULL WHERE id_sala = ?");
        $stmt0->bind_param("i", $id_sala);
        $stmt0->execute();
        $stmt0->close();

        // desvincula o criador da sala
        $stmt1 = $mysqli->prepare("UPDATE criador SET fk_sala_criada = NULL WHERE id_criador = ?");
        $stmt1->bind_param("i", $id_criador);
        $stmt1->execute();
        $stmt1->close();

        // apaga todos os participantes da sala
        $stmt2 = $mysqli->prepare("DELETE FROM participante WHERE fk_sala_atual = ?");
        $stmt2->bind_param("i", $id_sala);
        $stmt2->execute();
        $stmt2->close();

        // apaga a sala em si
        $stmt3 = $mysqli->prepare("DELETE FROM sala WHERE id_sala = ?");
        $stmt3->bind_param("i", $id_sala);
        $stmt3->execute();
        $stmt3->close();
    }

    $stmt_logout = $mysqli->prepare("UPDATE criador SET session_token = NULL, session_last_activity = NULL WHERE id_criador = ?");
    $stmt_logout->bind_param("i", $id_criador);
    $stmt_logout->execute();
    $stmt_logout->close();
}

// se havia um participante ativo em uma sala, remove o registro dele do banco
// (mesmo procedimento do sair_sala.php, para não deixar dado órfão)
if (isset($_SESSION['id_participante'])) {
    $id_participante = intval($_SESSION['id_participante']);

    $stmt0 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = NULL, fala_inicio = NULL WHERE fk_participante_falando = ?");
    $stmt0->bind_param("i", $id_participante);
    $stmt0->execute();
    $stmt0->close();

    $stmt1 = $mysqli->prepare("DELETE FROM participante WHERE id_participante = ?");
    $stmt1->bind_param("i", $id_participante);
    $stmt1->execute();
    $stmt1->close();
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

header("Location: /index.php");
exit;
?>
