<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna o token CSRF da sessão atual, gerando um novo se ainda não existir.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Confere se o token recebido bate com o da sessão atual.
 */
function csrf_verify(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}
