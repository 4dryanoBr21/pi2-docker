<?php

/**
 * Apaga a sala e todos os participantes dela, desvinculando o criador.
 * Mesmo procedimento usado em "Encerrar sala" e no logout do criador.
 */
function apagar_sala(mysqli $mysqli, int $id_sala): void
{
    $stmt0 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = NULL WHERE id_sala = ?");
    $stmt0->bind_param("i", $id_sala);
    $stmt0->execute();
    $stmt0->close();

    $stmt1 = $mysqli->prepare("UPDATE criador SET fk_sala_criada = NULL WHERE fk_sala_criada = ?");
    $stmt1->bind_param("i", $id_sala);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $mysqli->prepare("DELETE FROM participante WHERE fk_sala_atual = ?");
    $stmt2->bind_param("i", $id_sala);
    $stmt2->execute();
    $stmt2->close();

    $stmt3 = $mysqli->prepare("DELETE FROM sala WHERE id_sala = ?");
    $stmt3->bind_param("i", $id_sala);
    $stmt3->execute();
    $stmt3->close();
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
function limpar_sala_se_abandonada(mysqli $mysqli, int $id_sala, int $limite_minutos = 1): bool
{
    $stmt = $mysqli->prepare("SELECT session_last_activity FROM criador WHERE fk_sala_criada = ?");
    $stmt->bind_param("i", $id_sala);
    $stmt->execute();
    $dono = $stmt->get_result()->fetch_assoc();
    $stmt->close();

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
function limpar_participantes_inativos(mysqli $mysqli, int $id_sala, int $limite_minutos = 2): void
{
    $stmt = $mysqli->prepare("
        SELECT id_participante
        FROM participante
        WHERE fk_sala_atual = ?
          AND ultima_atividade IS NOT NULL
          AND ultima_atividade < (NOW() - INTERVAL ? MINUTE)
    ");
    $stmt->bind_param("ii", $id_sala, $limite_minutos);
    $stmt->execute();
    $result = $stmt->get_result();

    $inativos = [];
    while ($row = $result->fetch_assoc()) {
        $inativos[] = (int) $row['id_participante'];
    }
    $stmt->close();

    foreach ($inativos as $id_participante) {
        // se era quem estava com a palavra, libera a vez antes de remover
        $stmt0 = $mysqli->prepare("UPDATE sala SET fk_participante_falando = NULL, fala_inicio = NULL WHERE fk_participante_falando = ?");
        $stmt0->bind_param("i", $id_participante);
        $stmt0->execute();
        $stmt0->close();

        $stmt1 = $mysqli->prepare("DELETE FROM participante WHERE id_participante = ?");
        $stmt1->bind_param("i", $id_participante);
        $stmt1->execute();
        $stmt1->close();
    }
}
