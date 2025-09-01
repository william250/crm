-- ============================================================================
-- CONFIGURAÇÕES DE PIPELINE E ESTÁGIOS PARA O SISTEMA CRM CLOUTHUB
-- ============================================================================
-- Este arquivo contém configurações avançadas de pipeline de vendas,
-- estágios personalizáveis e automações do funil comercial

USE crm_system;

-- ============================================================================
-- TABELA DE PIPELINES DE VENDAS
-- ============================================================================

CREATE TABLE IF NOT EXISTS sales_pipelines (
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
);

-- ============================================================================
-- TABELA DE ESTÁGIOS DO PIPELINE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pipeline_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pipeline_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    stage_order INT NOT NULL,
    probability_percent INT DEFAULT 0, -- Probabilidade de fechamento neste estágio
    color VARCHAR(7) DEFAULT '#6c757d',
    is_closed_won BOOLEAN DEFAULT FALSE, -- Indica se é estágio de "ganhou"
    is_closed_lost BOOLEAN DEFAULT FALSE, -- Indica se é estágio de "perdeu"
    auto_actions JSON, -- Ações automáticas ao entrar no estágio
    required_fields JSON, -- Campos obrigatórios para avançar
    time_limit_days INT DEFAULT NULL, -- Limite de tempo no estágio
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id) ON DELETE CASCADE,
    
    INDEX idx_stage_pipeline (pipeline_id),
    INDEX idx_stage_order (pipeline_id, stage_order),
    INDEX idx_stage_status (status),
    
    UNIQUE KEY unique_pipeline_order (pipeline_id, stage_order)
);

-- ============================================================================
-- TABELA DE MOTIVOS DE PERDA
-- ============================================================================

CREATE TABLE IF NOT EXISTS loss_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('price', 'product', 'competitor', 'timing', 'budget', 'other') DEFAULT 'other',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_loss_reason_category (category),
    INDEX idx_loss_reason_active (is_active)
);

-- ============================================================================
-- TABELA DE HISTÓRICO DE MOVIMENTAÇÃO NO PIPELINE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pipeline_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT,
    client_id INT,
    pipeline_id INT NOT NULL,
    from_stage_id INT,
    to_stage_id INT NOT NULL,
    moved_by INT NOT NULL,
    move_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    loss_reason_id INT DEFAULT NULL,
    
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id) ON DELETE CASCADE,
    FOREIGN KEY (from_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (to_stage_id) REFERENCES pipeline_stages(id) ON DELETE CASCADE,
    FOREIGN KEY (moved_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (loss_reason_id) REFERENCES loss_reasons(id) ON DELETE SET NULL,
    
    INDEX idx_history_lead (lead_id),
    INDEX idx_history_client (client_id),
    INDEX idx_history_pipeline (pipeline_id),
    INDEX idx_history_date (move_date),
    
    CONSTRAINT chk_lead_or_client CHECK (
        (lead_id IS NOT NULL AND client_id IS NULL) OR 
        (lead_id IS NULL AND client_id IS NOT NULL)
    )
);

-- ============================================================================
-- TABELA DE CONFIGURAÇÕES DE AUTOMAÇÃO
-- ============================================================================

CREATE TABLE IF NOT EXISTS automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    trigger_event ENUM('stage_change', 'time_in_stage', 'field_update', 'new_lead', 'new_interaction') NOT NULL,
    trigger_conditions JSON, -- Condições para disparar a automação
    actions JSON, -- Ações a serem executadas
    pipeline_id INT,
    stage_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_automation_trigger (trigger_event),
    INDEX idx_automation_pipeline (pipeline_id),
    INDEX idx_automation_active (is_active)
);

-- ============================================================================
-- INSERÇÃO DE PIPELINES
-- ============================================================================

