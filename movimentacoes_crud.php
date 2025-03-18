<?php
require_once 'conexao.php';

/**
 * Função para listar movimentações
 * @param int $limite Número máximo de movimentações a retornar (0 = sem limite)
 * @param int $pagina Número da página para paginação
 * @param string $ordem Campo para ordenação
 * @param string $direcao Direção da ordenação (ASC ou DESC)
 * @param array $filtros Array com filtros (tipo, produto_id, armazem_id, data_inicio, data_fim)
 * @return array Array com as movimentações encontradas
 */
function listarMovimentacoes($limite = 0, $pagina = 1, $ordem = 'data_movimentacao', $direcao = 'DESC', $filtros = array()) {
    global $conn;
    
    $movimentacoes = array();
    $offset = ($pagina - 1) * $limite;
    $limitQuery = $limite > 0 ? "LIMIT $offset, $limite" : "";
    
    // Validar parâmetros de ordenação para evitar injeção SQL
    $ordemPermitida = array('id_movimentacao', 'data_movimentacao', 'tipo', 'quantidade', 'produto_id');
    $ordem = in_array($ordem, $ordemPermitida) ? $ordem : 'data_movimentacao';
    $direcao = strtoupper($direcao) === 'ASC' ? 'ASC' : 'DESC';
    
    // Construir a cláusula WHERE com os filtros
    $whereClause = array();
    
    if (!empty($filtros['tipo'])) {
        $tipo = $conn->real_escape_string($filtros['tipo']);
        $whereClause[] = "m.tipo = '$tipo'";
    }
    
    if (!empty($filtros['produto_id'])) {
        $produto_id = (int)$filtros['produto_id'];
        $whereClause[] = "m.produto_id = $produto_id";
    }
    
    if (!empty($filtros['armazem_origem_id'])) {
        $armazem_id = (int)$filtros['armazem_origem_id'];
        $whereClause[] = "m.armazem_origem_id = $armazem_id";
    }
    
    if (!empty($filtros['armazem_destino_id'])) {
        $armazem_id = (int)$filtros['armazem_destino_id'];
        $whereClause[] = "m.armazem_destino_id = $armazem_id";
    }
    
    if (!empty($filtros['data_inicio'])) {
        $data_inicio = $conn->real_escape_string($filtros['data_inicio']);
        $whereClause[] = "DATE(m.data_movimentacao) >= '$data_inicio'";
    }
    
    if (!empty($filtros['data_fim'])) {
        $data_fim = $conn->real_escape_string($filtros['data_fim']);
        $whereClause[] = "DATE(m.data_movimentacao) <= '$data_fim'";
    }
    
    $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
    
    $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo, 
            u.nome as usuario_nome, a1.nome as armazem_origem_nome, a2.nome as armazem_destino_nome
            FROM movimentacoes m
            JOIN produtos p ON m.produto_id = p.id_produto
            JOIN usuarios u ON m.usuario_id = u.id_usuario
            LEFT JOIN armazens a1 ON m.armazem_origem_id = a1.id_armazem
            LEFT JOIN armazens a2 ON m.armazem_destino_id = a2.id_armazem
            $whereSQL
            ORDER BY m.$ordem $direcao
            $limitQuery";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $movimentacoes[] = $row;
        }
    }
    
    return $movimentacoes;
}

/**
 * Função para contar movimentações (para paginação)
 * @param array $filtros Array com filtros (tipo, produto_id, armazem_id, data_inicio, data_fim)
 * @return int Total de movimentações
 */
function contarMovimentacoes($filtros = array()) {
    global $conn;
    
    // Construir a cláusula WHERE com os filtros
    $whereClause = array();
    
    if (!empty($filtros['tipo'])) {
        $tipo = $conn->real_escape_string($filtros['tipo']);
        $whereClause[] = "tipo = '$tipo'";
    }
    
    if (!empty($filtros['produto_id'])) {
        $produto_id = (int)$filtros['produto_id'];
        $whereClause[] = "produto_id = $produto_id";
    }
    
    if (!empty($filtros['armazem_origem_id'])) {
        $armazem_id = (int)$filtros['armazem_origem_id'];
        $whereClause[] = "armazem_origem_id = $armazem_id";
    }
    
    if (!empty($filtros['armazem_destino_id'])) {
        $armazem_id = (int)$filtros['armazem_destino_id'];
        $whereClause[] = "armazem_destino_id = $armazem_id";
    }
    
    if (!empty($filtros['data_inicio'])) {
        $data_inicio = $conn->real_escape_string($filtros['data_inicio']);
        $whereClause[] = "DATE(data_movimentacao) >= '$data_inicio'";
    }
    
    if (!empty($filtros['data_fim'])) {
        $data_fim = $conn->real_escape_string($filtros['data_fim']);
        $whereClause[] = "DATE(data_movimentacao) <= '$data_fim'";
    }
    
    $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
    
    $sql = "SELECT COUNT(*) as total FROM movimentacoes $whereSQL";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    return 0;
}

/**
 * Função para obter uma movimentação pelo ID
 * @param int $id ID da movimentação
 * @return array|null Array com os dados da movimentação ou null se não encontrada
 */
