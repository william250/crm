-- ============================================================================
-- CRIAR TABELAS FALTANTES: pipeline_history e automation_rules
-- ============================================================================

-- Tabela para histórico de mudanças de pipeline
CREATE TABLE pipeline_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    pipeline_id INT NOT NULL,
    from_stage_id INT,
    to_stage_id INT NOT NULL,
    move_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    moved_by INT NOT NULL,
    reason VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id) ON DELETE CASCADE,
    FOREIGN KEY (from_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (to_stage_id) REFERENCES pipeline_stages(id) ON DELETE CASCADE,
    FOREIGN KEY (moved_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pipeline_history_lead_id (lead_id),
    INDEX idx_pipeline_history_pipeline_id (pipeline_id),
    INDEX idx_pipeline_history_move_date (move_date)
);

-- Tabela para regras de automação
CREATE TABLE automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    pipeline_id INT,
    trigger_event ENUM('stage_change', 'time_based', 'field_update', 'lead_created', 'lead_updated') NOT NULL,
    trigger_conditions JSON COMMENT 'Condições em formato JSON',
    actions JSON COMMENT 'Ações a serem executadas em formato JSON',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pipeline_id) REFERENCES sales_pipelines(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_automation_rules_pipeline_id (pipeline_id),
    INDEX idx_automation_rules_trigger_event (trigger_event),
    INDEX idx_automation_rules_is_active (is_active)
);

-- Dados de exemplo para automation_rules
INSERT INTO automation_rules (name, description, pipeline_id, trigger_event, trigger_conditions, actions, is_active, created_by) VALUES
('Notificar Lead Qualificado', 'Enviar email quando lead passa para qualificado', 1, 'stage_change', '{"to_stage": "qualified"}', '{"send_email": {"template": "lead_qualified", "to": "manager@empresa.com"}}', TRUE, 1),
('Follow-up Automático', 'Criar tarefa de follow-up após 3 dias sem atividade', 1, 'time_based', '{"days_inactive": 3}', '{"create_task": {"title": "Follow-up necessário", "priority": "medium"}}', TRUE, 1),
('Lead Perdido - Pesquisa', 'Enviar pesquisa de satisfação quando lead é perdido', 1, 'stage_change', '{"to_stage": "lost"}', '{"send_survey": {"template": "why_lost", "delay_hours": 24}}', TRUE, 1);

-- Dados de exemplo para pipeline_history
INSERT INTO pipeline_history (lead_id, pipeline_id, from_stage_id, to_stage_id, move_date, moved_by, reason, notes) VALUES
(1, 1, 1, 2, '2025-01-20 10:00:00', 4, 'Interesse confirmado', 'Lead respondeu positivamente ao primeiro contato'),
(1, 1, 2, 3, '2025-01-22 14:30:00', 4, 'Reunião realizada', 'Apresentação do produto foi bem recebida'),
(2, 1, 1, 2, '2025-01-21 09:15:00', 5, 'Qualificação inicial', 'Lead tem budget e autoridade para decisão'),
(3, 1, 1, 6, '2025-01-19 16:45:00', 4, 'Não há interesse', 'Empresa já possui solução similar'),
(4, 1, 1, 2, '2025-01-23 11:20:00', 5, 'Interesse demonstrado', 'Solicitou mais informações técnicas'),
(5, 1, 1, 2, '2025-01-24 15:10:00', 4, 'Primeira qualificação', 'Empresa em fase de crescimento, bom fit');