INSERT INTO sales_pipelines (name, description, is_default, color, created_by) VALUES 
('Pipeline Padrão', 'Pipeline principal para vendas B2B', TRUE, '#CA773B', 1),
('Pipeline SMB', 'Pipeline otimizado para pequenas e médias empresas', FALSE, '#28a745', 1),
('Pipeline Enterprise', 'Pipeline para grandes contas corporativas', FALSE, '#6f42c1', 1),
('Pipeline Consultoria', 'Pipeline específico para projetos de consultoria', FALSE, '#17a2b8', 1),
('Pipeline Renovação', 'Pipeline para renovação de contratos existentes', FALSE, '#ffc107', 1);

-- ============================================================================
-- INSERÇÃO DE ESTÁGIOS - PIPELINE PADRÃO
-- ============================================================================

INSERT INTO pipeline_stages (pipeline_id, name, description, stage_order, probability_percent, color, auto_actions, required_fields, time_limit_days) VALUES 
-- Pipeline Padrão (id: 1)
(1, 'Lead Novo', 'Lead recém-capturado, aguardando primeira qualificação', 1, 5, '#6c757d', 
 JSON_OBJECT('send_email', true, 'assign_task', 'Fazer primeira ligação em 24h'), 
 JSON_OBJECT('required', ['phone', 'email']), 2),

(1, 'Contato Inicial', 'Primeiro contato realizado, lead demonstrou interesse', 2, 15, '#17a2b8',
 JSON_OBJECT('create_task', 'Agendar reunião de descoberta'),
 JSON_OBJECT('required', ['company', 'position']), 7),

(1, 'Qualificação', 'Lead qualificado, necessidades identificadas', 3, 25, '#20c997',
 JSON_OBJECT('update_probability', 25, 'notify_manager', true),
 JSON_OBJECT('required', ['value', 'expected_close_date', 'notes']), 10),

(1, 'Descoberta', 'Reunião de descoberta realizada, dores mapeadas', 4, 40, '#fd7e14',
 JSON_OBJECT('create_proposal_task', true),
 JSON_OBJECT('required', ['budget_range', 'decision_maker', 'timeline']), 14),

(1, 'Proposta', 'Proposta comercial enviada ao cliente', 5, 60, '#ffc107',
 JSON_OBJECT('schedule_followup', '3 days', 'notify_team', true),
 JSON_OBJECT('required', ['proposal_sent_date', 'proposal_value']), 10),

(1, 'Negociação', 'Cliente em processo de negociação de termos', 6, 75, '#fd7e14',
 JSON_OBJECT('alert_manager', true, 'prepare_contract', true),
 JSON_OBJECT('required', ['negotiation_points', 'decision_timeline']), 7),

(1, 'Fechamento', 'Aguardando assinatura do contrato', 7, 90, '#28a745',
 JSON_OBJECT('prepare_onboarding', true, 'notify_implementation', true),
 JSON_OBJECT('required', ['contract_terms', 'start_date']), 5),

(1, 'Ganhou', 'Negócio fechado com sucesso', 8, 100, '#198754', NULL, NULL, NULL),

(1, 'Perdeu', 'Negócio perdido', 9, 0, '#dc3545', 
 JSON_OBJECT('request_feedback', true, 'analyze_loss', true), 
 JSON_OBJECT('required', ['loss_reason']), NULL);

-- Marcar estágios de fechamento
UPDATE pipeline_stages SET is_closed_won = TRUE WHERE pipeline_id = 1 AND name = 'Ganhou';
UPDATE pipeline_stages SET is_closed_lost = TRUE WHERE pipeline_id = 1 AND name = 'Perdeu';

-- ============================================================================
-- INSERÇÃO DE ESTÁGIOS - PIPELINE SMB
-- ============================================================================

