/* Funções compartilhadas entre a tela do criador e a do participante. */
(function (global) {
  "use strict";

  /**
   * Segundos -> "MM:SS" (ou "H:MM:SS" a partir de 1 hora).
   * Antes o tempo era cortado em 59:59, o que escondia reuniões mais longas.
   */
  function formatarTempo(totalSegundos) {
    var t = Math.max(0, Math.floor(totalSegundos));
    var h = Math.floor(t / 3600);
    var m = Math.floor((t % 3600) / 60);
    var s = t % 60;
    var mm = String(m).padStart(2, "0");
    var ss = String(s).padStart(2, "0");
    return h > 0 ? h + ":" + mm + ":" + ss : mm + ":" + ss;
  }

  /**
   * Desenha a lista de participantes presentes dentro de `container`.
   * Usa textContent (nunca innerHTML com dados do usuário), então um nome
   * como "<script>" aparece como texto e não é executado.
   *
   * opcoes: { textoVazio, idPropio, sufixoVoce }
   */
  function renderizarPresentes(container, estado, opcoes) {
    opcoes = opcoes || {};
    container.innerHTML = "";

    if (estado.presentes.length === 0) {
      var vazio = document.createElement("p");
      vazio.textContent = opcoes.textoVazio || "";
      container.appendChild(vazio);
      return;
    }

    var idsNaFila = new Set(estado.fila.map(function (p) { return p.id_participante; }));

    estado.presentes.forEach(function (p) {
      var item = document.createElement("p");
      var marcador = "";

      if (estado.falando && estado.falando.id_participante === p.id_participante) {
        marcador = " 🎙️";
      } else if (idsNaFila.has(p.id_participante)) {
        marcador = " 🤚";
      }

      var voce = (opcoes.idPropio !== undefined && p.id_participante === opcoes.idPropio)
        ? (opcoes.sufixoVoce || "")
        : "";

      item.textContent = p.nome + voce + marcador;
      container.appendChild(item);
    });
  }

  global.MI = {
    formatarTempo: formatarTempo,
    renderizarPresentes: renderizarPresentes
  };
})(window);
