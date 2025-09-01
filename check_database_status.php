<?php

$host = 'localhost';
$dbname = 'crm_system';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado ao banco de dados\n\n";
    
    // Verificar todas as tabelas e seus registros
    $tables = [
        'users', 'leads', 'clients', 'appointments', 'interactions', 
        'contracts', 'payments', 'charges', 'product_categories', 
        'products_services', 'sales_pipelines', 'pipeline_stages', 'pipeline_history'
    ];
    
    echo "=== STATUS ATUAL DO BANCO DE DADOS ===\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "Tabela $table: $count registros\n";
            
            // Mostrar alguns dados de exemplo para tabelas principais
            if (in_array($table, ['users', 'leads', 'clients']) && $count > 0) {
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "  Exemplos:\n";
                foreach ($rows as $row) {
                    if ($table == 'users') {
                        echo "    - {$row['name']} ({$row['email']})\n";
                    } elseif ($table == 'leads') {
                        echo "    - {$row['name']} - Status: {$row['status']}\n";
                    } elseif ($table == 'clients') {
                        echo "    - {$row['name']} ({$row['email']})\n";
                    }
                }
            }
        } catch (PDOException $e) {
            echo "Erro ao verificar tabela $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Verificar se existem dados recentes
    echo "\n=== DADOS RECENTES ===\n";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $recent_leads = $stmt->fetchColumn();
        echo "Leads criados nas últimas 24h: $recent_leads\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status = 'signed'");
        $signed_contracts = $stmt->fetchColumn();
        echo "Contratos assinados: $signed_contracts\n";
        
        $stmt = $pdo->query("SELECT SUM(value) FROM contracts WHERE status = 'signed'");
        $total_value = $stmt->fetchColumn();
        echo "Valor total dos contratos: R$ " . number_format($total_value ?: 0, 2, ',', '.') . "\n";
        
    } catch (PDOException $e) {
        echo "Erro ao verificar dados recentes: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}

?>