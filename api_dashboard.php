<?php
// Desabilitar a exibição de erros no output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir header JSON antes de qualquer output
header('Content-Type: application/json');

require_once 'conexao_pdo.php';
require_once 'dashboard_crud.php';

// Verificar se a conexão foi estabelecida
if (!isset($conn)) {
    http_response_code(500);
    die(json_encode(['erro' => 'Erro de conexão com o banco de dados']));
}

// Inicializar o CRUD
$dashboardCRUD = new DashboardCRUD($conn);

// Verificar o método da requisição
$metodo = $_SERVER['REQUEST_METHOD'];
$acao = isset($_REQUEST['acao']) ? $_REQUEST['acao'] : '';

try {
    if ($metodo === 'GET') {
        // Ações GET
        switch ($acao) {
            case 'estatisticas':
                $dados = $dashboardCRUD->obterEstatisticas();
                echo json_encode($dados);
                break;
                
            case 'grafico':
                $periodo = isset($_GET['periodo']) ? intval($_GET['periodo']) : 7;
                $dados = $dashboardCRUD->obterDadosGrafico($periodo);
                echo json_encode($dados);
                break;
                
            case 'atividades':
                $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
                $dados = $dashboardCRUD->obterAtividadesRecentes($limite);
                echo json_encode($dados);
                break;
                
            case 'estoque_baixo':
                $dados = $dashboardCRUD->verificarEstoquesBaixos();
                echo json_encode($dados);
                break;
                
            default:
                throw new Exception('Ação inválida');
        }
    } 
    else if ($metodo === 'POST') {
        // Ações POST
        switch ($acao) {
            case 'registrar_movimentacao':
                // Validar parâmetros obrigatórios
                $parametros_obrigatorios = ['tipo', 'usuario_id', 'produto_id', 'quantidade'];
                foreach ($parametros_obrigatorios as $param) {
                    if (!isset($_POST[$param])) {
                        throw new Exception("Parâmetro obrigatório ausente: $param");
                    }
                }
                
                // Obter parâmetros
                $tipo = $_POST['tipo'];
                $usuario_id = intval($_POST['usuario_id']);
                $produto_id = intval($_POST['produto_id']);
                $quantidade = intval($_POST['quantidade']);
                $armazem_origem = isset($_POST['armazem_origem_id']) ? intval($_POST['armazem_origem_id']) : null;
                $armazem_destino = isset($_POST['armazem_destino_id']) ? intval($_POST['armazem_destino_id']) : null;
                $observacao = isset($_POST['observacao']) ? $_POST['observacao'] : '';
                
                // Validar tipo de movimentação
                if (!in_array($tipo, ['Entrada', 'Saída', 'Transferência'])) {
                    throw new Exception('Tipo de movimentação inválido');
                }
                
                // Registrar movimentação
                $resultado = $dashboardCRUD->registrarMovimentacao(
                    $tipo, $usuario_id, $produto_id, $quantidade,
                    $armazem_origem, $armazem_destino, $observacao
                );
                
                if ($resultado) {
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Movimentação registrada com sucesso'
                    ]);
                } else {
                    throw new Exception('Erro ao registrar movimentação');
                }
                break;
                
            case 'remover_movimentacao':
                if (!isset($_POST['id'])) {
                    throw new Exception('ID da movimentação não fornecido');
                }
                
                $id = intval($_POST['id']);
                $resultado = $dashboardCRUD->removerMovimentacao($id);
                
                if ($resultado) {
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Movimentação removida com sucesso'
                    ]);
                } else {
                    throw new Exception('Erro ao remover movimentação');
                }
                break;
                
            default:
                throw new Exception('Ação inválida');
        }
    } else {
        throw new Exception('Método HTTP não suportado');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>