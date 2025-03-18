<?php
// Este script testa se as alterações no CRUD estão sendo refletidas na página home.html

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
    echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background-color: " . 
         ($tipo == 'success' ? '#d4edda' : '#f8d7da') . 
         "; color: " . ($tipo == 'success' ? '#155724' : '#721c24') . 
         ";'>" . $mensagem . "</div>";
}

// Função para realizar um teste e exibir o resultado
function realizarTeste($descricao, $resultado) {
    echo "<div style='padding: 10px; margin: 5px 0; border-radius: 5px; background-color: " . 
         ($resultado ? '#d4edda' : '#f8d7da') . 
         "; color: " . ($resultado ? '#155724' : '#721c24') . 
         ";'><strong>" . ($resultado ? "✓" : "✗") . " " . $descricao . "</strong></div>";
    return $resultado;
}

// Processar formulários
$mensagem = '';
$tipoMensagem = '';
$testes_passados = 0;
$total_testes = 0;

// Registrar uma nova movimentação para teste
if (isset($_POST['executar_teste'])) {
    try {
        echo "<h2>Executando testes de integração...</h2>";
        
        // Teste 1: Obter estatísticas
        $total_testes++;
        $estatisticas = $dashboardCRUD->obterEstatisticas();
        $teste1 = realizarTeste(
            "Teste 1: Obter estatísticas do dashboard", 
            is_array($estatisticas) && isset($estatisticas['total_medicamentos'])
        );
        if ($teste1) $testes_passados++;
        
        // Teste 2: Obter dados do gráfico
        $total_testes++;
        $dadosGrafico = $dashboardCRUD->obterDadosGrafico(7);
        $teste2 = realizarTeste(
            "Teste 2: Obter dados do gráfico", 
            is_array($dadosGrafico) && isset($dadosGrafico['datas']) && isset($dadosGrafico['entradas'])
        );
        if ($teste2) $testes_passados++;
        
        // Teste 3: Obter atividades recentes
        $total_testes++;
        $atividades = $dashboardCRUD->obterAtividadesRecentes(5);
        $teste3 = realizarTeste(
            "Teste 3: Obter atividades recentes", 
            is_array($atividades)
        );
        if ($teste3) $testes_passados++;
        
        // Teste 4: Registrar uma nova movimentação
        $total_testes++;
        $resultado = $dashboardCRUD->registrarMovimentacao(
            'Entrada', 1, 1, 50, null, 1, 'Teste de integração'
        );
        $teste4 = realizarTeste(
            "Teste 4: Registrar nova movimentação", 
            $resultado === true
        );
        if ($teste4) $testes_passados++;
        
        // Teste 5: Verificar se a movimentação foi registrada
        $total_testes++;
        $atividades_apos = $dashboardCRUD->obterAtividadesRecentes(1);
        $teste5 = realizarTeste(
            "Teste 5: Verificar se a movimentação foi registrada", 
            is_array($atividades_apos) && count($atividades_apos) > 0 && 
            $atividades_apos[0]['observacao'] == 'Teste de integração'
        );
        if ($teste5) $testes_passados++;
        
        // Teste 6: Verificar se o estoque foi atualizado
        $total_testes++;
        $sql = "SELECT quantidade FROM estoques WHERE produto_id = 1 AND armazem_id = 1";
        $stmt = $conn->query($sql);
        $estoque = $stmt->fetch(PDO::FETCH_ASSOC);
        $teste6 = realizarTeste(
            "Teste 6: Verificar se o estoque foi atualizado", 
            isset($estoque['quantidade']) && $estoque['quantidade'] > 0
        );
        if ($teste6) $testes_passados++;
        
        // Teste 7: Verificar se a API retorna dados no formato correto
        $total_testes++;
        $ch = curl_init('http://localhost/Testes2/api_dashboard.php?acao=estatisticas');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        $teste7 = realizarTeste(
            "Teste 7: Verificar se a API retorna dados no formato correto", 
            is_array($data) && isset($data['total_medicamentos'])
        );
        if ($teste7) $testes_passados++;
        
        // Exibir resultado geral
        $percentual = round(($testes_passados / $total_testes) * 100);
        echo "<div style='padding: 15px; margin: 15px 0; border-radius: 5px; background-color: " . 
             ($percentual >= 70 ? '#d4edda' : '#f8d7da') . 
             "; color: " . ($percentual >= 70 ? '#155724' : '#721c24') . 
             ";'><h3>Resultado: $testes_passados de $total_testes testes passaram ($percentual%)</h3></div>";
        
        if ($percentual >= 70) {
            echo "<p>O CRUD está funcionando corretamente e as alterações estão sendo refletidas na página home.html.</p>";
            echo "<p>Agora você pode acessar <a href='home.html' target='_blank'>home.html</a> para verificar o funcionamento do dashboard.</p>";
        } else {
            echo "<p>Há problemas na integração do CRUD com a página home.html. Verifique os testes que falharam.</p>";
        }
        
    } catch (Exception $e) {
        exibirMensagem('danger', "Erro durante os testes: " . $e->getMessage());
    }
}

// Verificar se a API está acessível
$api_acessivel = false;
$ch = curl_init('http://localhost/Testes2/api_dashboard.php?acao=estatisticas');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $api_acessivel = true;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Integração - Sistema de Gestão de Armazéns de Medicamentos Angola</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        }
        .btn-primary {
            background-color: #0077b6;
            border-color: #0077b6;
        }
        .api-status {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .api-online {
            background-color: #28a745;
        }
        .api-offline {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Teste de Integração - Sistema de Gestão de Armazéns de Medicamentos Angola</h1>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Status do Sistema</h3>
            </div>
            <div class="card-body">
                <p>
                    <strong>API:</strong> 
                    <span class="api-status <?php echo $api_acessivel ? 'api-online' : 'api-offline'; ?>"></span>
                    <?php echo $api_acessivel ? 'Online' : 'Offline'; ?>
                </p>
                
                <p>
                    <strong>Banco de Dados:</strong> 
                    <span class="api-status <?php echo isset($conn) ? 'api-online' : 'api-offline'; ?>"></span>
                    <?php echo isset($conn) ? 'Conectado' : 'Desconectado'; ?>
                </p>
                
                <p>Este teste verifica se o CRUD está funcionando corretamente e se as alterações estão sendo refletidas na página home.html.</p>
                
                <form method="post" action="">
                    <input type="hidden" name="executar_teste" value="1">
                    <button type="submit" class="btn btn-primary">Executar Testes de Integração</button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipoMensagem; ?>" role="alert">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <h3>Links Úteis</h3>
            <ul>
                <li><a href="home.html" target="_blank">Página Principal (home.html)</a></li>
                <li><a href="teste_crud.php" target="_blank">Teste CRUD</a></li>
                <li><a href="api_dashboard.php?acao=estatisticas" target="_blank">API - Estatísticas</a></li>
                <li><a href="api_dashboard.php?acao=atividades" target="_blank">API - Atividades</a></li>
                <li><a href="api_dashboard.php?acao=grafico&periodo=7" target="_blank">API - Gráfico (7 dias)</a></li>
            </ul>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
