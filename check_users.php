<?php

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->query('SELECT * FROM users LIMIT 1');
    $user = $stmt->fetch();
    
    if($user) {
        echo 'User found: ' . $user['email'] . "\n";
        echo 'Role: ' . $user['role'] . "\n";
    } else {
        echo 'No users found' . "\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}