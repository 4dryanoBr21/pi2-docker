<?php
// Relatório exportável da reunião atual (criador.php -> "Exportar relatório").
//
// Reúne os dados pedidos (sala, duração, participantes, destaques) e sabe
// gerar o relatório em HTML, Markdown e TXT a partir do MESMO array de
// dados — só muda o formato de saída, a lógica de montagem é uma só.
//
// Fonte dos números: tempo_falado_segundos e pautas_total são acumulados em
// avancar_fala_sala() (ver sala_helpers.php) toda vez que a palavra passa de
// um participante para o próximo. Participantes que já saíram da sala não
// aparecem aqui (ver nota em remover_participante()).

require_once __DIR__ . '/util.php';

/**
 * Monta todos os dados do relatório da sala informada.
 *
 * Retorna null se a sala não existir. Caso contrário, retorna um array com:
 *   - nome_sala, codigo_sala
 *   - duracao_segundos, duracao_formatada   (desde sala.data_inicio até agora;
 *                                             0 se a reunião ainda não começou)
 *   - participantes: lista ordenada por nome, cada um com
 *       id, nome, tempo_falado_segundos, tempo_falado_formatado, pautas_total
 *   - total_participantes
 *   - destaque_tempo: participante com mais tempo_falado_segundos (ou null
 *     se ninguém falou ainda / sala vazia)
 *   - destaque_pautas: participante com mais pautas_total (mesma regra)
 *   - gerado_em: timestamp (string) de quando o relatório foi gerado
 *
 * Em caso de empate, fica o primeiro encontrado (ordem alfabética pelo
 * nome, já que é assim que $participantes é ordenado).
 */
function montar_relatorio_sala(mysqli $mysqli, int $id_sala): ?array
{
    $sala = db_fetch_one(
        $mysqli,
        "SELECT nome_sala, codigo_sala, data_inicio FROM sala WHERE id_sala = ?",
        "i",
        $id_sala
    );

    if ($sala === null) {
        return null;
    }

    $duracao_segundos = 0;
    if (!empty($sala['data_inicio'])) {
        $inicio = new DateTime($sala['data_inicio']);
        $agora = new DateTime();
        $duracao_segundos = max(0, $agora->getTimestamp() - $inicio->getTimestamp());
    }

    $linhas = db_fetch_all(
        $mysqli,
        "SELECT id_participante, nome_participante, tempo_falado_segundos, pautas_total
           FROM participante
          WHERE fk_sala_atual = ?
          ORDER BY nome_participante ASC",
        "i",
        $id_sala
    );

    $participantes = [];
    $destaque_tempo = null;
    $destaque_pautas = null;

    foreach ($linhas as $linha) {
        $item = [
            'id' => (int) $linha['id_participante'],
            'nome' => (string) $linha['nome_participante'],
            'tempo_falado_segundos' => (int) $linha['tempo_falado_segundos'],
            'tempo_falado_formatado' => formatar_duracao((int) $linha['tempo_falado_segundos']),
            'pautas_total' => (int) $linha['pautas_total'],
        ];
        $participantes[] = $item;

        if ($item['tempo_falado_segundos'] > 0
            && ($destaque_tempo === null || $item['tempo_falado_segundos'] > $destaque_tempo['tempo_falado_segundos'])) {
            $destaque_tempo = $item;
        }
        if ($item['pautas_total'] > 0
            && ($destaque_pautas === null || $item['pautas_total'] > $destaque_pautas['pautas_total'])) {
            $destaque_pautas = $item;
        }
    }

    return [
        'nome_sala' => (string) $sala['nome_sala'],
        'codigo_sala' => (string) $sala['codigo_sala'],
        'duracao_segundos' => $duracao_segundos,
        'duracao_formatada' => formatar_duracao($duracao_segundos),
        'participantes' => $participantes,
        'total_participantes' => count($participantes),
        'destaque_tempo' => $destaque_tempo,
        'destaque_pautas' => $destaque_pautas,
        'gerado_em' => date('d/m/Y H:i:s'),
    ];
}

