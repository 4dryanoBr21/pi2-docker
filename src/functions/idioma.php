<?php
// Sistema simples de internacionalização (i18n) do ME INSCREVO.
// Idioma é guardado num cookie ('idioma') que persiste por 1 ano.
// Padrão: português do Brasil ('pt').

if (!defined('IDIOMAS_DISPONIVEIS')) {
    define('IDIOMAS_DISPONIVEIS', ['pt', 'es']);
}

if (isset($_COOKIE['idioma']) && in_array($_COOKIE['idioma'], IDIOMAS_DISPONIVEIS, true)) {
    $GLOBALS['idioma_atual'] = $_COOKIE['idioma'];
} else {
    $GLOBALS['idioma_atual'] = 'pt';
}

$GLOBALS['traducoes'] = require __DIR__ . '/../lang/' . $GLOBALS['idioma_atual'] . '.php';

/**
 * Retorna o texto traduzido para a chave informada, no idioma atual.
 * Se $args for informado, o texto é passado por sprintf (útil para
 * textos com %s/%d, como "Você é o %dº da fila").
 */
function t(string $chave, ...$args): string
{
    $texto = $GLOBALS['traducoes'][$chave] ?? $chave;
    return $args ? vsprintf($texto, $args) : $texto;
}

/**
 * Mesma coisa que t(), mas pronta para uso dentro de atributos/textos
 * HTML já com escaping aplicado (uso em value="", placeholder, etc.).
 */
function te(string $chave, ...$args): string
{
    return htmlspecialchars(t($chave, ...$args), ENT_QUOTES, 'UTF-8');
}

/**
 * Mesma coisa que t(), mas segura para uso dentro de strings JavaScript
 * (usado em blocos <script> para textos que mudam dinamicamente via JS).
 */
function tj(string $chave, ...$args): string
{
    return json_encode(t($chave, ...$args), JSON_UNESCAPED_UNICODE);
}

/**
 * Imprime os dois botões de troca de idioma (bandeira do Brasil para
 * português, bandeira da Argentina para espanhol). Ao clicar, grava o
 * cookie 'idioma' e recarrega a página atual.
 */
function idioma_switch_html(): void
{
    $atual = $GLOBALS['idioma_atual'];
?>
    <div class="idioma-switch" role="group" aria-label="Selecionar idioma / Seleccionar idioma">
        <button type="button" class="btn-idioma<?php echo $atual === 'pt' ? ' ativo' : ''; ?>"
            data-lang="pt" title="<?php echo te('idioma_pt_titulo'); ?>"
            aria-label="<?php echo te('idioma_pt_titulo'); ?>">🇧🇷</button>
        <button type="button" class="btn-idioma<?php echo $atual === 'es' ? ' ativo' : ''; ?>"
            data-lang="es" title="<?php echo te('idioma_es_titulo'); ?>"
            aria-label="<?php echo te('idioma_es_titulo'); ?>">🇩🇪</button>
    </div>
    <script>
        document.querySelectorAll(".btn-idioma").forEach(function (botao) {
            botao.addEventListener("click", function () {
                document.cookie = "idioma=" + botao.dataset.lang + "; path=/; max-age=" + (60 * 60 * 24 * 365) + "; samesite=lax";
                location.reload();
            });
        });
    </script>
<?php
}
