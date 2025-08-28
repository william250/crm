-- Sistema CRM - Criação das Tabelas
-- Data: 2025-01-28

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS crm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm_system;

-- Tabela de Usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'seller', 'financial', 'basic') DEFAULT 'basic',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
);

-- Tabela de Leads
CREATE TABLE leads (
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
);

-- Tabela de Clientes
CREATE TABLE clients (
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
);

-- Tabela de Agendamentos
CREATE TABLE appointments (
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
);

-- Tabela de Contratos
CREATE TABLE contracts (
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
);

-- Tabela de Assinaturas
CREATE TABLE signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    user_id INT NOT NULL,
    signature_hash VARCHAR(255) NOT NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_signatures_contract_id (contract_id),
    INDEX idx_signatures_user_id (user_id),
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Cobranças
CREATE TABLE charges (
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
);

-- Tabela de Pagamentos
CREATE TABLE payments (
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
);

-- Tabela de Interações
CREATE TABLE interactions (
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
);

-- Inserir dados iniciais

-- Usuário administrador padrão
INSERT INTO users (email, password_hash, name, role) VALUES 
('admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador do Sistema', 'admin'),
('vendedor@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'João Vendedor', 'seller'),
('financeiro@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Financeiro', 'financial');

-- Leads de exemplo
INSERT INTO leads (user_id, name, email, phone, source, status, value) VALUES 
(2, 'Empresa ABC Ltda', 'contato@empresaabc.com', '(11) 99999-1111', 'website', 'new', 5000.00),
(2, 'João Silva', 'joao@email.com', '(11) 99999-2222', 'indicacao', 'contacted', 3000.00),
(2, 'Maria Santos', 'maria@email.com', '(11) 99999-3333', 'google_ads', 'qualified', 7500.00);

-- Clientes de exemplo
INSERT INTO clients (user_id, name, email, phone, document, address) VALUES 
(2, 'Empresa XYZ Ltda', 'contato@empresaxyz.com', '(11) 88888-1111', '12.345.678/0001-90', 'Rua das Empresas, 123 - São Paulo/SP'),
(2, 'Pedro Oliveira', 'pedro@email.com', '(11) 88888-2222', '123.456.789-00', 'Av. Principal, 456 - São Paulo/SP');

-- Agendamentos de exemplo
INSERT INTO appointments (user_id, client_id, title, start_time, end_time, status, fee) VALUES 
(2, 1, 'Reunião de Apresentação', '2025-01-29 14:00:00', '2025-01-29 15:00:00', 'scheduled', 0.00),
(2, 2, 'Reunião de Fechamento', '2025-01-30 10:00:00', '2025-01-30 11:30:00', 'confirmed', 100.00);

-- Contratos de exemplo
INSERT INTO contracts (client_id, title, content, status, value) VALUES 
(1, 'Contrato de Prestação de Serviços', 'Este contrato estabelece os termos e condições para prestação de serviços...', 'draft', 10000.00);

-- Cobranças de exemplo
INSERT INTO charges (client_id, type, amount, status, due_date, payment_method) VALUES 
(1, 'one_time', 2500.00, 'pending', '2025-02-15', 'pix'),
(2, 'recurring', 500.00, 'paid', '2025-01-15', 'credit_card');

-- Pagamentos de exemplo
INSERT INTO payments (charge_id, amount, method, transaction_id, status) VALUES 
(2, 500.00, 'credit_card', 'TXN123456789', 'completed');

-- Interações de exemplo
INSERT INTO interactions (user_id, lead_id, client_id, type, description) VALUES 
(2, 1, NULL, 'call', 'Primeira ligação para apresentar nossos serviços. Cliente demonstrou interesse.'),
(2, 2, NULL, 'email', 'Enviado proposta comercial por e-mail.'),
(2, NULL, 1, 'meeting', 'Reunião presencial para discussão do projeto. Definidos próximos passos.');

-- Criar índices adicionais para performance
CREATE INDEX idx_leads_email ON leads(email);
CREATE INDEX idx_clients_document ON clients(document);
CREATE INDEX idx_contracts_created_at ON contracts(created_at DESC);
CREATE INDEX idx_charges_created_at ON charges(created_at DESC);
CREATE INDEX idx_payments_processed_at ON payments(processed_at DESC);

COMMIT;