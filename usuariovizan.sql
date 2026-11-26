-- BANCO NOVO E PADRONIZADO PARA O PROJETO VIZAN
-- Importar pelo phpMyAdmin. Este script cria o banco usuariovizan_v2.
-- Não apaga o banco antigo usuariovizan.

CREATE DATABASE IF NOT EXISTS usuariovizan_v2
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE usuariovizan_v2;

CREATE TABLE IF NOT EXISTS usuario (
  Idu INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nomeusu VARCHAR(120) NOT NULL,
  loginusu VARCHAR(60) NOT NULL,
  senhausu VARCHAR(255) NOT NULL,
  emailusu VARCHAR(150) NOT NULL,
  tipousu ENUM('Administrador','Editor') NOT NULL DEFAULT 'Editor',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Idu),
  UNIQUE KEY uk_usuario_login (loginusu),
  UNIQUE KEY uk_usuario_email (emailusu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orcamento (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefone VARCHAR(30) DEFAULT NULL,
  assunto VARCHAR(150) NOT NULL,
  mensagem TEXT NOT NULL,
  status ENUM('Pendente','Em análise','Respondido') NOT NULL DEFAULT 'Pendente',
  data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orcamento_status (status),
  KEY idx_orcamento_data (data_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conteudo_site (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  secao VARCHAR(80) NOT NULL,
  descricao TEXT NOT NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_conteudo_secao (secao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS imagem_site (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  caminho VARCHAR(255) NOT NULL,
  descricao VARCHAR(180) NOT NULL,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO conteudo_site (secao, descricao) VALUES
('Quem Somos', 'A Vizan Engenharia e Construtora é referência no mercado de construção civil, entregando projetos com rigor técnico, pontualidade e excelência.'),
('Serviços', 'Execução completa de obras residenciais, comerciais e industriais, projetos de engenharia e reformas.'),
('Projetos', 'Conheça algumas obras executadas com foco em qualidade, segurança e prazo.'),
('Onde Estamos', 'Atendimento regional com equipes próprias prontas para atender projetos em todo o estado.')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
