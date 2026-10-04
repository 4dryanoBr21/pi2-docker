# Mudanças aplicadas nesta versão

Este documento lista tudo que foi alterado em relação ao projeto original (`pi2-docker-master`),
o que **não** foi alterado, e um roteiro para testar.

> ⚠️ **Leia isto primeiro:** estas alterações foram feitas e revisadas **sem poder executar** o projeto
> (o ambiente de edição não tinha PHP, Docker nem MariaDB). Foram feitas verificações automáticas de
> sintaxe/estrutura e testes da lógica em JavaScript, mas **o primeiro `docker compose up --build` é o
> teste de verdade**. Use o roteiro da última seção.

---

## 1. Corrigidos primeiro (os 6 itens críticos)

| # | Problema | O que foi feito |
|---|---|---|
| 1 | **Conflito de porta 8192** entre `proxy` e `phpmyadmin` | Portas agora vêm do `.env` (`HTTP_PORT=8192`, `PHPMYADMIN_PORT=8193`), com valores padrão diferentes |
| 2 | **phpMyAdmin com login automático** e aberto à rede | Removido `PMA_USER`/`PMA_PASSWORD` (agora pede login); publicado só em `127.0.0.1`; só sobe com `--profile debug` |
| 3 | **`estado_sala`/`verifica_sala` sem autorização** (qualquer um listava participantes de qualquer sala) | `estado_sala.php` exige ser o dono da sala ou participante *daquela* sala. `verifica_sala.php` foi **removido** (o `estado_sala` já avisa quando a sala acaba) |
| 4 | **Faltavam `UNIQUE`** e login ambíguo (usuário = e-mail de outro) | `UNIQUE` em `sala.codigo_sala`, `criador.email`, `criador.nome_criador`. Nome de usuário não pode conter `@`; login decide por `@` (e-mail) ou não (usuário); checagem cruzada no cadastro; erro 1062 tratado |
| 5 | **Logout via GET** apagava a sala (CSRF) e sem confirmação | `logout.php` só age via **POST + token CSRF**. O "Sair" do criador pede confirmação. Aba antiga (sessão derrubada) não apaga mais a sala da sessão nova |
| 6 | **Segredos** | `.env.example` criado; `.gitignore` cobre `.env` e `nginx/certs/*`; removido o usuário/senha padrão de `conexao.php` (agora é erro claro se faltar variável) |

## 2. Demais correções

**Bugs de lógica**
- **Corrida em `avancar_fala`:** agora é transacional (`SELECT … FOR UPDATE`) e o cliente informa *quem acha que está falando*; se já mudou, o servidor não avança de novo (ninguém é pulado com duplo clique ou clique + avanço automático).
- **Cronômetro:** passou a ser calculado a partir de horários (do servidor) em vez de "descontar 1 por tick" — não anda mais devagar em aba em segundo plano. Também corrigido o tempo da reunião, que travava em 59:59 (agora mostra `1:05:00` etc.).
- **Botão ❌ de quem está falando:** agora **encerra a própria fala e passa a palavra** (antes recolocava a pessoa na fila).
- **Senha no login:** removido o `trim()` (o cadastro não aparava, então senhas com espaço nas pontas nunca entravam).
- **Fila:** hora do pedido com milissegundos (`datetime(3)`) e desempate por id; alternar a mão agora é um único `UPDATE` atômico.
- **`criar.php`:** se o criador já tem sala aberta, volta para ela (antes criava outra e deixava a antiga órfã). Tempo de fala validado no servidor (formato, < 24h, > 0). Sala + vínculo criados em transação.
- **`participante.php`:** confere que o participante ainda pertence à sala (antes, id ausente gerava erro de JS).
- **`index.php`:** quem volta à tela inicial sem sair da sala anterior tem o registro antigo removido.
- **Limites de inatividade** mais folgados (3 min para criador e participante; antes 1 e 2), porque navegadores atrasam abas em segundo plano.
- `apagar_sala` e `remover_participante` agora em transação.

**Infra / segurança**
- PHP com `php.ini` de **produção** (não mostra erros na tela), `expose_php` off, `date.timezone=UTC`.
- Cookie de sessão `HttpOnly`, `SameSite=Lax`, `Secure` quando HTTPS (`functions/sessao.php` centraliza o início de sessão).
- Apache: sem listagem de diretórios; acesso direto bloqueado a `conexao.php`, `csrf.php`, `sessao.php`, `util.php`, `auth_criador.php`, `sala_helpers.php`, `idioma.php` e `lang/`.
- Nginx: limite de requisições (login/cadastro 10/min por IP; entrada em sala 120/min), `client_max_body_size`, headers `X-Content-Type-Options`/`X-Frame-Options`/`Referrer-Policy`, `server_name _` (sem placeholder). HSTS fica comentado de propósito (quebraria a exceção do certificado autoassinado).
- MariaDB **10.4 → 10.11** (a 10.4 está fora de suporte), com `utf8mb4` e fuso UTC; `phpmyadmin` fixado em `5.2`.
- `conexao.php`: `set_charset('utf8mb4')` e falha de conexão tratada com mensagem amigável (no PHP 8.1+ o `new mysqli` lança exceção — o `if ($connect_error)` antigo nunca era alcançado).
- Docker: `pdo_mysql` (não usado) e `rewrite`/`AllowOverride All` (sem `.htaccess`) removidos; `COPY src/` na imagem; `.dockerignore`; healthchecks em `app` e `proxy`; `version:` obsoleto removido.
- `htmlspecialchars` com flags explícitas; leitura de formulário tolerante a `campo[]=x` (antes dava erro 500).
- Login: gasta tempo equivalente quando o usuário não existe (dificulta descobrir contas pelo tempo de resposta).

