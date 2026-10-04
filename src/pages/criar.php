<?php
require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/csrf.php';
require_once __DIR__ . '/../functions/idioma.php';
require_once __DIR__ . '/../functions/util.php';
require_once __DIR__ . '/../functions/auth_criador.php';

if (!criador_autenticado($mysqli)) {
    destruir_sessao();
    header('Location: login.php');
    exit();
}

criador_registrar_atividade($mysqli);

// Já existe uma sala aberta deste criador? (voltou pelo botão "voltar" do
// navegador, ou fechou a aba e entrou de novo.) Em vez de criar outra e
// deixar a anterior órfã — com participantes dentro —, volta para ela.
// Para criar uma nova, é só usar "Encerrar sala" antes.
$sala_aberta = db_fetch_one(
    $mysqli,
    "SELECT fk_sala_criada FROM criador WHERE id_criador = ? AND fk_sala_criada IS NOT NULL",
    "i",
    (int) $_SESSION['id_criador']
);

if ($sala_aberta !== null) {
    header('Location: criador.php?id_sala=' . (int) $sala_aberta['fk_sala_criada']);
    exit();
}

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

$erro = "";
$nome_sala = "";
$tempo = "";
$codigo_sala = gerar_codigo_sala_aleatorio();

if (isset($_POST['submit'])) {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $erro = t('erro_sessao_expirada');
    } else {
        $nome_sala = post_texto('nome');
        $tempo = post_texto('tempo');
        $codigo_sala = post_texto('codigo');

        // Valida o tempo de fala no SERVIDOR (o <input type="time"> do
        // navegador não é garantia): aceita HH:MM ou HH:MM:SS, exige tempo
        // maior que zero e normaliza para HH:MM:SS.
        $tempo_normalizado = null;
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $tempo, $mt)) {
            $horas = (int) $mt[1];
            $minutos = (int) $mt[2];
            $segundos = isset($mt[3]) ? (int) $mt[3] : 0;

            if ($horas < 24 && $minutos < 60 && $segundos < 60
                && ($horas * 3600 + $minutos * 60 + $segundos) > 0) {
                $tempo_normalizado = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
            }
        }

        if ($nome_sala === '' || $tempo === '' || $codigo_sala === '') {
            $erro = t('erro_preencha_todos_campos');
        } elseif (mb_strlen($nome_sala) > 100) {
            $erro = t('erro_nome_longo');
        } elseif (!preg_match('/^[A-Za-z0-9]{4,20}$/', $codigo_sala)) {
            $erro = t('erro_codigo_formato');
        } elseif ($tempo_normalizado === null) {
            $erro = t('erro_tempo_invalido');
        } else {
            try {
                if (db_fetch_one($mysqli, "SELECT id_sala FROM sala WHERE nome_sala = ?", "s", $nome_sala) !== null) {
                    $erro = t('erro_sala_existente');
                } elseif (db_fetch_one($mysqli, "SELECT id_sala FROM sala WHERE codigo_sala = ?", "s", $codigo_sala) !== null) {
                    $erro = t('erro_codigo_em_uso');
                } else {
                    // sala + vínculo com o criador numa transação: nunca fica
                    // uma sala "solta" se o segundo passo falhar
                    $mysqli->begin_transaction();
                    try {
                        db_exec(
                            $mysqli,
                            "INSERT INTO sala (nome_sala, codigo_sala, tempo_de_fala) VALUES (?, ?, ?)",
                            "sss",
                            $nome_sala,
                            $codigo_sala,
                            $tempo_normalizado
                        );
                        $id_sala = (int) $mysqli->insert_id;

                        db_exec(
                            $mysqli,
                            "UPDATE criador SET fk_sala_criada = ? WHERE id_criador = ?",
                            "ii",
                            $id_sala,
                            (int) $_SESSION['id_criador']
                        );

                        $mysqli->commit();
                    } catch (Throwable $e) {
                        $mysqli->rollback();
                        throw $e;
                    }

                    $_SESSION['nome_sala'] = $nome_sala;
                    header("Location: criador.php?id_sala=" . $id_sala);
                    exit();
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    // o UNIQUE de sala.codigo_sala pegou uma corrida: outra
                    // sala com o mesmo código foi criada no mesmo instante
                    $erro = t('erro_codigo_em_uso');
                } else {
                    error_log('criar.php: ' . $e->getMessage());
                    $erro = t('erro_criar_sala');
                }
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
    <title><?php echo t('title_criar_sala'); ?></title>
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
                    <button type="button" class="btn-close" id="btnSair" aria-label="<?php echo te('aria_sair_conta'); ?>"></button>
                    <div class="card-body">
                        <h2 class="text-center fw-bold"><?php echo t('titulo_criar_sala'); ?></h2><br>

                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>

                            <label for="nome" class="form-label"><?php echo t('label_nome_sala'); ?></label>
                            <input name="nome" type="text" class="form-control" id="nome"
                                value="<?php echo htmlspecialchars($nome_sala, ENT_QUOTES, 'UTF-8'); ?>" required /><br>

                            <label for="codigo" class="form-label"><?php echo t('label_codigo_sala'); ?></label>
                            <div class="input-group">
                                <input name="codigo" type="text" class="form-control" id="codigo"
                                    value="<?php echo htmlspecialchars($codigo_sala, ENT_QUOTES, 'UTF-8'); ?>"
                                    required />
                                <button class="btn btn-outline-secondary" type="button" id="copiarCodigo"><?php echo t('btn_copiar_codigo'); ?></button>
                            </div><br>

                            <label for="tempo" class="form-label"><?php echo t('label_tempo_fala'); ?></label>
                            <input name="tempo" type="time" step="1" class="form-control" id="tempo"
                                value="<?php echo htmlspecialchars($tempo, ENT_QUOTES, 'UTF-8'); ?>" required /><br>

                            <div class="d-grid gap-2">
                                <button class="btn btn-dark" name="submit" type="submit"><?php echo t('btn_criar'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>

    <script>
        const textoCopiarCodigoOriginal = <?php echo tj('btn_copiar_codigo'); ?>;
        const textoCopiado = <?php echo tj('texto_copiado'); ?>;

        // "Sair" agora é um POST com token CSRF (antes era um link GET)
        document.getElementById("btnSair").addEventListener("click", () => {
            document.getElementById("formLogout").submit();
        });

        document.getElementById("copiarCodigo").addEventListener("click", () => {
            const campo = document.getElementById("codigo");
            const botao = document.getElementById("copiarCodigo");

            navigator.clipboard.writeText(campo.value).then(() => {
                botao.textContent = textoCopiado;
                setTimeout(() => {
                    botao.textContent = textoCopiarCodigoOriginal;
                }, 1500);
            }).catch(() => {
                // fallback para navegadores/contextos sem permissão de clipboard
                campo.select();
                document.execCommand("copy");
            });
        });
    </script>
</body>

</html>
