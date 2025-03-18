<?php
require_once 'conexao_pdo.php';
require_once 'dashboard_crud.php';

// Verificar se a conexão foi estabelecida
if (!isset($conn)) {
    die("Erro: Conexão com o banco de dados não foi estabelecida.");
}

// Inicializar o CRUD
$dashboardCRUD = new DashboardCRUD($conn);

// Função para exibir mensagens
function exibirMensagem($tipo, $mensagem) {
    echo "<div class='alert alert-$tipo' role='alert'>$mensagem</div>";
}

// Processar formulários
$mensagem = '';
$tipoMensagem = '';

// Processar formulário de movimentação
if (isset($_POST['acao']) && $_POST['acao'] == 'registrar_movimentacao') {
    try {
        $tipo = $_POST['tipo'];
        $usuario_id = $_POST['usuario_id'];
        $produto_id = $_POST['produto_id'];
        $quantidade = $_POST['quantidade'];
        $armazem_origem = !empty($_POST['armazem_origem_id']) ? $_POST['armazem_origem_id'] : null;
        $armazem_destino = !empty($_POST['armazem_destino_id']) ? $_POST['armazem_destino_id'] : null;
        $observacao = $_POST['observacao'] ?? '';
        
        $resultado = $dashboardCRUD->registrarMovimentacao(
            $tipo, $usuario_id, $produto_id, $quantidade, 
            $armazem_origem, $armazem_destino, $observacao
        );
        
        if ($resultado) {
            $mensagem = "Movimentação registrada com sucesso!";
            $tipoMensagem = "success";
        } else {
            $mensagem = "Erro ao registrar movimentação.";
            $tipoMensagem = "danger";
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Processar remoção de movimentação
if (isset($_GET['remover']) && is_numeric($_GET['remover'])) {
    try {
        $id = $_GET['remover'];
        $resultado = $dashboardCRUD->removerMovimentacao($id);
        
        if ($resultado) {
            $mensagem = "Movimentação removida com sucesso!";
            $tipoMensagem = "success";
        } else {
            $mensagem = "Erro ao remover movimentação.";
            $tipoMensagem = "danger";
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Obter dados para exibição
$estatisticas = $dashboardCRUD->obterEstatisticas();
$dadosGrafico = $dashboardCRUD->obterDadosGrafico(7);
$atividades = $dashboardCRUD->obterAtividadesRecentes(10);
$estoquesBaixos = $dashboardCRUD->verificarEstoquesBaixos();

// Obter lista de produtos, usuários e armazéns para o formulário
$sql = "SELECT id_produto, nome FROM produtos ORDER BY nome";
$result = $conn->query($sql);
$produtos = $result->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT id_usuario, nome FROM usuarios ORDER BY nome";
$result = $conn->query($sql);
$usuarios = $result->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT id_armazem, nome FROM armazens ORDER BY nome";
$result = $conn->query($sql);
$armazens = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste CRUD - Sistema de Gestão de Armazéns de Medicamentos Angola</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1, h2, h3 {
            color: #0077b6;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-responsive {
            margin-top: 20px;
        }
        .btn-primary {
            background-color: #0077b6;
            border-color: #0077b6;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Teste CRUD - Sistema de Gestão de Armazéns de Medicamentos Angola</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipoMensagem; ?>" role="alert">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Registrar Nova Movimentação</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="acao" value="registrar_movimentacao">
                            
                            <div class="form-group">
                                <label for="tipo">Tipo de Movimentação:</label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="">Selecione...</option>
                                    <option value="Entrada">Entrada</option>
                                    <option value="Saída">Saída</option>
                                    <option value="Transferência">Transferência</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="produto_id">Medicamento:</label>
                                <select class="form-control" id="produto_id" name="produto_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?php echo $produto['id_produto']; ?>"><?php echo $produto['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantidade">Quantidade:</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="usuario_id">Responsável:</label>
                                <select class="form-control" id="usuario_id" name="usuario_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id_usuario']; ?>"><?php echo $usuario['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="origem-container">
                                <label for="armazem_origem_id">Armazém de Origem:</label>
                                <select class="form-control" id="armazem_origem_id" name="armazem_origem_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($armazens as $armazem): ?>
                                        <option value="<?php echo $armazem['id_armazem']; ?>"><?php echo $armazem['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="destino-container">
                                <label for="armazem_destino_id">Armazém de Destino:</label>
                                <select class="form-control" id="armazem_destino_id" name="armazem_destino_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($armazens as $armazem): ?>
                                        <option value="<?php echo $armazem['id_armazem']; ?>"><?php echo $armazem['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="observacao">Observação:</label>
                                <textarea class="form-control" id="observacao" name="observacao" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Registrar Movimentação</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Estatísticas</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>Total de Medicamentos</h5>
                                        <h2><?php echo $estatisticas['total_medicamentos']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>Em Estoque</h5>
                                        <h2><?php echo $estatisticas['em_estoque']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>Estoque Baixo</h5>
                                        <h2><?php echo $estatisticas['estoque_baixo']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5>Próximos ao Vencimento</h5>
                                        <h2><?php echo $estatisticas['proximos_vencimento']; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Estoques Baixos</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($estoquesBaixos)): ?>
                            <p class="text-success">Não há medicamentos com estoque baixo.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Medicamento</th>
                                            <th>Quantidade</th>
                                            <th>Mínimo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($estoquesBaixos as $item): ?>
                                            <tr>
                                                <td><?php echo $item['nome']; ?></td>
                                                <td><?php echo $item['quantidade']; ?></td>
                                                <td><?php echo $item['estoque_minimo']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Atividades Recentes</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Responsável</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($atividades)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Nenhuma atividade registrada.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($atividades as $atividade): ?>
                                    <tr>
                                        <td><?php echo $atividade['id_movimentacao'] ?? 'N/A'; ?></td>
                                        <td><?php echo $atividade['data_formatada']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $atividade['tipo'] === 'entrada' ? 'success' : ($atividade['tipo'] === 'saida' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($atividade['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $atividade['produto_nome']; ?></td>
                                        <td><?php echo $atividade['quantidade']; ?></td>
                                        <td><?php echo $atividade['usuario_nome']; ?></td>
                                        <td><?php echo $atividade['armazem_origem_nome'] ?? 'N/A'; ?></td>
                                        <td><?php echo $atividade['armazem_destino_nome'] ?? 'N/A'; ?></td>
                                        <td>
                                            <a href="?remover=<?php echo $atividade['id_movimentacao']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja remover esta movimentação?')">
                                                <i class="fas fa-trash"></i> Remover
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Controlar exibição dos campos de armazém com base no tipo de movimentação
        document.getElementById('tipo').addEventListener('change', function() {
            const tipo = this.value;
            const origemContainer = document.getElementById('origem-container');
            const destinoContainer = document.getElementById('destino-container');
            
            if (tipo === 'Entrada') {
                origemContainer.style.display = 'none';
                destinoContainer.style.display = 'block';
                document.getElementById('armazem_origem_id').value = '';
                document.getElementById('armazem_origem_id').required = false;
                document.getElementById('armazem_destino_id').required = true;
            } else if (tipo === 'Saída') {
                origemContainer.style.display = 'block';
                destinoContainer.style.display = 'none';
                document.getElementById('armazem_destino_id').value = '';
                document.getElementById('armazem_origem_id').required = true;
                document.getElementById('armazem_destino_id').required = false;
            } else if (tipo === 'Transferência') {
                origemContainer.style.display = 'block';
                destinoContainer.style.display = 'block';
                document.getElementById('armazem_origem_id').required = true;
                document.getElementById('armazem_destino_id').required = true;
            } else {
                origemContainer.style.display = 'block';
                destinoContainer.style.display = 'block';
                document.getElementById('armazem_origem_id').required = false;
                document.getElementById('armazem_destino_id').required = false;
            }
        });
    </script>
</body>
</html>