function obterMovimentacaoPorId($id) {
    global $conn;
    
    $id = (int)$id;
    
    $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo, 
            u.nome as usuario_nome, a1.nome as armazem_origem_nome, a2.nome as armazem_destino_nome
            FROM movimentacoes m
            JOIN produtos p ON m.produto_id = p.id_produto
            JOIN usuarios u ON m.usuario_id = u.id_usuario
            LEFT JOIN armazens a1 ON m.armazem_origem_id = a1.id_armazem
            LEFT JOIN armazens a2 ON m.armazem_destino_id = a2.id_armazem
            WHERE m.id_movimentacao = $id";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
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
    $armazem_origem_id = $armazem_origem_id ? (int)$armazem_origem_id : null;
    $armazem_destino_id = $armazem_destino_id ? (int)$armazem_destino_id : null;
    $observacao = $conn->real_escape_string($observacao);
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Inserir movimentação
        $sql = "INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, armazem_origem_id, armazem_destino_id, observacao)
                VALUES ($produto_id, $usuario_id, '$tipo', $quantidade, " . 
                ($armazem_origem_id ? $armazem_origem_id : "NULL") . ", " . 
                ($armazem_destino_id ? $armazem_destino_id : "NULL") . ", '$observacao')";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao registrar movimentação: " . $conn->error);
        }
        
        $movimentacao_id = $conn->insert_id;
        
        // Atualizar estoque
        if ($tipo == 'Entrada' && $armazem_destino_id) {
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
        elseif ($tipo == 'Saída' && $armazem_origem_id) {
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
        elseif ($tipo == 'Transferência' && $armazem_origem_id && $armazem_destino_id) {
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

// Se este arquivo for chamado diretamente, processar a requisição como API
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    
    // Verificar o tipo de solicitação
    $acao = isset($_GET['acao']) ? $_GET['acao'] : '';
    
    switch ($acao) {
        case 'listar':
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'data_movimentacao';
            $direcao = isset($_GET['direcao']) ? $_GET['direcao'] : 'DESC';
            
            // Obter filtros
            $filtros = array();
            if (isset($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
            if (isset($_GET['produto_id'])) $filtros['produto_id'] = (int)$_GET['produto_id'];
            if (isset($_GET['armazem_origem_id'])) $filtros['armazem_origem_id'] = (int)$_GET['armazem_origem_id'];
            if (isset($_GET['armazem_destino_id'])) $filtros['armazem_destino_id'] = (int)$_GET['armazem_destino_id'];
            if (isset($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
            if (isset($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
            
            $movimentacoes = listarMovimentacoes($limite, $pagina, $ordem, $direcao, $filtros);
            $total = contarMovimentacoes($filtros);
            
            echo json_encode(array(
                'movimentacoes' => $movimentacoes,
                'total' => $total,
                'paginas' => $limite > 0 ? ceil($total / $limite) : 1
            ));
            break;
            
        case 'obter':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                echo json_encode(array('erro' => 'ID inválido'));
                exit;
            }
            
            $movimentacao = obterMovimentacaoPorId($id);
            
            if ($movimentacao) {
                echo json_encode($movimentacao);
            } else {
                echo json_encode(array('erro' => 'Movimentação não encontrada'));
            }
            break;
            
        case 'registrar':
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('erro' => 'Método não permitido'));
                exit;
            }
            
            // Obter dados do POST
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados) {
                echo json_encode(array('erro' => 'Dados inválidos'));
                exit;
            }
            
            // Validar campos obrigatórios
            if (empty($dados['produto_id']) || empty($dados['tipo']) || empty($dados['quantidade'])) {
                echo json_encode(array('erro' => 'Produto, tipo e quantidade são obrigatórios'));
                exit;
            }
            
            // Validar tipo de movimentação
            if (!in_array($dados['tipo'], array('Entrada', 'Saída', 'Transferência'))) {
                echo json_encode(array('erro' => 'Tipo de movimentação inválido'));
                exit;
            }
            
            // Validar armazéns de acordo com o tipo
            if ($dados['tipo'] == 'Entrada' && empty($dados['armazem_destino_id'])) {
                echo json_encode(array('erro' => 'Armazém de destino é obrigatório para entradas'));
                exit;
            }
            
            if ($dados['tipo'] == 'Saída' && empty($dados['armazem_origem_id'])) {
                echo json_encode(array('erro' => 'Armazém de origem é obrigatório para saídas'));
                exit;
            }
            
            if ($dados['tipo'] == 'Transferência' && (empty($dados['armazem_origem_id']) || empty($dados['armazem_destino_id']))) {
                echo json_encode(array('erro' => 'Armazéns de origem e destino são obrigatórios para transferências'));
                exit;
            }
            
            // Obter ID do usuário logado (ou usar um padrão para testes)
            $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1;
            
            $resultado = registrarMovimentacao(
                $dados['produto_id'],
                $usuario_id,
                $dados['tipo'],
                $dados['quantidade'],
                $dados['armazem_origem_id'] ?? null,
                $dados['armazem_destino_id'] ?? null,
                $dados['observacao'] ?? ''
            );
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true, 'id' => $resultado));
            } else {
                echo json_encode(array('erro' => 'Erro ao registrar movimentação'));
            }
            break;
            
        default:
            echo json_encode(array('erro' => 'Ação não reconhecida'));
            break;
    }
}
?>
