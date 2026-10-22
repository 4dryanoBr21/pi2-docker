---
name: run-me-inscrevo
description: build, launch, and test the ME INSCREVO speaking queue system
---

# ME INSCREVO Run Skill

This skill allows an agent to build, launch, and drive the ME INSCREVO application, a speaking queue manager for meetings. The app is a PHP/MariaDB system running inside Docker.

It is driven using `chromium-cli` for end-to-end browser interactions.

## Prerequisites

Ensure Docker and Docker Compose v2 are installed.

## Build

1. Create the `.env` file from the example:
   ```bash
   cp .env.example .env
   ```
2. Update passwords in `.env` (required before first launch):
   ```bash
   # Example: generate random hex passwords
   sed -i "s/root_secure_password_here/$(openssl rand -hex 12)/" .env
   sed -i "s/user_secure_password_here/$(openssl rand -hex 12)/" .env
   ```
3. Build and launch the containers:
   ```bash
   docker compose up -d --build
   ```

## Run (agent path)

The application is driven by `chromium-cli`. Use the following script to perform a smoke test of the core functionality (Registration -> Login -> Create Room -> Join Room).

```bash
# Run the smoke test driver
cat << 'DRIVER' | chromium-cli
# 1. Register a new user
goto http://localhost:8192/pages/register.php
type #nome, "Test User"
type #email, "test@example.com"
type #senha, "Password123!"
type #confirmar_senha, "Password123!"
click #btn-register
wait_for_element #mensagem-sucesso

# 2. Login
goto http://localhost:8192/pages/login.php
type #email, "test@example.com"
type #senha, "Password123!"
click #btn-login
wait_for_element #btn-criar-sala

# 3. Create a room
click #btn-criar-sala
type #nome_sala, "Testing Room"
type #codigo_sala, "TEST123"
type #tempo_fala, "2"
click #btn-salvar-sala
wait_for_element #btn-sair

# 4. Join as participant (in a new session/incognito if needed, but for smoke test we check the page)
goto http://localhost:8192/index.php
type #codigo, "TEST123"
click #btn-entrar
wait_for_element #btn-levantar-mao

# Take a final screenshot of the participant view
screenshot participant_view.png
DRIVER
```

## Run (human path)

1. Run `docker compose up -d`.
2. Open `http://localhost:8192` in your browser.

## Gotchas

- **Database Passwords:** MariaDB only reads passwords from `.env` during the first volume creation. If you change passwords in `.env` after the first run, you MUST recreate the volume:
  ```bash
  docker compose down -v && docker compose up -d
  ```
- **Port Conflicts:** If port `8192` is occupied, change `HTTP_PORT` in `.env`.

## Troubleshooting

- **503 Service Unavailable:** Usually means the PHP app is still booting or cannot connect to the DB. Check logs: `docker compose logs -f app`.
- **Access Denied for user 'pi2_user':** See the Database Passwords gotcha above.
