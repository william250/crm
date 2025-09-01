<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTRUTURA DA TABELA APPOINTMENTS ===\n";
    $stmt = $pdo->query('DESCRIBE appointments');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== EXEMPLO DE DADOS ===\n";
    $stmt = $pdo->query('SELECT * FROM appointments LIMIT 3');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>