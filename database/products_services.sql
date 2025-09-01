-- ============================================================================
-- TABELA DE PRODUTOS E SERVIÇOS PARA O SISTEMA CRM CLOUTHUB
-- ============================================================================
-- Esta tabela permite cadastrar produtos e serviços oferecidos pela empresa
-- e associá-los a contratos, propostas e oportunidades

USE crm_system;

-- ============================================================================
-- CRIAÇÃO DA TABELA DE CATEGORIAS DE PRODUTOS/SERVIÇOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff', -- Cor para identificação visual
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category_status (status),
    INDEX idx_category_name (name)
);

-- ============================================================================
-- CRIAÇÃO DA TABELA DE PRODUTOS/SERVIÇOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS products_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    type ENUM('product', 'service', 'subscription', 'consulting') NOT NULL,
    sku VARCHAR(50) UNIQUE, -- Código do produto/serviço
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(10,2) DEFAULT 0.00, -- Custo para cálculo de margem
    currency VARCHAR(3) DEFAULT 'BRL',
    billing_cycle ENUM('one_time', 'monthly', 'quarterly', 'semi_annual', 'annual') DEFAULT 'one_time',
    duration_months INT DEFAULT NULL, -- Duração em meses (para serviços/assinaturas)
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    features JSON, -- Características do produto/serviço em JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    
    INDEX idx_product_type (type),
    INDEX idx_product_status (status),
    INDEX idx_product_category (category_id),
    INDEX idx_product_sku (sku),
    INDEX idx_product_price (price)
);

-- ============================================================================
-- TABELA DE RELACIONAMENTO ENTRE CONTRATOS E PRODUTOS/SERVIÇOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS contract_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    product_service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    description TEXT, -- Descrição específica para este item no contrato
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_service_id) REFERENCES products_services(id) ON DELETE RESTRICT,
    
    INDEX idx_contract_items_contract (contract_id),
    INDEX idx_contract_items_product (product_service_id)
);

-- ============================================================================
-- INSERÇÃO DE CATEGORIAS
-- ============================================================================

INSERT INTO product_categories (name, description, color) VALUES 
('CRM Software', 'Sistemas de gestão de relacionamento com cliente', '#CA773B'),
('Consultoria', 'Serviços de consultoria e implementação', '#28a745'),
('Treinamento', 'Cursos e treinamentos para usuários', '#17a2b8'),
('Suporte Técnico', 'Serviços de suporte e manutenção', '#ffc107'),
('Customização', 'Desenvolvimento de funcionalidades específicas', '#6f42c1'),
('Integração', 'Serviços de integração com outros sistemas', '#fd7e14');

-- ============================================================================
-- INSERÇÃO DE PRODUTOS E SERVIÇOS
-- ============================================================================

INSERT INTO products_services (name, description, category_id, type, sku, price, cost, billing_cycle, duration_months, features) VALUES 
-- CRM Software
('CRM CloutHub Básico', 'Sistema CRM básico para pequenas empresas com até 5 usuários', 1, 'subscription', 'CRM-BASIC', 299.00, 50.00, 'monthly', 12, 
 JSON_OBJECT('users', 5, 'leads', 1000, 'clients', 500, 'storage_gb', 5, 'support', 'email', 'mobile_app', false)),

('CRM CloutHub Profissional', 'Sistema CRM profissional para empresas médias com até 25 usuários', 1, 'subscription', 'CRM-PRO', 899.00, 150.00, 'monthly', 12,
 JSON_OBJECT('users', 25, 'leads', 10000, 'clients', 5000, 'storage_gb', 50, 'support', 'phone_email', 'mobile_app', true, 'api_access', true)),

('CRM CloutHub Enterprise', 'Sistema CRM enterprise para grandes empresas com usuários ilimitados', 1, 'subscription', 'CRM-ENT', 2499.00, 400.00, 'monthly', 12,
 JSON_OBJECT('users', 'unlimited', 'leads', 'unlimited', 'clients', 'unlimited', 'storage_gb', 500, 'support', '24x7', 'mobile_app', true, 'api_access', true, 'white_label', true)),

('CRM CloutHub Anual Básico', 'Plano anual CRM básico com desconto', 1, 'subscription', 'CRM-BASIC-Y', 2990.00, 500.00, 'annual', 12,
 JSON_OBJECT('users', 5, 'leads', 1000, 'clients', 500, 'storage_gb', 5, 'support', 'email', 'mobile_app', false, 'discount', '17%')),

-- Consultoria
('Consultoria em CRM', 'Consultoria especializada em implementação de CRM por hora', 2, 'service', 'CONS-CRM-H', 180.00, 90.00, 'one_time', NULL,
 JSON_OBJECT('unit', 'hour', 'min_hours', 4, 'includes', ['análise', 'recomendações', 'documentação'])),

