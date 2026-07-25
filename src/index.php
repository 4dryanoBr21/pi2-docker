<?php
require('functions/conexao.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo = trim(htmlspecialchars($_POST['codigo'] ?? '', ENT_QUOTES, 'UTF-8'));
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));

    if (empty($codigo)) {
        echo "Preencha o código da sala!";
    } elseif (empty($nome)) {
        echo "Preencha seu nome!";
    } else {

        $stmt = $mysqli->prepare("SELECT id_sala, nome_sala FROM sala WHERE codigo_sala = ?");
        if ($stmt) {
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $sala = $result->fetch_assoc();
                $id_sala = $sala['id_sala'];

                $stmt_insert = $mysqli->prepare("INSERT INTO participante (nome_participante, fk_sala_atual) VALUES (?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("si", $nome, $id_sala);
                    if ($stmt_insert->execute()) {

                        $id_participante = $stmt_insert->insert_id;

                        $_SESSION['codigo'] = $codigo;
                        $_SESSION['nome'] = $nome;
                        $_SESSION['id_participante'] = $id_participante;
                        header("Location: pages/participante.php");
                        exit;
                    } else {
                        echo "Erro ao inserir participante.";
                    }
                    $stmt_insert->close();
                }
            } else {
                echo "Código de sala inválido.";
            }

            $stmt->close();
        } else {
            echo "Erro ao preparar consulta SQL.";
        }
    }
}
?>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/MI_legenda_branco.png" type="image/x-icon">
    <title>ME INSCREVO</title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="text-center">
                    <img class="logo-black" src="../img/MI_legenda.png" class="rounded" alt="Logo">
                </div>
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Entrar na Sala</h2><br>
                        <form action="" method="POST">
                            <label for="codigo" class="form-label">Código da Sala</label>
                            <input name="codigo" type="text" class="form-control" id="codigo" required><br>

                            <label for="nome" class="form-label">Nome do Convidado</label>
                            <input name="nome" type="text" class="form-control" id="nome" required><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" type="submit">Entrar</button>
                                <button id="login" class="btn" type="button">Criar Sala</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>

    <script>
        document.getElementById("login").addEventListener("click", () => {
            window.location.href = "pages/login.php";
        });
    </script>
</body>

</html>
