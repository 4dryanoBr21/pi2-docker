# 🙋 ME INSCREVO

**Acabe com a confusão nas reuniões. Organize a fila de fala de forma justa, automática e transparente.**

O **Me Inscrevo** é uma ferramenta simples para professores, moderadores e líderes de reunião. Ele permite que participantes "levantem a mão" digitalmente, criando uma fila de espera organizada com cronômetro individual, evitando que algumas pessoas dominem a conversa e garantindo que todos sejam ouvidos.

🇧🇷 Português (Brasil) · 🇦🇷 Español (Argentina)

---

## 🚀 Como funciona na prática? (O fluxo)

Se você é novo por aqui, veja como é a experiência:

1.  **O Moderador (Criador):** Faz login, cria uma sala rápida (nome, código de acesso e tempo de fala por pessoa).
2.  **Os Participantes:** Entram na sala apenas com o código fornecido e clicam em **"Levantar a Mão"**.
3.  **A Dinâmica:** O sistema monta a fila, controla o cronômetro de cada fala e passa a palavra automaticamente quando o tempo acaba.

---

## 🛠️ Instalação Rápida (5 minutos)

*Ideal para quem já possui Docker instalado.*

### 1. Antes de começar
Você só precisa do **Docker com Docker Compose v2**. Confira com:
```bash
docker --version
docker compose version
```
> 💡 No Linux, se aparecer erro de permissão, adicione seu usuário ao grupo do Docker: `sudo usermod -aG docker $USER` e reinicie a sessão.

### 2. Passo a passo
**1. Entre na pasta do projeto**
```bash
cd caminho/para/o/projeto
```

**2. Configure o ambiente**
```bash
cp .env.example .env
```
Abra o `.env` e troque `MYSQL_ROOT_PASSWORD` e `MYSQL_PASSWORD` por senhas suas.
> ⚠️ **Importante:** Defina as senhas agora. O MariaDB grava as senhas na primeira vez que o banco é criado. Alterar o `.env` depois não muda a senha de um banco já existente.

**3. Suba o sistema**
```bash
docker compose up -d --build
```

**4. Acesse o sistema**
👉 **http://localhost:8192** (A porta vem de `HTTP_PORT` no `.env`)

---

## 📖 Guia de Primeiro Uso

### 👤 Para o Moderador (Criador)
1.  **Cadastre-se**: Acesse o sistema e clique em **cadastrar** para criar sua conta.
2.  **Crie a Sala**: Faça login e defina o nome, código e tempo de fala por pessoa.
3.  **Compartilhe**: Passe o código para os participantes.
4.  **Conduza**: O sistema cuida da fila e do cronômetro enquanto você foca na reunião.

### 👥 Para o Participante
1.  **Entre**: Digite o código da sala na página inicial.
2.  **Inscreva-se**: Clique em "Levantar a Mão" para entrar na fila de fala.
3.  **Aguarde**: Acompanhe sua posição e o tempo de cada colega.

---

## ⚙️ Comandos do dia a dia

| O que você quer | Comando |
|---|---|
| Ver o estado dos serviços | `docker compose ps` |
| Ver os logs em tempo real | `docker compose logs -f` |
| Reiniciar um serviço | `docker compose restart app` |
| Apagar TUDO (inclusive banco) | `docker compose down -v` |
| Subir tudo novamente | `docker compose up -d` |

---

## 🔒 Opcionais e Avançado

### HTTPS com certificado autoassinado
Por padrão o sistema usa só HTTP (`USE_HTTPS=false`). Para ligar o HTTPS:
1. **Gere o certificado**:
   ```bash
   mkdir -p nginx/certs
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout nginx/certs/selfsigned.key -out nginx/certs/selfsigned.crt \
     -subj "/CN=localhost" \
     -addext "subjectAltName=DNS:localhost,IP=SEU_IP_LOCAL"
   ```
2. No `.env`, mude para `USE_HTTPS=true`.
3. Reinicie o proxy: `docker compose up -d --force-recreate proxy`.

### 🗄️ phpMyAdmin (Desenvolvimento)
Para gerenciar o banco visualmente:
```bash
docker compose --profile debug up -d phpmyadmin
```
Acesse **http://127.0.0.1:8193** com as credenciais do `.env`.

---

## 🔄 Atualizar ou migrar um banco antigo
Os scripts de `db-init/` rodam apenas na primeira criação. Para bancos existentes:
1. **Backup**: `docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup.sql`
2. **Suba a nova versão**: `docker compose up -d --build`
3. **Aplique a migração**: `docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < db-migrations/001_constraints_e_ajustes.sql`

---

## 🩺 Problemas Comuns (FAQ)

<details>
<summary><b>A página não abre em localhost:8192</b></summary>
- Rode `docker compose ps` e veja se `proxy` e `app` estão *running*.
- Confira a porta em `HTTP_PORT` no `.env`.
</details>

<details>
<summary><b>Erro de conexão com o banco / "Access denied"</b></summary>
Você provavelmente mudou as senhas no `.env` <b>depois</b> de o banco já ter sido criado.
<b>Solução:</b> Recrie o banco do zero: `docker compose down -v && docker compose up -d --build`.
</details>

<details>
<summary><b>Erro "port is already allocated"</b></summary>
Outra aplicação já usa a porta. Mude `HTTP_PORT` no `.env` e rode `docker compose up -d`.
</details>

---

## 📚 Referência Técnica

### Variáveis do `.env`
- `MYSQL_ROOT_PASSWORD`: Senha do root do MariaDB.
- `MYSQL_DATABASE`: Nome do banco (`pi2_database`).
- `MYSQL_USER` / `MYSQL_PASSWORD`: Credenciais da aplicação.
- `USE_HTTPS`: `true` ou `false`.
- `HTTP_PORT`: Porta exposta (Padrão: `8192`).

### Estrutura do projeto
- `docker-compose.yml`: Orquestração dos serviços.
- `Dockerfile`: Imagem PHP 8.3 + Apache.
- `src/`: Código fonte da aplicação.
- `db-init/`: Esquema inicial do banco.
- `db-migrations/`: Scripts de atualização.

### Idiomas
Para alterar textos, edite `src/lang/pt.php` e `src/lang/es.php` usando as funções `t()`, `te()` e `tj()`.

---

## 🛡️ Segurança e Produção
- **Implementado**: Senhas com `password_hash`, *prepared statements*, tokens CSRF e cookies `HttpOnly/Secure`.
- **Checklist Produção**:
    - [ ] Usar domínio com certificado válido (Let's Encrypt).
    - [ ] Ativar HSTS em `nginx/conf-templates/https.conf`.
    - [ ] Trocar todas as senhas do `.env` por senhas fortes.
    - [ ] Remover o volume de bind `./src:/var/www/html` no `docker-compose.yml` para rodar o código dentro da imagem.
