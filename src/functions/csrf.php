<?php
require_once __DIR__ . '/sessao.php';
iniciar_sessao();

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Aceita qualquer tipo de entrada (inclusive array vindo de um
// "csrf_token[]=x" malicioso) e simplesmente devolve false se não for string.
function csrf_verify($token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}
