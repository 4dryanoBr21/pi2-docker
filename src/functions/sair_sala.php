<?php
// Participante sai da sala por conta própria.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/sala_helpers.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_POST['id_participante'])) {
    http_response_code(400);
    echo "erro";
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo "erro";
    exit;
}

$id_participante = (int) $_POST['id_participante'];

if (!isset($_SESSION['id_participante']) || (int) $_SESSION['id_participante'] !== $id_participante) {
    http_response_code(403);
    echo "erro";
    exit;
}

remover_participante($mysqli, $id_participante);
limpar_sessao_participante();

echo "ok";
