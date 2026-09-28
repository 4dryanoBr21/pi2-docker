<?php
include('../functions/conexao.php');
require('../functions/csrf.php');
require('../functions/idioma.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$autenticado = false;

if (isset($_SESSION['id_criador']) && isset($_SESSION['session_token'])) {
    $stmt_auth = $mysqli->prepare("SELECT session_token FROM criador WHERE id_criador = ?");
    if ($stmt_auth) {
        $stmt_auth->bind_param("i", $_SESSION['id_criador']);
        $stmt_auth->execute();
        $res_auth = $stmt_auth->get_result()->fetch_assoc();
        $stmt_auth->close();

        if ($res_auth && $res_auth['session_token'] === $_SESSION['session_token']) {
            $autenticado = true;
        }
    }
}

if (!$autenticado) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id_sala'])) {
  die(t('erro_sala_nao_especificada', t('texto_voltar')));
}

$id_sala = intval($_GET['id_sala']);

$stmt_dono = $mysqli->prepare("SELECT 1 FROM criador WHERE id_criador = ? AND fk_sala_criada = ?");
$stmt_dono->bind_param("ii", $_SESSION['id_criador'], $id_sala);
$stmt_dono->execute();
$eh_dono = $stmt_dono->get_result()->num_rows > 0;
$stmt_dono->close();

if (!$eh_dono) {
    die(t('erro_sem_permissao', t('texto_voltar')));
}

$stmt_touch = $mysqli->prepare("UPDATE criador SET session_last_activity = NOW() WHERE id_criador = ?");
$stmt_touch->bind_param("i", $_SESSION['id_criador']);
$stmt_touch->execute();
$stmt_touch->close();

$stmt_inicio = $mysqli->prepare("UPDATE sala SET data_inicio = NOW() WHERE id_sala = ? AND data_inicio IS NULL");
$stmt_inicio->bind_param("i", $id_sala);
$stmt_inicio->execute();
$stmt_inicio->close();

$stmt_sala = $mysqli->prepare("SELECT * FROM sala WHERE id_sala = ?");
$stmt_sala->bind_param("i", $id_sala);
$stmt_sala->execute();
$row = $stmt_sala->get_result()->fetch_assoc();
$stmt_sala->close();

if ($row) {
  $nome_sala = htmlspecialchars($row['nome_sala']);
  $codigo_sala = htmlspecialchars($row['codigo_sala']);
  $tempo_fala = htmlspecialchars($row['tempo_de_fala']);
  $data_inicio_js = htmlspecialchars($row['data_inicio']);
} else {
  die(t('erro_sala_nao_encontrada', t('texto_voltar')));
}

$csrf = csrf_token();
?>

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
  <a href="../functions/logout.php" class="btn btn-sm btn-outline-dark" style="position:absolute; top:16px; right:16px;"><?php echo t('btn_sair'); ?></a>
  <div class="container">
    <div class="row">
      <div class="col-md-3"></div>
      <div class="col-md-6">
        <div class="text-center">
          <img class="logo-black rounded" src="../img/MI_legenda.png" alt="<?php echo te('alt_logo'); ?>">
        </div>
        <div class="card">
          <button type="button" class="btn-close" aria-label="<?php echo te('aria_encerrar_sala'); ?>"></button>
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

  <script>
    const idSala = <?php echo $id_sala; ?>;
    const csrfToken = "<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>";
    const dataInicio = new Date("<?php echo $data_inicio_js; ?>Z".replace(" ", "T"));

    const textos = {
      confirmarEncerrarSala: <?php echo tj('confirm_encerrar_sala'); ?>,
      erroFecharSala: <?php echo tj('erro_fechar_sala'); ?>,
      passarVez: <?php echo tj('js_passar_vez'); ?>,
      iniciarProximo: <?php echo tj('btn_iniciar_proximo'); ?>,
      nenhumParticipanteAinda: <?php echo tj('texto_nenhum_participante_ainda'); ?>
    };

    document.querySelector(".btn-close").addEventListener("click", function () {
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
        });
    });

    function formatarMMSS(totalSegundos) {
      const capado = Math.min(totalSegundos, 59 * 60 + 59); // no máximo 59:59
      const m = Math.floor(capado / 60).toString().padStart(2, "0");
      const s = Math.floor(capado % 60).toString().padStart(2, "0");
      return `${m}:${s}`;
    }

    let restanteLocal = null;
    let avancandoAutomaticamente = false;
    let speakerAtualId = null;

    function avancarFala() {
      if (avancandoAutomaticamente) return;
      avancandoAutomaticamente = true;

      const form = new FormData();
      form.append("id_sala", idSala);
      form.append("csrf_token", csrfToken);

      fetch("../functions/avancar_fala.php", { method: "POST", body: form })
        .then(res => res.json())
        .then(() => {
          avancandoAutomaticamente = false;
          atualizarEstado();
        })
        .catch(() => { avancandoAutomaticamente = false; });
    }

    document.getElementById("btnProximo").addEventListener("click", avancarFala);

    function atualizarEstado() {
      fetch("../functions/estado_sala.php?id_sala=" + idSala)
        .then(res => res.json())
        .then(estado => {
          if (estado.erro) {
            window.location.href = "criar.php";
            return;
          }

          if (estado.falando) {
            document.getElementById("semFalante").style.display = "none";
            document.getElementById("comFalante").style.display = "block";
            document.getElementById("nomeFalante").textContent = estado.falando.nome;

            // só resincroniza do servidor quando o orador muda (ou na primeira
            // vez) — evita que um poll periódico "reinicie" visualmente o
            // cronômetro de quem já está contando corretamente no cliente
            if (speakerAtualId !== estado.falando.id_participante) {
              speakerAtualId = estado.falando.id_participante;
              restanteLocal = estado.falando.restante_segundos;
            }
            document.getElementById("contadorFala").textContent = formatarMMSS(restanteLocal);
            document.getElementById("btnProximo").textContent = textos.passarVez;
          } else {
            document.getElementById("semFalante").style.display = "block";
            document.getElementById("comFalante").style.display = "none";
            speakerAtualId = null;
            restanteLocal = null;
            document.getElementById("btnProximo").textContent = textos.iniciarProximo;
          }

          const listaPresentes = document.getElementById("listaPresentes");
          listaPresentes.innerHTML = "";
          if (estado.presentes.length === 0) {
            const vazio = document.createElement("p");
            vazio.textContent = textos.nenhumParticipanteAinda;
            listaPresentes.appendChild(vazio);
          } else {
            const idsNaFila = new Set(estado.fila.map(p => p.id_participante));
            estado.presentes.forEach(p => {
              const item = document.createElement("p");
              let marcador = "";
              if (estado.falando && estado.falando.id_participante === p.id_participante) {
                marcador = " 🎙️";
              } else if (idsNaFila.has(p.id_participante)) {
                marcador = " 🤚";
              }
              item.textContent = p.nome + marcador;
              listaPresentes.appendChild(item);
            });
          }
        })
        .catch(err => console.error("Erro ao buscar estado da sala:", err));
    }

    setInterval(() => {
      const decorrido = Math.floor((new Date() - dataInicio) / 1000);
      document.getElementById("tempoReuniao").textContent = formatarMMSS(Math.max(0, decorrido));

      if (restanteLocal !== null) {
        restanteLocal = Math.max(0, restanteLocal - 1);
        document.getElementById("contadorFala").textContent = formatarMMSS(restanteLocal);
        if (restanteLocal === 0) {
          avancarFala();
        }
      }
    }, 1000);

    function verificarSessao() {
      fetch("../functions/verifica_sessao.php")
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
