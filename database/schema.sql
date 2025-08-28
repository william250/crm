-- CRM System Database Schema
-- Created: 2024
-- Description: Complete database schema for PHP CRM system with all required tables

-- Create database (uncomment if needed)
-- CREATE DATABASE crm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE crm_system;

-- ============================================================================
-- 1. USERS TABLE
-- ============================================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'salesperson', 'user') DEFAULT 'user',
    phone VARCHAR(20),
    avatar VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    email_verified_at DATETIME,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
);

-- ============================================================================
-- 2. LEADS TABLE
-- ============================================================================
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    position VARCHAR(100),
    source ENUM('website', 'referral', 'social_media', 'email_campaign', 'cold_call', 'event', 'other') DEFAULT 'website',
    status ENUM('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost') DEFAULT 'new',
    value DECIMAL(15,2) DEFAULT 0.00,
    probability INT DEFAULT 0 COMMENT 'Percentage 0-100',
    expected_close_date DATE,
    assigned_to INT,
    notes TEXT,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Brazil',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_leads_email (email),
    INDEX idx_leads_status (status),
    INDEX idx_leads_source (source),
    INDEX idx_leads_assigned_to (assigned_to),
    INDEX idx_leads_created_at (created_at)
);

-- ============================================================================
-- 3. CLIENTS TABLE
-- ============================================================================
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    position VARCHAR(100),
    type ENUM('individual', 'company') DEFAULT 'individual',
    status ENUM('active', 'inactive', 'prospect', 'former') DEFAULT 'active',
    assigned_to INT,
    notes TEXT,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Brazil',
    tax_id VARCHAR(50) COMMENT 'CPF/CNPJ',
    website VARCHAR(255),
    industry VARCHAR(100),
    annual_revenue DECIMAL(15,2),
    employee_count INT,
    lead_id INT COMMENT 'Original lead that converted to this client',
    converted_at DATETIME COMMENT 'When lead was converted to client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_clients_email (email),
    INDEX idx_clients_status (status),
    INDEX idx_clients_type (type),
    INDEX idx_clients_assigned_to (assigned_to),
    INDEX idx_clients_created_at (created_at)
);

-- ============================================================================
-- 4. APPOINTMENTS TABLE
-- ============================================================================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    location VARCHAR(255),
    type ENUM('meeting', 'call', 'demo', 'consultation', 'follow_up', 'other') DEFAULT 'meeting',
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    client_id INT,
    lead_id INT,
    assigned_to INT NOT NULL,
    created_by INT NOT NULL,
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    reminder_datetime DATETIME,
    meeting_url VARCHAR(500) COMMENT 'For online meetings',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_appointments_start_datetime (start_datetime),
    INDEX idx_appointments_status (status),
    INDEX idx_appointments_client_id (client_id),
    INDEX idx_appointments_lead_id (lead_id),
    INDEX idx_appointments_assigned_to (assigned_to),
    INDEX idx_appointments_created_at (created_at)
);

-- ============================================================================
-- 5. CONTRACTS TABLE
-- ============================================================================
CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    client_id INT NOT NULL,
    type ENUM('service', 'product', 'maintenance', 'consulting', 'other') DEFAULT 'service',
    status ENUM('draft', 'sent', 'signed', 'active', 'completed', 'cancelled', 'expired') DEFAULT 'draft',
    value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'BRL',
    start_date DATE,
    end_date DATE,
    renewal_date DATE,
    auto_renewal BOOLEAN DEFAULT FALSE,
    payment_terms TEXT,
    terms_conditions TEXT,
    created_by INT NOT NULL,
    assigned_to INT,
    template_used VARCHAR(255),
    file_path VARCHAR(500) COMMENT 'Path to contract PDF file',
    signed_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contracts_contract_number (contract_number),
    INDEX idx_contracts_client_id (client_id),
    INDEX idx_contracts_status (status),
    INDEX idx_contracts_start_date (start_date),
    INDEX idx_contracts_end_date (end_date),
    INDEX idx_contracts_created_at (created_at)
);

-- ============================================================================
-- 6. SIGNATURES TABLE
-- ============================================================================
CREATE TABLE signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    signer_name VARCHAR(255) NOT NULL,
    signer_email VARCHAR(255) NOT NULL,
    signer_role ENUM('client', 'witness', 'company_representative', 'other') DEFAULT 'client',
    signature_type ENUM('digital', 'electronic', 'physical') DEFAULT 'digital',
    status ENUM('pending', 'signed', 'rejected', 'expired') DEFAULT 'pending',
    signed_at DATETIME,
    signature_data TEXT COMMENT 'Base64 encoded signature or signature hash',
    ip_address VARCHAR(45),
    user_agent TEXT,
    rejection_reason TEXT,
    expires_at DATETIME,
    reminder_sent_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    INDEX idx_signatures_contract_id (contract_id),
    INDEX idx_signatures_status (status),
    INDEX idx_signatures_signer_email (signer_email),
    INDEX idx_signatures_signed_at (signed_at),
    INDEX idx_signatures_created_at (created_at)
);

