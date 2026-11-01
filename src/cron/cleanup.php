<?php
// Limpeza em segundo plano: roda a cada minuto no serviço "cron" do
// docker-compose. Remove salas abandonadas e participantes inativos.
//
// Só roda pela linha de comando. Pelo navegador (a pasta src/ é a raiz do
// Apache) responde 404 e não faz nada.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/sala_helpers.php';

$ids = [];
$result = $mysqli->query('SELECT id_sala FROM sala');
while ($row = $result->fetch_assoc()) {
    $ids[] = (int) $row['id_sala'];
}
$result->free();

foreach ($ids as $id_sala) {
    // Um erro numa sala não pode impedir a limpeza das outras.
    try {
        // Sala abandonada (criador sem sinal de vida)?
        if (limpar_sala_se_abandonada($mysqli, $id_sala)) {
            // Sala ativa: remove só os participantes inativos.
            limpar_participantes_inativos($mysqli, $id_sala);
        }
    } catch (Throwable $e) {
        error_log('cleanup.php (sala ' . $id_sala . '): ' . $e->getMessage());
    }
}
