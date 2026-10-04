-- ME INSCREVO — esquema inicial do banco.
-- Executado automaticamente pelo MariaDB SOMENTE na primeira criação do
-- volume (docker-entrypoint-initdb.d), dentro do banco definido em
-- MYSQL_DATABASE. Para bancos já existentes, use db-migrations/.

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Tabela `sala`
-- --------------------------------------------------------
CREATE TABLE `sala` (
  `id_sala` int(11) NOT NULL AUTO_INCREMENT,
  `nome_sala` varchar(100) DEFAULT NULL,
  `codigo_sala` varchar(100) DEFAULT NULL,
  `tempo_de_fala` time DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `fk_participante_falando` int(11) DEFAULT NULL,
  `fala_inicio` datetime DEFAULT NULL,
  PRIMARY KEY (`id_sala`),
  -- o código é a "chave de entrada" da sala: tem que ser único de verdade
  -- (não só por checagem na aplicação, que falha com requisições simultâneas)
  UNIQUE KEY `uq_sala_codigo` (`codigo_sala`),
  KEY `sala_ibfk_falando` (`fk_participante_falando`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabela `participante`
-- (data_hora_solicitacao com milissegundos: desempata quem levantou a mão
--  no mesmo segundo)
-- --------------------------------------------------------
CREATE TABLE `participante` (
  `id_participante` int(11) NOT NULL AUTO_INCREMENT,
  `nome_participante` varchar(100) DEFAULT NULL,
  `fk_sala_atual` int(11) DEFAULT NULL,
  `data_hora_solicitacao` datetime(3) DEFAULT NULL,
  `ultima_atividade` datetime DEFAULT NULL,
  PRIMARY KEY (`id_participante`),
  KEY `fk_sala_atual` (`fk_sala_atual`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabela `criador`
-- (senha em varchar(255): é o tamanho recomendado para password_hash(),
--  que pode gerar hashes maiores que 100 caracteres no futuro)
-- --------------------------------------------------------
CREATE TABLE `criador` (
  `id_criador` int(11) NOT NULL AUTO_INCREMENT,
  `nome_criador` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `fk_sala_criada` int(11) DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `session_last_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`id_criador`),
  UNIQUE KEY `uq_criador_email` (`email`),
  UNIQUE KEY `uq_criador_nome` (`nome_criador`),
  KEY `fk_sala_criada` (`fk_sala_criada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Chaves estrangeiras (sala <-> participante é circular, por isso ficam
-- por último)
-- --------------------------------------------------------
ALTER TABLE `criador`
  ADD CONSTRAINT `criador_ibfk_1` FOREIGN KEY (`fk_sala_criada`) REFERENCES `sala` (`id_sala`);

ALTER TABLE `participante`
  ADD CONSTRAINT `participante_ibfk_1` FOREIGN KEY (`fk_sala_atual`) REFERENCES `sala` (`id_sala`);

ALTER TABLE `sala`
  ADD CONSTRAINT `sala_ibfk_falando` FOREIGN KEY (`fk_participante_falando`) REFERENCES `participante` (`id_participante`);
