<?php
session_start();
include("../functions/conexao.php");
require("../functions/csrf.php");

$erro = "";

// tempo, em minutos, que uma sessão precisa ficar sem atividade para ser
// considerada "expirada" e liberar um novo login em outro lugar
$LIMITE_INATIVIDADE_MINUTOS = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $erro = "Sessão expirada. Recarregue a página e tente novamente.";
    } else {
        $identificador = trim($_POST['identificador'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        if (empty($identificador)) {
            $erro = "Preencha seu e-mail ou nome de usuário!";
        } else if (empty($senha)) {
            $erro = "Preencha sua senha!";
        } else {
            // autentica tanto por e-mail quanto por nome de usuário
            $stmt = $mysqli->prepare("SELECT id_criador, nome_criador, senha, session_token, session_last_activity FROM criador WHERE email = ? OR nome_criador = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $identificador, $identificador);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $usuario = $result->fetch_assoc();

                    if (password_verify($senha, $usuario['senha'])) {
                        $id_criador = $usuario['id_criador'];

                        $sessao_ativa = false;

                        if (!empty($usuario['session_token']) && !empty($usuario['session_last_activity'])) {
                            $ultima = new DateTime($usuario['session_last_activity']);
                            $agora = new DateTime();
                            $minutos_parado = ($agora->getTimestamp() - $ultima->getTimestamp()) / 60;

                            if ($minutos_parado < $LIMITE_INATIVIDADE_MINUTOS) {
                                $sessao_ativa = true;
                            }
                        }

                        if ($sessao_ativa) {
                            $erro = "Esta conta já está logada em outro dispositivo/aba. Saia de lá primeiro, ou aguarde alguns minutos de inatividade e tente novamente.";
                        } else {
                            $novo_token = bin2hex(random_bytes(32));

                            $stmt_token = $mysqli->prepare("UPDATE criador SET session_token = ?, session_last_activity = NOW() WHERE id_criador = ?");
                            if ($stmt_token) {
                                $stmt_token->bind_param("si", $novo_token, $id_criador);
                                $stmt_token->execute();
                                $stmt_token->close();

                                session_regenerate_id(true);
                                $_SESSION['id_criador'] = $id_criador;
                                $_SESSION['nome_criador'] = $usuario['nome_criador'];
                                $_SESSION['session_token'] = $novo_token;

                                $stmt->close();
                                header("Location: criar.php");
                                exit();
                            } else {
                                $erro = "Erro interno ao registrar sessão.";
                            }
                        }
                    } else {
                        $erro = "Usuário ou senha incorretos!";
                    }
                } else {
                    $erro = "Usuário ou senha incorretos!";
                }
                $stmt->close();
            } else {
                $erro = "Erro interno no servidor de banco de dados.";
            }
        }
    }
}
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
    <title>ME INSCREVO - Login</title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="text-center">
                    <img class="logo-black rounded" src="../img/MI_legenda.png" alt="Logo do ME INSCREVO">
                </div>
                <div class="card shadow">
                    <button type="button" class="btn-close" id="btnSair" aria-label="Sair sem entrar"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Login</h2><br>
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <label for="identificador" class="form-label">Nome de usuário ou e-mail</label>
                            <input name="identificador" type="text" class="form-control" id="identificador"
                                value="<?php echo isset($_POST['identificador']) ? htmlspecialchars($_POST['identificador'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                required><br>

                            <label for="password" class="form-label">Senha</label>
                            <input name="senha" type="password" class="form-control" id="password" required><br>

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

        document.getElementById("btnSair").addEventListener("click", () => {
            window.open("../functions/logout.php", "_self");
        });
    </script>
</body>

</html>
