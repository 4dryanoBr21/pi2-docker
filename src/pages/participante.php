<?php
require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/csrf.php';
require_once __DIR__ . '/../functions/idioma.php';
require_once __DIR__ . '/../functions/util.php';
require_once __DIR__ . '/../functions/sala_helpers.php';

// Sem os 3 dados da entrada na sala (código, nome e id), volta ao início.
// (Antes só código e nome eram conferidos, e um id ausente gerava um erro
// de JavaScript na página.)
if (!isset($_SESSION['codigo'], $_SESSION['nome'], $_SESSION['id_participante'])) {
    header("Location: ../index.php");
    exit;
}

$id_participante = (int) $_SESSION['id_participante'];

$sala = db_fetch_one(
    $mysqli,
    "SELECT id_sala, nome_sala FROM sala WHERE codigo_sala = ?",
    "s",
    (string) $_SESSION['codigo']
);

// A sala ainda existe E este participante ainda está registrado NELA?
// (a sala pode ter sido encerrada, ou o participante removido por inatividade)
$participante_valido = $sala !== null
    && db_fetch_one(
        $mysqli,
        "SELECT 1 AS ok FROM participante WHERE id_participante = ? AND fk_sala_atual = ?",
        "ii",
        $id_participante,
        (int) $sala['id_sala']
    ) !== null;

if (!$participante_valido) {
    limpar_sessao_participante();
    header("Location: ../index.php");
    exit;
}

$id_sala = (int) $sala['id_sala'];
$nome_sala = $sala['nome_sala'];

$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="<?php echo $idioma_atual === 'es' ? 'es' : 'pt-BR'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <link rel="stylesheet" href="../style.css">
    <link rel="shortcut icon" href="../img/MI_legenda_branco.png" type="image/x-icon">
    <title>ME INSCREVO - <?php echo htmlspecialchars($nome_sala); ?></title>
</head>

<body>
    <?php idioma_switch_html(); ?>
    <script src="../assets/lang.js"></script>
    <div id="app-config"
         data-id-sala="<?php echo $id_sala; ?>"
         data-id-participante="<?php echo $id_participante; ?>"
         data-csrf="<?php echo $csrf; ?>"
         data-is-criador="false"
         data-textos="<?php echo htmlspecialchars(json_encode([
           "levanteAMao" => t("texto_levante_mao"),
           "posicaoFila" => t("texto_posicao_fila"),
           "abaixarMao" => t("aria_abaixar_mao"),
           "levantarMao" => t("aria_levantar_mao"),
           "encerrarFala" => t("aria_encerrar_fala"),
           "nenhumParticipanteAinda" => t("texto_nenhum_participante_ainda"),
           "voceSufixo" => t("texto_voce_sufixo"),
           "erroSairSala" => t("erro_sair_sala"),
           "filaCheia" => t("texto_fila_cheia"),
           "infoParticipantesLimite" => t("texto_participantes_limite"),
           "infoParticipantesSemLimite" => t("texto_participantes_sem_limite"),
           "infoFilaLimite" => t("texto_fila_limite"),
           "infoFilaSemLimite" => t("texto_fila_sem_limite")
         ]), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                <div class="text-center">
                    <img class="logo-black rounded" src="../img/MI_legenda.png" alt="<?php echo te('alt_logo'); ?>">
                </div>
                <div class="card">
                    <button type="button" class="btn-close" id="btnSair" aria-label="<?php echo te('aria_sair_sala'); ?>"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo htmlspecialchars($nome_sala); ?></h2>

                        <div id="painelEstado" class="text-center p-4 mb-3 rounded shadow-sm painel-falando"
                            aria-live="polite">
                            <div id="estadoAguardando">
                                <p class="mb-1"><?php echo t('texto_aguardando'); ?></p>
                                <p id="posicaoFila" class="text-muted"><?php echo t('texto_levante_mao'); ?></p>
                            </div>
                            <div id="estadoFalando" class="d-none">
                                <h4 class="fw-bold mb-1"><?php echo t('texto_sua_vez'); ?></h4>
                                <div class="display-4 fw-bold" id="contadorFala">00:00</div>
                            </div>
                        </div>

                        <p id="infoLimites" class="text-center text-muted small mb-3" aria-live="polite"></p>
                        <div id="avisoSala" class="alert alert-warning py-2 d-none" role="alert"></div>

                        <div class="d-grid gap-2 mb-3">
                            <button id="mao" class="btn btn-mao-estilo" type="button" aria-label="<?php echo te('aria_levantar_mao'); ?>">🤚</button>
                        </div>

                        <h6 class="fw-bold"><?php echo t('titulo_participantes_presentes'); ?></h6>
                        <div id="listaPresentes" class="d-grid gap-2 overflow-auto shadow p-3 mb-2 bg-body-tertiary rounded lista-presentes"
                            aria-live="polite">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/app.js"></script>
    <script src="../assets/sala.js"></script>
    <script src="../assets/sala-init.js"></script>
</body>

</html>
