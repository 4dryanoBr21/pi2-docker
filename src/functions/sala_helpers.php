<?php
// Regras de negócio das salas (apagar, limpar abandonadas, passar a palavra).

require_once __DIR__ . '/util.php';

// Quanto tempo SEM sinal de vida até considerar alguém "sumido".
// Abas em segundo plano e celulares com tela bloqueada fazem o navegador
// atrasar os timers, então os limites são folgados de propósito.
const LIMITE_CRIADOR_INATIVO_MIN      = 3;  // criador some -> sala é apagada
const LIMITE_PARTICIPANTE_INATIVO_MIN = 3;  // participante some -> é removido da sala

/**
 * Apaga a sala e todos os participantes dela, desvinculando o criador.
 * Mesmo procedimento usado em "Encerrar sala" e no logout do criador.
 * Tudo numa transação: ou apaga tudo, ou não apaga nada.
 */
function apagar_sala(mysqli $mysqli, int $id_sala): void
{
    $mysqli->begin_transaction();
    try {
        db_exec($mysqli, "UPDATE sala SET fk_participante_falando = NULL WHERE id_sala = ?", "i", $id_sala);
        db_exec($mysqli, "UPDATE criador SET fk_sala_criada = NULL WHERE fk_sala_criada = ?", "i", $id_sala);
        db_exec($mysqli, "DELETE FROM participante WHERE fk_sala_atual = ?", "i", $id_sala);
        db_exec($mysqli, "DELETE FROM sala WHERE id_sala = ?", "i", $id_sala);
        $mysqli->commit();
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}

/**
 * Remove um participante. Se ele era quem estava com a palavra, libera a vez
 * antes (a FK de sala.fk_participante_falando impediria o DELETE).
 */
function remover_participante(mysqli $mysqli, int $id_participante): void
{
    $mysqli->begin_transaction();
    try {
        db_exec(
            $mysqli,
            "UPDATE sala SET fk_participante_falando = NULL, fala_inicio = NULL WHERE fk_participante_falando = ?",
            "i",
            $id_participante
        );
        db_exec($mysqli, "DELETE FROM participante WHERE id_participante = ?", "i", $id_participante);
        $mysqli->commit();
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}

/**
 * Verifica se o criador dono da sala ainda está "vivo" (atividade recente
 * registrada em criador.session_last_activity, renovada pelo polling da
 * página do criador). Se não estiver — aba fechada, PC travou, internet
 * caiu, etc. — apaga a sala e retorna false.
 *
 * Retorna true se a sala continua ativa. Retorna false se foi apagada
 * agora (ou já não existia por outro motivo).
 */
function limpar_sala_se_abandonada(mysqli $mysqli, int $id_sala, int $limite_minutos = LIMITE_CRIADOR_INATIVO_MIN): bool
{
    $dono = db_fetch_one(
        $mysqli,
        "SELECT session_last_activity FROM criador WHERE fk_sala_criada = ?",
        "i",
        $id_sala
    );

    // sala sem criador vinculado (não deveria acontecer no fluxo normal) ou
    // criador que nunca registrou atividade — trata como abandonada
    if (!$dono || empty($dono['session_last_activity'])) {
        apagar_sala($mysqli, $id_sala);
        return false;
    }

    $ultima = new DateTime($dono['session_last_activity']);
    $agora = new DateTime();
    $minutos_parado = ($agora->getTimestamp() - $ultima->getTimestamp()) / 60;

    if ($minutos_parado >= $limite_minutos) {
        apagar_sala($mysqli, $id_sala);
        return false;
    }

    return true;
}

/**
 * Remove da sala qualquer participante sem sinal de vida recente (mesmo
 * princípio da limpeza de sala abandonada, só que por participante).
 * Participantes com `ultima_atividade` NULL (acabaram de entrar, ainda
 * não tiveram tempo de mandar o primeiro sinal de vida) nunca são
 * removidos por esse motivo.
 */
function limpar_participantes_inativos(mysqli $mysqli, int $id_sala, int $limite_minutos = LIMITE_PARTICIPANTE_INATIVO_MIN): void
{
    $inativos = db_fetch_all(
        $mysqli,
        "SELECT id_participante
           FROM participante
          WHERE fk_sala_atual = ?
            AND ultima_atividade IS NOT NULL
            AND ultima_atividade < (NOW() - INTERVAL ? MINUTE)",
        "ii",
        $id_sala,
        $limite_minutos
    );

    foreach ($inativos as $linha) {
        remover_participante($mysqli, (int) $linha['id_participante']);
    }
}

/**
 * Passa a palavra para o próximo da fila (ou deixa a sala sem orador, se a
 * fila estiver vazia).
 *
 * $esperado = id do orador que o CLIENTE acredita estar falando (0 = ninguém).
 * Se o orador real for outro, nada é feito. Isso evita "pular" participantes
 * quando chegam dois pedidos quase juntos (duplo clique, ou clique + avanço
 * automático do cronômetro). Use null para avançar sem essa conferência.
 *
 * A sala fica travada (SELECT ... FOR UPDATE) durante a operação, então dois
 * pedidos simultâneos nunca se atropelam.
 *
 * Retorna ['avancou' => bool, 'proximo' => ?int].
 */
function avancar_fala_sala(mysqli $mysqli, int $id_sala, ?int $esperado = null): array
{
    $mysqli->begin_transaction();
    try {
        $sala = db_fetch_one(
            $mysqli,
            "SELECT fk_participante_falando FROM sala WHERE id_sala = ? FOR UPDATE",
            "i",
            $id_sala
        );

        if ($sala === null) {
            $mysqli->rollback();
            return ['avancou' => false, 'proximo' => null];
        }

        $atual = (int) ($sala['fk_participante_falando'] ?? 0);

        if ($esperado !== null && $esperado !== $atual) {
            $mysqli->rollback();
            return ['avancou' => false, 'proximo' => $atual > 0 ? $atual : null];
        }

        db_exec(
            $mysqli,
            "UPDATE sala SET fk_participante_falando = NULL, fala_inicio = NULL WHERE id_sala = ?",
            "i",
            $id_sala
        );

        $proximo = db_fetch_one(
            $mysqli,
            "SELECT id_participante
               FROM participante
              WHERE fk_sala_atual = ? AND data_hora_solicitacao IS NOT NULL
              ORDER BY data_hora_solicitacao ASC, id_participante ASC
              LIMIT 1",
            "i",
            $id_sala
        );

        $id_proximo = null;
        if ($proximo !== null) {
            $id_proximo = (int) $proximo['id_participante'];
            db_exec(
                $mysqli,
                "UPDATE sala SET fk_participante_falando = ?, fala_inicio = NOW() WHERE id_sala = ?",
                "ii",
                $id_proximo,
                $id_sala
            );
            db_exec(
                $mysqli,
                "UPDATE participante SET data_hora_solicitacao = NULL WHERE id_participante = ?",
                "i",
                $id_proximo
            );
        }

        $mysqli->commit();
        return ['avancou' => true, 'proximo' => $id_proximo];
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}
