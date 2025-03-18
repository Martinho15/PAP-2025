<?php
// Incluir conexão e funções CRUD
require_once 'conexao.php';
require_once 'produtos_crud.php';

// Processar ações (adicionar, editar, excluir)
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        
        // Adicionar produto
        if ($acao === 'adicionar') {
            // Coletar dados do formulário
            $nome = $_POST['nome'];
            $descricao = $_POST['descricao'];
            $categoria_id = $_POST['categoria_id'];
            $fornecedor_id = $_POST['fornecedor_id'];
            $estoque_minimo = $_POST['estoque_minimo'];
            $estoque_maximo = $_POST['estoque_maximo'];
            $data_validade = $_POST['data_validade'];
            $preco_custo = $_POST['preco_custo'];
            $preco_venda = $_POST['preco_venda'];
            
            // Validar dados
            if (empty($nome)) {
                $mensagem = 'O nome do medicamento é obrigatório';
                $tipo_mensagem = 'danger';
            } else {
                // Adicionar produto
                $resultado = adicionarProduto($nome, $descricao, $categoria_id, $fornecedor_id, 
                                             $estoque_minimo, $estoque_maximo, $data_validade, 
                                             $preco_custo, $preco_venda);
                
                if ($resultado) {
                    $mensagem = 'Medicamento adicionado com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = 'Erro ao adicionar medicamento';
                    $tipo_mensagem = 'danger';
                }
            }
        }
        
        // Editar produto
        else if ($acao === 'editar') {
            // Implementação similar ao adicionar, mas com id do produto
            $id = $_POST['id'];
            // ... coletar e validar dados
            // atualizarProduto($id, ...);
        }
        
        // Excluir produto
        else if ($acao === 'excluir') {
            $id = $_POST['id'];
            $resultado = excluirProduto($id);
            
            if ($resultado) {
                $mensagem = 'Medicamento excluído com sucesso!';
                $tipo_mensagem = 'success';
            } else {
                $mensagem = 'Erro ao excluir medicamento';
                $tipo_mensagem = 'danger';
            }
        }
    }
}

// Obter lista de produtos para exibição
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$itens_por_pagina = 10;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_fornecedor = isset($_GET['fornecedor']) ? $_GET['fornecedor'] : '';
$filtro_estoque = isset($_GET['estoque']) ? $_GET['estoque'] : '';
$termo_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Obter produtos com filtros
$produtos = listarProdutos($offset, $itens_por_pagina, $filtro_categoria, $filtro_fornecedor, $filtro_estoque, $termo_busca);
$total_produtos = contarProdutos($filtro_categoria, $filtro_fornecedor, $filtro_estoque, $termo_busca);
$total_paginas = ceil($total_produtos / $itens_por_pagina);

