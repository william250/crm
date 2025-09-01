<?php
/**
 * Script para executar arquivos SQL e popular o banco de dados
 * Execute: php execute_sql.php
 */

// Configurações do banco de dados
$host = 'localhost';
$username = 'root';
$passwords = ['', 'root', 'password', 'mysql']; // Tentar diferentes senhas
$database = 'crm_system';
$pdo = null;

// Tentar conectar com diferentes senhas
foreach ($passwords as $password) {
    try {
        echo "Tentando conectar com senha: '" . ($password === '' ? '(vazia)' : $password) . "'\n";
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Conectado ao banco de dados com sucesso!\n";
        break;
    } catch (PDOException $e) {
        echo "Falha na conexão: " . $e->getMessage() . "\n";
        continue;
    }
}

if (!$pdo) {
    echo "Não foi possível conectar ao banco de dados com nenhuma das senhas testadas.\n";
    exit(1);
}

try {
    
    // Lista de arquivos SQL para executar
    $sqlFiles = [
        'database/sample_data.sql',
        'database/products_services.sql',
        'database/pipeline_configurations.sql'
    ];
    
    foreach ($sqlFiles as $file) {
        if (file_exists($file)) {
            echo "\nExecutando arquivo: $file\n";
            
            // Ler o conteúdo do arquivo
            $sql = file_get_contents($file);
            
            // Dividir em comandos individuais (separados por ;)
            $commands = explode(';', $sql);
            
            $executedCommands = 0;
            $errors = 0;
            
            foreach ($commands as $command) {
                $command = trim($command);
                
                // Pular comandos vazios e comentários
                if (empty($command) || 
                    strpos($command, '--') === 0 || 
                    strpos($command, '/*') === 0 ||
                    strpos($command, 'USE ') === 0) {
                    continue;
                }
                
                try {
                    $pdo->exec($command);
                    $executedCommands++;
                } catch (PDOException $e) {
                    // Ignorar erros de "já existe" e "coluna duplicada"
                    if (strpos($e->getMessage(), 'already exists') !== false ||
                        strpos($e->getMessage(), 'Duplicate column') !== false ||
                        strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        continue;
                    }
                    
                    echo "Erro ao executar comando: " . $e->getMessage() . "\n";
                    echo "Comando: " . substr($command, 0, 100) . "...\n";
                    $errors++;
                }
            }
            
            echo "Arquivo $file processado: $executedCommands comandos executados";
            if ($errors > 0) {
                echo ", $errors erros";
            }
            echo "\n";
            
        } else {
            echo "Arquivo não encontrado: $file\n";
        }
    }
    
    echo "\n=== VERIFICAÇÃO DOS DADOS ===\n";
    
    // Verificar quantos registros foram inseridos
    $tables = ['users', 'leads', 'clients', 'appointments', 'interactions', 'contracts', 'products_services', 'sales_pipelines'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Tabela $table: {$result['count']} registros\n";
        } catch (PDOException $e) {
            echo "Tabela $table: não existe ou erro ao consultar\n";
        }
    }
    
    echo "\n=== EXECUÇÃO CONCLUÍDA ===\n";
    echo "Banco de dados populado com sucesso!\n";
    
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}
?>