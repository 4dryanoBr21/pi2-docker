/* Painel "Limites da sala" da tela do criador (criador.php).
 * Salva os limites de participantes e de pautas (mãos levantadas) da sala
 * já criada, sem recarregar a página. 0 ou vazio = sem limite.
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var config = document.getElementById("app-config");
    var btnAbrir = document.getElementById("btnLimites");
    var painel = document.getElementById("painelLimites");
    var form = document.getElementById("formLimites");
    var aviso = document.getElementById("avisoLimites");
    var campoParticipantes = document.getElementById("maxParticipantes");
    var campoFila = document.getElementById("maxFila");

    if (!config || !btnAbrir || !painel || !form || !aviso || !campoParticipantes || !campoFila) return;

    var textos = {};
    try {
      textos = JSON.parse(config.dataset.textos || "{}");
    } catch (e) {
      textos = {};
    }

    function avisar(mensagem, ehErro) {
      aviso.textContent = mensagem;
      aviso.classList.remove("d-none");
      aviso.classList.toggle("alert-danger", !!ehErro);
      aviso.classList.toggle("alert-success", !ehErro);
    }

    btnAbrir.addEventListener("click", function () {
      var abrir = painel.classList.contains("d-none");
      painel.classList.toggle("d-none", !abrir);
      btnAbrir.setAttribute("aria-expanded", abrir ? "true" : "false");
      if (!abrir) aviso.classList.add("d-none");
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      var botao = form.querySelector("button[type=submit]");
      if (botao) botao.disabled = true;

      var dados = new FormData();
      dados.append("id_sala", config.dataset.idSala);
      dados.append("csrf_token", config.dataset.csrf);
      dados.append("max_participantes", campoParticipantes.value);
      dados.append("max_fila", campoFila.value);

      fetch("../functions/atualizar_limites.php", { method: "POST", body: dados })
        .then(function (res) {
          return res.json().catch(function () { return {}; }).then(function (corpo) {
            return { ok: res.ok && corpo.ok === true, corpo: corpo };
          });
        })
        .then(function (r) {
          if (r.ok) {
            // Mostra o valor que o servidor realmente guardou (0 vira campo vazio).
            campoParticipantes.value = r.corpo.max_participantes > 0 ? r.corpo.max_participantes : "";
            campoFila.value = r.corpo.max_fila > 0 ? r.corpo.max_fila : "";
            avisar(textos.limitesSalvos || "", false);
          } else if (r.corpo.erro === "limite_invalido") {
            avisar(textos.erroLimiteInvalido || "", true);
          } else {
            avisar(textos.erroSalvarLimites || "", true);
          }
        })
        .catch(function () {
          avisar(textos.erroSalvarLimites || "", true);
        })
        .then(function () {
          if (botao) botao.disabled = false;
        });
    });
  });
})();
