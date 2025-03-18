<?php
require_once 'conexao.php';

/**
 * Função para listar notificações
 * @param int $usuario_id ID do usuário (0 para todas as notificações)
 * @param bool $apenas_nao_lidas Apenas notificações não lidas
 * @param int $limite Número máximo de notificações a retornar (0 = sem limite)
 * @return array Array com as notificações encontradas
 */
function listarNotificacoes($usuario_id = 0, $apenas_nao_lidas = false, $limite = 10) {
    global $conn;
    
    $notificacoes = array();
    $limitQuery = $limite > 0 ? "LIMIT $limite" : "";
    
    // Construir a cláusula WHERE
    $whereClause = array();
    
    if ($usuario_id > 0) {
        $whereClause[] = "(n.usuario_id = $usuario_id OR n.usuario_id IS NULL)";
    }
    
    if ($apenas_nao_lidas) {
        $whereClause[] = "n.lida = 0";
    }
    
    $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
    
    $sql = "SELECT n.*, u.nome as usuario_nome
            FROM notificacoes n
            LEFT JOIN usuarios u ON n.usuario_id = u.id_usuario
            $whereSQL
            ORDER BY n.data_criacao DESC
            $limitQuery";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notificacoes[] = $row;
        }
    }
    
    return $notificacoes;
}

/**
 * Função para contar notificações não lidas
 * @param int $usuario_id ID do usuário (0 para todas as notificações)
 * @return int Total de notificações não lidas
 */
function contarNotificacoesNaoLidas($usuario_id = 0) {
    global $conn;
    
    // Construir a cláusula WHERE
    $whereClause = array("lida = 0");
    
    if ($usuario_id > 0) {
        $whereClause[] = "(usuario_id = $usuario_id OR usuario_id IS NULL)";
    }
    
    $whereSQL = "WHERE " . implode(" AND ", $whereClause);
    
    $sql = "SELECT COUNT(*) as total FROM notificacoes $whereSQL";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    return 0;
}

/**
 * Função para criar uma nova notificação
 * @param string $titulo Título da notificação
 * @param string $mensagem Mensagem da notificação
 * @param string $tipo Tipo da notificação (info, warning, danger, success)
 * @param int|null $usuario_id ID do usuário (null para todos os usuários)
 * @param string|null $link Link relacionado à notificação
 * @return bool|int ID da notificação ou false em caso de erro
 */
function criarNotificacao($titulo, $mensagem, $tipo = 'info', $usuario_id = null, $link = null) {
    global $conn;
    
    // Validar e sanitizar parâmetros
    $titulo = $conn->real_escape_string(trim($titulo));
    $mensagem = $conn->real_escape_string(trim($mensagem));
    $tipo = $conn->real_escape_string(trim($tipo));
    $usuario_id = $usuario_id ? (int)$usuario_id : "NULL";
    $link = $link ? "'" . $conn->real_escape_string(trim($link)) . "'" : "NULL";
    
    $sql = "INSERT INTO notificacoes (titulo, mensagem, tipo, usuario_id, link)
            VALUES ('$titulo', '$mensagem', '$tipo', $usuario_id, $link)";
    
    if ($conn->query($sql)) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Função para marcar uma notificação como lida
 * @param int $id ID da notificação
 * @param int $usuario_id ID do usuário
 * @return bool True se marcada como lida com sucesso, false caso contrário
 */
function marcarNotificacaoComoLida($id, $usuario_id) {
    global $conn;
    
    $id = (int)$id;
    $usuario_id = (int)$usuario_id;
    
    // Verificar se a notificação existe e pertence ao usuário
    $sql = "SELECT id_notificacao FROM notificacoes 
            WHERE id_notificacao = $id 
            AND (usuario_id = $usuario_id OR usuario_id IS NULL)";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $sql = "UPDATE notificacoes SET lida = 1 WHERE id_notificacao = $id";
        return $conn->query($sql);
    }
    
    return false;
}

/**
 * Função para marcar todas as notificações de um usuário como lidas
 * @param int $usuario_id ID do usuário
 * @return bool True se marcadas como lidas com sucesso, false caso contrário
 */
function marcarTodasNotificacoesComoLidas($usuario_id) {
    global $conn;
    
    $usuario_id = (int)$usuario_id;
    
    $sql = "UPDATE notificacoes SET lida = 1 
            WHERE (usuario_id = $usuario_id OR usuario_id IS NULL) 
            AND lida = 0";
    
    return $conn->query($sql);
}

/**
 * Função para excluir uma notificação
 * @param int $id ID da notificação
 * @param int $usuario_id ID do usuário
 * @return bool True se excluída com sucesso, false caso contrário
 */
function excluirNotificacao($id, $usuario_id) {
    global $conn;
    
    $id = (int)$id;
    $usuario_id = (int)$usuario_id;
    
    // Verificar se a notificação existe e pertence ao usuário
    $sql = "SELECT id_notificacao FROM notificacoes 
            WHERE id_notificacao = $id 
            AND (usuario_id = $usuario_id OR usuario_id IS NULL)";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $sql = "DELETE FROM notificacoes WHERE id_notificacao = $id";
        return $conn->query($sql);
    }
    
    return false;
}

/**
 * Função para criar notificação de estoque baixo
 * @param int $produto_id ID do produto
 * @param int $quantidade Quantidade atual em estoque
 * @param int $estoque_minimo Estoque mínimo do produto
 * @return bool|int ID da notificação ou false em caso de erro
 */
