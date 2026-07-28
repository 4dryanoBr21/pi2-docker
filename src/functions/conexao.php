<?php
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'projetomi_user';
$dbpassword = getenv('DB_PASSWORD') ?: 'projetomi_pass';
$dbname = getenv('DB_NAME') ?: 'projetomi';

$mysqli = new mysqli($db_host, $db_user, $dbpassword, $dbname);

if ($mysqli->connect_error) {
    // detalhes vão só para o log do servidor — nunca para o visitante
    error_log('Falha ao conectar ao banco de dados: ' . $mysqli->connect_error);
    die('Não foi possível conectar ao banco de dados. Tente novamente mais tarde.');
}
