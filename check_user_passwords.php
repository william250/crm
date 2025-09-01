<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system;charset=utf8mb4', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICANDO SENHAS DOS USUÁRIOS ===\n";
    
    $users = [
        ['email' => 'admin@sistema.com', 'password' => 'admin123'],
        ['email' => 'vendedor@sistema.com', 'password' => 'vendedor123'],
        ['email' => 'admin@crm.com', 'password' => 'admin123']
    ];
    
    foreach ($users as $testUser) {
        $stmt = $pdo->prepare('SELECT email, password_hash FROM users WHERE email = ?');
        $stmt->execute([$testUser['email']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "\nEmail: " . $user['email'] . "\n";
            echo "Hash: " . substr($user['password_hash'], 0, 50) . "...\n";
            $isValid = password_verify($testUser['password'], $user['password_hash']);
            echo "Senha '{$testUser['password']}': " . ($isValid ? 'VÁLIDA' : 'INVÁLIDA') . "\n";
            
            if (!$isValid) {
                // Tentar outras senhas comuns
                $commonPasswords = ['123456', 'password', 'admin', ''];
                foreach ($commonPasswords as $pwd) {
                    if (password_verify($pwd, $user['password_hash'])) {
                        echo "Senha correta encontrada: '$pwd'\n";
                        break;
                    }
                }
            }
        } else {
            echo "\nUsuário {$testUser['email']} não encontrado\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>