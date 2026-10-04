# Incluído no contexto http (fora de qualquer "server").

# Só requisições POST entram na contagem do limite (GET fica com chave vazia
# e nunca é limitado).
map $request_method $chave_post {
    default "";
    POST    $binary_remote_addr;
}

# Login e cadastro: 10 tentativas por minuto, por IP (freia força bruta).
limit_req_zone $chave_post zone=zona_auth:10m rate=10r/m;

# Entrada em sala por código: mais folgado, porque uma turma inteira pode
# estar atrás do mesmo IP (NAT da escola/empresa).
limit_req_zone $chave_post zone=zona_entrada:10m rate=120r/m;
