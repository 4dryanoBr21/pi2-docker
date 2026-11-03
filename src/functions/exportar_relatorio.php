<?php
// Exporta o relatório da reunião atual (sala aberta pelo criador) em HTML,
// Markdown ou TXT, como download.
//
// Uso: exportar_relatorio.php?id_sala=123&formato=html
//      exportar_relatorio.php?id_sala=123&formato=md
//      exportar_relatorio.php?id_sala=123&formato=txt
//
// Só o criador dono da sala pode exportar (mesma checagem de criador.php).
// É uma rota GET somente leitura (não muda nada no banco), por isso não
// exige csrf_token — o acesso já é protegido pela sessão do criador.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_criador.php';
require_once __DIR__ . '/relatorio.php';

if (!criador_autenticado($mysqli)) {
    http_response_code(403);
    die('Acesso negado.');
}

$id_sala = (int) ($_GET['id_sala'] ?? 0);
if ($id_sala <= 0) {
    http_response_code(400);
    die('Sala não especificada.');
}

if (!criador_eh_dono_da_sala($mysqli, $id_sala)) {
    http_response_code(403);
    die('Você não tem permissão para exportar o relatório desta sala.');
}

$formato = strtolower((string) ($_GET['formato'] ?? 'html'));
$formatos_validos = ['html' => 'html', 'md' => 'md', 'txt' => 'txt'];

if (!isset($formatos_validos[$formato])) {
    http_response_code(400);
    die('Formato inválido. Use html, md ou txt.');
}

$relatorio = montar_relatorio_sala($mysqli, $id_sala);
if ($relatorio === null) {
    http_response_code(404);
    die('Sala não encontrada.');
}

switch ($formato) {
    case 'md':
        $conteudo = gerar_relatorio_markdown($relatorio);
        $content_type = 'text/markdown; charset=utf-8';
        break;
    case 'txt':
        $conteudo = gerar_relatorio_txt($relatorio);
        $content_type = 'text/plain; charset=utf-8';
        break;
    case 'html':
    default:
        $conteudo = gerar_relatorio_html($relatorio);
        $content_type = 'text/html; charset=utf-8';
        break;
}

// Nome de arquivo seguro: só letras/números/hífen/underscore a partir do
// nome da sala, com fallback genérico se ficar vazio.
$nome_base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $relatorio['nome_sala']);
$nome_base = trim($nome_base, '_');
if ($nome_base === '') {
    $nome_base = 'reuniao';
}
$nome_arquivo = sprintf('relatorio_%s_%s.%s', $nome_base, date('Ymd_His'), $formato);

header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Content-Length: ' . strlen($conteudo));
header('Cache-Control: no-store');
echo $conteudo;
exit;
