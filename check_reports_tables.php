<?php

$host = 'localhost';
$dbname = 'crm_system';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado ao banco de dados\n\n";
    
    // Verificar estrutura da tabela leads
    echo "=== ESTRUTURA DA TABELA LEADS ===\n";
    $stmt = $pdo->query("DESCRIBE leads");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Verificar estrutura da tabela contracts
    echo "\n=== ESTRUTURA DA TABELA CONTRACTS ===\n";
    $stmt = $pdo->query("DESCRIBE contracts");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Verificar estrutura da tabela pipeline_history
    echo "\n=== ESTRUTURA DA TABELA PIPELINE_HISTORY ===\n";
    $stmt = $pdo->query("DESCRIBE pipeline_history");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>