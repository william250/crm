<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTRUTURA DA TABELA USERS ===\n";
    $stmt = $pdo->query('DESCRIBE users');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== TODOS OS USUÁRIOS ===\n";
    $stmt = $pdo->query("SELECT * FROM users");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Nome: {$row['name']} | Email: {$row['email']} | Role: {$row['role']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>