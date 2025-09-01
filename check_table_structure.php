<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectado ao banco de dados!\n\n";
    
    // Verificar estrutura da tabela payments
    echo "=== ESTRUTURA DA TABELA PAYMENTS ===\n";
    $stmt = $pdo->query("DESCRIBE payments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== ESTRUTURA DA TABELA CHARGES ===\n";
    $stmt = $pdo->query("DESCRIBE charges");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== ESTRUTURA DA TABELA PRODUCTS_SERVICES ===\n";
    $stmt = $pdo->query("DESCRIBE products_services");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== ÍNDICES DA TABELA PRODUCTS_SERVICES ===\n";
    $stmt = $pdo->query("SHOW INDEX FROM products_services");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($indexes as $index) {
        echo "- {$index['Key_name']} ({$index['Column_name']})\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

?>