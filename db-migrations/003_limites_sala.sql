-- ME INSCREVO — migração 003
-- Limites da sala: máximo de participantes e máximo de pautas (pessoas com a
-- mão levantada ao mesmo tempo). 0 = sem limite (comportamento de antes).
--
-- Para bancos criados ANTES desta versão (volume db_data já existente).
-- Em bancos novos NÃO é necessário: o db-init já cria as colunas.
--
-- Como aplicar (com os containers rodando):
--   docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < db-migrations/003_limites_sala.sql
--
-- Pode ser executada mais de uma vez (IF NOT EXISTS). Aplique ANTES de usar a
-- nova versão do código: sem as colunas, as telas de sala dão erro.

ALTER TABLE `sala`
  ADD COLUMN IF NOT EXISTS `max_participantes` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `max_fila` int(11) NOT NULL DEFAULT 0;