INSERT INTO pipeline_stages (pipeline_id, name, description, stage_order, probability_percent, color, time_limit_days) VALUES 
-- Pipeline SMB (id: 2) - Processo mais ágil
(2, 'Lead Qualificado', 'Lead já pré-qualificado para SMB', 1, 20, '#17a2b8', 3),
(2, 'Demo Agendada', 'Demonstração do produto agendada', 2, 40, '#20c997', 5),
(2, 'Demo Realizada', 'Demonstração concluída, interesse confirmado', 3, 60, '#ffc107', 7),
(2, 'Proposta Enviada', 'Proposta comercial enviada', 4, 80, '#fd7e14', 5),
(2, 'Fechado - Ganhou', 'Venda concluída', 5, 100, '#198754', NULL),
(2, 'Fechado - Perdeu', 'Oportunidade perdida', 6, 0, '#dc3545', NULL);

-- Marcar estágios de fechamento SMB
UPDATE pipeline_stages SET is_closed_won = TRUE WHERE pipeline_id = 2 AND name = 'Fechado - Ganhou';
UPDATE pipeline_stages SET is_closed_lost = TRUE WHERE pipeline_id = 2 AND name = 'Fechado - Perdeu';

-- ============================================================================
-- INSERÇÃO DE ESTÁGIOS - PIPELINE ENTERPRISE
-- ============================================================================

INSERT INTO pipeline_stages (pipeline_id, name, description, stage_order, probability_percent, color, time_limit_days) VALUES 
-- Pipeline Enterprise (id: 3) - Processo mais longo e complexo
(3, 'Identificação', 'Conta identificada como potencial enterprise', 1, 5, '#6c757d', 14),
(3, 'Contato Executivo', 'Contato estabelecido com tomador de decisão', 2, 15, '#17a2b8', 21),
(3, 'Mapeamento de Stakeholders', 'Stakeholders mapeados e engajados', 3, 25, '#20c997', 30),
(3, 'Prova de Conceito', 'POC aprovada e em execução', 4, 45, '#fd7e14', 45),
(3, 'Avaliação Técnica', 'Avaliação técnica detalhada concluída', 5, 60, '#ffc107', 30),
(3, 'Proposta Formal', 'Proposta formal apresentada ao comitê', 6, 75, '#fd7e14', 21),
(3, 'Negociação Contratual', 'Negociação de termos contratuais', 7, 85, '#28a745', 30),
(3, 'Aprovação Final', 'Aguardando aprovação final da diretoria', 8, 95, '#198754', 14),
(3, 'Contrato Assinado', 'Contrato assinado e projeto iniciado', 9, 100, '#198754', NULL),
(3, 'Não Qualificado', 'Conta não qualificada para enterprise', 10, 0, '#dc3545', NULL);

-- Marcar estágios de fechamento Enterprise
UPDATE pipeline_stages SET is_closed_won = TRUE WHERE pipeline_id = 3 AND name = 'Contrato Assinado';
UPDATE pipeline_stages SET is_closed_lost = TRUE WHERE pipeline_id = 3 AND name = 'Não Qualificado';

-- ============================================================================
-- INSERÇÃO DE MOTIVOS DE PERDA
-- ============================================================================

INSERT INTO loss_reasons (name, description, category) VALUES 
-- Preço
('Preço muito alto', 'Cliente considerou o preço acima do orçamento disponível', 'price'),
('Concorrente mais barato', 'Concorrente ofereceu preço significativamente menor', 'price'),
('Sem orçamento aprovado', 'Cliente não conseguiu aprovação de orçamento', 'budget'),

-- Produto
('Funcionalidade insuficiente', 'Produto não atende todas as necessidades', 'product'),
('Complexidade de implementação', 'Cliente considerou implementação muito complexa', 'product'),
('Falta de integração', 'Produto não integra com sistemas existentes', 'product'),

-- Concorrência
('Escolheu concorrente', 'Cliente optou por solução concorrente', 'competitor'),
('Solução interna', 'Cliente decidiu desenvolver solução internamente', 'competitor'),
('Fornecedor atual', 'Cliente manteve fornecedor atual', 'competitor'),

