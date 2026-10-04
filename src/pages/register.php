<?php
require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/csrf.php';
require_once __DIR__ . '/../functions/idioma.php';
require_once __DIR__ . '/../functions/util.php';

$mensagem = "";
$tipo_alerta = "";

$nome = "";
$email = "";

if (isset($_POST['submit'])) {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $mensagem = t('erro_sessao_expirada');
        $tipo_alerta = "danger";
    } else {
        $nome = post_texto('nome');
        $email = post_texto('email');
        $senha_texto = (isset($_POST['senha']) && is_string($_POST['senha'])) ? $_POST['senha'] : '';

        if ($nome === '' || $email === '' || $senha_texto === '') {
            $mensagem = t('erro_preencha_campos');
            $tipo_alerta = "danger";
        } elseif (strpos($nome, '@') !== false) {
            // o login decide "e-mail ou usuário" pela presença do "@"; sem esta
            // regra alguém poderia cadastrar o e-mail de outra pessoa como
            // nome de usuário e travar o login dela
            $mensagem = t('erro_usuario_sem_arroba');
            $tipo_alerta = "danger";
        } elseif (mb_strlen($nome) > 100 || mb_strlen($email) > 100) {
            $mensagem = t('erro_nome_longo');
            $tipo_alerta = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem = t('erro_email_invalido');
            $tipo_alerta = "danger";
        } elseif (strlen($senha_texto) < 8) {
            $mensagem = t('erro_senha_minima');
            $tipo_alerta = "danger";
        } else {
            try {
                $existente = db_fetch_one(
                    $mysqli,
                    "SELECT id_criador FROM criador WHERE nome_criador IN (?, ?) OR email IN (?, ?)",
                    "ssss",
                    $nome,
                    $email,
                    $nome,
                    $email
                );

                if ($existente !== null) {
                    $mensagem = t('erro_usuario_existente');
                    $tipo_alerta = "danger";
                } else {
                    $senha_hash = password_hash($senha_texto, PASSWORD_DEFAULT);

                    db_exec(
                        $mysqli,
                        "INSERT INTO criador (nome_criador, email, senha) VALUES (?, ?, ?)",
                        "sss",
                        $nome,
                        $email,
                        $senha_hash
                    );

                    $mensagem = t('msg_cadastro_sucesso', t('msg_cadastro_sucesso_link'));
                    $tipo_alerta = "success";
                    $nome = "";
                    $email = "";
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    // duplicidade pega pelo UNIQUE do banco (dois cadastros
                    // iguais chegando ao mesmo tempo)
                    $mensagem = t('erro_usuario_existente');
                } else {
                    error_log('register.php: ' . $e->getMessage());
                    $mensagem = t('erro_cadastro_bd');
                }
                $tipo_alerta = "danger";
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
    <title><?php echo t('title_register'); ?></title>
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
                <div class="card">
                    <button type="button" class="btn-close" id="btnSair" aria-label="<?php echo te('aria_sair_sem_registrar'); ?>"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo t('titulo_register'); ?></h2><br>

                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-<?php echo $tipo_alerta; ?>" role="alert">
                                <?php echo $mensagem; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <label for="exampleInput1" class="form-label"><?php echo t('label_username'); ?></label>
                            <input name="nome" type="text" class="form-control" id="exampleInput1"
                                value="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" required><br>

                            <label for="exampleInputEmail1" class="form-label"><?php echo t('label_email'); ?></label>
                            <input name="email" type="email" class="form-control" id="exampleInputEmail1"
                                value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="emailHelp" required><br>

                            <label for="exampleInputPassword1" class="form-label"><?php echo t('label_password'); ?></label>
                            <input name="senha" type="password" class="form-control" id="exampleInputPassword1"
                                minlength="8" required aria-describedby="senhaAjuda">
                            <div id="senhaAjuda" class="form-text"><?php echo t('texto_min_caracteres'); ?></div><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit"><?php echo t('btn_registrar'); ?></button>
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
    // "Sair" agora é um POST com token CSRF (antes era um link GET)
    document.getElementById("btnSair").addEventListener("click", () => {
        document.getElementById("formLogout").submit();
    });
</script>

</html>
