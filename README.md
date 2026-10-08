# 🙋 ME INSCREVO

**Organize a fila de fala em reuniões e aulas — sem confusão e sem estourar o tempo.**

O criador abre uma sala, os participantes entram só com um código e **levantam a mão**. O sistema monta a fila, controla o cronômetro de cada fala e **passa a palavra automaticamente** quando o tempo acaba.

🇧🇷 Português (Brasil) · 🇦🇷 Español (Argentina)

**Tecnologias:** PHP 8.3 (Apache) · MariaDB 10.11 · Nginx (proxy reverso) · Docker Compose · Bootstrap 5

---

## 📑 Índice

1. [Como funciona](#-como-funciona)
2. [Instalação rápida](#-instalação-rápida-5-minutos)
3. [Primeiro uso](#-primeiro-uso)
4. [Acessar de outros dispositivos](#-acessar-de-outros-dispositivos-celulares-e-outros-pcs)
5. [Opcionais](#-opcionais): HTTPS e phpMyAdmin
6. [Comandos do dia a dia](#-comandos-do-dia-a-dia)
7. [Atualizar ou migrar um banco antigo](#-atualizar-ou-migrar-um-banco-antigo)
8. [Problemas comuns](#-problemas-comuns)
9. [Referência](#-referência): variáveis, estrutura e idiomas
10. [Segurança e produção](#-segurança-e-produção)

---

## 💡 Como funciona

| Quem | O que faz |
|---|---|
| **Criador** | Faz login, cria uma sala (nome, código e tempo de fala por pessoa) e conduz a reunião. |
| **Participante** | Entra na sala **só com o código** (sem cadastro) e levanta a mão para entrar na fila. |
| **Sistema** | Controla a fila, cronometra cada fala e passa a palavra sozinho quando o tempo acaba. |

---

## 🚀 Instalação rápida (5 minutos)

### Antes de começar

Você só precisa do **Docker com Docker Compose v2** ([instalar o Docker](https://docs.docker.com/get-docker/)). Confira com:

```bash
docker --version
docker compose version
```

> 💡 No Linux, se aparecer erro de permissão, adicione seu usuário ao grupo do Docker:
> `sudo usermod -aG docker $USER` e depois saia e entre novamente na sessão.

### Passo a passo

**1. Entre na pasta do projeto**

```bash
cd caminho/para/o/projeto
```

**2. Crie o arquivo de configuração**

```bash
cp .env.example .env
```

**3. Defina as senhas no `.env`**

Abra o `.env` em qualquer editor e troque `MYSQL_ROOT_PASSWORD` e `MYSQL_PASSWORD` por senhas suas. Para gerar senhas fortes no terminal:

```bash
openssl rand -hex 16
```

> ⚠️ **Defina as senhas agora, antes do passo 4.** O MariaDB grava as senhas na *primeira* vez que o banco é criado. Alterar o `.env` depois **não** muda a senha de um banco já existente (veja [Problemas comuns](#-problemas-comuns)).

**4. Suba o sistema**

```bash
docker compose up -d --build
```

A primeira vez demora um pouco (baixa as imagens e cria o banco). Depois disso, é rápido.

**5. Confira se está tudo no ar**

```bash
docker compose ps
```

Os serviços `app`, `db` e `proxy` devem aparecer como *running* (ou *healthy*).

**6. Abra no navegador**

👉 **http://localhost:8192** (a porta vem de `HTTP_PORT` no `.env`)

Pronto! 🎉

---

## 👣 Primeiro uso

1. Acesse o sistema e clique em **cadastrar** para criar sua conta de criador.
2. Faça login e crie uma **sala** (nome, código e tempo de fala por pessoa).
3. Passe o **código** para os participantes. Eles entram pela página inicial, digitam o código e levantam a mão.
4. Conduza a reunião: o sistema cuida da fila e do cronômetro.

Troque o idioma (português ou espanhol) pelos botões na interface.

---

## 📱 Acessar de outros dispositivos (celulares e outros PCs)

Quem estiver **na mesma rede** pode entrar usando o IP da máquina que roda o sistema:

```
http://IP_DA_MAQUINA:8192
```

Para descobrir o IP no Linux: `hostname -I` (use o primeiro número, algo como `192.168.0.10`).
No Windows: `ipconfig`. No macOS: `ipconfig getifaddr en0`.

Se não abrir, verifique se o firewall da máquina permite a porta `8192`.

---

## 🔧 Opcionais

### 🔒 HTTPS com certificado autoassinado

Por padrão o sistema usa só HTTP (`USE_HTTPS=false`). Para ligar o HTTPS:

**1. Gere o certificado** (troque `SEU_IP_LOCAL` pelo IP da máquina na rede, ex.: `192.168.0.10`):

```bash
mkdir -p nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/certs/selfsigned.key -out nginx/certs/selfsigned.crt \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:SEU_IP_LOCAL"
```

**2.** No `.env`, mude para `USE_HTTPS=true`.

**3. Reinicie o proxy:**

```bash
docker compose up -d --force-recreate proxy
```

> ℹ️ O navegador vai avisar que o certificado "não é confiável". **Isso é esperado** com certificado autoassinado: aceite a exceção para continuar.
> A pasta `nginx/certs/` está no `.gitignore` — a chave privada **nunca** deve ser versionada.

Para voltar ao HTTP: `USE_HTTPS=false` no `.env` e rode o comando do passo 3 novamente.

### 🗄️ phpMyAdmin (só para desenvolvimento)

Ele **não sobe junto** com o sistema. Quando precisar:

```bash
docker compose --profile debug up -d phpmyadmin
```

Abra **http://127.0.0.1:8193** e entre com o usuário e senha do banco (`MYSQL_USER` / `MYSQL_PASSWORD` do `.env`, ou `root` com `MYSQL_ROOT_PASSWORD`).

Ele só fica acessível **na própria máquina**. Para desligar:

```bash
docker compose --profile debug stop phpmyadmin
```

---

## 🛠️ Comandos do dia a dia

| O que você quer | Comando |
|---|---|
| Ver o estado dos serviços | `docker compose ps` |
| Ver os logs em tempo real | `docker compose logs -f` |
| Ver o log de um serviço só | `docker compose logs -f app` (ou `db`, `proxy`) |
| Parar (mantém os dados) | `docker compose down` |
| Subir de novo | `docker compose up -d` |
| Reiniciar um serviço | `docker compose restart app` |
| Reconstruir após mudar o código/Dockerfile | `docker compose up -d --build` |
| ⚠️ Apagar **tudo**, inclusive o banco | `docker compose down -v` |

Os dados ficam guardados no volume `db_data`. Só o `down -v` apaga o banco.

---

## 🔄 Atualizar ou migrar um banco antigo

> Os scripts de `db-init/` rodam **apenas na primeira criação** do volume. Se você já tem um banco da versão antiga, siga os passos abaixo.

**Só ambiente de testes e não precisa dos dados?** Recrie do zero (apaga o banco):

```bash
docker compose down -v && docker compose up -d --build
```

**Quer manter os dados?**

**1. Faça backup** (com os containers antigos ainda rodando):

```bash
docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup.sql
```

**2. Suba a versão nova.** Com `MARIADB_AUTO_UPGRADE` ligado, o MariaDB 10.11 tenta atualizar o volume antigo — por isso o backup do passo 1 é importante:

```bash
docker compose up -d --build
```

**3. Aplique a migração** (cria os `UNIQUE` nas tabelas, aumenta o tamanho da senha e ajusta a fila para milissegundos):

```bash
docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < db-migrations/001_constraints_e_ajustes.sql
```

> O arquivo `db-migrations/001_...sql` explica como checar registros duplicados **antes** de aplicar os `UNIQUE`. Leia o cabeçalho dele primeiro.

---

## 🩺 Problemas comuns

<details>
<summary><b>A página não abre em localhost:8192</b></summary>

- Rode `docker compose ps` e veja se `proxy` e `app` estão *running*.
- Veja os logs: `docker compose logs -f proxy app`.
- Confira a porta em `HTTP_PORT` no `.env`.
</details>

<details>
<summary><b>Erro "port is already allocated" / "address already in use"</b></summary>

Outra aplicação já usa a porta. Mude `HTTP_PORT` (e/ou `PHPMYADMIN_PORT`) no `.env` e rode `docker compose up -d`. As duas portas precisam ser **diferentes**.
</details>

<details>
<summary><b>Erro de conexão com o banco / "Access denied"</b></summary>

Você provavelmente mudou as senhas no `.env` **depois** de o banco já ter sido criado. O MariaDB só lê as senhas na primeira criação do volume. Opções:

- **Voltar** as senhas do `.env` para as que estavam quando o banco foi criado; ou
- **Recriar o banco do zero** (apaga os dados): `docker compose down -v && docker compose up -d --build`.
</details>

<details>
<summary><b>Logo após subir, o sistema dá erro de banco</b></summary>

Na primeira vez o banco leva alguns segundos para inicializar. Aguarde um pouco e recarregue a página. Se persistir, veja `docker compose logs db`.
</details>

<details>
<summary><b>"permission denied" ao rodar o docker (Linux)</b></summary>

Adicione seu usuário ao grupo do Docker: `sudo usermod -aG docker $USER` e reinicie a sessão (ou use `sudo` nos comandos).
</details>

<details>
<summary><b>O navegador diz que a conexão HTTPS não é segura</b></summary>

É esperado com certificado autoassinado. Aceite a exceção, ou use `USE_HTTPS=false`. Em produção, use um certificado válido (veja [Segurança e produção](#-segurança-e-produção)).
</details>

<details>
<summary><b>O .env foi parar no Git por engano</b></summary>

Remova do controle de versão e **troque as senhas**:

```bash
git rm --cached .env
git commit -m "Remove .env do versionamento"
```
</details>

---

## 📚 Referência

### Variáveis do `.env`

| Variável | Para que serve | Padrão |
|---|---|---|
| `MYSQL_ROOT_PASSWORD` | Senha do usuário root do MariaDB | *(troque!)* |
| `MYSQL_DATABASE` | Nome do banco | `pi2_database` |
| `MYSQL_USER` / `MYSQL_PASSWORD` | Usuário e senha usados pela aplicação | *(troque!)* |
| `USE_HTTPS` | `true` = HTTPS autoassinado · `false` = só HTTP | `false` |
| `HTTP_PORT` | Porta HTTP exposta no host | `8192` |
| `PHPMYADMIN_PORT` | Porta do phpMyAdmin (só em `127.0.0.1`). **Diferente de `HTTP_PORT`.** | `8193` |

> O `.env` **não deve ir para o Git** (já está no `.gitignore`).

### Estrutura do projeto

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

### Adicionando ou alterando textos (idiomas)

Para incluir um texto novo na interface:

1. Crie a chave em `src/lang/pt.php` **e** em `src/lang/es.php`.
2. Use no código:
   - `t('chave')` — no HTML/PHP em geral;
   - `te('chave')` — dentro de atributos HTML;
   - `tj('chave')` — em JavaScript.

---

## 🛡️ Segurança e produção

### O que já está implementado

- Senhas com `password_hash`; todas as consultas com *prepared statements*; token CSRF em todos os POSTs.
- Cookie de sessão `HttpOnly`, `SameSite=Lax` e `Secure` quando em HTTPS; ID de sessão regenerado no login.
- Sessão única por criador (um novo login derruba o anterior).
- `estado_sala.php` só responde ao dono da sala ou a participantes **daquela** sala.
- Limite de tentativas no Nginx: login/cadastro (10 por minuto por IP) e entrada em sala por código.
- Banco e aplicação **não** ficam expostos na rede; só o proxy publica portas.
- Arquivos internos (`conexao.php`, `csrf.php`, `lang/`...) não podem ser abertos direto pelo navegador.

### ✅ Checklist para colocar em produção

- [ ] Usar um **domínio** com certificado válido (Let's Encrypt) em vez do autoassinado.
- [ ] Ativar a linha de **HSTS** comentada em `nginx/conf-templates/https.conf`.
- [ ] Trocar **todas** as senhas do `.env` por senhas fortes e únicas.
- [ ] Remover a linha `./src:/var/www/html` do `docker-compose.yml`, para rodar o código de dentro da imagem.
- [ ] Manter o phpMyAdmin desligado.
- [ ] Fazer backups periódicos do banco (veja o comando de `mysqldump` em [Atualizar ou migrar](#-atualizar-ou-migrar-um-banco-antigo)).