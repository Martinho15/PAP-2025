<?php
require_once 'conexao.php';

/**
 * Função para obter estatísticas para o dashboard
 * @return array Array com as estatísticas para o dashboard
 */
function obterEstatisticasDashboard() {
    global $conn;
    $estatisticas = array();
    
    // Total de medicamentos (produtos)
    $sql = "SELECT COUNT(*) as total FROM produtos";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $estatisticas['total_medicamentos'] = $row['total'];
    } else {
        $estatisticas['total_medicamentos'] = 0;
    }
    
    // Total em estoque
    $sql = "SELECT SUM(quantidade) as total FROM estoques";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $estatisticas['em_estoque'] = $row['total'] ? $row['total'] : 0;
    } else {
        $estatisticas['em_estoque'] = 0;
    }
    
    // Produtos com estoque baixo
    $sql = "SELECT COUNT(*) as total FROM produtos p 
            JOIN estoques e ON p.id_produto = e.produto_id 
            WHERE e.quantidade <= p.estoque_minimo";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $estatisticas['estoque_baixo'] = $row['total'];
    } else {
        $estatisticas['estoque_baixo'] = 0;
    }
    
    // Não temos campo de data de validade na tabela de produtos, 
    // então vamos simular produtos próximos ao vencimento
    $estatisticas['proximos_vencimento'] = 12; // Valor fixo para exemplo
    
    return $estatisticas;
}

/**
 * Função para obter dados de movimentações para o gráfico
 * @param int $dias Número de dias para buscar dados
 * @return array Array com dados para o gráfico
 */
function obterDadosGrafico($dias = 7) {
    global $conn;
    $dados = array(
        'datas' => array(),
        'entradas' => array(),
        'saidas' => array()
    );
    
    // Obter datas dos últimos X dias
    $datas = array();
    for ($i = $dias - 1; $i >= 0; $i--) {
        $data = date('Y-m-d', strtotime("-$i days"));
        $datas[] = $data;
        $dados['datas'][] = date('d/m', strtotime($data));
    }
    
    // Obter entradas e saídas por dia
    foreach ($datas as $data) {
        // Entradas
        $sql = "SELECT COALESCE(SUM(quantidade), 0) as total FROM movimentacoes 
                WHERE tipo = 'Entrada' 
                AND DATE(data_movimentacao) = '$data'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dados['entradas'][] = (int)$row['total'];
        } else {
            $dados['entradas'][] = 0;
        }
        
        // Saídas
        $sql = "SELECT COALESCE(SUM(quantidade), 0) as total FROM movimentacoes 
                WHERE tipo = 'Saída' 
                AND DATE(data_movimentacao) = '$data'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dados['saidas'][] = (int)$row['total'];
        } else {
            $dados['saidas'][] = 0;
        }
    }
    
    return $dados;
}

/**
 * Função para obter atividades recentes
 * @param int $limite Número máximo de atividades a retornar
 * @return array Array com atividades recentes
 */
function obterAtividadesRecentes($limite = 5) {
    global $conn;
    $atividades = array();
    
    $sql = "SELECT m.id_movimentacao, m.tipo, m.quantidade, m.data_movimentacao, 
            p.nome as produto_nome, u.nome as usuario_nome,
            a1.nome as armazem_origem, a2.nome as armazem_destino
            FROM movimentacoes m
            JOIN produtos p ON m.produto_id = p.id_produto
            JOIN usuarios u ON m.usuario_id = u.id_usuario
            LEFT JOIN armazens a1 ON m.armazem_origem_id = a1.id_armazem
            LEFT JOIN armazens a2 ON m.armazem_destino_id = a2.id_armazem
            ORDER BY m.data_movimentacao DESC
            LIMIT $limite";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $atividades[] = $row;
        }
    }
    
    return $atividades;
}

/**
 * Função para registrar uma nova movimentação
 * @param int $produto_id ID do produto
 * @param int $usuario_id ID do usuário
 * @param string $tipo Tipo de movimentação (Entrada, Saída, Transferência)
 * @param int $quantidade Quantidade movimentada
 * @param int|null $armazem_origem_id ID do armazém de origem (opcional)
 * @param int|null $armazem_destino_id ID do armazém de destino (opcional)
 * @param string $observacao Observação sobre a movimentação (opcional)
 * @return bool|int ID da movimentação ou false em caso de erro
 */
