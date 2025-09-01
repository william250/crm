<?php
/**
 * Script para configurar o banco de dados do CRM localmente
 * Execute: php setup_database.php
 */

// Carregar variáveis de ambiente do arquivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Arquivo .env não encontrado. Certifique-se de que existe em: $path\n");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Pular comentários
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Carregar configurações
loadEnv(__DIR__ . '/.env');

echo "=== CONFIGURAÇÃO DO BANCO DE DADOS CRM ===\n\n";

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'crm_system';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? 'root';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$port = $_ENV['DB_PORT'] ?? '3306';

echo "Configurações do banco:\n";
echo "Host: $host\n";
echo "Database: $dbname\n";
echo "User: $username\n";
echo "Password: " . (empty($password) ? '(vazio)' : '***') . "\n\n";

try {
    // Conectar ao MySQL sem especificar o banco (para criar o banco)
    echo "1. Conectando ao MySQL...\n";
    $dsn = "mysql:host=$host;port=$port;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexão estabelecida com sucesso!\n\n";
    
    // Criar o banco de dados se não existir
    echo "2. Criando banco de dados '$dbname'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
    echo "✓ Banco de dados criado/verificado com sucesso!\n\n";
    
    // Conectar ao banco específico
    echo "3. Conectando ao banco '$dbname'...\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado ao banco específico!\n\n";
    
    // Criar tabelas uma por uma
    echo "4. Criando tabelas...\n";
    
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'seller', 'financial', 'basic') DEFAULT 'basic',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_email (email),
                INDEX idx_users_role (role)
            )",
        'leads' => "
            CREATE TABLE IF NOT EXISTS leads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(20),
                source VARCHAR(50),
                status ENUM('new', 'contacted', 'qualified', 'proposal', 'won', 'lost') DEFAULT 'new',
                value DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_leads_user_id (user_id),
                INDEX idx_leads_status (status),
                INDEX idx_leads_created_at (created_at DESC),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        'clients' => "
            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(20),
                document VARCHAR(20),
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_clients_user_id (user_id),
                INDEX idx_clients_email (email),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        'appointments' => "
            CREATE TABLE IF NOT EXISTS appointments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                client_id INT,
                title VARCHAR(200) NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                status ENUM('scheduled', 'confirmed', 'completed', 'cancelled') DEFAULT 'scheduled',
                fee DECIMAL(8,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_appointments_user_id (user_id),
                INDEX idx_appointments_start_time (start_time),
                INDEX idx_appointments_status (status),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
            )",
        'contracts' => "
            CREATE TABLE IF NOT EXISTS contracts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                content LONGTEXT NOT NULL,
                status ENUM('draft', 'sent', 'signed', 'cancelled') DEFAULT 'draft',
                value DECIMAL(12,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                signed_at TIMESTAMP NULL,
                INDEX idx_contracts_client_id (client_id),
                INDEX idx_contracts_status (status),
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            )",
        'signatures' => "
            CREATE TABLE IF NOT EXISTS signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                user_id INT NOT NULL,
                signature_hash VARCHAR(255) NOT NULL,
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_signatures_contract_id (contract_id),
                INDEX idx_signatures_user_id (user_id),
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        'charges' => "
            CREATE TABLE IF NOT EXISTS charges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                type ENUM('one_time', 'recurring') DEFAULT 'one_time',
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
                due_date DATE NOT NULL,
                payment_method ENUM('pix', 'credit_card', 'boleto') DEFAULT 'pix',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                paid_at TIMESTAMP NULL,
                INDEX idx_charges_client_id (client_id),
                INDEX idx_charges_status (status),
                INDEX idx_charges_due_date (due_date),
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            )",
        'payments' => "
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                charge_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                method ENUM('pix', 'credit_card', 'boleto', 'cash') NOT NULL,
                transaction_id VARCHAR(100),
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payments_charge_id (charge_id),
                INDEX idx_payments_status (status),
                INDEX idx_payments_transaction_id (transaction_id),
                FOREIGN KEY (charge_id) REFERENCES charges(id) ON DELETE CASCADE
            )",
        'interactions' => "
            CREATE TABLE IF NOT EXISTS interactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                lead_id INT NULL,
                client_id INT NULL,
                type ENUM('call', 'email', 'meeting', 'note', 'task') NOT NULL,
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_interactions_user_id (user_id),
                INDEX idx_interactions_lead_id (lead_id),
                INDEX idx_interactions_client_id (client_id),
                INDEX idx_interactions_type (type),
                INDEX idx_interactions_created_at (created_at DESC),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        try {
            echo "   Criando tabela $tableName...\n";
            $pdo->exec($sql);
            echo "   ✓ Tabela $tableName criada\n";
        } catch (PDOException $e) {
            echo "   ✗ Erro ao criar $tableName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n5. Inserindo dados iniciais...\n";
    
    // Inserir usuários (usando as credenciais do arquivo login.html)
     $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
     $managerHash = password_hash('manager123', PASSWORD_DEFAULT);
     $salesHash = password_hash('sales123', PASSWORD_DEFAULT);
     $userHash = password_hash('user123', PASSWORD_DEFAULT);
     
     $pdo->exec("
         INSERT IGNORE INTO users (email, password_hash, name, role) VALUES 
         ('admin@crm.com', '$adminHash', 'Administrator', 'admin'),
         ('manager@crm.com', '$managerHash', 'Manager', 'admin'),
         ('sales@crm.com', '$salesHash', 'Sales Representative', 'seller'),
         ('user@crm.com', '$userHash', 'Basic User', 'basic')
     ");
    echo "   ✓ Usuários inseridos\n";
    
    // Inserir leads (atribuídos ao vendedor sales@crm.com)
     $pdo->exec("
         INSERT IGNORE INTO leads (user_id, name, email, phone, source, status, value) VALUES 
         (3, 'Empresa ABC Ltda', 'contato@empresaabc.com', '(11) 99999-1111', 'website', 'new', 5000.00),
         (3, 'João Silva', 'joao@email.com', '(11) 99999-2222', 'indicacao', 'contacted', 3000.00),
         (3, 'Maria Santos', 'maria@email.com', '(11) 99999-3333', 'google_ads', 'qualified', 7500.00)
     ");
    echo "   ✓ Leads inseridos\n";
    
    // Inserir clientes (atribuídos ao vendedor sales@crm.com)
     $pdo->exec("
         INSERT IGNORE INTO clients (user_id, name, email, phone, document, address) VALUES 
         (3, 'Empresa XYZ Ltda', 'contato@empresaxyz.com', '(11) 88888-1111', '12.345.678/0001-90', 'Rua das Empresas, 123 - São Paulo/SP'),
         (3, 'Pedro Oliveira', 'pedro@email.com', '(11) 88888-2222', '123.456.789-00', 'Av. Principal, 456 - São Paulo/SP')
     ");
    echo "   ✓ Clientes inseridos\n";
    
    // Inserir agendamentos (atribuídos ao vendedor sales@crm.com)
     $pdo->exec("
         INSERT IGNORE INTO appointments (user_id, client_id, title, start_time, end_time, status, fee) VALUES 
         (3, 1, 'Reunião de Apresentação', '2025-01-29 14:00:00', '2025-01-29 15:00:00', 'scheduled', 0.00),
         (3, 2, 'Reunião de Fechamento', '2025-01-30 10:00:00', '2025-01-30 11:30:00', 'confirmed', 100.00)
     ");
    echo "   ✓ Agendamentos inseridos\n";
    
    // Inserir contratos
    $pdo->exec("
        INSERT IGNORE INTO contracts (client_id, title, content, status, value) VALUES 
        (1, 'Contrato de Prestação de Serviços', 'Este contrato estabelece os termos e condições para prestação de serviços...', 'draft', 10000.00)
    ");
    echo "   ✓ Contratos inseridos\n";
    
    // Inserir cobranças
    $pdo->exec("
        INSERT IGNORE INTO charges (client_id, type, amount, status, due_date, payment_method) VALUES 
        (1, 'one_time', 2500.00, 'pending', '2025-02-15', 'pix'),
        (2, 'recurring', 500.00, 'paid', '2025-01-15', 'credit_card')
    ");
    echo "   ✓ Cobranças inseridas\n";
    
    // Inserir pagamentos
    $pdo->exec("
        INSERT IGNORE INTO payments (charge_id, amount, method, transaction_id, status) VALUES 
        (2, 500.00, 'credit_card', 'TXN123456789', 'completed')
    ");
    echo "   ✓ Pagamentos inseridos\n";
    
    // Inserir interações (atribuídas ao vendedor sales@crm.com)
     $pdo->exec("
         INSERT IGNORE INTO interactions (user_id, lead_id, client_id, type, description) VALUES 
         (3, 1, NULL, 'call', 'Primeira ligação para apresentar nossos serviços. Cliente demonstrou interesse.'),
         (3, 2, NULL, 'email', 'Enviado proposta comercial por e-mail.'),
         (3, NULL, 1, 'meeting', 'Reunião presencial para discussão do projeto. Definidos próximos passos.')
     ");
    echo "   ✓ Interações inseridas\n";
    
    // Verificar se as tabelas foram criadas
    echo "\n6. Verificando tabelas criadas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedTables = ['users', 'leads', 'clients', 'appointments', 'contracts', 'signatures', 'charges', 'payments', 'interactions'];
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ Tabela '$table' criada\n";
        } else {
            echo "✗ Tabela '$table' NÃO encontrada\n";
        }
    }
    
    echo "\n7. Verificando dados iniciais...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "✓ Usuários cadastrados: $userCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM leads");
    $leadCount = $stmt->fetch()['count'];
    echo "✓ Leads cadastrados: $leadCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $clientCount = $stmt->fetch()['count'];
    echo "✓ Clientes cadastrados: $clientCount\n";
    
    echo "\n=== CONFIGURAÇÃO CONCLUÍDA COM SUCESSO! ===\n\n";
    echo "Credenciais de acesso (conforme login.html):\n";
     echo "Admin: admin@crm.com / admin123\n";
     echo "Manager: manager@crm.com / manager123\n";
     echo "Sales: sales@crm.com / sales123\n";
     echo "User: user@crm.com / user123\n\n";
    echo "Você pode agora iniciar o servidor web e acessar o sistema.\n";
    echo "Para iniciar o servidor PHP built-in: php -S localhost:8000 -t public\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERRO DE BANCO DE DADOS:\n";
    echo $e->getMessage() . "\n\n";
    echo "Verifique se:\n";
    echo "1. O MySQL está rodando\n";
    echo "2. As credenciais no arquivo .env estão corretas\n";
    echo "3. O usuário tem permissões para criar bancos de dados\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERRO:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}