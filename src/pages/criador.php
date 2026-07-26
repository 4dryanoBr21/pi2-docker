<?php
include('../functions/conexao.php');

session_start();

if (!isset($_GET['id_sala'])) {
  die("Sala não especificada. <a href='criar.php'>Voltar</a>");
}

$id_sala = intval($_GET['id_sala']);

$mysqli->query("UPDATE sala SET data_inicio = NOW() WHERE id_sala = $id_sala AND data_inicio IS NULL");

$sql = "SELECT * FROM sala WHERE id_sala = $id_sala";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $nome_sala = htmlspecialchars($row['nome_sala']);
  $tempo_fala = htmlspecialchars($row['tempo_de_fala']);
  $data_inicio_js = htmlspecialchars($row['data_inicio']);
} else {
  die("Sala não encontrada. <a href='criar.php'>Voltar</a>");
}
?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <link rel="stylesheet" href="../style.css">
    <link rel="shortcut icon" href="img/MI_legenda_branco.png" type="image/x-icon">
  <title>ME INSCREVO - Sala <?php echo $nome_sala; ?></title>
</head>

<body>
  <div class="container">
    <div class="row">
      <div class="col-md-3"></div>
      <div class="col-md-6">
        <div class="text-center">
          <img class="logo-black" src="../img/MI_legenda.png" class="rounded" alt="Logo">
        </div>
        <div class="card">
          <button type="button" class="btn-close" aria-label="Close"></button>
          <div class="card-body">
            <h2 class="text-center fw-bold"><?php echo $nome_sala; ?></h2>
            <p class="text-center text-muted mb-3">
              Tempo de reunião: <span id="tempoReuniao">00:00:00</span> &middot;
              Tempo de fala por participante: <?php echo $tempo_fala; ?>
            </p>

            <div id="painelFalando" class="text-center p-4 mb-3 rounded shadow-sm" style="background:#f5f5f5;">
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
              style="height: 160px;">
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3"></div>
    </div>
  </div>

  <script>
    const idSala = <?php echo $id_sala; ?>;
    const dataInicio = new Date("<?php echo $data_inicio_js; ?>Z".replace(" ", "T"));

    document.querySelector(".btn-close").addEventListener("click", function() {
      const form = new FormData();
      form.append("id_sala", idSala);

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
          if (estado.erro || estado.encerrada) {
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

    setInterval(atualizarEstado, 3000);
    atualizarEstado();
  </script>
</body>

</html>