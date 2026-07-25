<?php
include("../functions/conexao.php");
session_start();


if (!isset($_SESSION['id_criador'])) {
    header('Location: login.php');
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

$codigo_sala = gerar_codigo_sala_aleatorio();

if (isset($_POST['submit'])) {
    $nome_sala = trim($_POST['nome'] ?? '');
    $tempo = trim($_POST['tempo'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');

    if ($nome_sala === '' || $tempo === '' || $codigo === '') {
        echo "Por favor preencha todos os campos.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO sala (nome_sala, codigo_sala, tempo_de_fala) VALUES (?, ?, ?)");
        if ($stmt === false) {
            echo "Erro interno. Tente novamente.";
            exit();
        }

        $stmt->bind_param("sss", $nome_sala, $codigo, $tempo);
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
            echo "Erro ao criar sala. Tente novamente.";
            $stmt->close();
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
                    <img src="../img/MI_legenda.png" class="rounded" alt="Logo" style="height: 200px;">
                </div>
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center fw-bold">Criar Sala</h2>
                        <form action="" method="POST">
                            <label for="nome" class="form-label">Nome da Sala</label>
                            <input name="nome" type="text" class="form-control" id="nome" required /><br>

                            <label for="codigo" class="form-label">Código da Sala</label>
                            <input name="codigo" type="text" class="form-control" id="codigo" value="<?php echo htmlspecialchars($codigo_sala, ENT_QUOTES); ?>" required /><br>

                            <label for="tempo" class="form-label">Tempo de fala dos participantes</label>
                            <input name="tempo" type="time" class="form-control" id="tempo" required /><br>

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
</body>

</html>