function criarNotificacaoEstoqueBaixo($produto_id, $quantidade, $estoque_minimo) {
    global $conn;
    
    $produto_id = (int)$produto_id;
    
    // Obter informações do produto
    $sql = "SELECT nome FROM produtos WHERE id_produto = $produto_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $produto = $result->fetch_assoc();
        $titulo = "Estoque Crítico: {$produto['nome']}";
        $mensagem = "O produto {$produto['nome']} está com estoque baixo. Quantidade atual: $quantidade, Mínimo recomendado: $estoque_minimo";
        
        return criarNotificacao($titulo, $mensagem, 'danger', null, "produtos.php?id=$produto_id");
    }
    
    return false;
}

/**
 * Função para criar notificação de vencimento próximo
 * @param int $produto_id ID do produto
 * @param string $data_vencimento Data de vencimento
 * @param int $dias_alerta Dias de antecedência para alertar
 * @return bool|int ID da notificação ou false em caso de erro
 */
function criarNotificacaoVencimentoProximo($produto_id, $data_vencimento, $dias_alerta = 30) {
    global $conn;
    
    $produto_id = (int)$produto_id;
    $data_vencimento = $conn->real_escape_string($data_vencimento);
    
    // Calcular dias até o vencimento
    $hoje = new DateTime();
    $vencimento = new DateTime($data_vencimento);
    $diferenca = $hoje->diff($vencimento);
    $dias_ate_vencimento = $diferenca->days;
    
    // Se a data já passou, não criar notificação
    if ($vencimento < $hoje) {
        return false;
    }
    
    // Se ainda não está dentro do período de alerta, não criar notificação
    if ($dias_ate_vencimento > $dias_alerta) {
        return false;
    }
    
    // Obter informações do produto
    $sql = "SELECT nome FROM produtos WHERE id_produto = $produto_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $produto = $result->fetch_assoc();
        $titulo = "Vencimento Próximo: {$produto['nome']}";
        $mensagem = "O produto {$produto['nome']} vencerá em $dias_ate_vencimento dias (Data: " . date('d/m/Y', strtotime($data_vencimento)) . ")";
        
        return criarNotificacao($titulo, $mensagem, 'warning', null, "produtos.php?id=$produto_id");
    }
    
    return false;
}

/**
 * Função para verificar e criar notificações de estoque baixo
 * @return int Número de notificações criadas
 */
function verificarEstoquesBaixos() {
    global $conn;
    
    $count = 0;
    
    $sql = "SELECT p.id_produto, p.nome, p.estoque_minimo, e.quantidade
            FROM produtos p
            JOIN estoques e ON p.id_produto = e.produto_id
            WHERE e.quantidade <= p.estoque_minimo";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (criarNotificacaoEstoqueBaixo($row['id_produto'], $row['quantidade'], $row['estoque_minimo'])) {
                $count++;
            }
        }
    }
    
    return $count;
}

// Se este arquivo for chamado diretamente, processar a requisição como API
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    
    // Verificar o tipo de solicitação
    $acao = isset($_GET['acao']) ? $_GET['acao'] : '';
    
    switch ($acao) {
        case 'listar':
            $usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
            $apenas_nao_lidas = isset($_GET['apenas_nao_lidas']) ? (bool)$_GET['apenas_nao_lidas'] : false;
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
            
            $notificacoes = listarNotificacoes($usuario_id, $apenas_nao_lidas, $limite);
            $total_nao_lidas = contarNotificacoesNaoLidas($usuario_id);
            
            echo json_encode(array(
                'notificacoes' => $notificacoes,
                'total_nao_lidas' => $total_nao_lidas
            ));
            break;
            
        case 'marcar_lida':
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('erro' => 'Método não permitido'));
                exit;
            }
            
            // Obter dados do POST
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados || !isset($dados['id'])) {
                echo json_encode(array('erro' => 'Dados inválidos'));
                exit;
            }
            
            // Obter ID do usuário logado (ou usar um padrão para testes)
            $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1;
            
            $resultado = marcarNotificacaoComoLida($dados['id'], $usuario_id);
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true));
            } else {
                echo json_encode(array('erro' => 'Erro ao marcar notificação como lida'));
            }
            break;
            
        case 'marcar_todas_lidas':
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('erro' => 'Método não permitido'));
                exit;
            }
            
            // Obter ID do usuário logado (ou usar um padrão para testes)
            $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1;
            
            $resultado = marcarTodasNotificacoesComoLidas($usuario_id);
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true));
            } else {
                echo json_encode(array('erro' => 'Erro ao marcar notificações como lidas'));
            }
            break;
            
        case 'excluir':
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('erro' => 'Método não permitido'));
                exit;
            }
            
            // Obter dados do POST
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados || !isset($dados['id'])) {
                echo json_encode(array('erro' => 'Dados inválidos'));
                exit;
            }
            
            // Obter ID do usuário logado (ou usar um padrão para testes)
            $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1;
            
            $resultado = excluirNotificacao($dados['id'], $usuario_id);
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true));
            } else {
                echo json_encode(array('erro' => 'Erro ao excluir notificação'));
            }
            break;
            
        case 'verificar_estoques':
            $count = verificarEstoquesBaixos();
            echo json_encode(array('sucesso' => true, 'notificacoes_criadas' => $count));
            break;
            
        default:
            echo json_encode(array('erro' => 'Ação não reconhecida'));
            break;
    }
}
?>
