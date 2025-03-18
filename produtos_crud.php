<?php
require_once 'conexao.php';

/**
 * Função para listar todos os produtos
 * @param int $limite Número máximo de produtos a retornar (0 = sem limite)
 * @param int $pagina Número da página para paginação
 * @param string $ordem Campo para ordenação
 * @param string $direcao Direção da ordenação (ASC ou DESC)
 * @param string $busca Termo de busca para filtrar produtos
 * @return array Array com os produtos encontrados
 */
function listarProdutos($limite = 0, $pagina = 1, $ordem = 'nome', $direcao = 'ASC', $busca = '') {
    global $conn;
    
    $produtos = array();
    $offset = ($pagina - 1) * $limite;
    $limitQuery = $limite > 0 ? "LIMIT $offset, $limite" : "";
    
    // Validar parâmetros de ordenação para evitar injeção SQL
    $ordemPermitida = array('id_produto', 'nome', 'codigo', 'preco', 'estoque_minimo', 'estoque_maximo');
    $ordem = in_array($ordem, $ordemPermitida) ? $ordem : 'nome';
    $direcao = strtoupper($direcao) === 'DESC' ? 'DESC' : 'ASC';
    
    // Construir a consulta SQL com busca se necessário
    $whereClause = "";
    if (!empty($busca)) {
        $busca = $conn->real_escape_string($busca);
        $whereClause = "WHERE nome LIKE '%$busca%' OR codigo LIKE '%$busca%' OR descricao LIKE '%$busca%'";
    }
    
    $sql = "SELECT p.*, c.nome as categoria_nome, f.nome as fornecedor_nome,
            (SELECT SUM(quantidade) FROM estoques WHERE produto_id = p.id_produto) as quantidade_total
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id_categoria
            LEFT JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
            $whereClause
            ORDER BY $ordem $direcao
            $limitQuery";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $produtos[] = $row;
        }
    }
    
    return $produtos;
}

/**
 * Função para obter o total de produtos (para paginação)
 * @param string $busca Termo de busca para filtrar produtos
 * @return int Total de produtos
 */
function contarProdutos($busca = '') {
    global $conn;
    
    $whereClause = "";
    if (!empty($busca)) {
        $busca = $conn->real_escape_string($busca);
        $whereClause = "WHERE nome LIKE '%$busca%' OR codigo LIKE '%$busca%' OR descricao LIKE '%$busca%'";
    }
    
    $sql = "SELECT COUNT(*) as total FROM produtos $whereClause";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    return 0;
}

/**
 * Função para obter um produto pelo ID
 * @param int $id ID do produto
 * @return array|null Array com os dados do produto ou null se não encontrado
 */
function obterProdutoPorId($id) {
    global $conn;
    
    $id = (int)$id;
    
    $sql = "SELECT p.*, c.nome as categoria_nome, f.nome as fornecedor_nome,
            (SELECT SUM(quantidade) FROM estoques WHERE produto_id = p.id_produto) as quantidade_total
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id_categoria
            LEFT JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
            WHERE p.id_produto = $id";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Função para adicionar um novo produto
 * @param string $nome Nome do produto
 * @param string $codigo Código do produto
 * @param string $descricao Descrição do produto
 * @param float $preco Preço do produto
 * @param int $categoria_id ID da categoria
 * @param int $fornecedor_id ID do fornecedor
 * @param int $estoque_minimo Estoque mínimo
 * @param int $estoque_maximo Estoque máximo
 * @param string $unidade Unidade de medida
 * @return bool|int ID do produto inserido ou false em caso de erro
 */
function adicionarProduto($nome, $codigo, $descricao, $preco, $categoria_id, $fornecedor_id, $estoque_minimo, $estoque_maximo, $unidade = 'Un') {
    global $conn;
    
    // Validar e sanitizar parâmetros
    $nome = $conn->real_escape_string(trim($nome));
    $codigo = $conn->real_escape_string(trim($codigo));
    $descricao = $conn->real_escape_string(trim($descricao));
    $preco = (float)$preco;
    $categoria_id = $categoria_id ? (int)$categoria_id : "NULL";
    $fornecedor_id = $fornecedor_id ? (int)$fornecedor_id : "NULL";
    $estoque_minimo = (int)$estoque_minimo;
    $estoque_maximo = (int)$estoque_maximo;
    $unidade = $conn->real_escape_string(trim($unidade));
    
    // Verificar se o código já existe
    $sql = "SELECT id_produto FROM produtos WHERE codigo = '$codigo'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return false; // Código já existe
    }
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Inserir produto
        $sql = "INSERT INTO produtos (nome, codigo, descricao, preco, categoria_id, fornecedor_id, estoque_minimo, estoque_maximo, unidade)
                VALUES ('$nome', '$codigo', '$descricao', $preco, $categoria_id, $fornecedor_id, $estoque_minimo, $estoque_maximo, '$unidade')";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao adicionar produto: " . $conn->error);
        }
        
        $produto_id = $conn->insert_id;
        
        // Registrar auditoria
        $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1; // ID padrão se não estiver logado
        $sql = "INSERT INTO auditoria_produtos (produto_id, usuario_id, acao, data_acao)
                VALUES ($produto_id, $usuario_id, 'INSERIR', NOW())";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao registrar auditoria: " . $conn->error);
        }
        
        // Commit da transação
        $conn->commit();
        return $produto_id;
        
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
 * Função para atualizar um produto existente
 * @param int $id ID do produto
 * @param string $nome Nome do produto
 * @param string $codigo Código do produto
 * @param string $descricao Descrição do produto
 * @param float $preco Preço do produto
 * @param int $categoria_id ID da categoria
 * @param int $fornecedor_id ID do fornecedor
 * @param int $estoque_minimo Estoque mínimo
 * @param int $estoque_maximo Estoque máximo
 * @param string $unidade Unidade de medida
 * @return bool True se atualizado com sucesso, false caso contrário
 */
