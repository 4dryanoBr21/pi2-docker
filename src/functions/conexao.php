<?php
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'projetomi_user';
$dbpassword = getenv('DB_PASSWORD') ?: 'projetomi_pass';
$dbname = getenv('DB_NAME') ?: 'projetomi';

$mysqli = new mysqli($db_host, $db_user, $dbpassword, $dbname);

if ($mysqli->connect_error) {
    error_log('Falha ao conectar ao banco de dados: ' . $mysqli->connect_error);

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
    die($mensagens_erro_conexao[$idioma_erro]);
}
