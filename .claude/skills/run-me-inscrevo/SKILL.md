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
type #exampleInput1, "TestUser"
type #exampleInputEmail1, "test@example.com"
type #exampleInputPassword1, "Password123!"
click button[name=submit]
wait_for_element .alert-success

# 2. Login (accepts the username or the e-mail)
goto http://localhost:8192/pages/login.php
type #identificador, "TestUser"
type #password, "Password123!"
click button[name=submit]
wait_for_element #copiarCodigo

# 3. Create a room (the code field already comes filled with a random code;
#    the speaking time must be HH:MM or HH:MM:SS)
type #nome, "Testing Room"
type #tempo, "00:02:00"
click button[name=submit]
wait_for_element #btnEncerrarSala

# 4. Join as participant (use a new session/incognito window, because the
#    creator session is the one that is open here; use the room code shown on the creator page)
goto http://localhost:8192/index.php
type #codigo, "<room code>"
type #nome, "Guest"
click button[type=submit]
wait_for_element #mao

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
