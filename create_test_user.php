<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se já existe um usuário admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@crm.com'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Usuário admin já existe:\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Nome: " . $user['name'] . "\n";
        echo "Role: " . $user['role'] . "\n";
    } else {
        // Criar usuário admin
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, status, created_at) 
            VALUES ('Admin User', 'admin@crm.com', :password, 'admin', 'active', NOW())
        ");
        
        $stmt->execute([':password' => $hashedPassword]);
        
        echo "Usuário admin criado com sucesso!\n";
        echo "Email: admin@crm.com\n";
        echo "Senha: admin123\n";
    }
    
    // Mostrar todos os usuários
    echo "\n=== TODOS OS USUÁRIOS ===\n";
    $stmt = $pdo->query("SELECT id, name, email, role, status FROM users");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Nome: {$row['name']} | Email: {$row['email']} | Role: {$row['role']} | Status: {$row['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>