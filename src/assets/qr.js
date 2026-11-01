/* QR code da tela "Criar sala" (criar.php).
 *
 * O QR leva o participante à tela inicial (index.php) com o código da sala já
 * digitado: <endereço do sistema>/index.php?codigo=ABC123
 *
 * Tudo acontece no navegador, com a biblioteca assets/vendor/qrcode.js (sem
 * serviço externo e sem script inline, por causa da CSP). O QR é desenhado num
 * <canvas> grande e nítido, pensado para telão, e pode ser salvo como PNG.
 */
(function () {
  "use strict";

  // Mesmo formato que criar.php exige para o código da sala.
  var PADRAO_CODIGO = /^[A-Za-z0-9]{4,20}$/;
  var MODULOS_MARGEM = 4;   // margem branca ("quiet zone") exigida pelo padrão QR
  var LARGURA_ALVO = 1000;  // px aproximados do QR no PNG salvo

  document.addEventListener("DOMContentLoaded", function () {
    var btnGerar = document.getElementById("btnGerarQr");
    var painel = document.getElementById("painelQr");
    var campoCodigo = document.getElementById("codigo");
    var campoEndereco = document.getElementById("enderecoQr");
    var aviso = document.getElementById("avisoQr");
    var area = document.getElementById("areaQr");
    var canvas = document.getElementById("canvasQr");
    var btnSalvar = document.getElementById("btnSalvarQr");
    var config = document.getElementById("app-config");

    if (!btnGerar || !painel || !campoCodigo || !campoEndereco || !aviso || !area || !canvas || !btnSalvar
      || typeof qrcode !== "function") {
      return;
    }

    var textos = {};
    try {
      textos = JSON.parse((config && config.dataset.textos) || "{}");
    } catch (e) {
      textos = {};
    }

    var codigoDesenhado = null; // código do QR que está no canvas (usado no nome do arquivo)

    // Endereço padrão: a tela inicial deste mesmo sistema (criar.php fica em /pages/).
    campoEndereco.value = new URL("../index.php", window.location.href).href;

    function limparAviso() {
      aviso.textContent = "";
      aviso.classList.add("d-none");
    }

    function avisar(mensagem, ehErro) {
      aviso.textContent = mensagem;
      aviso.classList.remove("d-none");
      aviso.classList.toggle("alert-danger", !!ehErro);
      aviso.classList.toggle("alert-warning", !ehErro);
    }

    function esconderQr() {
      area.classList.add("d-none");
      codigoDesenhado = null;
    }

    // Endereço + ?codigo=XXXX (mantém outros parâmetros do endereço, se houver).
    function montarUrl(codigo) {
      var url;
      try {
        url = new URL(campoEndereco.value.trim());
      } catch (e) {
        return null;
      }
      if (url.protocol !== "http:" && url.protocol !== "https:") return null;
      url.searchParams.set("codigo", codigo);
      url.hash = "";
      return url;
    }

    function desenhar(qr, codigo) {
      var n = qr.getModuleCount();
      var total = n + MODULOS_MARGEM * 2;
      // Módulo com tamanho inteiro em pixels: bordas nítidas, sem borrar.
      var modulo = Math.max(6, Math.floor(LARGURA_ALVO / total));
      var lado = modulo * total;
      var alturaLegenda = Math.round(lado * 0.14);

      canvas.width = lado;
      canvas.height = lado + alturaLegenda;

      var ctx = canvas.getContext("2d");
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      ctx.fillStyle = "#000000";
      for (var r = 0; r < n; r++) {
        for (var c = 0; c < n; c++) {
          if (qr.isDark(r, c)) {
            ctx.fillRect((c + MODULOS_MARGEM) * modulo, (r + MODULOS_MARGEM) * modulo, modulo, modulo);
          }
        }
      }

      // Legenda com o código, para quem não conseguir ler o QR.
      var texto = ((textos.legenda_qr_codigo || "") + " " + codigo).trim();
      var tamanho = Math.round(alturaLegenda * 0.55);
      var fonte = function (px) { return "bold " + px + "px Montserrat, Arial, sans-serif"; };
      ctx.font = fonte(tamanho);
      while (ctx.measureText(texto).width > lado * 0.9 && tamanho > 12) {
        tamanho -= 2;
        ctx.font = fonte(tamanho);
      }
      ctx.fillStyle = "#000000";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(texto, lado / 2, lado + alturaLegenda / 2 - modulo);
    }

    function atualizar() {
      limparAviso();

      var codigo = campoCodigo.value.trim();
      if (!PADRAO_CODIGO.test(codigo)) {
        esconderQr();
        avisar(textos.erro_qr_codigo || "", true);
        return;
      }

      var url = montarUrl(codigo);
      if (url === null) {
        esconderQr();
        avisar(textos.erro_qr_endereco || "", true);
        return;
      }

      var qr;
      try {
        qr = qrcode(0, "M"); // tamanho automático, correção de erro média
        qr.addData(url.href);
        qr.make();
      } catch (e) {
        esconderQr();
        avisar(textos.erro_qr_endereco || "", true);
        return;
      }

      desenhar(qr, codigo);
      codigoDesenhado = codigo;
      canvas.setAttribute("aria-label", (textos.aria_qr_canvas || "").replace("%s", codigo));
      area.classList.remove("d-none");

      // "localhost" só abre no computador do próprio criador.
      if (/^(localhost|127\.|\[::1\]$)/i.test(url.hostname)) {
        avisar(textos.aviso_qr_localhost || "", false);
      }
    }

    function salvar() {
      if (codigoDesenhado === null) return;
      var nomeArquivo = "qrcode-sala-" + codigoDesenhado + ".png";

      function baixar(href, liberar) {
        var a = document.createElement("a");
        a.href = href;
        a.download = nomeArquivo;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        if (liberar) setTimeout(function () { URL.revokeObjectURL(href); }, 2000);
      }

      if (canvas.toBlob) {
        canvas.toBlob(function (blob) {
          if (blob) baixar(URL.createObjectURL(blob), true);
        }, "image/png");
      } else {
        baixar(canvas.toDataURL("image/png"), false);
      }
    }

    function painelAberto() {
      return !painel.classList.contains("d-none");
    }

    btnGerar.addEventListener("click", function () {
      var abrir = !painelAberto();
      painel.classList.toggle("d-none", !abrir);
      btnGerar.setAttribute("aria-expanded", abrir ? "true" : "false");
      btnGerar.textContent = abrir ? (textos.btn_ocultar_qr || "") : (textos.btn_gerar_qr || "");
      if (abrir) atualizar();
    });

    // O QR acompanha o que o criador digita (código e endereço).
    campoCodigo.addEventListener("input", function () { if (painelAberto()) atualizar(); });
    campoEndereco.addEventListener("input", function () { if (painelAberto()) atualizar(); });

    // Enter no campo de endereço não pode enviar o formulário (criaria a sala).
    campoEndereco.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        atualizar();
      }
    });

    btnSalvar.addEventListener("click", salvar);
  });
})();
