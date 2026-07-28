<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../functions/conexao.php");

// Verificação estrita de segurança estendida
$autenticado = false;

if (isset($_SESSION['id_criador']) && isset($_SESSION['session_token'])) {
    // Busca o token ativo atualmente registrado no banco de dados para este criador
    $stmt_auth = $mysqli->prepare("SELECT session_token FROM criador WHERE id_criador = ?");
    if ($stmt_auth) {
        $stmt_auth->bind_param("i", $_SESSION['id_criador']);
        $stmt_auth->execute();
        $res_auth = $stmt_auth->get_result()->fetch_assoc();
        $stmt_auth->close();

        // Se o token salvo na sessão atual for IGUAL ao token no banco, o dispositivo está autorizado
        if ($res_auth && $res_auth['session_token'] === $_SESSION['session_token']) {
            $autenticado = true;
        }
    }
}

// Se não passar no teste, destrói a sessão local (pois foi invalidada por outro login) e expulsa
if (!$autenticado) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();

    header('Location: login.php');
    exit();
}

// registra que esta sessão está ativa agora (mantém o login "vivo" e impede
// que outro dispositivo consiga logar enquanto este estiver em uso)
$stmt_touch = $mysqli->prepare("UPDATE criador SET session_last_activity = NOW() WHERE id_criador = ?");
$stmt_touch->bind_param("i", $_SESSION['id_criador']);
$stmt_touch->execute();
$stmt_touch->close();

// ... Daqui para baixo segue o código normal da página criar.php ...

function gerar_codigo_sala_aleatorio()
{
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = '';
    $max = strlen($caracteres) - 1;
    for ($i = 0; $i < 6; $i++) {
        $indice = random_int(0, $max);
        $codigo .= $caracteres[$indice];
    }
    return $codigo;
}

// Inicializamos a variável de erro seguindo o padrão do projeto
$erro = "";

// Inicializamos os valores padrão ou recuperamos o que foi submetido para persistência
$nome_sala = "";
$tempo = "";
$codigo_sala = gerar_codigo_sala_aleatorio();

if (isset($_POST['submit'])) {
    $nome_sala = trim($_POST['nome'] ?? '');
    $tempo = trim($_POST['tempo'] ?? '');
    $codigo_sala = trim($_POST['codigo'] ?? '');

    if ($nome_sala === '' || $tempo === '' || $codigo_sala === '') {
        $erro = "Por favor preencha todos os campos.";
    } else {
        // --- VERIFICAÇÃO DE SALA DUPLICADA NA VARIÁVEL DE ERRO ---
        $stmt_check = $mysqli->prepare("SELECT id_sala FROM sala WHERE nome_sala = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $nome_sala);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check && $result_check->num_rows > 0) {
                $erro = "Sala já existente.";
            }
            $stmt_check->close();
        } else {
            $erro = "Erro ao preparar consulta de verificação.";
        }

        // Se não houver nenhum erro anterior, prossegue com o cadastro
        if (empty($erro)) {
            $stmt = $mysqli->prepare("INSERT INTO sala (nome_sala, codigo_sala, tempo_de_fala) VALUES (?, ?, ?)");
            if ($stmt === false) {
                $erro = "Erro interno. Tente novamente.";
            } else {
                $stmt->bind_param("sss", $nome_sala, $codigo_sala, $tempo);
                if ($stmt->execute()) {
                    $id_sala = $mysqli->insert_id;
                    $stmt->close();

                    $update = $mysqli->prepare("UPDATE criador SET fk_sala_criada = ? WHERE id_criador = ?");
                    if ($update) {
                        $update->bind_param('ii', $id_sala, $_SESSION['id_criador']);
                        $update->execute();
                        $update->close();
                    }

                    $_SESSION['nome_sala'] = $nome_sala;
                    header("Location: criador.php?id_sala=$id_sala");
                    exit();
                } else {
                    $erro = "Erro ao criar sala. Tente novamente.";
                    $stmt->close();
                }
            }
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
    <title>ME INSCREVO - Criar Sala</title>
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
                    <button type="button" class="btn-close" id="btnSair" aria-label="Close"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Criar Sala</h2><br>

                        <form action="" method="POST">
                            <!-- Injeção exata do Bloco de Alerta Bootstrap sob demanda -->
                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>

                            <label for="nome" class="form-label">Nome da Sala</label>
                            <input name="nome" type="text" class="form-control" id="nome"
                                value="<?php echo htmlspecialchars($nome_sala, ENT_QUOTES, 'UTF-8'); ?>" required /><br>

                            <label for="codigo" class="form-label">Código da Sala</label>
                            <input name="codigo" type="text" class="form-control" id="codigo"
                                value="<?php echo htmlspecialchars($codigo_sala, ENT_QUOTES, 'UTF-8'); ?>"
                                required /><br>

                            <label for="tempo" class="form-label">Tempo de fala dos participantes
                                (horas:minutos:segundos)</label>
                            <input name="tempo" type="time" step="1" class="form-control" id="tempo"
                                value="<?php echo htmlspecialchars($tempo, ENT_QUOTES, 'UTF-8'); ?>" required /><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit">Criar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>

    <script>
        document.getElementById("btnSair").addEventListener("click", () => {
            window.open("../functions/logout.php", "_self");
        });
    </script>
</body>

</html>