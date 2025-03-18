<?php
require_once 'conexao.php';

class DashboardCRUD {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // CREATE - Registrar nova movimentação
    public function registrarMovimentacao($tipo, $usuario_id, $produto_id, $quantidade, $armazem_origem = null, $armazem_destino = null, $observacao = '') {
        // Converter quantidade de caixas para unidades (1 caixa = 30 medicamentos)
        $quantidade_unidades = $quantidade * 30;
        
        $sql = "INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, armazem_origem_id, armazem_destino_id, observacao, data_movimentacao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $resultado = $stmt->execute([$produto_id, $usuario_id, $tipo, $quantidade_unidades, $armazem_origem, $armazem_destino, $observacao]);
        
        if ($resultado) {
            // Atualizar estoque
            $this->atualizarEstoque($produto_id, $quantidade_unidades, $tipo, $armazem_origem, $armazem_destino);
        }
        
        return $resultado;
    }
    
    // READ - Obter estatísticas do dashboard
    public function obterEstatisticas() {
        $estatisticas = [
            'total_medicamentos' => 0,
            'em_estoque' => 0,
            'estoque_baixo' => 0,
            'proximos_vencimento' => 0
        ];
        
        // Total de medicamentos em unidades (considerando 30 unidades por caixa)
        $sql = "SELECT SUM(quantidade) as total FROM estoques";
        $result = $this->conn->query($sql);
        $total_unidades = $result->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $estatisticas['total_medicamentos'] = $total_unidades;
        
        // Total em estoque (em caixas)
        $estatisticas['em_estoque'] = ceil($total_unidades / 30); // Cada caixa contém 30 medicamentos
        
        // Estoque baixo (em caixas)
        $sql = "SELECT COUNT(DISTINCT p.id_produto) as total 
                FROM produtos p 
                JOIN estoques e ON p.id_produto = e.produto_id 
                WHERE e.quantidade < p.estoque_minimo";
        $result = $this->conn->query($sql);
        $estatisticas['estoque_baixo'] = $result->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Próximos ao vencimento
        $estatisticas['proximos_vencimento'] = 2;
        
        return $estatisticas;
    }
    
    // READ - Obter dados para o gráfico
    public function obterDadosGrafico($periodo = 7) {
        try {
            // Calcular datas
            $data_inicio = date('Y-m-d', strtotime("-$periodo days"));
            $data_atual = date('Y-m-d');
            
            // Arrays para armazenar os dados
            $datas = [];
            $entradas = [];
            $saidas = [];
            
            // Preencher arrays com zeros para todas as datas
            $data_temp = new DateTime($data_inicio);
            $data_fim = new DateTime($data_atual);
            
            while ($data_temp <= $data_fim) {
                $datas[] = $data_temp->format('d/m');
                $entradas[] = 0;
                $saidas[] = 0;
                $data_temp->modify('+1 day');
            }
            
            // Buscar movimentações de entrada
            $sql = "SELECT DATE(data_movimentacao) as data, SUM(quantidade) as total 
                    FROM movimentacoes 
                    WHERE tipo = 'Entrada' 
                    AND DATE(data_movimentacao) BETWEEN ? AND ? 
                    GROUP BY DATE(data_movimentacao)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$data_inicio, $data_atual]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($resultados as $row) {
                $data = new DateTime($row['data']);
                $indice = (int)$data->diff(new DateTime($data_inicio))->days;
                if (isset($entradas[$indice])) {
                    $entradas[$indice] = (int)$row['total'];
                }
            }
            
            // Buscar movimentações de saída
            $sql = "SELECT DATE(data_movimentacao) as data, SUM(quantidade) as total 
                    FROM movimentacoes 
                    WHERE tipo = 'Saída' 
                    AND DATE(data_movimentacao) BETWEEN ? AND ? 
                    GROUP BY DATE(data_movimentacao)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$data_inicio, $data_atual]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($resultados as $row) {
                $data = new DateTime($row['data']);
                $indice = (int)$data->diff(new DateTime($data_inicio))->days;
                if (isset($saidas[$indice])) {
                    $saidas[$indice] = (int)$row['total'];
                }
            }
            
