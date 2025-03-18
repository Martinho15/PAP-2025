-- Inserir categorias (apenas se não existirem)
INSERT IGNORE INTO categorias (nome) VALUES 
('Analgésicos'),
('Antibióticos'),
('Anti-inflamatórios'),
('Vitaminas'),
('Vacinas');

-- Inserir fornecedores (apenas se não existirem)
INSERT IGNORE INTO fornecedores (nome, contato, historico_fornecimento) VALUES 
('Farmacêutica Angola', '+244 923456789', 'Fornecedor principal de medicamentos desde 2020'),
('MediLuanda', '+244 912345678', 'Especializado em antibióticos e vacinas'),
('Global Pharma', '+244 945678901', 'Fornecedor internacional com sede em Luanda');

-- Inserir armazéns (apenas se não existirem)
INSERT IGNORE INTO armazens (nome, localizacao, descricao) VALUES
('Armazém Central', 'Luanda', 'Armazém principal da capital'),
('Armazém Norte', 'Huambo', 'Armazém regional do norte'),
('Armazém Sul', 'Lubango', 'Armazém regional do sul');

-- Inserir usuários (apenas se não existirem)
INSERT IGNORE INTO usuarios (nome, email, senha) VALUES 
('Admin', 'admin@sistema.com', 'admin123'),
('João Silva', 'joao@sistema.com', 'joao123');

-- Inserir produtos (apenas se não existirem)
INSERT IGNORE INTO produtos (nome, codigo, descricao, fornecedor_id, unidade_medida, estoque_minimo, estoque_maximo, preco) VALUES 
('Paracetamol 500mg', 'MED001', 'Analgésico e antitérmico', 1, 'Caixa', 200, 2000, 300),
('Amoxicilina 500mg', 'MED002', 'Antibiótico de amplo espectro', 2, 'Caixa', 100, 500, 800),
('Ibuprofeno 400mg', 'MED003', 'Anti-inflamatório', 1, 'Caixa', 100, 600, 400),
('Vitamina C 1000mg', 'MED004', 'Suplemento vitamínico', 3, 'Frasco', 50, 400, 250),
('Vacina Gripe', 'MED005', 'Vacina contra Influenza', 2, 'Ampola', 30, 100, 2500);

-- Associar produtos às categorias (apenas se não existirem)
INSERT IGNORE INTO produto_categoria (id_produto, id_categoria) VALUES 
(1, 1), -- Paracetamol -> Analgésicos
(2, 2), -- Amoxicilina -> Antibióticos
(3, 3), -- Ibuprofeno -> Anti-inflamatórios
(4, 4), -- Vitamina C -> Vitaminas
(5, 5); -- Vacina Gripe -> Vacinas

-- Inserir estoques iniciais (apenas se não existirem)
INSERT IGNORE INTO estoques (produto_id, armazem_id, quantidade, descricao) VALUES 
(1, 1, 1000, 'Estoque inicial'),
(2, 1, 150, 'Estoque inicial'),
(3, 1, 80, 'Estoque inicial'),
(4, 1, 300, 'Estoque inicial'),
(5, 1, 50, 'Estoque inicial');

-- Inserir movimentações (últimos 7 dias)
-- Não usamos IGNORE aqui porque queremos registrar todas as movimentações
INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, armazem_origem_id, armazem_destino_id, data_movimentacao, observacao) VALUES 
(1, 1, 'Entrada', 200, NULL, 1, DATE_SUB(NOW(), INTERVAL 6 DAY), 'Compra inicial'),
(1, 2, 'Saída', 50, 1, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Dispensação'),
(2, 1, 'Entrada', 100, NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY), 'Compra semanal'),
(3, 2, 'Saída', 30, 1, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Dispensação'),
(4, 1, 'Entrada', 150, NULL, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Compra semanal'),
(2, 2, 'Saída', 20, 1, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Dispensação'),
(5, 1, 'Entrada', 25, NULL, 1, NOW(), 'Compra de vacinas'),
(4, 2, 'Saída', 45, 1, NULL, NOW(), 'Dispensação para hospital');
