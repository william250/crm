<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectado ao banco de dados!\n\n";
    
    // Primeiro, inserir categorias de produtos
    echo "=== INSERINDO CATEGORIAS DE PRODUTOS ===\n";
    
    $categories = [
        ["Software CRM", "Sistemas de gestão de relacionamento com clientes", "#2196F3"],
        ["Consultoria", "Serviços de consultoria especializada", "#FF9800"],
        ["Integração", "Serviços de integração e desenvolvimento", "#4CAF50"],
        ["Suporte", "Serviços de suporte técnico e manutenção", "#9C27B0"]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO product_categories (name, description, color, status) VALUES (?, ?, ?, 'active')");
    
    foreach ($categories as $category) {
        try {
            $stmt->execute($category);
            echo "✓ Categoria '{$category[0]}' inserida\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir categoria '{$category[0]}': " . $e->getMessage() . "\n";
        }
    }
    
    // Inserir dados em products_services
    echo "\n=== INSERINDO PRODUTOS E SERVIÇOS ===\n";
    
    $products = [
        ["Sistema CRM Básico", "Sistema de gestão de relacionamento com clientes - Plano Básico", 1, "subscription", "CRM-BASIC", 299.00, 50.00, "monthly", 12],
        ["Sistema CRM Profissional", "Sistema CRM com recursos avançados - Plano Profissional", 1, "subscription", "CRM-PRO", 599.00, 100.00, "monthly", 12],
        ["Sistema CRM Enterprise", "Sistema CRM completo para grandes empresas", 1, "subscription", "CRM-ENT", 1299.00, 200.00, "monthly", 12],
        ["Consultoria em CRM", "Consultoria especializada em implementação de CRM", 2, "consulting", "CONS-CRM", 2500.00, 800.00, "one_time", NULL],
        ["Treinamento CRM", "Treinamento completo para uso do sistema CRM", 2, "service", "TRAIN-CRM", 800.00, 200.00, "one_time", NULL],
        ["Integração API", "Serviço de integração com sistemas externos via API", 3, "service", "INT-API", 1500.00, 400.00, "one_time", NULL],
        ["Suporte Premium", "Suporte técnico premium 24/7", 4, "subscription", "SUP-PREM", 199.00, 50.00, "monthly", 12],
        ["Backup e Segurança", "Serviço de backup automático e segurança avançada", 4, "subscription", "BACKUP-SEC", 99.00, 20.00, "monthly", 12]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products_services (name, description, category_id, type, sku, price, cost, billing_cycle, duration_months, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    
    foreach ($products as $product) {
        try {
            $stmt->execute($product);
            echo "✓ Produto '{$product[0]}' inserido\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir produto '{$product[0]}': " . $e->getMessage() . "\n";
        }
    }
    
    // Inserir dados em sales_pipelines
    echo "\n=== INSERINDO PIPELINES DE VENDAS ===\n";
    
    $pipelines = [
        ["Pipeline Principal", "Pipeline principal de vendas B2B", 1, "#2196F3", 1],
        ["Pipeline B2C", "Pipeline para vendas diretas ao consumidor", 0, "#FF9800", 1],
        ["Pipeline Parcerias", "Pipeline para vendas através de parceiros", 0, "#4CAF50", 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO sales_pipelines (name, description, is_default, color, status, created_by) VALUES (?, ?, ?, ?, 'active', ?)");
    
    foreach ($pipelines as $pipeline) {
        try {
            $stmt->execute($pipeline);
            echo "✓ Pipeline '{$pipeline[0]}' inserido\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir pipeline '{$pipeline[0]}': " . $e->getMessage() . "\n";
        }
    }
    
    // Inserir dados em pipeline_stages
    echo "\n=== INSERINDO ESTÁGIOS DE PIPELINE ===\n";
    
    $stages = [
        [1, "Novo Lead", "Lead recém capturado", 1, 10, "#e3f2fd", 0, 0],
        [1, "Qualificado", "Lead qualificado e com interesse", 2, 25, "#f3e5f5", 0, 0],
        [1, "Proposta", "Proposta enviada ao cliente", 3, 50, "#fff3e0", 0, 0],
        [1, "Negociação", "Em processo de negociação", 4, 75, "#f1f8e9", 0, 0],
        [1, "Fechado - Ganho", "Venda realizada com sucesso", 5, 100, "#e8f5e8", 1, 0],
        [1, "Fechado - Perdido", "Venda não realizada", 6, 0, "#ffebee", 0, 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO pipeline_stages (pipeline_id, name, description, stage_order, probability_percent, color, is_closed_won, is_closed_lost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    
    foreach ($stages as $stage) {
        try {
            $stmt->execute($stage);
            echo "✓ Estágio '{$stage[1]}' inserido\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir estágio '{$stage[1]}': " . $e->getMessage() . "\n";
        }
    }
    
    // Verificar dados finais
    echo "\n=== VERIFICAÇÃO FINAL ===\n";
    
    $tables = ['product_categories', 'products_services', 'sales_pipelines', 'pipeline_stages', 'payments', 'users', 'leads', 'clients'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Tabela $table: $count registros\n";
    }
    
    echo "\n✅ Banco de dados populado com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

?>