-- ============================================================================
-- 7. CHARGES TABLE (Billing/Invoices)
-- ============================================================================
CREATE TABLE charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    contract_id INT,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded') DEFAULT 'draft',
    due_date DATE NOT NULL,
    issue_date DATE NOT NULL,
    paid_date DATETIME,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto', 'check', 'other'),
    payment_reference VARCHAR(255) COMMENT 'Transaction ID or reference',
    recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('weekly', 'monthly', 'quarterly', 'yearly'),
    next_charge_date DATE,
    created_by INT NOT NULL,
    notes TEXT,
    file_path VARCHAR(500) COMMENT 'Path to invoice PDF file',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_charges_invoice_number (invoice_number),
    INDEX idx_charges_client_id (client_id),
    INDEX idx_charges_contract_id (contract_id),
    INDEX idx_charges_status (status),
    INDEX idx_charges_due_date (due_date),
    INDEX idx_charges_issue_date (issue_date),
    INDEX idx_charges_created_at (created_at)
);

-- ============================================================================
-- 8. PAYMENTS TABLE
-- ============================================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    charge_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto', 'check', 'other') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) COMMENT 'External payment processor transaction ID',
    reference VARCHAR(255) COMMENT 'Payment reference or confirmation number',
    payment_date DATETIME NOT NULL,
    processed_at DATETIME,
    gateway VARCHAR(100) COMMENT 'Payment gateway used (stripe, paypal, etc)',
    gateway_response TEXT COMMENT 'JSON response from payment gateway',
    fee_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Payment processing fee',
    net_amount DECIMAL(15,2) NOT NULL COMMENT 'Amount after fees',
    currency VARCHAR(3) DEFAULT 'BRL',
    refund_amount DECIMAL(15,2) DEFAULT 0.00,
    refund_date DATETIME,
    refund_reason TEXT,
    created_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (charge_id) REFERENCES charges(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payments_charge_id (charge_id),
    INDEX idx_payments_status (status),
    INDEX idx_payments_payment_date (payment_date),
    INDEX idx_payments_transaction_id (transaction_id),
    INDEX idx_payments_created_at (created_at)
);

-- ============================================================================
-- 9. INTERACTIONS TABLE
-- ============================================================================
CREATE TABLE interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    lead_id INT,
    user_id INT NOT NULL,
    type ENUM('call', 'email', 'meeting', 'sms', 'note', 'other') NOT NULL,
    direction ENUM('inbound', 'outbound') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    notes TEXT,
    interaction_date DATETIME NOT NULL,
    duration INT COMMENT 'Duration in minutes',
    status ENUM('pending', 'scheduled', 'completed', 'cancelled') DEFAULT 'completed',
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_interactions_client_id (client_id),
    INDEX idx_interactions_lead_id (lead_id),
    INDEX idx_interactions_user_id (user_id),
    INDEX idx_interactions_type (type),
    INDEX idx_interactions_interaction_date (interaction_date),
    INDEX idx_interactions_status (status),
    INDEX idx_interactions_follow_up_date (follow_up_date),
    INDEX idx_interactions_created_at (created_at),
    
    CONSTRAINT chk_interactions_client_or_lead CHECK (
        (client_id IS NOT NULL AND lead_id IS NULL) OR 
        (client_id IS NULL AND lead_id IS NOT NULL)
    )
);

-- ============================================================================
-- TRIGGERS
-- ============================================================================

-- Trigger to automatically update lead status when converted to client
DELIMITER //
CREATE TRIGGER update_lead_on_client_conversion
    AFTER INSERT ON clients
    FOR EACH ROW
BEGIN
    IF NEW.lead_id IS NOT NULL THEN
        UPDATE leads SET status = 'won' WHERE id = NEW.lead_id;
    END IF;
END//
DELIMITER ;

-- Trigger to update charge status when payment is completed
DELIMITER //
CREATE TRIGGER update_charge_on_payment
    AFTER UPDATE ON payments
    FOR EACH ROW