**Qualidade**
- `<!DOCTYPE html>` em todas as páginas (antes rodavam em *quirks mode*).
- Removida a imagem não usada `src/img/ME INSCREVO.png` (8,7 MB) — **continua no seu zip original** se quiser guardá-la.
- Duplicação reduzida: `auth_criador.php` (autenticação do criador, antes copiada em 5 arquivos), `util.php` (acesso ao banco), `sessao.php`, `assets/app.js` (JS comum às duas telas).
- `README.md` reescrito; `MUDANCAS.md` (este arquivo).

## 3. O que NÃO foi alterado (de propósito)

- **Bootstrap/Google Fonts continuam vindo de CDN** (precisa de internet nas máquinas dos usuários). Hospedar localmente exigia baixar arquivos externos, o que não foi possível aqui.
- **Foreign keys circulares** sala↔participante continuam como estavam (a limpeza manual em transação funciona e é segura). `ON DELETE CASCADE/SET NULL` num ciclo precisaria de testes com o banco real.
- **Unicidade global do nome da sala** (regra do app original) foi mantida.
- **Polling:** continua sendo polling (a cada 2–3 s), mas agora com 1 requisição em vez de 2 por participante e sem travar a sessão PHP. Trocar por SSE/WebSocket seria uma mudança maior.
- **Sem testes automatizados/CI** no repositório.
- Mensagens de erro do `idioma.php`, `style.css` e demais arquivos de interface não mudaram.
- Chaves de tradução `erro_preparar_consulta`/`erro_preparar_verificacao`/`erro_sala_nao_encontrada_simples` ficaram sem uso (inofensivas; podem ser apagadas dos dois arquivos de idioma).

## 4. Atenção ao atualizar um ambiente já existente

1. **Banco:** aplique `db-migrations/001_constraints_e_ajustes.sql` (instruções no README). Sem isso o sistema ainda funciona, mas sem os `UNIQUE` novos.
2. **Senhas e `.env`:** o `.env` entregue mantém as senhas-modelo do original. **Troque-as.** Se o `.env` foi commitado, rode `git rm --cached .env`.
3. **phpMyAdmin** não sobe mais por padrão: use `docker compose --profile debug up -d phpmyadmin` e acesse `http://127.0.0.1:8193`.
4. **MariaDB 10.4 → 10.11:** faça backup antes (comando no README).

## 5. Roteiro de teste manual (≈10 minutos)

1. `cp .env.example .env` → `docker compose up -d --build` → `docker compose ps` (tudo `healthy`/`running`).
2. Abrir `http://localhost:8192`. **Cadastrar** um usuário (tente `a@b` como nome de usuário: deve recusar) e **logar**.
3. **Criar sala** com tempo `00:00:20` (e teste `00:00:00`: deve recusar). Voltar com o botão "voltar" do navegador: deve cair de volta na mesma sala, sem criar outra.
4. Em outra aba/janela anônima, **entrar** com o código como 2 participantes diferentes.
5. Participante A levanta a mão, depois B. No criador, "Iniciar fala do próximo": A deve falar, B aparece como 2º.
6. Deixar o tempo de A acabar: B deve assumir **automaticamente**, sem pular ninguém. Dar duplo clique rápido em "Passar a vez" com B falando: só pode avançar **uma** vez.
7. Com B falando, B clica no ❌: a fala encerra (e a fila continua), B **não** volta para a fila.
8. Com a aba do criador em segundo plano por ~1 min, voltar: cronômetros devem estar certos.
9. **Segurança:** em janela anônima sem entrar em sala, abrir `http://localhost:8192/functions/estado_sala.php?id_sala=1` → deve responder 404. Abrir `/functions/conexao.php`, `/lang/pt.php` e `/functions/` → devem dar 403/404 (sem listar).
10. Clicar em **Sair** (criador): deve pedir confirmação e apagar a sala; abrir `/functions/logout.php` direto no navegador **não** deve deslogar.
11. Errar a senha 7 ou mais vezes em poucos segundos no login: a partir daí deve responder `429` (o limite é 10/min por IP, com rajada de 5).