function registrarMovimentacao($produto_id, $usuario_id, $tipo, $quantidade, $armazem_origem_id = null, $armazem_destino_id = null, $observacao = '') {
    global $conn;
    
    // Validar parâmetros
    $produto_id = (int)$produto_id;
    $usuario_id = (int)$usuario_id;
    $tipo = $conn->real_escape_string($tipo);
    $quantidade = (int)$quantidade;
    $armazem_origem_id = $armazem_origem_id ? (int)$armazem_origem_id : "NULL";
    $armazem_destino_id = $armazem_destino_id ? (int)$armazem_destino_id : "NULL";
    $observacao = $conn->real_escape_string($observacao);
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Inserir movimentação
        $sql = "INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, armazem_origem_id, armazem_destino_id, observacao)
                VALUES ($produto_id, $usuario_id, '$tipo', $quantidade, $armazem_origem_id, $armazem_destino_id, '$observacao')";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao registrar movimentação: " . $conn->error);
        }
        
        $movimentacao_id = $conn->insert_id;
        
        // Atualizar estoque
        if ($tipo == 'Entrada' && $armazem_destino_id != "NULL") {
            // Verificar se já existe estoque para este produto neste armazém
            $sql = "SELECT id_estoque FROM estoques 
                    WHERE produto_id = $produto_id AND armazem_id = $armazem_destino_id";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                // Atualizar estoque existente
                $row = $result->fetch_assoc();
                $sql = "UPDATE estoques SET quantidade = quantidade + $quantidade 
                        WHERE id_estoque = {$row['id_estoque']}";
            } else {
                // Criar novo registro de estoque
                $sql = "INSERT INTO estoques (produto_id, armazem_id, quantidade) 
                        VALUES ($produto_id, $armazem_destino_id, $quantidade)";
            }
            
            if (!$conn->query($sql)) {
                throw new Exception("Erro ao atualizar estoque: " . $conn->error);
            }
        } 
        elseif ($tipo == 'Saída' && $armazem_origem_id != "NULL") {
            // Verificar se há estoque suficiente
            $sql = "SELECT id_estoque, quantidade FROM estoques 
                    WHERE produto_id = $produto_id AND armazem_id = $armazem_origem_id";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['quantidade'] < $quantidade) {
                    throw new Exception("Estoque insuficiente para esta saída");
                }
                
                // Atualizar estoque
                $sql = "UPDATE estoques SET quantidade = quantidade - $quantidade 
                        WHERE id_estoque = {$row['id_estoque']}";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Erro ao atualizar estoque: " . $conn->error);
                }
            } else {
                throw new Exception("Não há estoque registrado para este produto neste armazém");
            }
        }
        elseif ($tipo == 'Transferência' && $armazem_origem_id != "NULL" && $armazem_destino_id != "NULL") {
            // Verificar se há estoque suficiente na origem
            $sql = "SELECT id_estoque, quantidade FROM estoques 
                    WHERE produto_id = $produto_id AND armazem_id = $armazem_origem_id";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['quantidade'] < $quantidade) {
                    throw new Exception("Estoque insuficiente para esta transferência");
                }
                
                // Reduzir estoque na origem
                $sql = "UPDATE estoques SET quantidade = quantidade - $quantidade 
                        WHERE id_estoque = {$row['id_estoque']}";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Erro ao atualizar estoque de origem: " . $conn->error);
                }
                
                // Verificar se já existe estoque no destino
                $sql = "SELECT id_estoque FROM estoques 
                        WHERE produto_id = $produto_id AND armazem_id = $armazem_destino_id";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    // Atualizar estoque existente
                    $row = $result->fetch_assoc();
                    $sql = "UPDATE estoques SET quantidade = quantidade + $quantidade 
                            WHERE id_estoque = {$row['id_estoque']}";
                } else {
                    // Criar novo registro de estoque
                    $sql = "INSERT INTO estoques (produto_id, armazem_id, quantidade) 
                            VALUES ($produto_id, $armazem_destino_id, $quantidade)";
                }
                
                if (!$conn->query($sql)) {
                    throw new Exception("Erro ao atualizar estoque de destino: " . $conn->error);
                }
            } else {
                throw new Exception("Não há estoque registrado para este produto no armazém de origem");
            }
        }
        
        // Registrar auditoria
        $sql = "INSERT INTO auditoria_movimentacoes (movimentacao_id, usuario_id, acao)
                VALUES ($movimentacao_id, $usuario_id, 'INSERIR')";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao registrar auditoria: " . $conn->error);
        }
        
        // Commit da transação
        $conn->commit();
        return $movimentacao_id;
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        
        // Registrar erro no log
        $mensagem = $conn->real_escape_string($e->getMessage());
        $sql = "INSERT INTO logs_erros (mensagem, nivel) VALUES ('$mensagem', 'ERROR')";
        $conn->query($sql);
        
        return false;
    }
}

/**
 * Função para obter dados do dashboard em formato JSON
 * @return string JSON com os dados do dashboard
 */
function obterDadosDashboardJSON() {
    $estatisticas = obterEstatisticasDashboard();
    $dados_grafico = obterDadosGrafico(7);
    $atividades = obterAtividadesRecentes(5);
    
    $dados = array(
        'estatisticas' => $estatisticas,
        'grafico' => $dados_grafico,
        'atividades' => $atividades
    );
    
    return json_encode($dados);
}

// Se este arquivo for chamado diretamente, retornar os dados em JSON
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    echo obterDadosDashboardJSON();
}
?>
