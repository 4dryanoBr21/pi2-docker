/* Lógica de troca de idioma */
(function() {
  "use strict";
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".btn-idioma").forEach(function (botao) {
      botao.addEventListener("click", function () {
        document.cookie = "idioma=" + botao.dataset.lang + "; path=/; max-age=" + (60 * 60 * 24 * 365) + "; samesite=lax";
        location.reload();
      });
    });
  });
})();
