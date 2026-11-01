/* Lógica de autenticação e navegação global */
(function() {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        // Logout via POST com token CSRF
        const btnSair = document.getElementById("btnSair");
        const formLogout = document.getElementById("formLogout");
        if (btnSair && formLogout) {
            btnSair.addEventListener("click", () => {
                formLogout.submit();
            });
        }

        // Redirecionamento para registro
        const btnCad = document.getElementById("cad");
        if (btnCad) {
            btnCad.addEventListener("click", () => {
                window.open("register.php", "_self");
            });
        }

        // Lógica de Copiar Código (página criar.php)
        const btnCopiar = document.getElementById("copiarCodigo");
        if (btnCopiar) {
            btnCopiar.addEventListener("click", () => {
                const campo = document.getElementById("codigo");
                const config = document.getElementById("app-config");
                const textos = config ? JSON.parse(config.dataset.textos || "{}") : {};

                const textoCopiarOriginal = textos.btn_copiar_codigo || "Copiar";
                const textoCopiado = textos.texto_copiado || "Copiado!";

                navigator.clipboard.writeText(campo.value).then(() => {
                    btnCopiar.textContent = textoCopiado;
                    setTimeout(() => {
                        btnCopiar.textContent = textoCopiarOriginal;
                    }, 1500);
                }).catch(() => {
                    campo.select();
                    document.execCommand("copy");
                });
            });
        }
    });
})();
