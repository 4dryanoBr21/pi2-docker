-- ME INSCREVO — migração 001
-- Para bancos criados com a versão ANTIGA do projeto (volume db_data já
-- existente). Em bancos novos NÃO é necessário: o db-init já cria tudo assim.
--
-- Como aplicar (com os containers rodando):
--   docker compose exec -T db sh -c 'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < db-migrations/001_constraints_e_ajustes.sql
--
-- ATENÇÃO: os UNIQUE falham se já houver duplicados. Confira antes:
--   SELECT codigo_sala, COUNT(*) FROM sala GROUP BY codigo_sala HAVING COUNT(*) > 1;
--   SELECT email, COUNT(*) FROM criador GROUP BY email HAVING COUNT(*) > 1;
--   SELECT nome_criador, COUNT(*) FROM criador GROUP BY nome_criador HAVING COUNT(*) > 1;
-- (se aparecer algo, apague/renomeie os duplicados e rode de novo)
--
-- Aplicar UMA vez só: rodar de novo dá erro "Duplicate key name".

ALTER TABLE `sala`
  ADD UNIQUE KEY `uq_sala_codigo` (`codigo_sala`);

ALTER TABLE `criador`
  ADD UNIQUE KEY `uq_criador_email` (`email`),
  ADD UNIQUE KEY `uq_criador_nome` (`nome_criador`),
  MODIFY `senha` varchar(255) DEFAULT NULL;

ALTER TABLE `participante`
  MODIFY `data_hora_solicitacao` datetime(3) DEFAULT NULL;
