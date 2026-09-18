<?php
session_start();
require_once(__DIR__ . '/conexao.php');
require_once(__DIR__ . '/sala_helpers.php');

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
        apagar_sala($mysqli, (int) $id_sala);
    }

    $stmt_logout = $mysqli->prepare("UPDATE criador SET session_token = NULL, session_last_activity = NULL WHERE id_criador = ?");
    $stmt_logout->bind_param("i", $id_criador);
    $stmt_logout->execute();
    $stmt_logout->close();
}

// se havia um participante ativo em uma sala, remove o registro dele do banco
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
