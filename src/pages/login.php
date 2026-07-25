<?php
session_start();
include("../functions/conexao.php");

if (isset($_POST['email']) || isset($_POST['senha'])) {

    if (strlen($_POST['email']) == 0) {
        echo "Prencha seu email!";
    } else if (strlen($_POST['senha']) == 0) {
        echo "Prencha sua senha!";
    } else {

        $email = $mysqli->real_escape_string($_POST['email']);
        $senha = $mysqli->real_escape_string($_POST['senha']);

        $sql_code = "SELECT * FROM criador WHERE email = '$email' AND senha = '$senha'";
        $sql_query = $mysqli->query($sql_code) or die("Falha na execução do SQL: " . $mysqli->error);

        $quantidade = $sql_query->num_rows;

        if ($quantidade == 1) {

            $usuario = $sql_query->fetch_assoc();

            $_SESSION['id_criador'] = $usuario['id_criador'];
            $_SESSION['nome_criador'] = $usuario['nome_criador'];

            header("Location: criar.php");
        } else {
            echo "email ou senha incorretos!";
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
    <link rel="stylesheet" href="../style.css">
    <link rel="shortcut icon" href="../img/MI_legenda_branco.png" type="image/x-icon">
    <title>ME INSCREVO - Login</title>
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
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Login</h2><br>
                        <form action="" method="POST">
                            <label for="email" class="form-label">Endereço de email</label>
                            <input name="email" type="email" class="form-control" id="email"><br>

                            <label for="password" class="form-label">Senha</label>
                            <input name="senha" type="password" class="form-control" id="password"><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit">Entrar</button>
                                <button id="cad" class="btn" type="button">Registrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>

    <script>
        document.getElementById("cad").addEventListener("click", () => {
            window.open("register.php", "_self");
        });
    </script>
</body>

</html>