BEGIN
    DECLARE total_paid DECIMAL(15,2);
    DECLARE charge_total DECIMAL(15,2);
    
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Calculate total paid for this charge
        SELECT COALESCE(SUM(amount), 0) INTO total_paid
        FROM payments 
        WHERE charge_id = NEW.charge_id AND status = 'completed';
        
        -- Get charge total amount
        SELECT total_amount INTO charge_total
        FROM charges 
        WHERE id = NEW.charge_id;
        
        -- Update charge status based on payment
        IF total_paid >= charge_total THEN
            UPDATE charges SET status = 'paid', paid_date = NOW() WHERE id = NEW.charge_id;
        END IF;
    END IF;
END//
DELIMITER ;

-- ============================================================================
-- INITIAL DATA
-- ============================================================================

-- Insert default admin user
INSERT INTO users (name, email, password, role, status) VALUES 
('Administrator', 'admin@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
-- Password is 'password' (hashed)

-- Insert sample salesperson
INSERT INTO users (name, email, password, role, status, phone) VALUES 
('João Silva', 'joao@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'salesperson', 'active', '(11) 99999-9999');

-- Insert sample manager
INSERT INTO users (name, email, password, role, status, phone) VALUES 
('Maria Santos', 'maria@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active', '(11) 88888-8888');

-- Insert sample leads
INSERT INTO leads (title, first_name, last_name, email, phone, company, source, status, value, assigned_to) VALUES 
('Sr.', 'Carlos', 'Oliveira', 'carlos@empresa.com', '(11) 77777-7777', 'Empresa ABC', 'website', 'new', 15000.00, 2),
('Sra.', 'Ana', 'Costa', 'ana@startup.com', '(11) 66666-6666', 'Startup XYZ', 'referral', 'contacted', 25000.00, 2),
('Dr.', 'Roberto', 'Lima', 'roberto@consultoria.com', '(11) 55555-5555', 'Consultoria 123', 'social_media', 'qualified', 35000.00, 2);

-- Insert sample client (converted from lead)
INSERT INTO clients (name, email, phone, company, type, status, assigned_to, lead_id, converted_at) VALUES 
('Pedro Ferreira', 'pedro@tech.com', '(11) 44444-4444', 'Tech Solutions', 'company', 'active', 2, NULL, NOW());

-- Insert sample appointment
INSERT INTO appointments (title, description, start_datetime, end_datetime, type, status, client_id, assigned_to, created_by) VALUES 
('Reunião de Apresentação', 'Apresentar nossa solução para o cliente', '2024-02-15 14:00:00', '2024-02-15 15:00:00', 'meeting', 'scheduled', 1, 2, 1);

-- Insert sample contract
INSERT INTO contracts (contract_number, title, description, client_id, type, status, value, start_date, end_date, created_by, assigned_to) VALUES 
('CONT-2024-001', 'Contrato de Serviços de TI', 'Prestação de serviços de tecnologia da informação', 1, 'service', 'draft', 50000.00, '2024-03-01', '2025-02-28', 1, 2);

-- Insert sample charge
INSERT INTO charges (invoice_number, client_id, contract_id, description, amount, total_amount, due_date, issue_date, created_by) VALUES 
('INV-2024-001', 1, 1, 'Primeira parcela do contrato de serviços', 10000.00, 10000.00, '2024-03-15', '2024-02-15', 1);

-- Insert sample interaction
INSERT INTO interactions (client_id, user_id, type, direction, subject, notes, interaction_date, duration, status) VALUES 
(1, 2, 'call', 'outbound', 'Ligação de follow-up', 'Cliente interessado em expandir o contrato', '2024-02-10 10:30:00', 15, 'completed');

-- ============================================================================
-- VIEWS (Optional - for reporting)
-- ============================================================================

-- View for lead pipeline
CREATE VIEW lead_pipeline AS
SELECT 
    l.*,
    u.name as assigned_user_name,
    DATEDIFF(CURDATE(), l.created_at) as days_in_pipeline
FROM leads l
LEFT JOIN users u ON l.assigned_to = u.id
WHERE l.status NOT IN ('won', 'lost');

-- View for client summary
CREATE VIEW client_summary AS
SELECT 
    c.*,
    u.name as assigned_user_name,
    COUNT(DISTINCT co.id) as contract_count,
    COUNT(DISTINCT ch.id) as invoice_count,
    COALESCE(SUM(ch.total_amount), 0) as total_billed,
    COALESCE(SUM(CASE WHEN ch.status = 'paid' THEN ch.total_amount ELSE 0 END), 0) as total_paid
FROM clients c
LEFT JOIN users u ON c.assigned_to = u.id
LEFT JOIN contracts co ON c.id = co.client_id
LEFT JOIN charges ch ON c.id = ch.client_id
GROUP BY c.id;

-- View for sales performance
CREATE VIEW sales_performance AS
SELECT 
    u.id,
    u.name,
    COUNT(DISTINCT l.id) as total_leads,
    COUNT(DISTINCT CASE WHEN l.status = 'won' THEN l.id END) as won_leads,
    COUNT(DISTINCT c.id) as total_clients,
    COALESCE(SUM(CASE WHEN ch.status = 'paid' THEN ch.total_amount ELSE 0 END), 0) as total_revenue,
    ROUND(COUNT(DISTINCT CASE WHEN l.status = 'won' THEN l.id END) * 100.0 / NULLIF(COUNT(DISTINCT l.id), 0), 2) as conversion_rate
FROM users u
LEFT JOIN leads l ON u.id = l.assigned_to
LEFT JOIN clients c ON u.id = c.assigned_to
LEFT JOIN charges ch ON c.id = ch.client_id
WHERE u.role IN ('salesperson', 'manager')
GROUP BY u.id, u.name;

-- ============================================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_leads_status_assigned ON leads(status, assigned_to);
CREATE INDEX idx_clients_status_assigned ON clients(status, assigned_to);
CREATE INDEX idx_appointments_date_user ON appointments(start_datetime, assigned_to);
CREATE INDEX idx_contracts_status_client ON contracts(status, client_id);
CREATE INDEX idx_charges_status_due ON charges(status, due_date);
CREATE INDEX idx_payments_status_date ON payments(status, payment_date);
CREATE INDEX idx_interactions_date_user ON interactions(interaction_date, user_id);

-- Full-text search indexes
ALTER TABLE leads ADD FULLTEXT(title, first_name, last_name, company, notes);
ALTER TABLE clients ADD FULLTEXT(name, company, notes);
ALTER TABLE contracts ADD FULLTEXT(title, description);
ALTER TABLE interactions ADD FULLTEXT(subject, notes);

-- ============================================================================
-- STORED PROCEDURES (Optional)
-- ============================================================================

-- Procedure to convert lead to client
DELIMITER //
CREATE PROCEDURE ConvertLeadToClient(
    IN p_lead_id INT,
    IN p_converted_by INT
)
BEGIN
    DECLARE v_lead_exists INT DEFAULT 0;
    DECLARE v_client_id INT;
    
    -- Check if lead exists and is not already converted
    SELECT COUNT(*) INTO v_lead_exists 
    FROM leads 
    WHERE id = p_lead_id AND status NOT IN ('won', 'lost');
    
    IF v_lead_exists > 0 THEN
        -- Insert new client based on lead data
        INSERT INTO clients (
            name, email, phone, company, position, type, status, 
            assigned_to, notes, address, city, state, zip_code, country,
            lead_id, converted_at
        )
        SELECT 
            CONCAT(first_name, ' ', last_name), email, phone, company, position, 
            CASE WHEN company IS NOT NULL AND company != '' THEN 'company' ELSE 'individual' END,
            'active', assigned_to, notes, address, city, state, zip_code, country,
            id, NOW()
        FROM leads 
        WHERE id = p_lead_id;
        
        SET v_client_id = LAST_INSERT_ID();
        
        -- Update lead status
        UPDATE leads SET status = 'won' WHERE id = p_lead_id;
        
        SELECT v_client_id as client_id, 'Lead converted successfully' as message;
    ELSE
        SELECT 0 as client_id, 'Lead not found or already converted' as message;
    END IF;
END//
DELIMITER ;

-- Procedure to generate invoice number
DELIMITER //
CREATE PROCEDURE GenerateInvoiceNumber(
    OUT p_invoice_number VARCHAR(50)
)
BEGIN
    DECLARE v_year VARCHAR(4);
    DECLARE v_sequence INT;
    
    SET v_year = YEAR(CURDATE());
    
    -- Get next sequence number for current year
    SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number, -3) AS UNSIGNED)), 0) + 1 INTO v_sequence
    FROM charges 
    WHERE invoice_number LIKE CONCAT('INV-', v_year, '-%');
    
    SET p_invoice_number = CONCAT('INV-', v_year, '-', LPAD(v_sequence, 3, '0'));
END//
DELIMITER ;

-- ============================================================================
-- FINAL NOTES
-- ============================================================================
/*
This schema includes:
1. All 9 required tables with proper relationships
2. Comprehensive indexes for performance
3. Triggers for automatic status updates
4. Views for common reporting needs
5. Stored procedures for common operations
6. Sample data for testing
7. Full-text search capabilities
8. Proper constraints and data validation

To use this schema:
1. Create a new MySQL database
2. Run this entire script
3. Update your database configuration in config/database.php
4. Test the connection and sample data

Default login credentials:
- Email: admin@crm.com
- Password: password
*/