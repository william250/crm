<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectado ao banco de dados!\n";
    
    // Criar tabela pipeline_history
    $sql1 = "CREATE TABLE IF NOT EXISTS pipeline_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        pipeline_id INT NOT NULL,
        from_stage_id INT,
        to_stage_id INT NOT NULL,
        move_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        moved_by INT NOT NULL,
        reason VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql1);
    echo "✓ Tabela pipeline_history criada\n";
    
    // Criar tabela automation_rules
    $sql2 = "CREATE TABLE IF NOT EXISTS automation_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        pipeline_id INT,
        trigger_event ENUM('stage_change', 'time_based', 'field_update', 'lead_created', 'lead_updated') NOT NULL,
        trigger_conditions JSON,
        actions JSON,
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql2);
    echo "✓ Tabela automation_rules criada\n";
    
    echo "\nTabelas criadas com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

?>