<?php
include('../functions/conexao.php');

session_start();

if (!isset($_GET['id_sala'])) {
  die("Sala não especificada. <a href='criar.php'>Voltar</a>");
}

$id_sala = intval($_GET['id_sala']);

// registra início se ainda não tiver valor
$mysqli->query("UPDATE sala SET data_inicio = NOW() WHERE id_sala = $id_sala AND data_inicio IS NULL");

$sql = "SELECT * FROM sala WHERE id_sala = $id_sala";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $nome_sala = htmlspecialchars($row['nome_sala']);
  $tempo_fala = htmlspecialchars($row['tempo_de_fala']);
} else {
  die("Sala não encontrada. <a href='criar.php'>Voltar</a>");
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
  <title>ME INSCREVO - Sala <?php echo $nome_sala; ?></title>
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
          <button type="button" class="btn-close" aria-label="Close"></button>
          <h2 class="text-center fw-bold"><?php echo $nome_sala; ?></h2>
          <div class="card-body">
            <form>
              <div id="listaUsuarios" class="d-grid gap-2 overflow-auto shadow p-3 mb-5 bg-body-tertiary rounded"
                style="height: 200px;">
              </div>
              <div class="d-grid gap-2">
                <button id="relogio" class="btn" type="button" style="font-size: 75px;">⏰</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-md-4"></div>
    </div>
  </div>

  <script>
    document.querySelector(".btn-close").addEventListener("click", function() {

      const idSala = <?php echo $id_sala; ?>;
      const form = new FormData();
      form.append("id_sala", idSala);

      fetch("../functions/fechar_sala.php", {
          method: "POST",
          body: form
        })
        .then(res => res.text())
        .then(ret => {
          if (ret.trim() === "ok") {
            window.location.href = "criar.php";
          } else {
            alert("Erro ao fechar a sala.");
          }
        });
    });
  </script>


  <script>
    function atualizarUsuarios() {
      fetch("../functions/get_usuarios.php?id_sala=<?php echo $id_sala; ?>")
        .then(res => res.text())
        .then(html => {
          document.getElementById("listaUsuarios").innerHTML = html;
        })
        .catch(err => console.error("Erro ao buscar usuários:", err));
    }

    setInterval(atualizarUsuarios, 1000);
    atualizarUsuarios();
  </script>


  <script>
    const emoji = document.getElementById("relogio");

    emoji.addEventListener("click", () => {
      emoji.textContent = emoji.textContent === "⏰" ? "❌" : "⏰";
    });
  </script>
</body>

</html>