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
    <link rel="stylesheet" href="../style.css">
    <link rel="shortcut icon" href="../img/MI_legenda_branco.png" type="image/x-icon">
    <title>ME INSCREVO - <?php echo htmlspecialchars($nome_sala); ?></title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="text-center">
                    <img src="../img/MI_legenda.png" class="rounded" alt="Logo" style="height: 200px;">
                </div>
                <div class="card">
                    <button type="button" class="btn-close" id="btnSair" aria-label="Close"></button>
                    <h2 class="text-center fw-bold"><?php echo htmlspecialchars($nome_sala); ?></h2>
                    <div class="card-body">
                        <div id="listaUsuarios" class="d-grid gap-2 overflow-auto shadow p-3 mb-5 bg-body-tertiary rounded"
                            style="height: 200px;">
                        </div>
                        <div class="d-grid gap-2">
                            <button id="mao" class="btn" type="button" style="font-size: 75px;">🤚</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>

    <script>
        function verificarSala() {
            fetch("../functions/verifica_sala.php?id_sala=<?php echo $id_sala; ?>")
                .then(res => res.text())
                .then(resp => {
                    if (resp.trim() === "1") {
                        window.location.href = "../index.php";
                    }
                });
        }

        setInterval(verificarSala, 1000);
    </script>

    <script>
        document.getElementById("mao").addEventListener("click", () => {

            const idParticipante = <?php echo $_SESSION['id_participante']; ?>;

            fetch("../functions/salvar_hora.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "id_participante=" + idParticipante
                })
                .then(res => res.text())
                .then(resp => {
                    if (resp === "hora") {
                        console.log("Data/hora registrada");
                    } else if (resp === "null") {
                        console.log("Data/hora removida (NULL)");
                    } else {
                        console.log("Erro ao alternar horário");
                    }
                });
        });
    </script>

    <script>
        const idParticipante = <?php echo $_SESSION['id_participante']; ?>;
    </script>

    <script>
        document.getElementById("btnSair").addEventListener("click", function() {
            const formData = new FormData();
            formData.append("id_participante", idParticipante);

            fetch("../functions/sair_sala.php", {
                    method: "POST",
                    body: formData
                })
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
    </script>

    <script>
        function atualizarUsuarios() {
            fetch("../functions/get_usuarios.php?id_sala=<?php echo $id_sala; ?>")
                .then(res => res.text())
                .then(html => {
                    document.getElementById("listaUsuarios").innerHTML = html;
                })
                .catch(err => console.error("Erro ao buscar usuários:", err));
        }

        setInterval(atualizarUsuarios, 1000);
        atualizarUsuarios();
    </script>

    <script>
        const emoji = document.getElementById("mao");

        emoji.addEventListener("click", () => {
            emoji.textContent = emoji.textContent === "🤚" ? "❌" : "🤚";
        });
    </script>

</body>

</html>