/**
 * Formata uma duração em segundos como "HHh MMmin SSs" (omite horas/minutos
 * quando são zero, pra não poluir relatórios de reuniões curtas).
 * Ex.: 45 -> "45s" | 125 -> "2min 5s" | 3725 -> "1h 2min 5s"
 */
function formatar_duracao(int $segundos): string
{
    $segundos = max(0, $segundos);
    $h = intdiv($segundos, 3600);
    $m = intdiv($segundos % 3600, 60);
    $s = $segundos % 60;

    $partes = [];
    if ($h > 0) {
        $partes[] = $h . 'h';
    }
    if ($m > 0 || $h > 0) {
        $partes[] = $m . 'min';
    }
    $partes[] = $s . 's';

    return implode(' ', $partes);
}

/**
 * Relatório em HTML — documento autocontido (com um CSS mínimo embutido),
 * pronto para abrir em qualquer navegador ou anexar por e-mail.
 */
function gerar_relatorio_html(array $r): string
{
    $nome_sala = htmlspecialchars($r['nome_sala'], ENT_QUOTES, 'UTF-8');
    $codigo_sala = htmlspecialchars($r['codigo_sala'], ENT_QUOTES, 'UTF-8');

    $linhas_tabela = '';
    foreach ($r['participantes'] as $p) {
        $nome = htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8');
        $linhas_tabela .= "      <tr>\n"
            . "        <td>{$nome}</td>\n"
            . "        <td>{$p['tempo_falado_formatado']}</td>\n"
            . "        <td>{$p['pautas_total']}</td>\n"
            . "      </tr>\n";
    }
    if ($linhas_tabela === '') {
        $linhas_tabela = "      <tr><td colspan=\"3\">Nenhum participante na sala.</td></tr>\n";
    }

    $destaque_tempo_html = $r['destaque_tempo'] !== null
        ? htmlspecialchars($r['destaque_tempo']['nome'], ENT_QUOTES, 'UTF-8') . ' (' . $r['destaque_tempo']['tempo_falado_formatado'] . ')'
        : '—';
    $destaque_pautas_html = $r['destaque_pautas'] !== null
        ? htmlspecialchars($r['destaque_pautas']['nome'], ENT_QUOTES, 'UTF-8') . ' (' . $r['destaque_pautas']['pautas_total'] . ' pauta(s))'
        : '—';

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório da reunião - {$nome_sala}</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; margin: 2rem; color: #222; }
  h1 { margin-bottom: 0; }
  .subtitulo { color: #666; margin-top: 0.25rem; }
  .resumo { display: flex; gap: 1.5rem; flex-wrap: wrap; margin: 1.5rem 0; }
  .resumo .cartao { background: #f4f4f4; border-radius: 8px; padding: 0.75rem 1rem; min-width: 180px; }
  .resumo .cartao strong { display: block; font-size: 0.85rem; color: #555; }
  table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
  th, td { border: 1px solid #ddd; padding: 0.5rem 0.75rem; text-align: left; }
  th { background: #222; color: #fff; }
  .destaques { margin-top: 1.5rem; }
  .destaques li { margin-bottom: 0.25rem; }
  footer { margin-top: 2rem; font-size: 0.8rem; color: #999; }
</style>
</head>
<body>
  <h1>Relatório da reunião</h1>
  <p class="subtitulo">Sala: <strong>{$nome_sala}</strong> &middot; Código: {$codigo_sala}</p>

  <div class="resumo">
    <div class="cartao"><strong>Duração da reunião</strong>{$r['duracao_formatada']}</div>
    <div class="cartao"><strong>Participantes</strong>{$r['total_participantes']}</div>
  </div>

  <h2>Participantes</h2>
  <table>
    <thead>
      <tr><th>Nome</th><th>Tempo de fala</th><th>Pautas atendidas</th></tr>
    </thead>
    <tbody>
{$linhas_tabela}    </tbody>
  </table>

  <div class="destaques">
    <h2>Destaques</h2>
    <ul>
      <li>🏆 Mais tempo de fala: {$destaque_tempo_html}</li>
      <li>🗣️ Mais pautas registradas: {$destaque_pautas_html}</li>
    </ul>
  </div>

  <footer>Relatório gerado em {$r['gerado_em']} pelo ME INSCREVO.</footer>
</body>
</html>
HTML;
}

/**
 * Relatório em Markdown — útil para colar em atas, wikis ou PRs.
 */
function gerar_relatorio_markdown(array $r): string
{
    $linhas = [];
    $linhas[] = "# Relatório da reunião — {$r['nome_sala']}";
    $linhas[] = '';
    $linhas[] = "**Código da sala:** {$r['codigo_sala']}  ";
    $linhas[] = "**Duração da reunião:** {$r['duracao_formatada']}  ";
    $linhas[] = "**Participantes:** {$r['total_participantes']}";
    $linhas[] = '';
    $linhas[] = '## Participantes';
    $linhas[] = '';
    $linhas[] = '| Nome | Tempo de fala | Pautas atendidas |';
    $linhas[] = '| --- | --- | --- |';

    if (empty($r['participantes'])) {
        $linhas[] = '| _Nenhum participante na sala_ | - | - |';
    } else {
        foreach ($r['participantes'] as $p) {
            $nome = str_replace('|', '\\|', $p['nome']);
            $linhas[] = "| {$nome} | {$p['tempo_falado_formatado']} | {$p['pautas_total']} |";
        }
    }

    $linhas[] = '';
    $linhas[] = '## Destaques';
    $linhas[] = '';
    $linhas[] = '- 🏆 **Mais tempo de fala:** ' . (
        $r['destaque_tempo'] !== null
            ? "{$r['destaque_tempo']['nome']} ({$r['destaque_tempo']['tempo_falado_formatado']})"
            : '—'
    );
    $linhas[] = '- 🗣️ **Mais pautas registradas:** ' . (
        $r['destaque_pautas'] !== null
            ? "{$r['destaque_pautas']['nome']} ({$r['destaque_pautas']['pautas_total']} pauta(s))"
            : '—'
    );
    $linhas[] = '';
    $linhas[] = "_Relatório gerado em {$r['gerado_em']} pelo ME INSCREVO._";

    return implode("\n", $linhas) . "\n";
}

/**
 * Relatório em texto puro — sem marcação, pra leitura rápida ou impressão.
 */
function gerar_relatorio_txt(array $r): string
{
    $linhas = [];
    $linhas[] = 'RELATÓRIO DA REUNIÃO';
    $linhas[] = str_repeat('=', 40);
    $linhas[] = "Sala: {$r['nome_sala']} (código {$r['codigo_sala']})";
    $linhas[] = "Duração da reunião: {$r['duracao_formatada']}";
    $linhas[] = "Total de participantes: {$r['total_participantes']}";
    $linhas[] = '';
    $linhas[] = 'PARTICIPANTES';
    $linhas[] = str_repeat('-', 40);

    if (empty($r['participantes'])) {
        $linhas[] = 'Nenhum participante na sala.';
    } else {
        foreach ($r['participantes'] as $p) {
            $linhas[] = "- {$p['nome']}: {$p['tempo_falado_formatado']} de fala, {$p['pautas_total']} pauta(s)";
        }
    }

    $linhas[] = '';
    $linhas[] = 'DESTAQUES';
    $linhas[] = str_repeat('-', 40);
    $linhas[] = 'Mais tempo de fala: ' . (
        $r['destaque_tempo'] !== null
            ? "{$r['destaque_tempo']['nome']} ({$r['destaque_tempo']['tempo_falado_formatado']})"
            : 'nenhum'
    );
    $linhas[] = 'Mais pautas registradas: ' . (
        $r['destaque_pautas'] !== null
            ? "{$r['destaque_pautas']['nome']} ({$r['destaque_pautas']['pautas_total']} pauta(s))"
            : 'nenhum'
    );
    $linhas[] = '';
    $linhas[] = "Relatório gerado em {$r['gerado_em']} pelo ME INSCREVO.";

    return implode("\n", $linhas) . "\n";
}
