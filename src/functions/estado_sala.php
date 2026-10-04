<?php
// Estado atual da sala (quem fala, fila, presentes, tempo de reunião).
// Consultado por polling tanto pela tela do criador quanto pela do participante.
//
// ACESSO RESTRITO: só responde para o dono da sala (criador logado) ou para
// um participante que está registrado NESTA sala. Antes, qualquer pessoa sem
// login conseguia listar os nomes de qualquer sala variando ?id_sala=.
//
// Este endpoint também faz o papel do antigo verifica_sala.php: se a sala foi
// encerrada (ou o participante removido), responde 404 e o cliente é
// redirecionado.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/auth_criador.php';
require_once __DIR__ . '/sala_helpers.php';

iniciar_sessao();

$id_sala = (int) ($_GET['id_sala'] ?? 0);
if ($id_sala <= 0) {
    responder_json(400, ['erro' => 'id_sala não informado']);
}

$papel = papel_na_sala($mysqli, $id_sala);
if ($papel === null) {
    // mesma resposta para "sala não existe" e "sem permissão": não revela
    // quais salas existem
    responder_json(404, ['erro' => 'sala não encontrada']);
}

// Quem consulta está vivo: renova o "sinal de vida".
// (checamos a existência acima com SELECT, não com affected_rows do UPDATE:
// o affected_rows só conta linhas que realmente mudaram de valor — duas
// renovações no mesmo segundo dariam 0 e expulsariam o participante por
// engano.)
if ($papel === 'criador') {
    criador_registrar_atividade($mysqli);
} else {
    db_exec(
        $mysqli,
        "UPDATE participante SET ultima_atividade = NOW() WHERE id_participante = ?",
        "i",
        (int) $_SESSION['id_participante']
    );
}

// Já lemos tudo que precisávamos da sessão: libera o lock dela para que as
// outras requisições da mesma aba (polling paralelo) não fiquem enfileiradas.
session_write_close();

if (!limpar_sala_se_abandonada($mysqli, $id_sala)) {
    responder_json(404, ['erro' => 'sala não encontrada']);
}

// remove participantes sem sinal de vida recente (aba fechada, PC travou, etc.)
limpar_participantes_inativos($mysqli, $id_sala);

// TIMESTAMPDIFF roda dentro do próprio MySQL: fala_inicio, data_inicio e
// "agora" vêm da mesma fonte de tempo, então não há risco de descompasso de
// fuso/relógio entre o container do PHP, o do banco e o navegador.
$sala = db_fetch_one(
    $mysqli,
    "SELECT fk_participante_falando, tempo_de_fala, data_inicio,
            TIMESTAMPDIFF(SECOND, fala_inicio, NOW())  AS decorrido_fala,
            TIMESTAMPDIFF(SECOND, data_inicio, NOW())  AS decorrido_reuniao
       FROM sala
      WHERE id_sala = ?",
    "i",
    $id_sala
);

if ($sala === null) {
    responder_json(404, ['erro' => 'sala não encontrada']);
}

list($h, $m, $s) = explode(':', $sala['tempo_de_fala'] ?? '00:00:00');
$duracao_segundos = ((int) $h * 3600) + ((int) $m * 60) + (int) $s;

$falando = null;

if ($sala['fk_participante_falando']) {
    $p = db_fetch_one(
        $mysqli,
        "SELECT id_participante, nome_participante FROM participante WHERE id_participante = ?",
        "i",
        (int) $sala['fk_participante_falando']
    );

    if ($p !== null) {
        $decorrido = max(0, (int) $sala['decorrido_fala']);
        $restante = max(0, $duracao_segundos - $decorrido);

        $falando = [
            'id_participante' => (int) $p['id_participante'],
            'nome' => $p['nome_participante'],
            'restante_segundos' => $restante,
        ];
    }
}

$falando_id = $falando['id_participante'] ?? 0;

$fila = [];
foreach (db_fetch_all(
    $mysqli,
    "SELECT id_participante, nome_participante
       FROM participante
      WHERE fk_sala_atual = ?
        AND data_hora_solicitacao IS NOT NULL
        AND id_participante != ?
      ORDER BY data_hora_solicitacao ASC, id_participante ASC",
    "ii",
    $id_sala,
    $falando_id
) as $row) {
    $fila[] = ['id_participante' => (int) $row['id_participante'], 'nome' => $row['nome_participante']];
}

// lista de TODOS os participantes presentes na sala (independente de fila/fala)
$presentes = [];
foreach (db_fetch_all(
    $mysqli,
    "SELECT id_participante, nome_participante
       FROM participante
      WHERE fk_sala_atual = ?
      ORDER BY id_participante ASC",
    "i",
    $id_sala
) as $row) {
    $presentes[] = ['id_participante' => (int) $row['id_participante'], 'nome' => $row['nome_participante']];
}

responder_json(200, [
    'duracao_segundos' => $duracao_segundos,
    'data_inicio' => $sala['data_inicio'],
    'reuniao_segundos' => max(0, (int) ($sala['decorrido_reuniao'] ?? 0)),
    'falando' => $falando,
    'fila' => $fila,
    'presentes' => $presentes,
]);
