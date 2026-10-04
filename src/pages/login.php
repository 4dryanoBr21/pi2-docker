<?php
require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/csrf.php';
require_once __DIR__ . '/../functions/idioma.php';
require_once __DIR__ . '/../functions/util.php';

$erro = "";
$identificador = "";

// tempo, em minutos, que uma sessão precisa ficar sem atividade para ser
// considerada "expirada" e liberar um novo login em outro lugar
$LIMITE_INATIVIDADE_MINUTOS = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $erro = t('erro_sessao_expirada');
    } else {
        $identificador = post_texto('identificador');

        // A senha NÃO passa por trim(): o cadastro também não remove espaços,
        // então uma senha com espaço no começo/fim nunca conseguiria entrar.
        $senha = (isset($_POST['senha']) && is_string($_POST['senha'])) ? $_POST['senha'] : '';

        if ($identificador === '') {
            $erro = t('erro_preencha_email_usuario');
        } elseif ($senha === '') {
            $erro = t('erro_preencha_senha');
        } else {
            // Autentica por e-mail OU nome de usuário, sem ambiguidade: e-mail
            // sempre tem "@" e o cadastro proíbe "@" em nome de usuário.
            // ($campo vem de uma lista fixa, nunca do usuário.)
            $campo = (strpos($identificador, '@') !== false) ? 'email' : 'nome_criador';

            try {
                $usuario = db_fetch_one(
                    $mysqli,
                    "SELECT id_criador, nome_criador, senha, session_token, session_last_activity FROM criador WHERE $campo = ?",
                    "s",
                    $identificador
                );

                if ($usuario === null) {
                    // Gasta o mesmo tempo de uma verificação real, para que o
                    // tempo de resposta não revele quais contas existem.
                    password_hash($senha, PASSWORD_DEFAULT);
                    $erro = t('erro_usuario_senha_incorretos');
                } elseif (!password_verify($senha, (string) $usuario['senha'])) {
                    $erro = t('erro_usuario_senha_incorretos');
                } else {
                    $id_criador = (int) $usuario['id_criador'];

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
                        $erro = t('erro_conta_logada');
                    } else {
                        $novo_token = bin2hex(random_bytes(32));

                        db_exec(
                            $mysqli,
                            "UPDATE criador SET session_token = ?, session_last_activity = NOW() WHERE id_criador = ?",
                            "si",
                            $novo_token,
                            $id_criador
                        );

                        session_regenerate_id(true);
                        unset($_SESSION['csrf_token']); // token novo para a sessão autenticada
                        $_SESSION['id_criador'] = $id_criador;
                        $_SESSION['nome_criador'] = $usuario['nome_criador'];
                        $_SESSION['session_token'] = $novo_token;

                        header("Location: criar.php");
                        exit();
                    }
                }
            } catch (mysqli_sql_exception $e) {
                error_log('login.php: ' . $e->getMessage());
                $erro = t('erro_servidor_bd');
            }
        }
    }
}
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
    <title><?php echo t('title_login'); ?></title>
</head>

<body>
    <?php idioma_switch_html(); ?>
    <form id="formLogout" action="../functions/logout.php" method="POST" class="d-none">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    </form>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="text-center">
                    <img class="logo-black rounded" src="../img/MI_legenda.png" alt="<?php echo te('alt_logo'); ?>">
                </div>
                <div class="card shadow">
                    <button type="button" class="btn-close" id="btnSair" aria-label="<?php echo te('aria_sair_sem_entrar'); ?>"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo t('titulo_login'); ?></h2><br>
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <label for="identificador" class="form-label"><?php echo t('label_usuario_email'); ?></label>
                            <input name="identificador" type="text" class="form-control" id="identificador"
                                value="<?php echo htmlspecialchars($identificador, ENT_QUOTES, 'UTF-8'); ?>"
                                required><br>

                            <label for="password" class="form-label"><?php echo t('label_senha'); ?></label>
                            <input name="senha" type="password" class="form-control" id="password" required><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit"><?php echo t('btn_entrar'); ?></button>
                                <button id="cad" class="btn" type="button"><?php echo t('btn_registrar'); ?></button>
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

        // "Sair" agora é um POST com token CSRF (antes era um link GET)
        document.getElementById("btnSair").addEventListener("click", () => {
            document.getElementById("formLogout").submit();
        });
    </script>
</body>

</html>
