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
 *
 * Nota (relatório de reunião): como o participante é apagado, ele some do
 * relatório junto com o tempo/pautas acumulados até aqui — e, se estava
 * falando no momento da remoção, os segundos da fala em andamento não são
 * contabilizados (só são somados em avancar_fala_sala(), que não roda aqui).
 * Isso é aceitável para o caso de uso (alguém que sai/cai da reunião), mas
 * fica registrado caso o relatório precise futuramente de um histórico que
 * sobreviva à saída do participante.
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
    // Otimização: Batch Delete.
    // Primeiro, liberamos a palavra de qualquer participante inativo que esteja falando.
    db_exec(
        $mysqli,
        "UPDATE sala SET fk_participante_falando = NULL, fala_inicio = NULL
         WHERE fk_participante_falando IN (
             SELECT id_participante FROM (
                 SELECT id_participante FROM participante
                 WHERE fk_sala_atual = ?
                   AND ultima_atividade IS NOT NULL
                   AND ultima_atividade < (NOW() - INTERVAL ? MINUTE)
             ) AS tmp
         )",
        "ii",
        $id_sala,
        $limite_minutos
    );

    // Depois, removemos todos os inativos em uma única query.
    db_exec(
        $mysqli,
        "DELETE FROM participante
         WHERE fk_sala_atual = ?
           AND ultima_atividade IS NOT NULL
           AND ultima_atividade < (NOW() - INTERVAL ? MINUTE)",
        "ii",
        $id_sala,
        $limite_minutos
    );
}

/**
 * Passa a palavra para o próximo da fila (ou deixa a sala sem orador, se a
 * fila estiver vazia).
 *
 * $esperado = id do orador que o CLIENTE acredita estar falando (0 = ninguém).
 * Se o orador real for outro, nada é feita. Isso evita "pular" participantes
 * quando chegam dois pedidos quase juntos (duplo clique, ou clique + avanço
 * automático do cronômetro). Use null para avançar sem essa conferência.
 *
 * A sala fica travada (SELECT ... FOR UPDATE) durante a operação, então dois
 * pedidos simultâneos nunca se atropelam.
 *
 * Também é aqui que o relatório de reunião (relatorio.php) é alimentado:
 * ao TIRAR a palavra de quem estava falando, o tempo decorrido desde
 * `fala_inicio` é somado em participante.tempo_falado_segundos; ao DAR a
 * palavra para o próximo, participante.pautas_total é incrementado (conta
 * quantas vezes aquela pessoa foi chamada a falar).
 *
 * Retorna ['avancou' => bool, 'proximo' => ?int].
 */
