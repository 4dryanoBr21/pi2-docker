<?php
include('../functions/conexao.php');
require('../functions/csrf.php');

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
  die("Sala não especificada. <a href='criar.php'>Voltar</a>");
}

$id_sala = intval($_GET['id_sala']);

$stmt_dono = $mysqli->prepare("SELECT 1 FROM criador WHERE id_criador = ? AND fk_sala_criada = ?");
$stmt_dono->bind_param("ii", $_SESSION['id_criador'], $id_sala);
$stmt_dono->execute();
$eh_dono = $stmt_dono->get_result()->num_rows > 0;
$stmt_dono->close();

if (!$eh_dono) {
    die("Você não tem permissão para acessar esta sala. <a href='criar.php'>Voltar</a>");
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
  $tempo_fala = htmlspecialchars($row['tempo_de_fala']);
  $data_inicio_js = htmlspecialchars($row['data_inicio']);
} else {
  die("Sala não encontrada. <a href='criar.php'>Voltar</a>");
}

$csrf = csrf_token();
?>

<html lang="pt-BR">

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
  <title>ME INSCREVO - Sala <?php echo $nome_sala; ?></title>
</head>

<body>
  <a href="../functions/logout.php" class="btn btn-sm btn-outline-dark" style="position:absolute; top:16px; right:16px;">Sair</a>
  <div class="container">
    <div class="row">
      <div class="col-md-3"></div>
      <div class="col-md-6">
        <div class="text-center">
          <img class="logo-black rounded" src="../img/MI_legenda.png" alt="Logo do ME INSCREVO">
        </div>
        <div class="card">
          <button type="button" class="btn-close" aria-label="Encerrar sala e apagar todos os participantes"></button>
          <div class="card-body">
            <h2 class="text-center fw-bold"><?php echo $nome_sala; ?></h2>
            <p class="text-center text-muted mb-3">
              Tempo de reunião: <span id="tempoReuniao">00:00:00</span> &middot;
              Tempo de fala por participante: <?php echo $tempo_fala; ?>
            </p>

            <div id="painelFalando" class="text-center p-4 mb-3 rounded shadow-sm" style="background:#f5f5f5;" aria-live="polite">
              <div id="semFalante">Nenhum participante falando no momento.</div>
              <div id="comFalante" style="display:none;">
                <h4 class="fw-bold mb-1" id="nomeFalante"></h4>
                <div style="font-size: 48px;" id="contadorFala">00:00</div>
              </div>
            </div>

            <div class="d-grid gap-2 mb-3">
              <button id="btnProximo" class="btn btn-dark" type="button">Iniciar fala do próximo</button>
            </div>

            <h6 class="fw-bold">Fila de espera</h6>
            <div id="listaFila" class="d-grid gap-2 overflow-auto shadow p-3 mb-2 bg-body-tertiary rounded"
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

    document.querySelector(".btn-close").addEventListener("click", function () {
      if (!confirm("Tem certeza que deseja encerrar a sala? Isso vai apagar a sala e remover todos os participantes — não pode ser desfeito.")) {
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
            alert("Erro ao fechar a sala.");
          }
        });
    });

    function formatarMMSS(totalSegundos) {
      const m = Math.floor(totalSegundos / 60).toString().padStart(2, "0");
      const s = Math.floor(totalSegundos % 60).toString().padStart(2, "0");
      return `${m}:${s}`;
    }

    function formatarHHMMSS(totalSegundos) {
      const h = Math.floor(totalSegundos / 3600).toString().padStart(2, "0");
      const m = Math.floor((totalSegundos % 3600) / 60).toString().padStart(2, "0");
      const s = Math.floor(totalSegundos % 60).toString().padStart(2, "0");
      return `${h}:${m}:${s}`;
    }

    let restanteLocal = null;
    let avancandoAutomaticamente = false;

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
            restanteLocal = estado.falando.restante_segundos;
            document.getElementById("contadorFala").textContent = formatarMMSS(restanteLocal);
            document.getElementById("btnProximo").textContent = "Passar a vez";
          } else {
            document.getElementById("semFalante").style.display = "block";
            document.getElementById("comFalante").style.display = "none";
            restanteLocal = null;
            document.getElementById("btnProximo").textContent = "Iniciar fala do próximo";
          }

          const listaFila = document.getElementById("listaFila");
          listaFila.innerHTML = "";
          if (estado.fila.length === 0) {
            const vazio = document.createElement("p");
            vazio.textContent = "Ninguém na fila.";
            listaFila.appendChild(vazio);
          } else {
            estado.fila.forEach((p, i) => {
              const item = document.createElement("p");
              item.textContent = `${i + 1}. ${p.nome}`;
              listaFila.appendChild(item);
            });
          }
        })
        .catch(err => console.error("Erro ao buscar estado da sala:", err));
    }

    setInterval(() => {
      const decorrido = Math.floor((new Date() - dataInicio) / 1000);
      document.getElementById("tempoReuniao").textContent = formatarHHMMSS(Math.max(0, decorrido));

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
