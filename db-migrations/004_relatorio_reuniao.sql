-- ME INSCREVO — migração 004
-- Suporte ao relatório de reunião exportável (criador.php -> "Exportar
-- relatório"). Para gerar os destaques "quem participou por mais tempo" e
-- "quem teve mais pautas registradas" o banco precisa acumular, por
-- participante:
--   data_entrada           : quando entrou na sala (duração de presença).
--   tempo_falado_segundos  : soma do tempo com a palavra (acumulado toda vez
--                             que a vez passa para o próximo, em
--                             avancar_fala_sala()).
--   pautas_total           : quantas vezes recebeu a palavra (cada vez que
--                             avancar_fala_sala() tira alguém da fila e o
--                             coloca para falar).
--
-- Para bancos criados ANTES desta versão (volume db_data já existente).
-- Em bancos novos NÃO é necessário: o db-init já cria as colunas.
--
-- Como aplicar (com os containers rodando):
--   docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < db-migrations/004_relatorio_reuniao.sql
--
-- Pode ser executada mais de uma vez (IF NOT EXISTS).

ALTER TABLE `participante`
  ADD COLUMN IF NOT EXISTS `data_entrada` datetime DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `tempo_falado_segundos` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `pautas_total` int(11) NOT NULL DEFAULT 0;

-- participantes já existentes (sem data_entrada): melhor aproximação
-- disponível é "agora", pra não ficar NULL.
UPDATE `participante` SET `data_entrada` = NOW() WHERE `data_entrada` IS NULL;
