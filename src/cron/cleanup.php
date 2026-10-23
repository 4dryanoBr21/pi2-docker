<?php
// Background cleanup script – runs every minute via a cron service.
// It removes abandoned rooms and inactive participants.

require_once __DIR__ . '/../functions/conexao.php';
require_once __DIR__ . '/../functions/sala_helpers.php';

// Fetch all active rooms (rooms that still exist).
$result = $mysqli->query('SELECT id_sala FROM sala');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id_sala = (int) $row['id_sala'];
        // Clean up potential abandoned room (creator inactive).
        limpar_sala_se_abandonada($mysqli, $id_sala);
        // Clean up inactive participants.
        limpar_participantes_inativos($mysqli, $id_sala);
    }
    $result->free();
}

// Optional: log when run (useful for debugging). Comment out in production.
// error_log('Cleanup run at ' . date('c'));
?>