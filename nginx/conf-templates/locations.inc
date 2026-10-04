# Incluído dentro de cada bloco "server" (http.conf e https.conf).

client_max_body_size 1m;
limit_req_status 429;

proxy_set_header Host $host;
proxy_set_header X-Real-IP $remote_addr;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;

add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "same-origin" always;

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
