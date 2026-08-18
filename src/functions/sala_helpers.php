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
