<?php
session_start();
require_once(__DIR__ . '/conexao.php');

if (isset($_SESSION['id_criador'])) {
    $stmt = $mysqli->prepare("UPDATE criador SET session_token = NULL, session_last_activity = NULL WHERE id_criador = ?");
    $stmt->bind_param("i", $_SESSION['id_criador']);
    $stmt->execute();
    $stmt->close();
}

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