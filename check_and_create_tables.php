<?php
/**
 * Script para verificar e criar tabelas faltantes
 */

$host = 'localhost';
$username = 'root';
$passwords = ['', 'root', 'password', 'mysql'];
$database = 'crm_system';
$pdo = null;

// Conectar ao banco
foreach ($passwords as $password) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Conectado com sucesso!\n";
        break;
    } catch (PDOException $e) {
        continue;
    }
}

if (!$pdo) {
    echo "Erro de conexão\n";
    exit(1);
}

// Verificar tabelas existentes
$stmt = $pdo->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tabelas existentes:\n";
foreach ($existingTables as $table) {
    echo "- $table\n";
}

// Tabelas que precisamos criar
$requiredTables = [
    'product_categories',
    'products_services', 
    'contract_items',
    'sales_pipelines',
    'pipeline_stages',
    'loss_reasons',
    'pipeline_history',
    'automation_rules'
];

$missingTables = array_diff($requiredTables, $existingTables);

if (empty($missingTables)) {
    echo "\nTodas as tabelas necessárias já existem!\n";
} else {
    echo "\nTabelas faltantes:\n";
    foreach ($missingTables as $table) {
        echo "- $table\n";
    }
    
    // Criar tabelas faltantes
    echo "\nCriando tabelas faltantes...\n";
    
    // SQL para criar as tabelas
    $createStatements = [
        'product_categories' => "
            CREATE TABLE product_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                color VARCHAR(7) DEFAULT '#007bff',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category_status (status),
                INDEX idx_category_name (name)
            )
        ",
        
        'products_services' => "
            CREATE TABLE products_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                description TEXT,
                category_id INT,
                type ENUM('product', 'service', 'subscription', 'consulting') NOT NULL,
                sku VARCHAR(50) UNIQUE,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                cost DECIMAL(10,2) DEFAULT 0.00,
                currency VARCHAR(3) DEFAULT 'BRL',
                billing_cycle ENUM('one_time', 'monthly', 'quarterly', 'semi_annual', 'annual') DEFAULT 'one_time',
                duration_months INT DEFAULT NULL,
                status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
                features JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
                INDEX idx_product_type (type),
                INDEX idx_product_status (status),
                INDEX idx_product_category (category_id),
                INDEX idx_product_sku (sku),
                INDEX idx_product_price (price)
            )
        ",
        
        'contract_items' => "
            CREATE TABLE contract_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                product_service_id INT NOT NULL,
                quantity INT DEFAULT 1,
                unit_price DECIMAL(10,2) NOT NULL,
                discount_percent DECIMAL(5,2) DEFAULT 0.00,
                discount_amount DECIMAL(10,2) DEFAULT 0.00,
                total_amount DECIMAL(10,2) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (product_service_id) REFERENCES products_services(id) ON DELETE RESTRICT,
                INDEX idx_contract_items_contract (contract_id),
                INDEX idx_contract_items_product (product_service_id)
            )
        ",
        
        'sales_pipelines' => "
            CREATE TABLE sales_pipelines (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                is_default BOOLEAN DEFAULT FALSE,
                color VARCHAR(7) DEFAULT '#CA773B',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_pipeline_status (status),
                INDEX idx_pipeline_default (is_default)
            )
        ",
        
        'pipeline_stages' => "
            CREATE TABLE pipeline_stages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pipeline_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                stage_order INT NOT NULL,
                probability_percent INT DEFAULT 0,
                color VARCHAR(7) DEFAULT '#6c757d',
                is_closed_won BOOLEAN DEFAULT FALSE,
                is_closed_lost BOOLEAN DEFAULT FALSE,
                auto_actions JSON,
                required_fields JSON,
                time_limit_days INT DEFAULT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id) ON DELETE CASCADE,
                INDEX idx_stage_pipeline (pipeline_id),
                INDEX idx_stage_order (pipeline_id, stage_order),
                INDEX idx_stage_status (status),
                UNIQUE KEY unique_pipeline_order (pipeline_id, stage_order)
            )
        "
    ];
    
    foreach ($missingTables as $table) {
        if (isset($createStatements[$table])) {
            try {
                $pdo->exec($createStatements[$table]);
                echo "✓ Tabela $table criada com sucesso\n";
            } catch (PDOException $e) {
                echo "✗ Erro ao criar tabela $table: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n=== VERIFICAÇÃO FINAL ===\n";
$stmt = $pdo->query("SHOW TABLES");
$finalTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Total de tabelas: " . count($finalTables) . "\n";

?>