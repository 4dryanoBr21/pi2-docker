-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Tempo de geração: 25/07/2026 às 18:23
-- Versão do servidor: 10.4.34-MariaDB-1:10.4.34+maria~ubu2004
-- Versão do PHP: 8.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `projetomi`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `criador`
--

CREATE TABLE `criador` (
  `id_criador` int(11) NOT NULL,
  `nome_criador` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(100) DEFAULT NULL,
  `fk_sala_criada` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `participante`
--

CREATE TABLE `participante` (
  `id_participante` int(11) NOT NULL,
  `nome_participante` varchar(100) DEFAULT NULL,
  `fk_sala_atual` int(11) DEFAULT NULL,
  `data_hora_solicitacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sala`
--

CREATE TABLE `sala` (
  `id_sala` int(11) NOT NULL,
  `nome_sala` varchar(100) DEFAULT NULL,
  `codigo_sala` varchar(100) DEFAULT NULL,
  `tempo_de_fala` time DEFAULT NULL,
  `encerrada` tinyint(4) NOT NULL DEFAULT 0,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `fk_participante_falando` int(11) DEFAULT NULL,
  `fala_inicio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `criador`
--
ALTER TABLE `criador`
  ADD PRIMARY KEY (`id_criador`),
  ADD KEY `fk_sala_criada` (`fk_sala_criada`);

--
-- Índices de tabela `participante`
--
ALTER TABLE `participante`
  ADD PRIMARY KEY (`id_participante`),
  ADD KEY `fk_sala_atual` (`fk_sala_atual`);

--
-- Índices de tabela `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id_sala`),
  ADD KEY `sala_ibfk_falando` (`fk_participante_falando`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `criador`
--
ALTER TABLE `criador`
  MODIFY `id_criador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `participante`
--
ALTER TABLE `participante`
  MODIFY `id_participante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de tabela `sala`
--
ALTER TABLE `sala`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `criador`
--
ALTER TABLE `criador`
  ADD CONSTRAINT `criador_ibfk_1` FOREIGN KEY (`fk_sala_criada`) REFERENCES `sala` (`id_sala`);

--
-- Restrições para tabelas `participante`
--
ALTER TABLE `participante`
  ADD CONSTRAINT `participante_ibfk_1` FOREIGN KEY (`fk_sala_atual`) REFERENCES `sala` (`id_sala`);

--
-- Restrições para tabelas `sala`
--
ALTER TABLE `sala`
  ADD CONSTRAINT `sala_ibfk_falando` FOREIGN KEY (`fk_participante_falando`) REFERENCES `participante` (`id_participante`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