function atualizarProduto($id, $nome, $codigo, $descricao, $preco, $categoria_id, $fornecedor_id, $estoque_minimo, $estoque_maximo, $unidade = 'Un') {
    global $conn;
    
    // Validar e sanitizar parâmetros
    $id = (int)$id;
    $nome = $conn->real_escape_string(trim($nome));
    $codigo = $conn->real_escape_string(trim($codigo));
    $descricao = $conn->real_escape_string(trim($descricao));
    $preco = (float)$preco;
    $categoria_id = $categoria_id ? (int)$categoria_id : "NULL";
    $fornecedor_id = $fornecedor_id ? (int)$fornecedor_id : "NULL";
    $estoque_minimo = (int)$estoque_minimo;
    $estoque_maximo = (int)$estoque_maximo;
    $unidade = $conn->real_escape_string(trim($unidade));
    
    // Verificar se o produto existe
    $sql = "SELECT id_produto FROM produtos WHERE id_produto = $id";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        return false; // Produto não existe
    }
    
    // Verificar se o código já existe em outro produto
    $sql = "SELECT id_produto FROM produtos WHERE codigo = '$codigo' AND id_produto != $id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return false; // Código já existe em outro produto
    }
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Atualizar produto
        $sql = "UPDATE produtos SET 
                nome = '$nome',
                codigo = '$codigo',
                descricao = '$descricao',
                preco = $preco,
                categoria_id = $categoria_id,
                fornecedor_id = $fornecedor_id,
                estoque_minimo = $estoque_minimo,
                estoque_maximo = $estoque_maximo,
                unidade = '$unidade'
                WHERE id_produto = $id";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao atualizar produto: " . $conn->error);
        }
        
        // Registrar auditoria
        $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1; // ID padrão se não estiver logado
        $sql = "INSERT INTO auditoria_produtos (produto_id, usuario_id, acao, data_acao)
                VALUES ($id, $usuario_id, 'ATUALIZAR', NOW())";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao registrar auditoria: " . $conn->error);
        }
        
        // Commit da transação
        $conn->commit();
        return true;
        
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
 * Função para excluir um produto
 * @param int $id ID do produto
 * @return bool True se excluído com sucesso, false caso contrário
 */
function excluirProduto($id) {
    global $conn;
    
    $id = (int)$id;
    
    // Verificar se o produto existe
    $sql = "SELECT id_produto FROM produtos WHERE id_produto = $id";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        return false; // Produto não existe
    }
    
    // Verificar se há estoque para este produto
    $sql = "SELECT SUM(quantidade) as total FROM estoques WHERE produto_id = $id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ((int)$row['total'] > 0) {
            return false; // Não pode excluir produto com estoque
        }
    }
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Registrar auditoria antes de excluir
        $usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1; // ID padrão se não estiver logado
        $sql = "INSERT INTO auditoria_produtos (produto_id, usuario_id, acao, data_acao)
                VALUES ($id, $usuario_id, 'EXCLUIR', NOW())";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao registrar auditoria: " . $conn->error);
        }
        
        // Excluir produto
        $sql = "DELETE FROM produtos WHERE id_produto = $id";
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao excluir produto: " . $conn->error);
        }
        
        // Commit da transação
        $conn->commit();
        return true;
        
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
 * Função para listar categorias
 * @return array Array com as categorias
 */
