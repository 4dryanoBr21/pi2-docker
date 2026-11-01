# Incluído dentro de cada bloco "server" (http.conf e https.conf).

client_max_body_size 1m;
limit_req_status 429;

proxy_set_header Host $host;
proxy_set_header X-Real-IP $remote_addr;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;

# Atrás do ngrok, $remote_addr seria o IP do container do ngrok e TODOS os
# visitantes dividiriam o mesmo limite de requisições (login, entrada na sala).
# Usa o IP do X-Forwarded-For só quando a conexão vem da rede interna do
# Docker (172.16.0.0/12); conexões de fora continuam usando o IP real.
set_real_ip_from 172.16.0.0/12;
real_ip_header X-Forwarded-For;
real_ip_recursive on;

# X-Frame-Options e Referrer-Policy vêm só do Apache (antes o nginx mandava
# valores diferentes e os cabeçalhos saíam duplicados e conflitantes).
add_header X-Content-Type-Options "nosniff" always;

location = /pages/login.php {
    limit_req zone=zona_auth burst=5 nodelay;
    proxy_pass http://app:80;
}

location = /pages/register.php {
    limit_req zone=zona_auth burst=5 nodelay;
    proxy_pass http://app:80;
}

location = /index.php {
    limit_req zone=zona_entrada burst=60 nodelay;
    proxy_pass http://app:80;
}

location = / {
    limit_req zone=zona_entrada burst=60 nodelay;
    proxy_pass http://app:80;
}

location / {
    proxy_pass http://app:80;
}
