<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectado ao banco de dados!\n\n";
    
    // Verificar estrutura da tabela sales_pipelines
    echo "=== ESTRUTURA DA TABELA SALES_PIPELINES ===\n";
    $stmt = $pdo->query("DESCRIBE sales_pipelines");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== ESTRUTURA DA TABELA PIPELINE_STAGES ===\n";
    $stmt = $pdo->query("DESCRIBE pipeline_stages");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== ESTRUTURA DA TABELA PRODUCT_CATEGORIES ===\n";
    $stmt = $pdo->query("DESCRIBE product_categories");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    // Verificar se existem categorias
    echo "\n=== CATEGORIAS EXISTENTES ===\n";
    $stmt = $pdo->query("SELECT * FROM product_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        echo "Nenhuma categoria encontrada.\n";
    } else {
        foreach ($categories as $category) {
            echo "- ID: {$category['id']}, Nome: {$category['name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

?>