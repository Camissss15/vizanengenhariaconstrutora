-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26-Nov-2026 às 13:29
-- Versão do servidor: 10.4.22-MariaDB
-- versão do PHP: 8.0.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `usuariovizan_v2`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `conteudo_site`
--

CREATE TABLE `conteudo_site` (
  `id` int(10) UNSIGNED NOT NULL,
  `secao` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `conteudo_site`
--

INSERT INTO `conteudo_site` (`id`, `secao`, `descricao`, `atualizado_em`) VALUES
(1, 'Quem Somos', 'A Vizan Engenharia e Construtora é referência no mercado de construção civil, entregando projetos com rigor técnico, pontualidade e excelência.', '2026-11-26 09:16:16'),
(2, 'Serviços', 'Execução completa de obras residenciais, comerciais e industriais, projetos de engenharia e reformas.', '2026-11-26 09:16:16'),
(3, 'Projetos', 'Conheça algumas obras executadas com foco em qualidade, segurança e prazo.', '2026-11-26 09:16:16'),
(4, 'Onde Estamos', 'Atendimento regional com equipes próprias prontas para atender projetos em todo o estado.', '2026-11-26 09:16:16');

-- --------------------------------------------------------

--
-- Estrutura da tabela `imagem_site`
--

CREATE TABLE `imagem_site` (
  `id` int(10) UNSIGNED NOT NULL,
  `caminho` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `orcamento`
--

CREATE TABLE `orcamento` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assunto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pendente','Em análise','Respondido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendente',
  `data_envio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE `usuario` (
  `Idu` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senhausu` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emailusu` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`Idu`, `usuario`, `senhausu`, `emailusu`) VALUES
(1, 'Vizanadmin', 'vizanengenharia', 'administrativo@vizanconstrucoes.com.br');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `conteudo_site`
--
ALTER TABLE `conteudo_site`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_conteudo_secao` (`secao`);

--
-- Índices para tabela `imagem_site`
--
ALTER TABLE `imagem_site`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `orcamento`
--
ALTER TABLE `orcamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orcamento_status` (`status`),
  ADD KEY `idx_orcamento_data` (`data_envio`);

--
-- Índices para tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`Idu`),
  ADD UNIQUE KEY `uk_usuario_email` (`emailusu`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `conteudo_site`
--
ALTER TABLE `conteudo_site`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `imagem_site`
--
ALTER TABLE `imagem_site`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `orcamento`
--
ALTER TABLE `orcamento`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `Idu` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
