/* Painel "Exportar relatório" da tela do criador (criador.php).
 * Só alterna a visibilidade dos links de download (HTML/Markdown/TXT) —
 * os links em si apontam direto para exportar_relatorio.php, que faz o
 * download sem precisar de JS (mesmo padrão de link simples usado no resto
 * do app).
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var btnAbrir = document.getElementById("btnExportarRelatorio");
    var painel = document.getElementById("painelExportarRelatorio");

    if (!btnAbrir || !painel) return;

    btnAbrir.addEventListener("click", function () {
      var abrir = painel.classList.contains("d-none");
      painel.classList.toggle("d-none", !abrir);
      btnAbrir.setAttribute("aria-expanded", abrir ? "true" : "false");
    });
  });
})();