// Obter categorias e fornecedores para os formulários
$categorias = listarCategorias();
$fornecedores = listarFornecedores();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Medicamentos - Sistema de Gestão de Armazéns</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <!-- Sidebar e Header similar ao home.html -->
    
    <main>
        <!-- Header -->
        
        <div class="container">
            <div class="page-header">
                <h1>Gestão de Medicamentos</h1>
                <button class="btn btn-primary" id="btnNovoProduto">
                    <i class="fas fa-plus"></i> Novo Medicamento
                </button>
            </div>
            
            <!-- Alerta de mensagem -->
            <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="filters-container">
                <form action="" method="GET" class="filters-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="busca">Pesquisar</label>
                            <input type="text" name="busca" id="busca" class="form-control" 
                                   value="<?php echo htmlspecialchars($termo_busca); ?>" 
                                   placeholder="Nome ou descrição...">
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria">Categoria</label>
                            <select name="categoria" id="categoria" class="form-control">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>" 
                                    <?php echo $filtro_categoria == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fornecedor">Fornecedor</label>
                            <select name="fornecedor" id="fornecedor" class="form-control">
                                <option value="">Todos</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?php echo $fornecedor['id_fornecedor']; ?>" 
                                    <?php echo $filtro_fornecedor == $fornecedor['id_fornecedor'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fornecedor['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="estoque">Estoque</label>
                            <select name="estoque" id="estoque" class="form-control">
                                <option value="">Todos</option>
                                <option value="baixo" <?php echo $filtro_estoque == 'baixo' ? 'selected' : ''; ?>>
                                    Estoque Baixo
                                </option>
                                <option value="normal" <?php echo $filtro_estoque == 'normal' ? 'selected' : ''; ?>>
                                    Estoque Normal
                                </option>
                                <option value="alto" <?php echo $filtro_estoque == 'alto' ? 'selected' : ''; ?>>
                                    Estoque Alto
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="produtos.php" class="btn btn-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de produtos -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Fornecedor</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Data Validade</th>
                            <th>Preço Venda</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($produtos) > 0): ?>
                            <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?php echo $produto['id_produto']; ?></td>
                                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                <td><?php echo htmlspecialchars($produto['fornecedor_nome']); ?></td>
                                <td class="<?php echo $produto['estoque_atual'] < $produto['estoque_minimo'] ? 'text-danger' : ''; ?>">
                                    <?php echo $produto['estoque_atual']; ?>
                                </td>
                                <td><?php echo $produto['estoque_minimo']; ?></td>
                                <td><?php 
                                    $data_validade = new DateTime($produto['data_validade']);
                                    echo $data_validade->format('d/m/Y'); 
                                ?></td>
                                <td><?php echo number_format($produto['preco_venda'], 2, ',', '.') . ' AOA'; ?></td>
                                <td class="table-actions">
                                    <button class="btn-icon-sm btn-view" 
                                            onclick="verProduto(<?php echo $produto['id_produto']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon-sm btn-edit" 
                                            onclick="editarProduto(<?php echo $produto['id_produto']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon-sm btn-delete" 
                                            onclick="confirmarExclusao(<?php echo $produto['id_produto']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Nenhum medicamento encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=<?php echo $pagina_atual - 1; ?>&categoria=<?php echo $filtro_categoria; ?>&fornecedor=<?php echo $filtro_fornecedor; ?>&estoque=<?php echo $filtro_estoque; ?>&busca=<?php echo urlencode($termo_busca); ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>&categoria=<?php echo $filtro_categoria; ?>&fornecedor=<?php echo $filtro_fornecedor; ?>&estoque=<?php echo $filtro_estoque; ?>&busca=<?php echo urlencode($termo_busca); ?>" 
                   class="page-link <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($pagina_atual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_atual + 1; ?>&categoria=<?php echo $filtro_categoria; ?>&fornecedor=<?php echo $filtro_fornecedor; ?>&estoque=<?php echo $filtro_estoque; ?>&busca=<?php echo urlencode($termo_busca); ?>" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Modal para adicionar/editar produto -->
    <div class="modal-backdrop" id="produtoModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Novo Medicamento</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="produtoForm" method="POST" action="">
                    <input type="hidden" name="acao" id="formAcao" value="adicionar">
                    <input type="hidden" name="id" id="produtoId" value="">
                    
                    <div class="form-group">
                        <label for="nome">Nome do Medicamento*</label>
                        <input type="text" id="nome" name="nome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="categoria_id">Categoria*</label>
                            <select id="categoria_id" name="categoria_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fornecedor_id">Fornecedor*</label>
                            <select id="fornecedor_id" name="fornecedor_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?php echo $fornecedor['id_fornecedor']; ?>">
                                    <?php echo htmlspecialchars($fornecedor['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="estoque_minimo">Estoque Mínimo*</label>
                            <input type="number" id="estoque_minimo" name="estoque_minimo" 
                                   class="form-control" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="estoque_maximo">Estoque Máximo*</label>
                            <input type="number" id="estoque_maximo" name="estoque_maximo" 
                                   class="form-control" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_validade">Data de Validade*</label>
                            <input type="date" id="data_validade" name="data_validade" 
                                   class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="preco_custo">Preço de Custo (AOA)*</label>
                            <input type="number" id="preco_custo" name="preco_custo" 
                                   class="form-control" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="preco_venda">Preço de Venda (AOA)*</label>
                            <input type="number" id="preco_venda" name="preco_venda" 
                                   class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <p><small>* Campos obrigatórios</small></p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelarBtn">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="salvarBtn">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmação de exclusão -->
    <div class="modal-backdrop" id="confirmacaoModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirmar Exclusão</h3>
                <button class="modal-close" id="closeConfirmModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este medicamento?</p>
                <p>Esta ação não pode ser desfeita.</p>
                
                <form id="excluirForm" method="POST" action="">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" id="excluirId" value="">
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelarExcluirBtn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Controle de modais
        const produtoModal = document.getElementById('produtoModal');
        const confirmacaoModal = document.getElementById('confirmacaoModal');
        const btnNovoProduto = document.getElementById('btnNovoProduto');
        const closeModal = document.getElementById('closeModal');
        const cancelarBtn = document.getElementById('cancelarBtn');
        const closeConfirmModal = document.getElementById('closeConfirmModal');
        const cancelarExcluirBtn = document.getElementById('cancelarExcluirBtn');
        
        // Abrir modal de novo produto
        btnNovoProduto.addEventListener('click', () => {
            document.getElementById('modalTitle').textContent = 'Novo Medicamento';
            document.getElementById('formAcao').value = 'adicionar';
            document.getElementById('produtoId').value = '';
            document.getElementById('produtoForm').reset();
            produtoModal.classList.add('show');
        });
        
        // Fechar modais
        closeModal.addEventListener('click', () => {
            produtoModal.classList.remove('show');
        });
        
        cancelarBtn.addEventListener('click', () => {
            produtoModal.classList.remove('show');
        });
        
        closeConfirmModal.addEventListener('click', () => {
            confirmacaoModal.classList.remove('show');
        });
        
        cancelarExcluirBtn.addEventListener('click', () => {
            confirmacaoModal.classList.remove('show');
        });
        
        // Editar produto
        function editarProduto(id) {
            fetch(`produtos_crud.php?acao=obter&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        const produto = data.produto;
                        
                        document.getElementById('modalTitle').textContent = 'Editar Medicamento';
                        document.getElementById('formAcao').value = 'editar';
                        document.getElementById('produtoId').value = produto.id_produto;
                        
                        document.getElementById('nome').value = produto.nome;
                        document.getElementById('descricao').value = produto.descricao;
                        document.getElementById('categoria_id').value = produto.categoria_id;
                        document.getElementById('fornecedor_id').value = produto.fornecedor_id;
                        document.getElementById('estoque_minimo').value = produto.estoque_minimo;
                        document.getElementById('estoque_maximo').value = produto.estoque_maximo;
                        document.getElementById('data_validade').value = produto.data_validade.split(' ')[0];
                        document.getElementById('preco_custo').value = produto.preco_custo;
                        document.getElementById('preco_venda').value = produto.preco_venda;
                        
                        produtoModal.classList.add('show');
                    } else {
                        alert('Erro ao carregar dados do medicamento');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados do medicamento');
                });
        }
        
        // Ver detalhes do produto
        function verProduto(id) {
            window.location.href = `produto_detalhes.php?id=${id}`;
        }
        
        // Confirmar exclusão
        function confirmarExclusao(id) {
            document.getElementById('excluirId').value = id;
            confirmacaoModal.classList.add('show');
        }
        
        // Validação de formulário
        document.getElementById('produtoForm').addEventListener('submit', function(e) {
            const estoqueMinimo = parseInt(document.getElementById('estoque_minimo').value);
            const estoqueMaximo = parseInt(document.getElementById('estoque_maximo').value);
            
            if (estoqueMinimo > estoqueMaximo) {
                e.preventDefault();
                alert('O estoque mínimo não pode ser maior que o estoque máximo');
            }
            
            const precoCusto = parseFloat(document.getElementById('preco_custo').value);
            const precoVenda = parseFloat(document.getElementById('preco_venda').value);
            
            if (precoVenda < precoCusto) {
                const confirmar = confirm('O preço de venda é menor que o preço de custo. Deseja continuar?');
                if (!confirmar) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>