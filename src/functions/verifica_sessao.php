<?php
// Chamado por polling pela tela do criador: a sessão ainda é válida?
// (deixa de ser quando a mesma conta faz login em outro lugar)

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_criador.php';

if (!criador_autenticado($mysqli)) {
    responder_json(200, ['valido' => false]);
}

criador_registrar_atividade($mysqli);

responder_json(200, ['valido' => true]);