-- Timing
('Projeto adiado', 'Cliente adiou o projeto por questões internas', 'timing'),
('Prioridades mudaram', 'Mudança de prioridades estratégicas do cliente', 'timing'),
('Timing inadequado', 'Momento inadequado para implementação', 'timing'),

-- Outros
('Sem resposta', 'Cliente parou de responder contatos', 'other'),
('Mudança de equipe', 'Mudança na equipe responsável pela decisão', 'other'),
('Processo interno', 'Problemas no processo interno de aprovação', 'other');

-- ============================================================================
-- INSERÇÃO DE REGRAS DE AUTOMAÇÃO
-- ============================================================================

INSERT INTO automation_rules (name, description, trigger_event, trigger_conditions, actions, pipeline_id, is_active, created_by) VALUES 
('Notificar Manager - Negócio Alto Valor', 'Notifica o gerente quando negócio > R$ 50.000 entra em negociação', 'stage_change',
 JSON_OBJECT('stage_name', 'Negociação', 'min_value', 50000),
 JSON_OBJECT('notify_manager', true, 'create_task', 'Acompanhar negociação de alto valor'),
 1, TRUE, 1),

('Follow-up Automático', 'Cria tarefa de follow-up após 3 dias na proposta', 'time_in_stage',
 JSON_OBJECT('stage_name', 'Proposta', 'days_in_stage', 3),
 JSON_OBJECT('create_task', 'Follow-up da proposta enviada', 'send_email_template', 'followup_proposal'),
 1, TRUE, 1),

('Alerta Lead Parado', 'Alerta quando lead fica mais de 7 dias no mesmo estágio', 'time_in_stage',
 JSON_OBJECT('days_in_stage', 7, 'exclude_stages', ['Ganhou', 'Perdeu']),
 JSON_OBJECT('notify_owner', true, 'create_task', 'Lead parado - necessita ação'),
 NULL, TRUE, 1),

('Preparar Onboarding', 'Inicia processo de onboarding quando negócio é ganho', 'stage_change',
 JSON_OBJECT('stage_name', 'Ganhou'),
 JSON_OBJECT('create_onboarding_task', true, 'notify_implementation_team', true, 'send_welcome_email', true),
 1, TRUE, 1);

-- ============================================================================
-- ATUALIZAR LEADS EXISTENTES COM ESTÁGIOS
-- ============================================================================

-- Adicionar coluna de pipeline e estágio na tabela leads (se não existir)
ALTER TABLE leads 
ADD COLUMN IF NOT EXISTS pipeline_id INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS stage_id INT,
ADD FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id),
ADD FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id);

-- Atualizar leads existentes com estágios baseados no status atual
UPDATE leads SET 
    pipeline_id = 1,
    stage_id = CASE 
        WHEN status = 'new' THEN 1
        WHEN status = 'contacted' THEN 2
        WHEN status = 'qualified' THEN 3
        WHEN status = 'proposal' THEN 5
        WHEN status = 'negotiation' THEN 6
        WHEN status = 'won' THEN 8
        WHEN status = 'lost' THEN 9
        ELSE 1
    END;

-- ============================================================================
-- VIEWS PARA RELATÓRIOS DE PIPELINE
-- ============================================================================

-- View do funil de vendas
CREATE OR REPLACE VIEW sales_funnel_view AS
SELECT 
    sp.name as pipeline_name,
    ps.name as stage_name,
    ps.stage_order,
    ps.probability_percent,
    COUNT(l.id) as leads_count,
    SUM(l.value) as total_value,
    AVG(l.value) as avg_value,
    AVG(l.probability) as avg_probability
FROM sales_pipelines sp
JOIN pipeline_stages ps ON sp.id = ps.pipeline_id
LEFT JOIN leads l ON ps.id = l.stage_id AND l.status NOT IN ('won', 'lost')
WHERE sp.status = 'active' AND ps.status = 'active'
GROUP BY sp.id, sp.name, ps.id, ps.name, ps.stage_order, ps.probability_percent
ORDER BY sp.id, ps.stage_order;

