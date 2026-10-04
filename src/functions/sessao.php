<?php
// Início de sessão com cookie seguro. TODO código que precisa de sessão deve
// usar iniciar_sessao() em vez de chamar session_start() direto.

/**
 * Inicia a sessão (se ainda não estiver ativa) com cookie HttpOnly,
 * SameSite=Lax e Secure quando o acesso é por HTTPS (direto ou atrás do
 * proxy Nginx, que informa o esquema em X-Forwarded-Proto).
 */
function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Encerra a sessão por completo: limpa os dados, apaga o cookie e destrói
 * a sessão no servidor.
 */
function destruir_sessao(): void
{
    iniciar_sessao();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }

    session_destroy();
}

/**
 * Esquece os dados de participante guardados na sessão (sem destruí-la).
 */
function limpar_sessao_participante(): void
{
    unset($_SESSION['id_participante'], $_SESSION['nome'], $_SESSION['codigo']);
}
