/* Lógica de salas - Interações de tempo e fila */
(function (global) {
  "use strict";

  function initSala() {
    const config = document.getElementById("app-config");
    if (!config) return;

    const idSala = parseInt(config.dataset.idSala);
    const idParticipante = config.dataset.idParticipante ? parseInt(config.dataset.idParticipante) : null;
    const csrfToken = config.dataset.csrf;
    const isCriador = config.dataset.isCriador === "true";

    let textos = {};
    try {
      textos = JSON.parse(config.dataset.textos || "{}");
    } catch (e) {
      console.error("Erro ao parsear textos da sala", e);
    }

    let fimFalaMs = null;
    let maoLevantada = false;
    let speakerAtualId = null;
    let enviandoMao = false;
    let inicioReuniaoMs = null;
    let avancando = false;
    let ultimoAvancoMs = 0;

    const TOLERANCIA_MS = 1500;

    // Mostra/esconde com a classe "d-none" do Bootstrap. Não usa style.display:
    // "d-none" tem !important e ganharia de qualquer valor definido por style.
    function mostrar(el, visivel) {
      if (el) el.classList.toggle("d-none", !visivel);
    }

    function formatarPosicaoFila(posicao, total) {
      if (!textos.posicaoFila) return "";
      let indice = 0;
      const valores = [posicao, total];
      return textos.posicaoFila.replace(/%d/g, () => valores[indice++]);
    }

    function desenharRelogios() {
      if (inicioReuniaoMs !== null) {
        const decorrido = Math.floor((Date.now() - inicioReuniaoMs) / 1000);
        const el = document.getElementById("tempoReuniao");
        if (el) el.textContent = MI.formatarTempo(decorrido);
      }
      if (fimFalaMs !== null) {
        const restante = Math.max(0, Math.ceil((fimFalaMs - Date.now()) / 1000));
        const el = document.getElementById("contadorFala");
        if (el) el.textContent = MI.formatarTempo(restante);
        if (restante === 0 && isCriador) {
          avancarFala();
        }
      }
    }

    function atualizarEstado() {
      fetch("../functions/estado_sala.php?id_sala=" + idSala, { cache: "no-store" })
        .then(res => res.json())
        .then(estado => {
          if (estado.erro) {
            window.location.href = isCriador ? "criar.php" : "../index.php";
            return;
          }

          const agora = Date.now();

          if (isCriador) {
            const novoInicio = agora - estado.reuniao_segundos * 1000;
            if (inicioReuniaoMs === null || Math.abs(novoInicio - inicioReuniaoMs) > TOLERANCIA_MS) {
              inicioReuniaoMs = novoInicio;
            }

            if (estado.falando) {
              const semF = document.getElementById("semFalante");
              const comF = document.getElementById("comFalante");
              mostrar(semF, false);
              mostrar(comF, true);
              const nomeF = document.getElementById("nomeFalante");
              if (nomeF) nomeF.textContent = estado.falando.nome;

              const novoFim = agora + estado.falando.restante_segundos * 1000;
              if (speakerAtualId !== estado.falando.id_participante || fimFalaMs === null
                || Math.abs(novoFim - fimFalaMs) > TOLERANCIA_MS) {
                speakerAtualId = estado.falando.id_participante;
                fimFalaMs = novoFim;
              }
              const btnP = document.getElementById("btnProximo");
              if (btnP) btnP.textContent = textos.passarVez || "";
            } else {
              const semF = document.getElementById("semFalante");
              const comF = document.getElementById("comFalante");
              mostrar(semF, true);
              mostrar(comF, false);
              speakerAtualId = null;
              fimFalaMs = null;
              const btnP = document.getElementById("btnProximo");
              if (btnP) btnP.textContent = textos.iniciarProximo || "";
            }
          } else if (idParticipante) {
            const souEu = estado.falando && estado.falando.id_participante === idParticipante;
            const maoEl = document.getElementById("mao");

            const estA = document.getElementById("estadoAguardando");
            const estF = document.getElementById("estadoFalando");

            if (souEu) {
              mostrar(estA, false);
              mostrar(estF, true);
              const novoFim = agora + estado.falando.restante_segundos * 1000;
              if (speakerAtualId !== estado.falando.id_participante || fimFalaMs === null
                || Math.abs(novoFim - fimFalaMs) > TOLERANCIA_MS) {
                speakerAtualId = estado.falando.id_participante;
                fimFalaMs = novoFim;
              }
              maoLevantada = false;
            } else {
              mostrar(estA, true);
              mostrar(estF, false);
              speakerAtualId = null;
              fimFalaMs = null;
              const pos = estado.fila.findIndex(p => p.id_participante === idParticipante);
              const posEl = document.getElementById("posicaoFila");
              if (pos === -1) {
                if (posEl) posEl.textContent = textos.levanteAMao || "";
                maoLevantada = false;
              } else {
                if (posEl) posEl.textContent = formatarPosicaoFila(pos + 1, estado.fila.length);
                maoLevantada = true;
              }
            }

            if (maoEl) {
              maoEl.textContent = (maoLevantada || (estado.falando && estado.falando.id_participante === idParticipante)) ? "❌" : "🤚";
              maoEl.setAttribute("aria-label", (estado.falando && estado.falando.id_participante === idParticipante) ? (textos.encerrarFala || "") : (maoLevantada ? (textos.abaixarMao || "") : (textos.levantarMao || "")));
            }
          }

          const list = document.getElementById("listaPresentes");
          if (list) {
            MI.renderizarPresentes(list, estado, {
              textoVazio: textos.nenhumParticipanteAinda || "",
              idPropio: idParticipante,
              sufixoVoce: textos.voceSufixo || ""
            });
          }

          desenharRelogios();
        })
        .catch(err => console.error("Erro ao buscar estado da sala:", err));
    }

    function avancarFala() {
      const agora = Date.now();
      if (avancando || agora - ultimoAvancoMs < 1500) return;
      avancando = true;
      ultimoAvancoMs = agora;

      const form = new FormData();
      form.append("id_sala", idSala);
      form.append("csrf_token", csrfToken);
      form.append("falando_atual", speakerAtualId === null ? 0 : speakerAtualId);

      fetch("../functions/avancar_fala.php", { method: "POST", body: form })
        .then(() => {
          avancando = false;
          atualizarEstado();
        })
        .catch(() => { avancando = false; });
    }

    const btnProximo = document.getElementById("btnProximo");
    if (btnProximo) btnProximo.addEventListener("click", avancarFala);

    const btnMao = document.getElementById("mao");
    if (btnMao) {
      btnMao.addEventListener("click", () => {
        if (enviandoMao) return;
        enviandoMao = true;
        fetch("../functions/salvar_hora.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "id_participante=" + idParticipante + "&csrf_token=" + encodeURIComponent(csrfToken)
        })
        .then(() => { enviandoMao = false; atualizarEstado(); })
        .catch(err => { enviandoMao = false; console.error(err); });
      });
    }

    const btnSair = document.getElementById("btnSair");
    if (btnSair) {
      btnSair.addEventListener("click", () => {
        const formData = new FormData();
        formData.append("id_participante", idParticipante);
        formData.append("csrf_token", csrfToken);
        fetch("../functions/sair_sala.php", { method: "POST", body: formData })
          .then(res => res.text())
          .then(ret => {
            if (ret.trim() === "ok") window.location.href = "../index.php";
            else alert(textos.erroSairSala || "");
          })
          .catch(err => console.error(err));
      });
    }

    const btnEncerrar = document.getElementById("btnEncerrarSala");
    if (btnEncerrar) {
      btnEncerrar.addEventListener("click", () => {
        if (!confirm(textos.confirmarEncerrarSala || "")) return;
        const form = new FormData();
        form.append("id_sala", idSala);
        form.append("csrf_token", csrfToken);
        fetch("../functions/fechar_sala.php", { method: "POST", body: form })
          .then(res => res.text())
          .then(ret => {
            if (ret.trim() === "ok") window.location.href = "criar.php";
            else alert(textos.erroFecharSala || "");
          })
          .catch(err => console.error(err));
      });
    }

    const btnSairConta = document.getElementById("btnSairConta");
    if (btnSairConta) {
      btnSairConta.addEventListener("click", () => {
        if (confirm(textos.confirmarSairConta || "")) {
          const formLogout = document.getElementById("formLogout");
          if (formLogout) formLogout.submit();
        }
      });
    }

    setInterval(desenharRelogios, 250);
    setInterval(atualizarEstado, 2000);
    atualizarEstado();
  }

  global.initSala = initSala;
})(window);
