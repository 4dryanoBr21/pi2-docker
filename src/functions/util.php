<?php
// Utilitários pequenos para reduzir a repetição de prepare/bind/execute.
// Todos usam prepared statements. Em caso de erro de SQL o mysqli lança
// mysqli_sql_exception (modo definido em conexao.php).

/**
 * Executa INSERT/UPDATE/DELETE. Retorna o nº de linhas afetadas.
 * $tipos segue o padrão do bind_param ("i", "ss", "sii"...).
 */
function db_exec(mysqli $mysqli, string $sql, string $tipos = '', ...$params): int
{
    $stmt = $mysqli->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    $stmt->close();
    return $afetadas;
}

/**
 * Executa um SELECT e retorna a primeira linha (array associativo) ou null.
 */
function db_fetch_one(mysqli $mysqli, string $sql, string $tipos = '', ...$params): ?array
{
    $stmt = $mysqli->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $linha ?: null;
}

/**
 * Executa um SELECT e retorna todas as linhas (array de arrays associativos).
 */
function db_fetch_all(mysqli $mysqli, string $sql, string $tipos = '', ...$params): array
{
    $stmt = $mysqli->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $linhas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $linhas;
}

/**
 * Responde em JSON e encerra o script.
 */
function responder_json(int $status, array $dados): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados);
    exit;
}

/**
 * Lê um campo de $_POST como texto "aparado". Se o campo não existir ou não
 * for uma string (ex.: "campo[]=x" enviado à mão), devolve '' — evita erro
 * 500 ao chamar trim() em array.
 */
function post_texto(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}
