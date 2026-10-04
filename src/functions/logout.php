<?php
// Sair da conta (criador) / sair da sala (participante).
//
// Só age via POST com token CSRF. Antes era um GET: qualquer link ou imagem
// em outro site podia deslogar o criador — e, como logout apaga a sala dele,
// derrubar a reunião inteira.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth_criador.php';
require_once __DIR__ . '/sala_helpers.php';

// GET (links antigos, favoritos) ou token inválido: não faz nada, só volta
// para a página inicial.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
    header('Location: /index.php');
    exit;
}

// se havia um criador logado, invalida o token/atividade e apaga a sala
// dele, se tiver uma aberta (mesmo procedimento do botão "Encerrar sala").
// Só age se a sessão ainda for VÁLIDA: uma aba antiga (derrubada por um login
// mais novo da mesma conta) não pode apagar a sala da sessão nova.
if (criador_autenticado($mysqli)) {
    $id_criador = (int) $_SESSION['id_criador'];

    $row_sala = db_fetch_one(
        $mysqli,
        "SELECT fk_sala_criada FROM criador WHERE id_criador = ?",
        "i",
        $id_criador
    );

    $id_sala = $row_sala['fk_sala_criada'] ?? null;

    if ($id_sala) {
        apagar_sala($mysqli, (int) $id_sala);
    }

    db_exec(
        $mysqli,
        "UPDATE criador SET session_token = NULL, session_last_activity = NULL WHERE id_criador = ?",
        "i",
        $id_criador
    );
}

// se havia um participante ativo em uma sala, remove o registro dele do banco
if (isset($_SESSION['id_participante'])) {
    remover_participante($mysqli, (int) $_SESSION['id_participante']);
}

destruir_sessao();

header('Location: /index.php');
exit;