function listarCategorias() {
    global $conn;
    
    $categorias = array();
    
    $sql = "SELECT * FROM categorias ORDER BY nome";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    
    return $categorias;
}

/**
 * Função para listar fornecedores
 * @return array Array com os fornecedores
 */
function listarFornecedores() {
    global $conn;
    
    $fornecedores = array();
    
    $sql = "SELECT * FROM fornecedores ORDER BY nome";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fornecedores[] = $row;
        }
    }
    
    return $fornecedores;
}

/**
 * Função para listar armazéns
 * @return array Array com os armazéns
 */
function listarArmazens() {
    global $conn;
    
    $armazens = array();
    
    $sql = "SELECT * FROM armazens ORDER BY nome";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $armazens[] = $row;
        }
    }
    
    return $armazens;
}

/**
 * Função para obter o estoque de um produto por armazém
 * @param int $produto_id ID do produto
 * @return array Array com o estoque por armazém
 */
function obterEstoquePorArmazem($produto_id) {
    global $conn;
    
    $produto_id = (int)$produto_id;
    $estoques = array();
    
    $sql = "SELECT e.*, a.nome as armazem_nome 
            FROM estoques e
            JOIN armazens a ON e.armazem_id = a.id_armazem
            WHERE e.produto_id = $produto_id";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $estoques[] = $row;
        }
    }
    
    return $estoques;
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
            $ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'nome';
            $direcao = isset($_GET['direcao']) ? $_GET['direcao'] : 'ASC';
            $busca = isset($_GET['busca']) ? $_GET['busca'] : '';
            
            $produtos = listarProdutos($limite, $pagina, $ordem, $direcao, $busca);
            $total = contarProdutos($busca);
            
            echo json_encode(array(
                'produtos' => $produtos,
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
            
            $produto = obterProdutoPorId($id);
            
            if ($produto) {
                // Obter estoque por armazém
                $produto['estoques'] = obterEstoquePorArmazem($id);
                echo json_encode($produto);
            } else {
                echo json_encode(array('erro' => 'Produto não encontrado'));
            }
            break;
            
        case 'categorias':
            echo json_encode(listarCategorias());
            break;
            
        case 'fornecedores':
            echo json_encode(listarFornecedores());
            break;
            
        case 'armazens':
            echo json_encode(listarArmazens());
            break;
            
        case 'adicionar':
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
            if (empty($dados['nome']) || empty($dados['codigo'])) {
                echo json_encode(array('erro' => 'Nome e código são obrigatórios'));
                exit;
            }
            
            $resultado = adicionarProduto(
                $dados['nome'],
                $dados['codigo'],
                $dados['descricao'] ?? '',
                $dados['preco'] ?? 0,
                $dados['categoria_id'] ?? null,
                $dados['fornecedor_id'] ?? null,
                $dados['estoque_minimo'] ?? 0,
                $dados['estoque_maximo'] ?? 0,
                $dados['unidade'] ?? 'Un'
            );
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true, 'id' => $resultado));
            } else {
                echo json_encode(array('erro' => 'Erro ao adicionar produto'));
            }
            break;
            
        case 'atualizar':
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(array('erro' => 'Método não permitido'));
                exit;
            }
            
            // Obter dados do POST
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados || !isset($dados['id_produto'])) {
                echo json_encode(array('erro' => 'Dados inválidos'));
                exit;
            }
            
            // Validar campos obrigatórios
            if (empty($dados['nome']) || empty($dados['codigo'])) {
                echo json_encode(array('erro' => 'Nome e código são obrigatórios'));
                exit;
            }
            
            $resultado = atualizarProduto(
                $dados['id_produto'],
                $dados['nome'],
                $dados['codigo'],
                $dados['descricao'] ?? '',
                $dados['preco'] ?? 0,
                $dados['categoria_id'] ?? null,
                $dados['fornecedor_id'] ?? null,
                $dados['estoque_minimo'] ?? 0,
                $dados['estoque_maximo'] ?? 0,
                $dados['unidade'] ?? 'Un'
            );
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true));
            } else {
                echo json_encode(array('erro' => 'Erro ao atualizar produto'));
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
            
            $resultado = excluirProduto($dados['id']);
            
            if ($resultado) {
                echo json_encode(array('sucesso' => true));
            } else {
                echo json_encode(array('erro' => 'Erro ao excluir produto. Verifique se não há estoque para este produto.'));
            }
            break;
            
        default:
            echo json_encode(array('erro' => 'Ação não reconhecida'));
            break;
    }
}
?>