('Projeto de Implementação', 'Projeto completo de implementação de CRM (40h)', 2, 'service', 'PROJ-IMPL', 6500.00, 3200.00, 'one_time', 3,
 JSON_OBJECT('duration_weeks', 12, 'includes', ['análise completa', 'configuração', 'migração de dados', 'treinamento', 'go-live'])),

('Consultoria Estratégica', 'Consultoria estratégica para otimização de processos comerciais', 2, 'consulting', 'CONS-ESTR', 350.00, 175.00, 'one_time', NULL,
 JSON_OBJECT('unit', 'hour', 'min_hours', 8, 'includes', ['diagnóstico', 'estratégia', 'roadmap', 'KPIs'])),

-- Treinamento
('Treinamento Básico CRM', 'Treinamento básico para usuários finais (8h)', 3, 'service', 'TRAIN-BAS', 1200.00, 400.00, 'one_time', NULL,
 JSON_OBJECT('duration_hours', 8, 'max_participants', 15, 'includes', ['material didático', 'certificado', 'suporte pós-treinamento'])),

('Treinamento Avançado', 'Treinamento avançado para administradores (16h)', 3, 'service', 'TRAIN-ADV', 2400.00, 800.00, 'one_time', NULL,
 JSON_OBJECT('duration_hours', 16, 'max_participants', 10, 'includes', ['configurações avançadas', 'relatórios', 'automações', 'integrações'])),

('Workshop Personalizado', 'Workshop personalizado conforme necessidade do cliente', 3, 'service', 'WORK-PERS', 450.00, 200.00, 'one_time', NULL,
 JSON_OBJECT('unit', 'hour', 'min_hours', 4, 'customizable', true, 'includes', ['conteúdo específico', 'exercícios práticos'])),

-- Suporte Técnico
('Suporte Premium', 'Suporte técnico premium 24x7 com SLA de 2h', 4, 'subscription', 'SUP-PREM', 599.00, 200.00, 'monthly', 12,
 JSON_OBJECT('sla_hours', 2, 'channels', ['phone', 'email', 'chat', 'remote'], 'availability', '24x7')),

('Suporte Básico', 'Suporte técnico básico em horário comercial', 4, 'subscription', 'SUP-BAS', 199.00, 80.00, 'monthly', 12,
 JSON_OBJECT('sla_hours', 24, 'channels', ['email', 'chat'], 'availability', 'business_hours')),

('Hora de Suporte Avulsa', 'Hora de suporte técnico avulsa', 4, 'service', 'SUP-HOUR', 120.00, 60.00, 'one_time', NULL,
 JSON_OBJECT('unit', 'hour', 'min_hours', 1, 'includes', ['diagnóstico', 'resolução', 'documentação'])),

-- Customização
('Desenvolvimento de Relatório', 'Desenvolvimento de relatório personalizado', 5, 'service', 'DEV-REP', 800.00, 400.00, 'one_time', NULL,
 JSON_OBJECT('complexity', 'medium', 'delivery_days', 10, 'includes', ['análise', 'desenvolvimento', 'testes', 'documentação'])),

('Integração API', 'Desenvolvimento de integração via API', 5, 'service', 'INT-API', 2500.00, 1200.00, 'one_time', NULL,
 JSON_OBJECT('complexity', 'high', 'delivery_days', 20, 'includes', ['análise técnica', 'desenvolvimento', 'testes', 'documentação', 'suporte'])),

('Customização de Tela', 'Customização de interface conforme layout do cliente', 5, 'service', 'CUST-UI', 1200.00, 600.00, 'one_time', NULL,
 JSON_OBJECT('complexity', 'medium', 'delivery_days', 15, 'includes', ['design', 'desenvolvimento', 'testes'])),

-- Integração
('Integração ERP', 'Integração com sistemas ERP (SAP, TOTVS, etc.)', 6, 'service', 'INT-ERP', 4500.00, 2200.00, 'one_time', NULL,
 JSON_OBJECT('complexity', 'high', 'delivery_days', 30, 'systems', ['SAP', 'TOTVS', 'Oracle'], 'includes', ['mapeamento', 'desenvolvimento', 'testes', 'go-live'])),

('Integração E-commerce', 'Integração com plataformas de e-commerce', 6, 'service', 'INT-ECOM', 1800.00, 900.00, 'one_time', NULL,
 JSON_OBJECT('complexity', 'medium', 'delivery_days', 15, 'platforms', ['Shopify', 'WooCommerce', 'Magento'], 'includes', ['sincronização de pedidos', 'clientes', 'produtos'])),

