<?php
require("../functions/conexao.php");

session_start();

if (!isset($_SESSION['codigo']) || !isset($_SESSION['nome'])) {
    header("Location: ../index.php");
    exit;
}

$codigo_sala = $_SESSION['codigo'];
$nome_participante = $_SESSION['nome'];

$stmt = $mysqli->prepare("SELECT id_sala, nome_sala FROM sala WHERE codigo_sala = ?");
$stmt->bind_param("s", $codigo_sala);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $sala = $result->fetch_assoc();
    $id_sala = $sala['id_sala'];
    $nome_sala = $sala['nome_sala'];
} else {
    echo "Sala não encontrada.";
    exit;
}

$stmt->close();
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
    <title>ME INSCREVO - <?php echo htmlspecialchars($nome_sala); ?></title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-6">
                <div class="text-center">
                    <img src="../img/MI_legenda.png" class="rounded" alt="Logo" style="height: 220px;">
                </div>
                <div class="card">
                    <button type="button" class="btn-close" id="btnSair" aria-label="Close"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo htmlspecialchars($nome_sala); ?></h2>

                        <div id="painelEstado" class="text-center p-4 mb-3 rounded shadow-sm" style="background:#f5f5f5;">
                            <div id="estadoAguardando">
                                <p class="mb-1">Aguardando...</p>
                                <p id="posicaoFila" class="text-muted">Levante a mão para entrar na fila.</p>
                            </div>
                            <div id="estadoFalando" style="display:none;">
                                <h4 class="fw-bold mb-1">É a sua vez de falar!</h4>
                                <div style="font-size: 48px;" id="contadorFala">00:00</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button id="mao" class="btn" type="button" style="font-size: 75px;">🤚</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3"></div>
        </div>
    </div>

    <script>
        const idSala = <?php echo $id_sala; ?>;
        const idParticipante = <?php echo $_SESSION['id_participante']; ?>;
        let restanteLocal = null;
        let maoLevantada = false;

        function verificarSala() {
            fetch("../functions/verifica_sala.php?id_sala=" + idSala)
                .then(res => res.text())
                .then(resp => {
                    if (resp.trim() === "1") {
                        window.location.href = "../index.php";
                    }
                });
        }

        function formatarMMSS(totalSegundos) {
            const m = Math.floor(totalSegundos / 60).toString().padStart(2, "0");
            const s = Math.floor(totalSegundos % 60).toString().padStart(2, "0");
            return `${m}:${s}`;
        }

        function atualizarEstado() {
            fetch("../functions/estado_sala.php?id_sala=" + idSala)
                .then(res => res.json())
                .then(estado => {
                    if (estado.erro) {
                        window.location.href = "../index.php";
                        return;
                    }

                    const souEu = estado.falando && estado.falando.id_participante === idParticipante;

                    if (souEu) {
                        document.getElementById("estadoAguardando").style.display = "none";
                        document.getElementById("estadoFalando").style.display = "block";
                        restanteLocal = estado.falando.restante_segundos;
                        document.getElementById("contadorFala").textContent = formatarMMSS(restanteLocal);
                    } else {
                        document.getElementById("estadoAguardando").style.display = "block";
                        document.getElementById("estadoFalando").style.display = "none";
                        restanteLocal = null;

                        const posicao = estado.fila.findIndex(p => p.id_participante === idParticipante);
                        if (posicao === -1) {
                            document.getElementById("posicaoFila").textContent = "Levante a mão para entrar na fila.";
                            maoLevantada = false;
                        } else {
                            document.getElementById("posicaoFila").textContent =
                                `Você é o ${posicao + 1}º da fila (${estado.fila.length} no total).`;
                            maoLevantada = true;
                        }
                    }

                    document.getElementById("mao").textContent = maoLevantada || souEu ? "❌" : "🤚";
                })
                .catch(err => console.error("Erro ao buscar estado da sala:", err));
        }

        document.getElementById("mao").addEventListener("click", () => {
            fetch("../functions/salvar_hora.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id_participante=" + idParticipante
                })
                .then(res => res.text())
                .then(() => atualizarEstado())
                .catch(err => console.error("Erro ao alternar horário:", err));
        });

        document.getElementById("btnSair").addEventListener("click", function() {
            const formData = new FormData();
            formData.append("id_participante", idParticipante);

            fetch("../functions/sair_sala.php", { method: "POST", body: formData })
                .then(res => res.text())
                .then(ret => {
                    if (ret.trim() === "ok") {
                        window.location.href = "../index.php";
                    } else {
                        alert("Erro ao sair da sala.");
                    }
                })
                .catch(err => console.error("Erro:", err));
        });

        setInterval(verificarSala, 1000);
        setInterval(() => {
            if (restanteLocal !== null) {
                restanteLocal = Math.max(0, restanteLocal - 1);
                document.getElementById("contadorFala").textContent = formatarMMSS(restanteLocal);
            }
        }, 1000);
        setInterval(atualizarEstado, 2000);
        atualizarEstado();
    </script>

</body>

</html>
