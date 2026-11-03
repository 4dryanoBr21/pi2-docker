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

        // "Olhinho" de mostrar/ocultar senha (login.php e register.php).
        // Funciona pra qualquer input de senha: basta um botão com a classe
        // .btn-toggle-senha, data-target apontando pro id do campo, e
        // data-label-mostrar/data-label-ocultar com os textos já traduzidos
        // (vêm do PHP via t(), não há necessidade de #app-config aqui).
        document.querySelectorAll(".btn-toggle-senha").forEach((botao) => {
            const campo = document.getElementById(botao.dataset.target);
            if (!campo) return;

            const labelMostrar = botao.dataset.labelMostrar || "Mostrar senha";
            const labelOcultar = botao.dataset.labelOcultar || "Ocultar senha";

            botao.addEventListener("click", () => {
                const vaiMostrar = campo.type === "password";
                campo.type = vaiMostrar ? "text" : "password";
                botao.textContent = vaiMostrar ? "🙈" : "👁️";
                botao.setAttribute("aria-label", vaiMostrar ? labelOcultar : labelMostrar);
                botao.setAttribute("aria-pressed", vaiMostrar ? "true" : "false");
            });
        });

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