function avancar_fala_sala(mysqli $mysqli, int $id_sala, ?int $esperado = null): array
{
    $mysqli->begin_transaction();
    try {
        $sala = db_fetch_one(
            $mysqli,
            "SELECT fk_participante_falando, fala_inicio FROM sala WHERE id_sala = ? FOR UPDATE",
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

        // Quem estava falando sai agora: soma o tempo desta fala ao total
        // acumulado dele (direto no banco, com TIMESTAMPDIFF, pra não
        // depender do relógio do PHP nem de condição de corrida).
        if ($atual > 0 && !empty($sala['fala_inicio'])) {
            db_exec(
                $mysqli,
                "UPDATE participante
                    SET tempo_falado_segundos = tempo_falado_segundos
                        + GREATEST(0, TIMESTAMPDIFF(SECOND, ?, NOW()))
                  WHERE id_participante = ?",
                "si",
                $sala['fala_inicio'],
                $atual
            );
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
                "UPDATE participante
                    SET data_hora_solicitacao = NULL, pautas_total = pautas_total + 1
                  WHERE id_participante = ?",
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

// ---------------------------------------------------------------------------
// Limites da sala. 0 = sem limite.
//   max_participantes: quantas pessoas podem estar na sala ao mesmo tempo.
//   max_fila: quantas pessoas podem estar com a mão levantada (pautas) ao mesmo
//             tempo. Quem está falando agora não conta (já saiu da fila).
// Reduzir um limite não remove ninguém: ele só vale para novas entradas/mãos.
// ---------------------------------------------------------------------------

const LIMITE_MAXIMO_CONFIGURAVEL = 1000;

/**
 * Converte o valor digitado de um limite. Vazio = 0 (sem limite).
 * Devolve null se for inválido (texto, negativo, decimal ou acima do máximo).
 */
function normalizar_limite($valor): ?int
{
    if ($valor === null) {
        return 0;
    }
    if (!is_string($valor)) {
        return null;
    }
    $valor = trim($valor);
    if ($valor === '') {
        return 0;
    }
    if (!preg_match('/^[0-9]{1,4}$/', $valor)) {
        return null;
    }
    $n = (int) $valor;
    return $n <= LIMITE_MAXIMO_CONFIGURAVEL ? $n : null;
}

/**
 * Registra um participante na sala, respeitando max_participantes.
 * A linha da sala fica travada (FOR UPDATE) durante a contagem + inserção:
 * entradas simultâneas são tratadas uma de cada vez e o limite nunca é
 * ultrapassado.
 *
 * Retorna ['status' => 'ok', 'id' => int]
 *      ou ['status' => 'cheia', 'limite' => int]
 *      ou ['status' => 'inexistente'] (a sala foi encerrada no meio do caminho).
 */
function entrar_na_sala(mysqli $mysqli, int $id_sala, string $nome): array
{
    $mysqli->begin_transaction();
    try {
        $sala = db_fetch_one(
            $mysqli,
            "SELECT max_participantes FROM sala WHERE id_sala = ? FOR UPDATE",
            "i",
            $id_sala
        );

        if ($sala === null) {
            $mysqli->rollback();
            return ['status' => 'inexistente'];
        }

        $limite = (int) $sala['max_participantes'];
        if ($limite > 0) {
            $linha = db_fetch_one(
                $mysqli,
                "SELECT COUNT(*) AS n FROM participante WHERE fk_sala_atual = ?",
                "i",
                $id_sala
            );
            if ((int) $linha['n'] >= $limite) {
                $mysqli->rollback();
                return ['status' => 'cheia', 'limite' => $limite];
            }
        }

        db_exec(
            $mysqli,
            "INSERT INTO participante (nome_participante, fk_sala_atual, ultima_atividade) VALUES (?, ?, NOW())",
            "si",
            $nome,
            $id_sala
        );
        $id = (int) $mysqli->insert_id;

        $mysqli->commit();
        return ['status' => 'ok', 'id' => $id];
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}

/**
 * Levanta ou abaixa a mão do participante (quem está falando é tratado antes,
 * em salvar_hora.php). Ao LEVANTAR, respeita max_fila; abaixar é sempre
 * permitido. A hora vem do relógio do banco, com milissegundos, a mesma fonte
 * usada para ordenar a fila. A linha da sala fica travada (FOR UPDATE), então
 * duas mãos levantadas ao mesmo tempo não furam o limite.
 *
 * Retorna 'hora' (mão levantada), 'null' (mão abaixada), 'fila_cheia' ou
 * 'inexistente'.
 */
function alternar_mao(mysqli $mysqli, int $id_sala, int $id_participante): string
{
    $mysqli->begin_transaction();
    try {
        $sala = db_fetch_one(
            $mysqli,
            "SELECT max_fila FROM sala WHERE id_sala = ? FOR UPDATE",
            "i",
            $id_sala
        );
        $participante = db_fetch_one(
            $mysqli,
            "SELECT data_hora_solicitacao FROM participante WHERE id_participante = ? AND fk_sala_atual = ?",
            "ii",
            $id_participante,
            $id_sala
        );

        if ($sala === null || $participante === null) {
            $mysqli->rollback();
            return 'inexistente';
        }

        if ($participante['data_hora_solicitacao'] === null) {
            $limite = (int) $sala['max_fila'];
            if ($limite > 0) {
                $linha = db_fetch_one(
                    $mysqli,
                    "SELECT COUNT(*) AS n FROM participante
                      WHERE fk_sala_atual = ? AND data_hora_solicitacao IS NOT NULL",
                    "i",
                    $id_sala
                );
                if ((int) $linha['n'] >= $limite) {
                    $mysqli->rollback();
                    return 'fila_cheia';
                }
            }

            db_exec(
                $mysqli,
                "UPDATE participante SET data_hora_solicitacao = NOW(3), ultima_atividade = NOW()
                  WHERE id_participante = ?",
                "i",
                $id_participante
            );
            $resultado = 'hora';
        } else {
            db_exec(
                $mysqli,
                "UPDATE participante SET data_hora_solicitacao = NULL, ultima_atividade = NOW()
                  WHERE id_participante = ?",
                "i",
                $id_participante
            );
            $resultado = 'null';
        }

        $mysqli->commit();
        return $resultado;
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
}
