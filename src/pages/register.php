<?php
include("../functions/conexao.php");

// Variáveis para controlar as mensagens e os tipos de alerta
$mensagem = "";
$tipo_alerta = "";

// Mantém o que o usuário digitou na tela, mesmo se a validação falhar
$nome = "";
$email = "";

if (isset($_POST['submit'])) {

    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $email = trim(htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'));
    $senha_texto = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '' || $senha_texto === '') {
        $mensagem = "Preencha todos os campos.";
        $tipo_alerta = "danger";
    } else {
        $stmt_check = $mysqli->prepare("SELECT id_criador FROM criador WHERE nome_criador = ? OR email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("ss", $nome, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check && $result_check->num_rows > 0) {
                $mensagem = "Usuário ou E-mail já cadastrado no sistema.";
                $tipo_alerta = "danger";
            } else {
                // Nunca gravar a senha em texto puro — gera um hash seguro (bcrypt)
                $senha_hash = password_hash($senha_texto, PASSWORD_DEFAULT);

                $stmt_insert = $mysqli->prepare("INSERT INTO criador (nome_criador, email, senha) VALUES (?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sss", $nome, $email, $senha_hash);

                    if ($stmt_insert->execute()) {
                        $mensagem = 'Usuário cadastrado com sucesso. Clique em <a href="login.php" class="alert-link">aqui</a> para continuar';
                        $tipo_alerta = "success";
                        $nome = "";
                        $email = "";
                    } else {
                        $mensagem = "Erro ao realizar o cadastro no banco de dados.";
                        $tipo_alerta = "danger";
                    }
                    $stmt_insert->close();
                }
            }
            $stmt_check->close();
        } else {
            $mensagem = "Erro interno no servidor de dados.";
            $tipo_alerta = "danger";
        }
    }
}
?>
<html>

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
                    <button type="button" class="btn-close" id="btnSair" aria-label="Close"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Register</h2><br>

                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-<?php echo $tipo_alerta; ?>" role="alert">
                                <?php echo $mensagem; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <label for="exampleInput1" class="form-label">Username</label>
                            <input name="nome" type="text" class="form-control" id="exampleInput1"
                                value="<?php echo $nome; ?>" required><br>

                            <label for="exampleInputEmail1" class="form-label">Email address</label>
                            <input name="email" type="email" class="form-control" id="exampleInputEmail1"
                                value="<?php echo $email; ?>" aria-describedby="emailHelp" required><br>

                            <label for="exampleInputPassword1" class="form-label">Password</label>
                            <div class="input-group">
                                <input name="senha" type="password" class="form-control" id="exampleInputPassword1"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="gerarSenha">Gerar</button>
                            </div><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit">Registrar</button>
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
    document.getElementById("btnSair").addEventListener("click", () => {
        window.open("../functions/logout.php", "_self");
    });

    document.getElementById("gerarSenha").addEventListener("click", () => {
        const caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*";
        let senha = "";
        for (let i = 0; i < 12; i++) {
            senha += caracteres[Math.floor(Math.random() * caracteres.length)];
        }

        const campo = document.getElementById("exampleInputPassword1");
        campo.type = "text";
        campo.value = senha;

        setTimeout(() => {
            campo.type = "password";
        }, 1500);
    });
</script>

</html>