('Integração Contábil', 'Integração com sistemas contábeis', 6, 'service', 'INT-CONT', 2200.00, 1100.00, 'one_time', NULL,
 JSON_OBJECT('complexity', 'medium', 'delivery_days', 20, 'includes', ['sincronização de dados fiscais', 'relatórios', 'automação']));

-- ============================================================================
-- INSERÇÃO DE ITENS NOS CONTRATOS EXISTENTES
-- ============================================================================

-- Contrato TechCorp (CONT-2025-001)
INSERT INTO contract_items (contract_id, product_service_id, quantity, unit_price, total_amount, description) VALUES 
(1, 2, 1, 899.00, 10788.00, 'CRM Profissional - 12 meses'),
(1, 11, 1, 599.00, 7188.00, 'Suporte Premium - 12 meses'),
(1, 8, 2, 1200.00, 2400.00, 'Treinamento para 2 turmas');

-- Contrato Marina Fernandes (CONT-2025-002)
INSERT INTO contract_items (contract_id, product_service_id, quantity, unit_price, total_amount, description) VALUES 
(2, 5, 40, 180.00, 7200.00, 'Consultoria CRM - 40 horas'),
(2, 14, 5, 800.00, 4000.00, 'Desenvolvimento de 5 relatórios personalizados');

-- Contrato Metalúrgica ABC (CONT-2025-003)
INSERT INTO contract_items (contract_id, product_service_id, quantity, unit_price, total_amount, description) VALUES 
(3, 3, 1, 2499.00, 29988.00, 'CRM Enterprise - 12 meses'),
(3, 6, 1, 6500.00, 6500.00, 'Projeto de Implementação Completo'),
(3, 15, 2, 2500.00, 5000.00, 'Integração com ERP SAP'),
(3, 9, 1, 2400.00, 2400.00, 'Treinamento Avançado para Administradores');

-- ============================================================================
-- VIEWS PARA RELATÓRIOS
-- ============================================================================

-- View para produtos mais vendidos
CREATE OR REPLACE VIEW products_sales_summary AS
SELECT 
    ps.id,
    ps.name,
    ps.type,
    pc.name as category_name,
    COUNT(ci.id) as times_sold,
    SUM(ci.quantity) as total_quantity,
    SUM(ci.total_amount) as total_revenue,
    AVG(ci.unit_price) as avg_price,
    ps.price as current_price
FROM products_services ps
LEFT JOIN product_categories pc ON ps.category_id = pc.id
LEFT JOIN contract_items ci ON ps.id = ci.product_service_id
GROUP BY ps.id, ps.name, ps.type, pc.name, ps.price
ORDER BY total_revenue DESC;

-- View para análise de margem
CREATE OR REPLACE VIEW products_margin_analysis AS
SELECT 
    ps.id,
    ps.name,
    ps.price,
    ps.cost,
    (ps.price - ps.cost) as margin_amount,
    CASE 
        WHEN ps.price > 0 THEN ROUND(((ps.price - ps.cost) / ps.price) * 100, 2)
        ELSE 0
    END as margin_percent,
    ps.type,
    pc.name as category_name
FROM products_services ps
LEFT JOIN product_categories pc ON ps.category_id = pc.id
WHERE ps.status = 'active'
ORDER BY margin_percent DESC;

-- ============================================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================================

CREATE INDEX idx_contract_items_totals ON contract_items(total_amount, quantity);
CREATE INDEX idx_products_pricing ON products_services(price, cost, billing_cycle);

-- ============================================================================
-- COMENTÁRIOS FINAIS
-- ============================================================================
/*
Este arquivo adiciona ao sistema CRM:

1. TABELAS:
   - product_categories: Categorias de produtos/serviços
   - products_services: Catálogo completo de produtos e serviços
   - contract_items: Relacionamento entre contratos e produtos/serviços

2. DADOS DE EXEMPLO:
   - 6 categorias de produtos/serviços
   - 18 produtos/serviços variados (CRM, consultoria, treinamento, suporte, etc.)
   - Itens associados aos contratos existentes

3. VIEWS:
   - products_sales_summary: Resumo de vendas por produto
   - products_margin_analysis: Análise de margem de lucro

4. FUNCIONALIDADES:
   - Preços e custos para cálculo de margem
   - Diferentes tipos de cobrança (mensal, anual, única)
   - Características em JSON para flexibilidade
   - Relacionamento com contratos existentes

Esta estrutura permite:
- Gestão completa do catálogo de produtos/serviços
- Análise de rentabilidade
- Relatórios de vendas
- Propostas comerciais detalhadas
- Controle de margem de lucro
*/