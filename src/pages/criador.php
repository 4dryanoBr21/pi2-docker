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
  <form id="formLogout" action="../functions/logout.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
  </form>
  <button type="button" id="btnSairConta" class="btn btn-sm btn-outline-dark"
    style="position:absolute; top:16px; right:16px;"><?php echo t('btn_sair'); ?></button>
  <div class="container">
    <div class="row">
      <div class="col-md-3"></div>
      <div class="col-md-6">
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

            <div id="painelFalando" class="text-center p-4 mb-3 rounded shadow-sm" style="background:#f5f5f5;" aria-live="polite">
              <div id="semFalante"><?php echo t('texto_nenhum_falando'); ?></div>
              <div id="comFalante" style="display:none;">
                <h4 class="fw-bold mb-1" id="nomeFalante"></h4>
                <div style="font-size: 48px;" id="contadorFala">00:00</div>
              </div>
            </div>

            <div class="d-grid gap-2 mb-3">
              <button id="btnProximo" class="btn btn-dark" type="button"><?php echo t('btn_iniciar_proximo'); ?></button>
            </div>

            <h6 class="fw-bold"><?php echo t('titulo_participantes_presentes'); ?></h6>
            <div id="listaPresentes" class="d-grid gap-2 overflow-auto shadow p-3 mb-2 bg-body-tertiary rounded"
              style="height: 160px;" aria-live="polite">
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3"></div>
    </div>
  </div>

  <script src="../assets/app.js"></script>
  <script>
    const idSala = <?php echo (int) $id_sala; ?>;
    const csrfToken = "<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>";

    const textos = {
      confirmarEncerrarSala: <?php echo tj('confirm_encerrar_sala'); ?>,
      confirmarSairConta: <?php echo tj('confirm_sair_encerra_sala'); ?>,
      erroFecharSala: <?php echo tj('erro_fechar_sala'); ?>,
      passarVez: <?php echo tj('js_passar_vez'); ?>,
      iniciarProximo: <?php echo tj('btn_iniciar_proximo'); ?>,
      nenhumParticipanteAinda: <?php echo tj('texto_nenhum_participante_ainda'); ?>
    };

    // O SERVIDOR é a única fonte da verdade sobre o tempo. Aqui guardamos
    // apenas "em que instante do relógio deste navegador" cada contagem
    // começou/termina e recalculamos a tela a partir disso. Assim, se o
    // navegador atrasar os timers (aba em segundo plano), o valor mostrado
    // continua certo assim que a aba volta — antes o cronômetro "andava
    // devagar" porque descontava 1 segundo por tick, qualquer que fosse o
    // tempo real decorrido.
    const TOLERANCIA_MS = 1500;        // diferença aceitável antes de ressincronizar
    let inicioReuniaoMs = null;        // instante (ms) em que a reunião começou
    let fimFalaMs = null;              // instante (ms) em que a fala atual termina
    let speakerAtualId = null;
    let avancando = false;
    let ultimoAvancoMs = 0;

    // "Sair": apaga a sala junto, então pede confirmação (e agora é POST + CSRF)
    document.getElementById("btnSairConta").addEventListener("click", function () {
      if (confirm(textos.confirmarSairConta)) {
        document.getElementById("formLogout").submit();
      }
    });

    document.getElementById("btnEncerrarSala").addEventListener("click", function () {
      if (!confirm(textos.confirmarEncerrarSala)) {
        return;
      }

      const form = new FormData();
      form.append("id_sala", idSala);
      form.append("csrf_token", csrfToken);

      fetch("../functions/fechar_sala.php", { method: "POST", body: form })
        .then(res => res.text())
        .then(ret => {
          if (ret.trim() === "ok") {
            window.location.href = "criar.php";
          } else {
            alert(textos.erroFecharSala);
          }
        })
        .catch(() => alert(textos.erroFecharSala));
    });

    function avancarFala() {
      const agora = Date.now();
      // evita duplo clique e martelar o servidor se algo falhar
      if (avancando || agora - ultimoAvancoMs < 1500) return;
      avancando = true;
      ultimoAvancoMs = agora;

      const form = new FormData();
      form.append("id_sala", idSala);
      form.append("csrf_token", csrfToken);
      // diz ao servidor QUEM acreditamos estar falando: se já mudou (outro
      // clique, o próprio participante encerrou...), ele não avança de novo
      // e ninguém é pulado
      form.append("falando_atual", speakerAtualId === null ? 0 : speakerAtualId);

      fetch("../functions/avancar_fala.php", { method: "POST", body: form })
        .then(res => res.json())
        .then(() => {
          avancando = false;
          atualizarEstado();
        })
        .catch(() => { avancando = false; });
    }

    document.getElementById("btnProximo").addEventListener("click", avancarFala);

    function desenharRelogios() {
      if (inicioReuniaoMs !== null) {
        const decorrido = Math.floor((Date.now() - inicioReuniaoMs) / 1000);
        document.getElementById("tempoReuniao").textContent = MI.formatarTempo(decorrido);
      }

      if (fimFalaMs !== null) {
        const restante = Math.max(0, Math.ceil((fimFalaMs - Date.now()) / 1000));
        document.getElementById("contadorFala").textContent = MI.formatarTempo(restante);
        if (restante === 0) {
          avancarFala();
        }
      }
    }

    function atualizarEstado() {
      fetch("../functions/estado_sala.php?id_sala=" + idSala, { cache: "no-store" })
        .then(res => res.json())
        .then(estado => {
          if (estado.erro) {
            window.location.href = "criar.php";
            return;
          }

          const agora = Date.now();

          const novoInicio = agora - estado.reuniao_segundos * 1000;
          if (inicioReuniaoMs === null || Math.abs(novoInicio - inicioReuniaoMs) > TOLERANCIA_MS) {
            inicioReuniaoMs = novoInicio;
          }

          if (estado.falando) {
            document.getElementById("semFalante").style.display = "none";
            document.getElementById("comFalante").style.display = "block";
            document.getElementById("nomeFalante").textContent = estado.falando.nome;

            // Ressincroniza quando o orador muda OU quando o relógio local
            // se afastou do servidor além da tolerância. Pequenas variações
            // (latência da rede) não mexem no cronômetro na tela.
            const novoFim = agora + estado.falando.restante_segundos * 1000;
            if (speakerAtualId !== estado.falando.id_participante || fimFalaMs === null
              || Math.abs(novoFim - fimFalaMs) > TOLERANCIA_MS) {
              speakerAtualId = estado.falando.id_participante;
              fimFalaMs = novoFim;
            }
            document.getElementById("btnProximo").textContent = textos.passarVez;
          } else {
            document.getElementById("semFalante").style.display = "block";
            document.getElementById("comFalante").style.display = "none";
            speakerAtualId = null;
            fimFalaMs = null;
            document.getElementById("btnProximo").textContent = textos.iniciarProximo;
          }

          MI.renderizarPresentes(document.getElementById("listaPresentes"), estado, {
            textoVazio: textos.nenhumParticipanteAinda
          });

          desenharRelogios();
        })
        .catch(err => console.error("Erro ao buscar estado da sala:", err));
    }

    // redesenha 4x por segundo a partir dos instantes guardados acima
    setInterval(desenharRelogios, 250);

    function verificarSessao() {
      fetch("../functions/verifica_sessao.php", { cache: "no-store" })
        .then(res => res.json())
        .then(estado => {
          if (!estado.valido) {
            window.location.href = "login.php";
          }
        })
        .catch(err => console.error("Erro ao verificar sessão:", err));
    }

    setInterval(verificarSessao, 5000);

    setInterval(atualizarEstado, 3000);
    atualizarEstado();
  </script>
</body>

</html>
