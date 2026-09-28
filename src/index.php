<?php
require('functions/conexao.php');
require('functions/csrf.php');
require('functions/idioma.php');

$erro = "";
$codigo_valor = "";
$nome_valor = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $erro = t('erro_sessao_expirada');
    } else {
        $codigo = trim($_POST['codigo'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $codigo_valor = $codigo;
        $nome_valor = $nome;

        if (empty($codigo)) {
            $erro = t('erro_preencha_codigo');
        } elseif (empty($nome)) {
            $erro = t('erro_preencha_nome');
        } elseif (mb_strlen($nome) > 100) {
            $erro = t('erro_nome_longo');
        } else {

            $stmt = $mysqli->prepare("SELECT id_sala, nome_sala FROM sala WHERE codigo_sala = ?");
            if ($stmt) {
                $stmt->bind_param("s", $codigo);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $sala = $result->fetch_assoc();
                    $id_sala = $sala['id_sala'];

                    $stmt_insert = $mysqli->prepare("INSERT INTO participante (nome_participante, fk_sala_atual, ultima_atividade) VALUES (?, ?, NOW())");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param("si", $nome, $id_sala);
                        if ($stmt_insert->execute()) {

                            $id_participante = $stmt_insert->insert_id;

                            session_regenerate_id(true);
                            $_SESSION['codigo'] = $codigo;
                            $_SESSION['nome'] = $nome;
                            $_SESSION['id_participante'] = $id_participante;
                            header("Location: pages/participante.php");
                            exit;
                        } else {
                            $erro = t('erro_inserir_participante');
                        }
                        $stmt_insert->close();
                    }
                } else {
                    $erro = t('erro_codigo_invalido');
                }

                $stmt->close();
            } else {
                $erro = t('erro_preparar_consulta');
            }
        }
    }
}
?>
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
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/MI_legenda_branco.png" type="image/x-icon">
    <title>ME INSCREVO</title>
</head>

<body>
    <?php idioma_switch_html(); ?>
    <div class="container">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="text-center">
                    <img class="logo-black rounded" src="img/MI_legenda.png" alt="<?php echo te('alt_logo'); ?>">
                </div>
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo t('titulo_entrar_sala'); ?></h2><br>
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <label for="codigo" class="form-label"><?php echo t('label_codigo_sala'); ?></label>
                            <input name="codigo" type="text" class="form-control" id="codigo"
                                value="<?php echo htmlspecialchars($codigo_valor, ENT_QUOTES, 'UTF-8'); ?>" required><br>

                            <label for="nome" class="form-label"><?php echo t('label_nome_convidado'); ?></label>
                            <input name="nome" type="text" class="form-control" id="nome"
                                value="<?php echo htmlspecialchars($nome_valor, ENT_QUOTES, 'UTF-8'); ?>" required><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" type="submit"><?php echo t('btn_entrar'); ?></button>
                                <button id="login" class="btn" type="button"><?php echo t('btn_criar_sala'); ?></button>
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
