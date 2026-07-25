<?php
include("../functions/conexao.php");

if (isset($_POST['submit'])) {

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $result = mysqli_query($mysqli, "INSERT INTO criador (nome_criador, email, senha) VALUES ('$nome', '$email', '$senha')");

    header("Location: login.php");
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
    <title>ME INSCREVO - Register</title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="text-center">
                    <img class="logo-black" src="../img/MI_legenda.png" class="rounded" alt="Logo">
                </div>
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Register</h2><br>
                        <form action="" method="POST">
                            <label for="exampleInput1" class="form-label">Username</label>
                            <input name="nome" type="text" class="form-control" id="exampleInput1" required><br>

                            <label for="exampleInputEmail1" class="form-label">Email address</label>
                            <input name="email" type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" required><br>

                            <label for="exampleInputPassword1" class="form-label">Password</label>
                            <input name="senha" type="password" class="form-control" id="exampleInputPassword1" required><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit">Registrar</button>
                                <button id="login_page" class="btn" type="button">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>
</body>

<script>
    const login = document.getElementById("login_page")

    function login_page() {
        window.open("login.php", "_self")
    }

    login.addEventListener("click", login_page)
</script>

</html>