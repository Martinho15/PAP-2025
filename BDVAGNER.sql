-- Criar o banco de dados caso não exista
CREATE DATABASE IF NOT EXISTS `estoque` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `estoque`;

-- Tabela de usuários
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`email`)  -- Índice para melhorar a busca por email
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de armazéns
CREATE TABLE `armazens` (
  `id_armazem` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `localizacao` VARCHAR(255) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de fornecedores
CREATE TABLE `fornecedores` (
  `id_fornecedor` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(130) NOT NULL,
  `contato` VARCHAR(50) NOT NULL,
  `historico_fornecimento` TEXT DEFAULT NULL,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de categorias
CREATE TABLE `categorias` (
  `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL UNIQUE,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de produtos
CREATE TABLE `produtos` (
  `id_produto` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `descricao` TEXT DEFAULT NULL,
  `fornecedor_id` INT NOT NULL,
  `unidade_medida` VARCHAR(40) DEFAULT NULL,
  `imagem` VARCHAR(255) DEFAULT NULL,
  `estoque_minimo` INT NOT NULL DEFAULT 0 CHECK (`estoque_minimo` >= 0),  -- Validação para estoque mínimo
  `estoque_maximo` INT NOT NULL DEFAULT 1000 CHECK (`estoque_maximo` > `estoque_minimo`),  -- Validação para estoque máximo
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (`preco` >= 0),  -- Validação para preço
  FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id_fornecedor`),
  INDEX (`nome`),  -- Índice para melhorar a busca por nome
  INDEX (`codigo`)  -- Índice para melhorar a busca por código
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de relação entre produtos e categorias
CREATE TABLE `produto_categoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_produto` INT NOT NULL,
  `id_categoria` INT NOT NULL,
  FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de estoques
CREATE TABLE `estoques` (
  `id_estoque` INT AUTO_INCREMENT PRIMARY KEY,
  `quantidade` INT DEFAULT 0 CHECK (`quantidade` >= 0),  -- Validação para quantidade
  `produto_id` INT NOT NULL,
  `armazem_id` INT NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`armazem_id`) REFERENCES `armazens`(`id_armazem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de movimentações de estoque
CREATE TABLE `movimentacoes` (
  `id_movimentacao` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('Entrada','Saída','Transferência') NOT NULL,
  `quantidade` INT NOT NULL CHECK (`quantidade` > 0),  -- Validação para quantidade
  `armazem_origem_id` INT DEFAULT NULL,
  `armazem_destino_id` INT DEFAULT NULL,
  `data_movimentacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `observacao` TEXT DEFAULT NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id_usuario`),
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`armazem_origem_id`) REFERENCES `armazens`(`id_armazem`),
  FOREIGN KEY (`armazem_destino_id`) REFERENCES `armazens`(`id_armazem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabelas de controle de permissões e funções
CREATE TABLE `roles` (
    `id_role` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `permissions` (
    `id_permission` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `role_permissions` (-- Criar o banco de dados caso não exista
CREATE DATABASE IF NOT EXISTS `estoque` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `estoque`;

-- Tabela de usuários
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`email`)  -- Índice para melhorar a busca por email
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de armazéns
CREATE TABLE `armazens` (
  `id_armazem` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `localizacao` VARCHAR(255) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de fornecedores
CREATE TABLE `fornecedores` (
  `id_fornecedor` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(130) NOT NULL,
  `contato` VARCHAR(50) NOT NULL,
  `historico_fornecimento` TEXT DEFAULT NULL,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de categorias
CREATE TABLE `categorias` (
  `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL UNIQUE,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de produtos
CREATE TABLE `produtos` (
  `id_produto` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `descricao` TEXT DEFAULT NULL,
  `fornecedor_id` INT NOT NULL,
  `unidade_medida` VARCHAR(40) DEFAULT NULL,
  `imagem` VARCHAR(255) DEFAULT NULL,
  `estoque_minimo` INT NOT NULL DEFAULT 0 CHECK (`estoque_minimo` >= 0),  -- Validação para estoque mínimo
  `estoque_maximo` INT NOT NULL DEFAULT 1000 CHECK (`estoque_maximo` > `estoque_minimo`),  -- Validação para estoque máximo
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (`preco` >= 0),  -- Validação para preço
  FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id_fornecedor`),
  INDEX (`nome`),  -- Índice para melhorar a busca por nome
  INDEX (`codigo`)  -- Índice para melhorar a busca por código
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de relação entre produtos e categorias
CREATE TABLE `produto_categoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_produto` INT NOT NULL,
  `id_categoria` INT NOT NULL,
  FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de estoques
CREATE TABLE `estoques` (
  `id_estoque` INT AUTO_INCREMENT PRIMARY KEY,
  `quantidade` INT DEFAULT 0 CHECK (`quantidade` >= 0),  -- Validação para quantidade
  `produto_id` INT NOT NULL,
  `armazem_id` INT NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`armazem_id`) REFERENCES `armazens`(`id_armazem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de movimentações de estoque
CREATE TABLE `movimentacoes` (
  `id_movimentacao` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('Entrada','Saída','Transferência') NOT NULL,
  `quantidade` INT NOT NULL CHECK (`quantidade` > 0),  -- Validação para quantidade
  `armazem_origem_id` INT DEFAULT NULL,
  `armazem_destino_id` INT DEFAULT NULL,
  `data_movimentacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `observacao` TEXT DEFAULT NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id_usuario`),
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`armazem_origem_id`) REFERENCES `armazens`(`id_armazem`),
  FOREIGN KEY (`armazem_destino_id`) REFERENCES `armazens`(`id_armazem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabelas de controle de permissões e funções
CREATE TABLE `roles` (
    `id_role` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `permissions` (
    `id_permission` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `role_permissions` (-- Criar o banco de dados caso não exista
CREATE DATABASE IF NOT EXISTS `estoque` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `estoque`;

-- Tabela de usuários
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`email`)  -- Índice para melhorar a busca por email
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de armazéns
CREATE TABLE `armazens` (
  `id_armazem` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `localizacao` VARCHAR(255) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de fornecedores
CREATE TABLE `fornecedores` (
  `id_fornecedor` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(130) NOT NULL,
  `contato` VARCHAR(50) NOT NULL,
  `historico_fornecimento` TEXT DEFAULT NULL,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de categorias
CREATE TABLE `categorias` (
  `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL UNIQUE,
  INDEX (`nome`)  -- Índice para melhorar a busca por nome
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de produtos
CREATE TABLE `produtos` (
  `id_produto` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `descricao` TEXT DEFAULT NULL,
  `fornecedor_id` INT NOT NULL,
  `unidade_medida` VARCHAR(40) DEFAULT NULL,
  `imagem` VARCHAR(255) DEFAULT NULL,
  `estoque_minimo` INT NOT NULL DEFAULT 0 CHECK (`estoque_minimo` >= 0),  -- Validação para estoque mínimo
  `estoque_maximo` INT NOT NULL DEFAULT 1000 CHECK (`estoque_maximo` > `estoque_minimo`),  -- Validação para estoque máximo
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (`preco` >= 0),  -- Validação para preço
  FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id_fornecedor`),
  INDEX (`nome`),  -- Índice para melhorar a busca por nome
  INDEX (`codigo`)  -- Índice para melhorar a busca por código
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de relação entre produtos e categorias
CREATE TABLE `produto_categoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_produto` INT NOT NULL,
  `id_categoria` INT NOT NULL,
  FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de estoques
CREATE TABLE `estoques` (
  `id_estoque` INT AUTO_INCREMENT PRIMARY KEY,
  `quantidade` INT DEFAULT 0 CHECK (`quantidade` >= 0),  -- Validação para quantidade
  `produto_id` INT NOT NULL,
  `armazem_id` INT NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`armazem_id`) REFERENCES `armazens`(`id_armazem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de movimentações de estoque
CREATE TABLE `movimentacoes` (
  `id_movimentacao` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('Entrada','Saída','Transferência') NOT NULL,
  `quantidade` INT NOT NULL CHECK (`quantidade` > 0),  -- Validação para quantidade
  `armazem_origem_id` INT DEFAULT NULL,
  `armazem_destino_id` INT DEFAULT NULL,
  `data_movimentacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `observacao` TEXT DEFAULT NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id_usuario`),
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id_produto`),
  FOREIGN KEY (`armazem_origem_id`) REFERENCES `armazens`(`id_armazem`),
  FOREIGN KEY (`armazem_destino_id`) REFERENCES `armazens`(`id_armazem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabelas de controle de permissões e funções
CREATE TABLE `roles` (
    `id_role` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `permissions` (
    `id_permission` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `role_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`),
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id_usuario`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`),
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id_usuario`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`),
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id_usuario`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;