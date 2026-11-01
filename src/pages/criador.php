<?php
require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/csrf.php';
require_once __DIR__ . '/../functions/idioma.php';
require_once __DIR__ . '/../functions/util.php';
require_once __DIR__ . '/../functions/auth_criador.php';

if (!criador_autenticado($mysqli)) {
    header('Location: login.php');
    exit();
}

$id_sala = (int) ($_GET['id_sala'] ?? 0);

if ($id_sala <= 0) {
    die(t('erro_sala_nao_especificada', t('texto_voltar')));
}

if (!criador_eh_dono_da_sala($mysqli, $id_sala)) {
    die(t('erro_sem_permissao', t('texto_voltar')));
}

criador_registrar_atividade($mysqli);

// marca o início da reunião na primeira vez que o criador abre a sala
db_exec($mysqli, "UPDATE sala SET data_inicio = NOW() WHERE id_sala = ? AND data_inicio IS NULL", "i", $id_sala);

$row = db_fetch_one(
    $mysqli,
    "SELECT nome_sala, codigo_sala, tempo_de_fala FROM sala WHERE id_sala = ?",
    "i",
    $id_sala
);

if ($row === null) {
    die(t('erro_sala_nao_encontrada', t('texto_voltar')));
}

$nome_sala = htmlspecialchars($row['nome_sala'], ENT_QUOTES, 'UTF-8');
$codigo_sala = htmlspecialchars($row['codigo_sala'], ENT_QUOTES, 'UTF-8');
$tempo_fala = htmlspecialchars($row['tempo_de_fala'], ENT_QUOTES, 'UTF-8');

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
  <title>ME INSCREVO - <?php echo $nome_sala; ?></title>
</head>

<body>
  <?php idioma_switch_html(); ?>
  <script src="../assets/lang.js"></script>
  <div id="app-config"
       data-id-sala="<?php echo $id_sala; ?>"
       data-csrf="<?php echo $csrf; ?>"
       data-is-criador="true"
       data-textos="<?php echo htmlspecialchars(json_encode([
         "confirmarEncerrarSala" => t("confirm_encerrar_sala"),
         "confirmarSairConta" => t("confirm_sair_encerra_sala"),
         "erroFecharSala" => t("erro_fechar_sala"),
         "passarVez" => t("js_passar_vez"),
         "iniciarProximo" => t("btn_iniciar_proximo"),
         "nenhumParticipanteAinda" => t("texto_nenhum_participante_ainda")
       ]), ENT_QUOTES, 'UTF-8'); ?>"></div>
  <form id="formLogout" action="../functions/logout.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
  </form>
  <button type="button" id="btnSairConta" class="btn btn-sm btn-outline-dark btn-sair-conta"><?php echo t('btn_sair'); ?></button>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <div class="text-center">
          <img class="logo-black rounded" src="../img/MI_legenda.png" alt="<?php echo te('alt_logo'); ?>">
        </div>
        <div class="card">
          <button type="button" class="btn-close" id="btnEncerrarSala" aria-label="<?php echo te('aria_encerrar_sala'); ?>"></button>
          <div class="card-body">
            <h2 class="text-center fw-bold">
              <?php echo $nome_sala; ?>
              <span class="fs-6 text-muted d-block"><?php echo t('label_codigo_prefixo'); ?> <?php echo $codigo_sala; ?></span>
            </h2>
            <p class="text-center text-muted mb-3">
              <?php echo t('texto_tempo_reuniao'); ?> <span id="tempoReuniao">00:00</span> &middot;
              <?php echo t('texto_tempo_fala_participante'); ?> <?php echo $tempo_fala; ?>
            </p>

            <div id="painelFalando" class="text-center p-4 mb-3 rounded shadow-sm painel-falando" aria-live="polite">
              <div id="semFalante"><?php echo t('texto_nenhum_falando'); ?></div>
              <div id="comFalante" class="d-none">
                <h4 class="fw-bold mb-1" id="nomeFalante"></h4>
                <div class="display-4 fw-bold" id="contadorFala">00:00</div>
              </div>
            </div>

            <div class="d-grid gap-2 mb-3">
              <button id="btnProximo" class="btn btn-dark" type="button"><?php echo t('btn_iniciar_proximo'); ?></button>
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
