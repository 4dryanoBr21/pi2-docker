# ME INSCREVO

Sistema web para organizar a **fila de fala em reuniões e aulas**.

- O **criador** faz login, cria uma sala (nome, código e tempo de fala por pessoa) e conduz a reunião.
- Os **participantes** entram na sala só com o código — sem cadastro — e **levantam a mão** para entrar na fila.
- O sistema controla a fila, o cronômetro de cada fala e passa a palavra automaticamente quando o tempo acaba.
- Interface em **português (Brasil)** e **espanhol (Argentina)**.

**Tecnologias:** PHP 8.3 (Apache) · MariaDB 10.11 · Nginx (proxy reverso) · Docker Compose · Bootstrap 5.

---

## Como rodar

Requisitos: [Docker](https://docs.docker.com/get-docker/) com Docker Compose v2.

```bash
# 1. Crie seu arquivo de configuração e TROQUE as senhas
cp .env.example .env
#    (edite o .env: MYSQL_ROOT_PASSWORD e MYSQL_PASSWORD)

# 2. Suba tudo
docker compose up -d --build
```

Acesse: **http://localhost:8192** (porta definida em `HTTP_PORT` no `.env`).

Para ver os logs: `docker compose logs -f`. Para parar: `docker compose down` (os dados ficam guardados no volume `db_data`; `docker compose down -v` apaga também o banco).

### HTTPS (opcional)

Por padrão o `.env` usa `USE_HTTPS=false` (só HTTP). Para usar HTTPS com certificado autoassinado:

```bash
# Gere o certificado (troque SEU_IP_LOCAL pelo IP da máquina na rede, ex.: 192.168.0.10)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/certs/selfsigned.key -out nginx/certs/selfsigned.crt \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:SEU_IP_LOCAL"

# No .env, defina USE_HTTPS=true e reinicie
docker compose up -d --force-recreate proxy
```

O navegador vai avisar que o certificado não é confiável (é autoassinado) — é esperado. A pasta `nginx/certs/` está no `.gitignore`: a chave privada **não** deve ser versionada.

### phpMyAdmin (opcional, para desenvolvimento)

Não sobe junto com o sistema. Quando precisar:

```bash
docker compose --profile debug up -d phpmyadmin
```

Abra **http://127.0.0.1:8193** e entre com o usuário/senha do banco (`MYSQL_USER`/`MYSQL_PASSWORD` do `.env`, ou `root`). Fica acessível **somente na própria máquina**. Para desligar: `docker compose --profile debug stop phpmyadmin`.

---

## Já tenho um banco criado com a versão antiga?

Os scripts de `db-init/` só rodam na **primeira** criação do volume. Para um banco existente:

```bash
# 1) Faça backup (com os containers antigos ainda rodando)
docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup.sql

# 2) Suba a versão nova (com MARIADB_AUTO_UPGRADE ligado, o MariaDB 10.11 tenta atualizar o volume antigo — por isso o backup do passo 1)
docker compose up -d --build

# 3) Aplique a migração (UNIQUE nas tabelas, senha maior, fila com milissegundos)
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < db-migrations/001_constraints_e_ajustes.sql
```

Se for só ambiente de testes, é mais simples recriar do zero: `docker compose down -v && docker compose up -d --build`.

> O arquivo `db-migrations/001_...sql` explica como checar duplicados antes de aplicar os `UNIQUE`.

---

## Variáveis do `.env`

| Variável | Para que serve | Padrão |
|---|---|---|
| `MYSQL_ROOT_PASSWORD` | Senha do root do MariaDB | *(troque!)* |
| `MYSQL_DATABASE` | Nome do banco | `pi2_database` |
| `MYSQL_USER` / `MYSQL_PASSWORD` | Usuário e senha usados pela aplicação | *(troque!)* |
| `USE_HTTPS` | `true` = HTTPS autoassinado · `false` = só HTTP | `false` |
| `HTTP_PORT` | Porta HTTP exposta no host | `8192` |
| `PHPMYADMIN_PORT` | Porta do phpMyAdmin (só em `127.0.0.1`). **Diferente de `HTTP_PORT`.** | `8193` |

O `.env` **não deve ir para o Git** (está no `.gitignore`). Se ele já foi commitado alguma vez, remova do controle de versão e troque as senhas:

```bash
git rm --cached .env
git commit -m "Remove .env do versionamento"
```

---

## Estrutura do projeto

```
├── docker-compose.yml        # serviços: app (PHP), db (MariaDB), proxy (Nginx), phpmyadmin (opcional)
├── Dockerfile                # imagem PHP 8.3 + Apache
├── .env.example              # modelo de configuração (copie para .env)
├── docker/
│   ├── php/custom.ini        # sessão segura, timezone, expose_php off
│   └── apache/zz-app-security.conf   # sem listagem de diretório; bloqueia arquivos internos
├── nginx/
│   ├── entrypoint.sh         # escolhe HTTP ou HTTPS conforme USE_HTTPS
│   ├── conf-templates/       # http.conf, https.conf + trechos comuns (limite de requisições, headers)
│   └── certs/                # certificado autoassinado (NÃO versionado)
├── db-init/projetomi.sql     # esquema do banco (roda só na 1ª criação do volume)
├── db-migrations/            # ajustes para bancos já existentes
└── src/                      # aplicação PHP (raiz do servidor web)
    ├── index.php             # entrar em uma sala com o código
    ├── pages/                # login, register, criar, criador, participante
    ├── functions/            # endpoints (estado_sala, avancar_fala, ...) e includes
    ├── assets/app.js         # JavaScript compartilhado entre as telas
    ├── lang/                 # textos pt.php / es.php
    └── style.css
```

Para adicionar um texto novo na interface: crie a chave em `src/lang/pt.php` **e** em `src/lang/es.php` e use `t('chave')` (ou `te()` em atributos HTML / `tj()` em JavaScript).

---

## Segurança — o que já está implementado

- Senhas com `password_hash`; todas as consultas com *prepared statements*; token CSRF em todos os POSTs.
- Cookie de sessão `HttpOnly`, `SameSite=Lax` e `Secure` quando em HTTPS; ID de sessão regenerado no login.
- Sessão única por criador (um novo login derruba o anterior).
- `estado_sala.php` só responde ao dono da sala ou a participantes **daquela** sala.
- Limite de tentativas no Nginx: login/cadastro (10 por minuto por IP) e entrada em sala por código.
- Banco e aplicação **não** ficam expostos na rede; só o proxy publica portas.
- Arquivos internos (`conexao.php`, `csrf.php`, `lang/`...) não podem ser abertos direto pelo navegador.

**Em produção real:** use um domínio com certificado válido (Let's Encrypt) e ative a linha de HSTS comentada em `nginx/conf-templates/https.conf`; troque todas as senhas do `.env`; e remova a linha `./src:/var/www/html` do `docker-compose.yml` para rodar o código de dentro da imagem.
