<?php
// Conexão com o banco.
//
// As credenciais vêm EXCLUSIVAMENTE de variáveis de ambiente (definidas no
// docker-compose.yml a partir do .env). Não existe mais usuário/senha padrão
// no código.

// A partir do PHP 8.1 este já é o padrão, mas deixamos explícito: erros de
// SQL/conexão viram exceções (mysqli_sql_exception), nunca falhas silenciosas.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host    = getenv('DB_HOST') ?: 'db';
$db_user    = getenv('DB_USER');
$dbpassword = getenv('DB_PASSWORD');
$dbname     = getenv('DB_NAME');

try {
    if ($db_user === false || $dbpassword === false || $dbname === false) {
        throw new RuntimeException('Variáveis DB_USER, DB_PASSWORD e DB_NAME não definidas.');
    }

    $mysqli = new mysqli($db_host, $db_user, $dbpassword, $dbname);
    $mysqli->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('Falha ao conectar ao banco de dados: ' . $e->getMessage());

    // Aqui ainda não é possível saber o idioma escolhido pelo usuário
    // (o cookie 'idioma' é lido em functions/idioma.php, que normalmente
    // é incluído depois deste arquivo), então detectamos diretamente
    // pelo cookie para dar a mensagem no idioma certo mesmo numa falha
    // tão cedo no carregamento da página.
    $idioma_erro = (isset($_COOKIE['idioma']) && $_COOKIE['idioma'] === 'es') ? 'es' : 'pt';
    $mensagens_erro_conexao = [
        'pt' => 'Não foi possível conectar ao banco de dados. Tente novamente mais tarde.',
        'es' => 'No fue posible conectar con la base de datos. Intentá de nuevo más tarde.',
    ];

    http_response_code(503);
    die($mensagens_erro_conexao[$idioma_erro]);
}
