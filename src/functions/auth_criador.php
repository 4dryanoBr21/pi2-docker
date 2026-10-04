<?php
// Autenticação/autorização do CRIADOR de sala. Centraliza o que antes estava
// copiado em criar.php, criador.php, avancar_fala.php, fechar_sala.php etc.

require_once __DIR__ . '/sessao.php';
require_once __DIR__ . '/util.php';

/**
 * A sessão atual pertence a um criador válido? (o token guardado na sessão
 * precisa ser o mesmo que está no banco — é isso que derruba a sessão antiga
 * quando a mesma conta faz login em outro lugar).
 */
function criador_autenticado(mysqli $mysqli): bool
{
    iniciar_sessao();

    if (!isset($_SESSION['id_criador'], $_SESSION['session_token'])
        || !is_string($_SESSION['session_token']) || $_SESSION['session_token'] === '') {
        return false;
    }

    $linha = db_fetch_one(
        $mysqli,
        "SELECT session_token FROM criador WHERE id_criador = ?",
        "i",
        (int) $_SESSION['id_criador']
    );

    return $linha !== null
        && !empty($linha['session_token'])
        && hash_equals((string) $linha['session_token'], $_SESSION['session_token']);
}

/**
 * O criador logado é o dono desta sala?
 */
function criador_eh_dono_da_sala(mysqli $mysqli, int $id_sala): bool
{
    if (!isset($_SESSION['id_criador'])) {
        return false;
    }

    return db_fetch_one(
        $mysqli,
        "SELECT 1 AS ok FROM criador WHERE id_criador = ? AND fk_sala_criada = ?",
        "ii",
        (int) $_SESSION['id_criador'],
        $id_sala
    ) !== null;
}

/**
 * Marca "sinal de vida" do criador (usado para detectar sala abandonada).
 */
function criador_registrar_atividade(mysqli $mysqli): void
{
    db_exec(
        $mysqli,
        "UPDATE criador SET session_last_activity = NOW() WHERE id_criador = ?",
        "i",
        (int) $_SESSION['id_criador']
    );
}

/**
 * Qual a relação de quem está chamando com a sala?
 * Retorna 'criador' (dono autenticado), 'participante' (está registrado
 * NESTA sala) ou null (sem acesso).
 */
function papel_na_sala(mysqli $mysqli, int $id_sala): ?string
{
    iniciar_sessao();

    if (criador_autenticado($mysqli) && criador_eh_dono_da_sala($mysqli, $id_sala)) {
        return 'criador';
    }

    if (isset($_SESSION['id_participante'])) {
        $achou = db_fetch_one(
            $mysqli,
            "SELECT 1 AS ok FROM participante WHERE id_participante = ? AND fk_sala_atual = ?",
            "ii",
            (int) $_SESSION['id_participante'],
            $id_sala
        );
        if ($achou !== null) {
            return 'participante';
        }
    }

    return null;
}