            // Retornar os dados no formato esperado pelo gráfico
            return [
                'datas' => $datas,
                'entradas' => $entradas,
                'saidas' => $saidas
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter dados do gráfico: " . $e->getMessage());
            return [
                'datas' => [],
                'entradas' => [],
                'saidas' => []
            ];
        }
    }
    
    // READ - Obter atividades recentes
    public function obterAtividadesRecentes($limite = 5) {
        $sql = "SELECT m.*, p.nome as produto_nome, u.nome as usuario_nome,
                ao.nome as armazem_origem_nome, ad.nome as armazem_destino_nome
                FROM movimentacoes m
                LEFT JOIN produtos p ON m.produto_id = p.id_produto
                LEFT JOIN usuarios u ON m.usuario_id = u.id_usuario
                LEFT JOIN armazens ao ON m.armazem_origem_id = ao.id_armazem
                LEFT JOIN armazens ad ON m.armazem_destino_id = ad.id_armazem
                ORDER BY m.data_movimentacao DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limite]);
        $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatar dados para exibição
        foreach ($atividades as &$atividade) {
            $data = new DateTime($atividade['data_movimentacao']);
            $atividade['data_formatada'] = $data->format('d/m/Y H:i');
            
            if ($atividade['tipo'] === 'Entrada') {
                $atividade['descricao'] = "Entrada de {$atividade['quantidade']} unidades de {$atividade['produto_nome']}";
                $atividade['tipo'] = 'entrada';
            } else if ($atividade['tipo'] === 'Saída') {
                $atividade['descricao'] = "Saída de {$atividade['quantidade']} unidades de {$atividade['produto_nome']}";
                $atividade['tipo'] = 'saida';
            } else {
                $atividade['descricao'] = "Transferência de {$atividade['quantidade']} unidades de {$atividade['produto_nome']}";
                $atividade['tipo'] = 'transferencia';
            }
        }
        
        return $atividades;
    }
    
    // UPDATE - Atualizar estoque
    private function atualizarEstoque($produto_id, $quantidade, $tipo, $armazem_origem = null, $armazem_destino = null) {
        if ($tipo === 'Entrada' && $armazem_destino) {
            // Verificar se já existe registro para este produto e armazém
            $sql = "SELECT id_estoque FROM estoques WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$produto_id, $armazem_destino]);
            $estoque = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($estoque) {
                // Atualizar estoque existente
                $sql = "UPDATE estoques SET quantidade = quantidade + ? WHERE id_estoque = ?";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$quantidade, $estoque['id_estoque']]);
            } else {
                // Criar novo registro de estoque
                $sql = "INSERT INTO estoques (produto_id, armazem_id, quantidade) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$produto_id, $armazem_destino, $quantidade]);
            }
        } 
        else if ($tipo === 'Saída' && $armazem_origem) {
            // Verificar se há estoque suficiente
            $sql = "SELECT id_estoque, quantidade FROM estoques WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$produto_id, $armazem_origem]);
            $estoque = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($estoque && $estoque['quantidade'] >= $quantidade) {
                $sql = "UPDATE estoques SET quantidade = quantidade - ? WHERE id_estoque = ?";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([$quantidade, $estoque['id_estoque']]);
            } else {
                throw new Exception('Estoque insuficiente para realizar a saída');
            }
        }
        else if ($tipo === 'Transferência' && $armazem_origem && $armazem_destino) {
            // Verificar se há estoque suficiente na origem
            $sql = "SELECT id_estoque, quantidade FROM estoques WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$produto_id, $armazem_origem]);
            $estoque_origem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($estoque_origem && $estoque_origem['quantidade'] >= $quantidade) {
                // Reduzir da origem
                $sql = "UPDATE estoques SET quantidade = quantidade - ? WHERE id_estoque = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$quantidade, $estoque_origem['id_estoque']]);
                
                // Verificar se já existe registro no destino
                $sql = "SELECT id_estoque FROM estoques WHERE produto_id = ? AND armazem_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$produto_id, $armazem_destino]);
                $estoque_destino = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($estoque_destino) {
                    // Atualizar estoque existente no destino
                    $sql = "UPDATE estoques SET quantidade = quantidade + ? WHERE id_estoque = ?";
                    $stmt = $this->conn->prepare($sql);
                    return $stmt->execute([$quantidade, $estoque_destino['id_estoque']]);
                } else {
                    // Criar novo registro de estoque no destino
                    $sql = "INSERT INTO estoques (produto_id, armazem_id, quantidade) VALUES (?, ?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    return $stmt->execute([$produto_id, $armazem_destino, $quantidade]);
                }
            } else {
                throw new Exception('Estoque insuficiente para realizar a transferência');
            }
        }
        
        return false;
    }
    
    // DELETE - Remover movimentação
    public function removerMovimentacao($id) {
        // Obter detalhes da movimentação antes de excluir
        $sql = "SELECT * FROM movimentacoes WHERE id_movimentacao = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $movimentacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$movimentacao) {
            return false;
        }
        
        // Reverter a atualização de estoque
        $this->reverterEstoque($movimentacao);
        
        // Excluir a movimentação
        $sql = "DELETE FROM movimentacoes WHERE id_movimentacao = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    // Método auxiliar para reverter estoque
    private function reverterEstoque($movimentacao) {
        $produto_id = $movimentacao['produto_id'];
        $quantidade = $movimentacao['quantidade']; // Já está em unidades no banco de dados
        $tipo = $movimentacao['tipo'];
        $armazem_origem = $movimentacao['armazem_origem_id'];
        $armazem_destino = $movimentacao['armazem_destino_id'];
        
        if ($tipo === 'Entrada' && $armazem_destino) {
            // Reverter entrada: reduzir do armazém de destino
            $sql = "UPDATE estoques SET quantidade = quantidade - ? WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$quantidade, $produto_id, $armazem_destino]);
        } 
        else if ($tipo === 'Saída' && $armazem_origem) {
            // Reverter saída: aumentar no armazém de origem
            $sql = "UPDATE estoques SET quantidade = quantidade + ? WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$quantidade, $produto_id, $armazem_origem]);
        }
        else if ($tipo === 'Transferência' && $armazem_origem && $armazem_destino) {
            // Reverter transferência: aumentar na origem e reduzir no destino
            $sql = "UPDATE estoques SET quantidade = quantidade + ? WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$quantidade, $produto_id, $armazem_origem]);
            
            $sql = "UPDATE estoques SET quantidade = quantidade - ? WHERE produto_id = ? AND armazem_id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$quantidade, $produto_id, $armazem_destino]);
        }
        
        return false;
    }
    
    // Verificar estoques baixos
    public function verificarEstoquesBaixos() {
        $sql = "SELECT p.id_produto, p.nome, e.quantidade, p.estoque_minimo 
                FROM produtos p 
                JOIN estoques e ON p.id_produto = e.produto_id 
                WHERE e.quantidade < p.estoque_minimo";
        
        $result = $this->conn->query($sql);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Criar instância do CRUD se necessário
if (isset($conn)) {
    $dashboardCRUD = new DashboardCRUD($conn);
}
?>
