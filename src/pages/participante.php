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
    <div class="container">
        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-6">
                <div class="text-center">
                    <img class="logo-black rounded" src="../img/MI_legenda.png" alt="<?php echo te('alt_logo'); ?>">
                </div>
                <div class="card">
                    <button type="button" class="btn-close" id="btnSair" aria-label="<?php echo te('aria_sair_sala'); ?>"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo htmlspecialchars($nome_sala); ?></h2>

                        <div id="painelEstado" class="text-center p-4 mb-3 rounded shadow-sm"
                            style="background:#f5f5f5;" aria-live="polite">
                            <div id="estadoAguardando">
                                <p class="mb-1"><?php echo t('texto_aguardando'); ?></p>
                                <p id="posicaoFila" class="text-muted"><?php echo t('texto_levante_mao'); ?></p>
                            </div>
                            <div id="estadoFalando" style="display:none;">
                                <h4 class="fw-bold mb-1"><?php echo t('texto_sua_vez'); ?></h4>
                                <div style="font-size: 48px;" id="contadorFala">00:00</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button id="mao" class="btn" type="button" style="font-size: 75px;" aria-label="<?php echo te('aria_levantar_mao'); ?>">🤚</button>
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
        const idParticipante = <?php echo (int) $id_participante; ?>;
        const csrfToken = "<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>";

        // O servidor é a fonte da verdade sobre o tempo; aqui guardamos só o
        // instante (relógio deste navegador) em que a fala termina e
        // recalculamos a tela a partir dele. Não depende de os timers do
        // navegador dispararem exatamente a cada segundo.
        const TOLERANCIA_MS = 1500;
        let fimFalaMs = null;
        let maoLevantada = false;
        let speakerAtualId = null;
        let enviandoMao = false;

        const textos = {
            levanteAMao: <?php echo tj('texto_levante_mao'); ?>,
            posicaoFila: <?php echo tj('texto_posicao_fila'); ?>,
            abaixarMao: <?php echo tj('aria_abaixar_mao'); ?>,
            levantarMao: <?php echo tj('aria_levantar_mao'); ?>,
            encerrarFala: <?php echo tj('aria_encerrar_fala'); ?>,
            nenhumParticipanteAinda: <?php echo tj('texto_nenhum_participante_ainda'); ?>,
            voceSufixo: <?php echo tj('texto_voce_sufixo'); ?>,
            erroSairSala: <?php echo tj('erro_sair_sala'); ?>
        };

        function formatarPosicaoFila(posicao, total) {
            // textos.posicaoFila vem do PHP no formato usado por sprintf/vsprintf
            // (ex.: "Você é o %dº da fila (%d no total)."); replicamos a mesma
            // lógica em JS trocando os "%d" na ordem em que aparecem.
            let indice = 0;
            const valores = [posicao, total];
            return textos.posicaoFila.replace(/%d/g, () => valores[indice++]);
        }

        function desenharRelogio() {
            if (fimFalaMs !== null) {
                const restante = Math.max(0, Math.ceil((fimFalaMs - Date.now()) / 1000));
                document.getElementById("contadorFala").textContent = MI.formatarTempo(restante);
            }
        }

        // Este mesmo endpoint também avisa quando a sala foi encerrada ou o
        // participante foi removido (responde com "erro"): era a função do
        // antigo verifica_sala.php, que consultava o servidor 1x por segundo.
        function atualizarEstado() {
            fetch("../functions/estado_sala.php?id_sala=" + idSala, { cache: "no-store" })
                .then(res => res.json())
                .then(estado => {
                    if (estado.erro) {
                        window.location.href = "../index.php";
                        return;
                    }

                    const agora = Date.now();
                    const souEu = estado.falando && estado.falando.id_participante === idParticipante;
                    const mao = document.getElementById("mao");

                    if (souEu) {
                        document.getElementById("estadoAguardando").style.display = "none";
                        document.getElementById("estadoFalando").style.display = "block";

                        const novoFim = agora + estado.falando.restante_segundos * 1000;
                        if (speakerAtualId !== estado.falando.id_participante || fimFalaMs === null
                            || Math.abs(novoFim - fimFalaMs) > TOLERANCIA_MS) {
                            speakerAtualId = estado.falando.id_participante;
                            fimFalaMs = novoFim;
                        }
                        maoLevantada = false;
                    } else {
                        document.getElementById("estadoAguardando").style.display = "block";
                        document.getElementById("estadoFalando").style.display = "none";
                        speakerAtualId = null;
                        fimFalaMs = null;

                        const posicao = estado.fila.findIndex(p => p.id_participante === idParticipante);
                        if (posicao === -1) {
                            document.getElementById("posicaoFila").textContent = textos.levanteAMao;
                            maoLevantada = false;
                        } else {
                            document.getElementById("posicaoFila").textContent =
                                formatarPosicaoFila(posicao + 1, estado.fila.length);
                            maoLevantada = true;
                        }
                    }

                    // ❌ tem dois significados: abaixar a mão (na fila) ou
                    // encerrar a própria fala (quando é a sua vez)
                    mao.textContent = (maoLevantada || souEu) ? "❌" : "🤚";
                    mao.setAttribute(
                        "aria-label",
                        souEu ? textos.encerrarFala : (maoLevantada ? textos.abaixarMao : textos.levantarMao)
                    );

                    MI.renderizarPresentes(document.getElementById("listaPresentes"), estado, {
                        textoVazio: textos.nenhumParticipanteAinda,
                        idPropio: idParticipante,
                        sufixoVoce: textos.voceSufixo
                    });

                    desenharRelogio();
                })
                .catch(err => console.error("Erro ao buscar estado da sala:", err));
        }

        document.getElementById("mao").addEventListener("click", () => {
            if (enviandoMao) return; // evita duplo clique (levantar e abaixar sem querer)
            enviandoMao = true;

            fetch("../functions/salvar_hora.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id_participante=" + idParticipante + "&csrf_token=" + encodeURIComponent(csrfToken)
            })
                .then(res => res.text())
                .then(() => { enviandoMao = false; atualizarEstado(); })
                .catch(err => { enviandoMao = false; console.error("Erro ao alternar horário:", err); });
        });

        document.getElementById("btnSair").addEventListener("click", function () {
            const formData = new FormData();
            formData.append("id_participante", idParticipante);
            formData.append("csrf_token", csrfToken);

            fetch("../functions/sair_sala.php", { method: "POST", body: formData })
                .then(res => res.text())
                .then(ret => {
                    if (ret.trim() === "ok") {
                        window.location.href = "../index.php";
                    } else {
                        alert(textos.erroSairSala);
                    }
                })
                .catch(err => console.error("Erro:", err));
        });

        setInterval(desenharRelogio, 250);
        setInterval(atualizarEstado, 2000);
        atualizarEstado();
    </script>

</body>

</html>