-- View de conversão entre estágios
CREATE OR REPLACE VIEW stage_conversion_rates AS
SELECT 
    sp.name as pipeline_name,
    ps1.name as from_stage,
    ps2.name as to_stage,
    COUNT(ph.id) as moves_count,
    AVG(TIMESTAMPDIFF(DAY, 
        LAG(ph.move_date) OVER (PARTITION BY ph.lead_id ORDER BY ph.move_date),
        ph.move_date
    )) as avg_days_in_previous_stage
FROM pipeline_history ph
JOIN sales_pipelines sp ON ph.pipeline_id = sp.id
JOIN pipeline_stages ps1 ON ph.from_stage_id = ps1.id
JOIN pipeline_stages ps2 ON ph.to_stage_id = ps2.id
WHERE ph.from_stage_id IS NOT NULL
GROUP BY sp.id, sp.name, ps1.id, ps1.name, ps2.id, ps2.name
ORDER BY sp.id, ps1.stage_order;

-- View de performance por vendedor
CREATE OR REPLACE VIEW salesperson_pipeline_performance AS
SELECT 
    u.name as salesperson_name,
    sp.name as pipeline_name,
    COUNT(l.id) as total_leads,
    SUM(CASE WHEN ps.is_closed_won THEN 1 ELSE 0 END) as won_count,
    SUM(CASE WHEN ps.is_closed_lost THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN ps.is_closed_won THEN l.value ELSE 0 END) as won_value,
    CASE 
        WHEN COUNT(l.id) > 0 THEN 
            ROUND((SUM(CASE WHEN ps.is_closed_won THEN 1 ELSE 0 END) / COUNT(l.id)) * 100, 2)
        ELSE 0
    END as win_rate_percent
FROM users u
LEFT JOIN leads l ON u.id = l.assigned_to
LEFT JOIN sales_pipelines sp ON l.pipeline_id = sp.id
LEFT JOIN pipeline_stages ps ON l.stage_id = ps.id
WHERE u.role IN ('salesperson', 'manager')
GROUP BY u.id, u.name, sp.id, sp.name
ORDER BY won_value DESC;

-- ============================================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================================

CREATE INDEX idx_leads_pipeline_stage ON leads(pipeline_id, stage_id);
CREATE INDEX idx_pipeline_history_lead_date ON pipeline_history(lead_id, move_date);
CREATE INDEX idx_automation_trigger_pipeline ON automation_rules(trigger_event, pipeline_id);

-- ============================================================================
-- COMENTÁRIOS FINAIS
-- ============================================================================
/*
Este arquivo adiciona ao sistema CRM:

1. ESTRUTURA DE PIPELINE AVANÇADA:
   - Múltiplos pipelines personalizáveis
   - Estágios com probabilidades e automações
   - Histórico completo de movimentações
   - Motivos de perda categorizados

2. AUTOMAÇÕES:
   - Regras baseadas em eventos
   - Ações automáticas por estágio
   - Alertas e notificações
   - Tarefas automáticas

3. PIPELINES PRÉ-CONFIGURADOS:
   - Pipeline Padrão (9 estágios)
   - Pipeline SMB (6 estágios - mais ágil)
   - Pipeline Enterprise (10 estágios - mais complexo)
   - Pipeline Consultoria
   - Pipeline Renovação

4. RELATÓRIOS E ANÁLISES:
   - Funil de vendas detalhado
   - Taxa de conversão entre estágios
   - Performance por vendedor
   - Análise de tempo em cada estágio

5. FUNCIONALIDADES:
   - Campos obrigatórios por estágio
   - Limites de tempo por estágio
   - Ações automáticas
   - Rastreamento completo de histórico

Esta estrutura permite:
- Gestão avançada do funil de vendas
- Automação de processos comerciais
- Análises detalhadas de performance
- Customização por tipo de venda
- Previsibilidade de resultados
*/