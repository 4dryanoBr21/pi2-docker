## Comandos uteis para do projeto
```
# primeira vez (ou depois de mudar o Dockerfile)
docker compose up -d --build

# dia a dia, e depois de mudar portas ou o .env
docker compose up -d

# depois de editar arquivos do nginx
docker compose up -d --force-recreate proxy
```

## Comando completo para gerar um certificado autoassinado pelo openssl de um ano podendo ser modificado caso nescessário
```
mkdir -p nginx/certs

openssl req -x509 -nodes -newkey rsa:2048 \
  -keyout nginx/certs/selfsigned.key \
  -out nginx/certs/selfsigned.crt \
  -days 365 \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP: `SEU_IP_LOCAL` ,IP:127.0.0.1"
```
## Escolhendo entre HTTP e HTTPS

O proxy (nginx) pode subir de duas formas, controladas pela variável de
ambiente `USE_HTTPS` no arquivo `.env`:

- `USE_HTTPS=true` (padrão): usa o certificado autoassinado gerado acima,
  serve em HTTPS na porta 443 e redireciona automaticamente qualquer
  acesso HTTP (porta definida em `APP_PORT`, padrão 8192) para HTTPS.
- `USE_HTTPS=false`: não usa certificado nenhum, serve só em HTTP na
  porta definida em `APP_PORT` (padrão 8192). Útil quando não é possível ou não se quer gerar o
  certificado (ex.: ambiente de desenvolvimento local).

Basta definir a variável no `.env` e subir/reiniciar os containers:
## Exemplo de .env para as variaveis de ambiente
```
MYSQL_ROOT_PASSWORD=root_secure_password_here
MYSQL_DATABASE=pi2_database
MYSQL_USER=pi2_user
MYSQL_PASSWORD=user_secure_password_here

USE_HTTPS=false

PMA_USER=pi2_user
PMA_PASSWORD=user_secure_password_here

# Portas expostas no host (opcionais - se omitidas, valem os padrões abaixo)
APP_PORT=8192
PHPMYADMIN_PORT=8168
```
## Customizando as portas

| Variável          | Serviço     | Padrão |
|-------------------|-------------|--------|
| `APP_PORT`        | App (proxy) | 8192   |
| `PHPMYADMIN_PORT` | phpMyAdmin  | 8168   |

Altere os valores no `.env` e recrie os containers: