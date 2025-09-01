-- ============================================================================
-- DADOS DE EXEMPLO COMPLETOS PARA O SISTEMA CRM CLOUTHUB
-- ============================================================================
-- Este arquivo contém dados de exemplo mais robustos para popular o banco de dados
-- Execute após a criação das tabelas principais

USE crm_system;

-- ============================================================================
-- USUÁRIOS ADICIONAIS
-- ============================================================================

INSERT INTO users (name, email, password, role, phone, status) VALUES 
('Carlos Mendes', 'carlos@clouthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'salesperson', '(11) 91234-5678', 'active'),
('Ana Paula', 'ana@clouthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'salesperson', '(11) 92345-6789', 'active'),
('Roberto Silva', 'roberto@clouthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '(11) 93456-7890', 'active'),
('Fernanda Costa', 'fernanda@clouthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '(11) 94567-8901', 'active');

-- ============================================================================
-- LEADS ADICIONAIS (30 leads variados)
-- ============================================================================

INSERT INTO leads (title, first_name, last_name, email, phone, company, position, source, status, value, probability, expected_close_date, assigned_to, notes, city, state, country) VALUES 
-- Leads Novos
('Sr.', 'Alexandre', 'Rodrigues', 'alexandre@techstart.com', '(11) 95678-9012', 'TechStart Solutions', 'CEO', 'website', 'new', 45000.00, 20, '2025-03-15', 4, 'Interessado em automação de processos', 'São Paulo', 'SP', 'Brazil'),
('Sra.', 'Beatriz', 'Almeida', 'beatriz@inovacorp.com', '(11) 96789-0123', 'InovaCorp', 'Diretora de TI', 'referral', 'new', 32000.00, 15, '2025-02-28', 5, 'Precisa de sistema CRM personalizado', 'Rio de Janeiro', 'RJ', 'Brazil'),
('Dr.', 'Claudio', 'Ferreira', 'claudio@medtech.com', '(11) 97890-1234', 'MedTech Ltda', 'Diretor Médico', 'social_media', 'new', 28000.00, 25, '2025-04-10', 4, 'Setor de saúde, compliance rigoroso', 'Belo Horizonte', 'MG', 'Brazil'),
('Sra.', 'Daniela', 'Santos', 'daniela@educaplus.com', '(11) 98901-2345', 'EducaPlus', 'Coordenadora', 'email_campaign', 'new', 18000.00, 30, '2025-03-20', 5, 'Instituição de ensino, orçamento limitado', 'Curitiba', 'PR', 'Brazil'),
('Sr.', 'Eduardo', 'Lima', 'eduardo@construtora.com', '(11) 99012-3456', 'Construtora Lima', 'Sócio', 'cold_call', 'new', 55000.00, 10, '2025-05-01', 4, 'Construção civil, gestão de obras', 'Porto Alegre', 'RS', 'Brazil'),

-- Leads Contatados
('Sra.', 'Fabiana', 'Oliveira', 'fabiana@retailmax.com', '(21) 91234-5678', 'RetailMax', 'Gerente de Vendas', 'website', 'contacted', 38000.00, 40, '2025-03-05', 5, 'Varejo, múltiplas lojas', 'Rio de Janeiro', 'RJ', 'Brazil'),
('Sr.', 'Gabriel', 'Pereira', 'gabriel@logistica.com', '(11) 92345-6789', 'LogísticaBR', 'Diretor Operacional', 'referral', 'contacted', 42000.00, 35, '2025-02-25', 4, 'Logística e transporte', 'São Paulo', 'SP', 'Brazil'),
('Dra.', 'Helena', 'Martins', 'helena@consultoria.com', '(31) 93456-7890', 'Consultoria Estratégica', 'Sócia', 'event', 'contacted', 25000.00, 45, '2025-03-12', 5, 'Consultoria empresarial', 'Belo Horizonte', 'MG', 'Brazil'),
('Sr.', 'Igor', 'Souza', 'igor@agroneg.com', '(62) 94567-8901', 'AgroNegócios', 'Proprietário', 'social_media', 'contacted', 35000.00, 30, '2025-04-15', 4, 'Agronegócio, fazenda grande', 'Goiânia', 'GO', 'Brazil'),
('Sra.', 'Juliana', 'Costa', 'juliana@financeira.com', '(11) 95678-9012', 'Financeira Plus', 'Diretora', 'website', 'contacted', 48000.00, 50, '2025-02-20', 5, 'Serviços financeiros', 'São Paulo', 'SP', 'Brazil'),

-- Leads Qualificados
('Sr.', 'Kevin', 'Barbosa', 'kevin@startup.com', '(11) 96789-0123', 'StartupTech', 'Fundador', 'referral', 'qualified', 22000.00, 60, '2025-02-15', 4, 'Startup em crescimento', 'São Paulo', 'SP', 'Brazil'),
('Sra.', 'Larissa', 'Nunes', 'larissa@ecommerce.com', '(21) 97890-1234', 'E-commerce Brasil', 'CEO', 'email_campaign', 'qualified', 40000.00, 65, '2025-03-01', 5, 'E-commerce em expansão', 'Rio de Janeiro', 'RJ', 'Brazil'),
('Dr.', 'Marcos', 'Ribeiro', 'marcos@clinica.com', '(11) 98901-2345', 'Clínica Saúde+', 'Diretor', 'cold_call', 'qualified', 30000.00, 55, '2025-02-28', 4, 'Clínica médica, 5 unidades', 'São Paulo', 'SP', 'Brazil'),
('Sra.', 'Natália', 'Gomes', 'natalia@hotel.com', '(85) 99012-3456', 'Hotel Executivo', 'Gerente Geral', 'website', 'qualified', 26000.00, 70, '2025-03-10', 5, 'Rede hoteleira regional', 'Fortaleza', 'CE', 'Brazil'),
('Sr.', 'Otávio', 'Dias', 'otavio@industria.com', '(11) 90123-4567', 'Indústria Moderna', 'Diretor Industrial', 'event', 'qualified', 65000.00, 60, '2025-04-05', 4, 'Indústria metalúrgica', 'São Bernardo', 'SP', 'Brazil'),

-- Leads em Proposta
('Sra.', 'Patrícia', 'Moreira', 'patricia@advocacia.com', '(11) 91234-5678', 'Advocacia & Cia', 'Sócia', 'referral', 'proposal', 32000.00, 75, '2025-02-10', 5, 'Escritório de advocacia', 'São Paulo', 'SP', 'Brazil'),
('Sr.', 'Quintino', 'Araújo', 'quintino@imobiliaria.com', '(21) 92345-6789', 'Imobiliária Prime', 'Diretor', 'social_media', 'proposal', 38000.00, 80, '2025-02-18', 4, 'Imobiliária de alto padrão', 'Rio de Janeiro', 'RJ', 'Brazil'),
('Dra.', 'Renata', 'Silva', 'renata@laboratorio.com', '(31) 93456-7890', 'Laboratório Exames', 'Diretora Técnica', 'website', 'proposal', 28000.00, 70, '2025-02-22', 5, 'Laboratório de análises clínicas', 'Belo Horizonte', 'MG', 'Brazil'),

-- Leads em Negociação
('Sr.', 'Sérgio', 'Cardoso', 'sergio@transportes.com', '(11) 94567-8901', 'Transportes Rápidos', 'Proprietário', 'cold_call', 'negotiation', 45000.00, 85, '2025-02-08', 4, 'Empresa de transportes', 'São Paulo', 'SP', 'Brazil'),
('Sra.', 'Tatiana', 'Rocha', 'tatiana@farmacia.com', '(41) 95678-9012', 'Farmácia Saúde', 'Proprietária', 'referral', 'negotiation', 24000.00, 90, '2025-02-05', 5, 'Rede de farmácias', 'Curitiba', 'PR', 'Brazil');

-- ============================================================================
-- CLIENTES ADICIONAIS (15 clientes)
-- ============================================================================

INSERT INTO clients (name, email, phone, company, position, type, status, assigned_to, notes, address, city, state, zip_code, country, tax_id, website, industry, annual_revenue, employee_count) VALUES 
('TechCorp Solutions', 'contato@techcorp.com', '(11) 3333-4444', 'TechCorp Solutions Ltda', 'Empresa', 'company', 'active', 4, 'Cliente desde 2023, sempre pontual nos pagamentos', 'Av. Paulista, 1000 - Conj. 501', 'São Paulo', 'SP', '01310-100', 'Brazil', '12.345.678/0001-90', 'www.techcorp.com', 'Tecnologia', 2500000.00, 45),
('Marina Fernandes', 'marina@consultora.com', '(11) 4444-5555', 'Consultoria MF', 'Consultora Sênior', 'individual', 'active', 5, 'Consultora independente, projetos de médio porte', 'Rua Augusta, 2500 - Sala 12', 'São Paulo', 'SP', '01412-100', 'Brazil', '123.456.789-01', NULL, 'Consultoria', 500000.00, 1),
('Indústria Metalúrgica ABC', 'vendas@metalurgica.com', '(11) 5555-6666', 'Metalúrgica ABC S.A.', 'Empresa', 'company', 'active', 4, 'Grande cliente industrial, contratos anuais', 'Rod. Anchieta, Km 15', 'São Bernardo', 'SP', '09600-000', 'Brazil', '23.456.789/0001-12', 'www.metalurgicaabc.com', 'Metalurgia', 15000000.00, 200),
('Dr. Ricardo Medeiros', 'ricardo@clinicarm.com', '(21) 6666-7777', 'Clínica RM', 'Diretor Médico', 'company', 'active', 5, 'Clínica médica com 3 unidades no RJ', 'Av. Copacabana, 800', 'Rio de Janeiro', 'RJ', '22050-000', 'Brazil', '34.567.890/0001-23', 'www.clinicarm.com', 'Saúde', 3000000.00, 25),
('Escola Futuro Brilhante', 'diretoria@futurobrilhante.com', '(31) 7777-8888', 'Colégio Futuro Brilhante', 'Instituição', 'company', 'active', 4, 'Escola particular, 800 alunos', 'Rua das Flores, 123', 'Belo Horizonte', 'MG', '30112-000', 'Brazil', '45.678.901/0001-34', 'www.futurobrilhante.com', 'Educação', 1200000.00, 35),
('Loja Moda & Estilo', 'gerencia@modaestilo.com', '(85) 8888-9999', 'Moda & Estilo Ltda', 'Varejo', 'company', 'active', 5, 'Loja de roupas, 4 unidades', 'Av. Beira Mar, 456', 'Fortaleza', 'CE', '60165-121', 'Brazil', '56.789.012/0001-45', 'www.modaestilo.com', 'Varejo', 800000.00, 15),
('Carlos Entrepreneur', 'carlos@startup.tech', '(11) 9999-0000', 'StartupTech Inovação', 'CEO', 'company', 'prospect', 4, 'Startup em fase de crescimento', 'Rua Inovação, 789', 'São Paulo', 'SP', '04038-001', 'Brazil', '67.890.123/0001-56', 'www.startuptech.com', 'Tecnologia', 300000.00, 8),
('Restaurante Sabor Caseiro', 'contato@saborcaseiro.com', '(41) 1111-2222', 'Sabor Caseiro Ltda', 'Restaurante', 'company', 'active', 5, 'Restaurante familiar, delivery', 'Rua do Sabor, 321', 'Curitiba', 'PR', '80020-100', 'Brazil', '78.901.234/0001-67', NULL, 'Alimentação', 400000.00, 12),
('Advocacia Justiça & Direito', 'contato@justicadireito.com', '(61) 2222-3333', 'Justiça & Direito Advogados', 'Escritório', 'company', 'active', 4, 'Escritório de advocacia empresarial', 'SCS Quadra 1, Bloco A', 'Brasília', 'DF', '70300-500', 'Brazil', '89.012.345/0001-78', 'www.justicadireito.com', 'Jurídico', 1500000.00, 18),
('Farmácia Vida Saudável', 'admin@vidasaudavel.com', '(51) 3333-4444', 'Vida Saudável Farmácias', 'Rede', 'company', 'active', 5, 'Rede com 6 farmácias', 'Av. Independência, 654', 'Porto Alegre', 'RS', '90035-070', 'Brazil', '90.123.456/0001-89', NULL, 'Farmácia', 2200000.00, 28);

-- ============================================================================
-- AGENDAMENTOS ADICIONAIS (20 agendamentos)
-- ============================================================================

INSERT INTO appointments (title, description, start_datetime, end_datetime, location, type, status, client_id, lead_id, assigned_to, created_by, notes) VALUES 
-- Agendamentos desta semana
('Apresentação Comercial - TechCorp', 'Apresentar nova funcionalidade do sistema CRM', '2025-01-29 09:00:00', '2025-01-29 10:30:00', 'Escritório TechCorp', 'demo', 'confirmed', 1, NULL, 4, 1, 'Levar notebook e projetor'),
('Reunião de Follow-up - Marina', 'Acompanhar implementação do projeto', '2025-01-29 14:00:00', '2025-01-29 15:00:00', 'Online - Teams', 'follow_up', 'scheduled', 2, NULL, 5, 1, 'Verificar status das customizações'),
('Ligação - Lead Alexandre', 'Primeira abordagem comercial', '2025-01-30 10:00:00', '2025-01-30 10:30:00', 'Telefone', 'call', 'scheduled', NULL, 4, 4, 1, 'Preparar pitch sobre automação'),
('Demo Sistema - Metalúrgica', 'Demonstração do módulo industrial', '2025-01-30 15:00:00', '2025-01-30 16:30:00', 'Metalúrgica ABC', 'demo', 'confirmed', 3, NULL, 4, 1, 'Focar em relatórios de produção'),
('Consultoria - Dr. Ricardo', 'Consultoria sobre integração com sistema médico', '2025-01-31 11:00:00', '2025-01-31 12:00:00', 'Clínica RM', 'consultation', 'scheduled', 4, NULL, 5, 1, 'Verificar compatibilidade TISS'),

-- Próxima semana
('Treinamento - Escola Futuro', 'Treinamento da equipe administrativa', '2025-02-03 08:30:00', '2025-02-03 12:00:00', 'Escola Futuro Brilhante', 'meeting', 'scheduled', 5, NULL, 4, 1, 'Preparar material didático'),
('Negociação - Loja Moda', 'Negociar condições do contrato', '2025-02-03 16:00:00', '2025-02-03 17:00:00', 'Loja Moda & Estilo', 'meeting', 'scheduled', 6, NULL, 5, 1, 'Levar proposta atualizada'),
('Call - Lead Beatriz', 'Qualificar necessidades da InovaCorp', '2025-02-04 09:30:00', '2025-02-04 10:00:00', 'Telefone', 'call', 'scheduled', NULL, 5, 5, 1, 'Focar em personalização'),
('Visita Técnica - Startup', 'Avaliar infraestrutura atual', '2025-02-04 14:00:00', '2025-02-04 16:00:00', 'StartupTech', 'meeting', 'scheduled', 7, NULL, 4, 1, 'Avaliar integração com APIs'),
('Apresentação - Restaurante', 'Apresentar módulo de delivery', '2025-02-05 10:00:00', '2025-02-05 11:00:00', 'Restaurante Sabor Caseiro', 'demo', 'scheduled', 8, NULL, 5, 1, 'Demo do app mobile'),

-- Semana seguinte
('Reunião Estratégica - Advocacia', 'Definir roadmap de implementação', '2025-02-06 15:00:00', '2025-02-06 16:30:00', 'Advocacia Justiça & Direito', 'meeting', 'scheduled', 9, NULL, 4, 1, 'Discutir compliance LGPD'),
('Follow-up - Farmácia', 'Acompanhar uso do sistema', '2025-02-07 11:00:00', '2025-02-07 11:30:00', 'Online - Zoom', 'follow_up', 'scheduled', 10, NULL, 5, 1, 'Verificar relatórios de vendas'),
('Call - Lead Claudio', 'Apresentar compliance para área médica', '2025-02-10 14:00:00', '2025-02-10 14:45:00', 'Telefone', 'call', 'scheduled', NULL, 6, 4, 1, 'Enfatizar segurança de dados'),
('Demo - Lead Daniela', 'Demonstração para setor educacional', '2025-02-11 09:00:00', '2025-02-11 10:30:00', 'EducaPlus', 'demo', 'scheduled', NULL, 7, 5, 1, 'Mostrar módulo acadêmico'),
('Reunião - Lead Eduardo', 'Reunião presencial na construtora', '2025-02-12 16:00:00', '2025-02-12 17:30:00', 'Construtora Lima', 'meeting', 'scheduled', NULL, 8, 4, 1, 'Focar em gestão de projetos');

-- ============================================================================
-- INTERAÇÕES ADICIONAIS (30 interações)
-- ============================================================================

INSERT INTO interactions (client_id, lead_id, user_id, type, direction, subject, notes, interaction_date, duration, status) VALUES 
-- Interações com clientes
(1, NULL, 4, 'email', 'outbound', 'Envio de relatório mensal', 'Enviado relatório de uso do sistema no mês de janeiro. Cliente satisfeito com os resultados.', '2025-01-28 09:00:00', NULL, 'completed'),
(2, NULL, 5, 'call', 'inbound', 'Dúvida sobre nova funcionalidade', 'Cliente ligou para esclarecer dúvidas sobre o novo módulo de relatórios. Explicado passo a passo.', '2025-01-27 14:30:00', 20, 'completed'),
(3, NULL, 4, 'meeting', 'outbound', 'Visita técnica mensal', 'Visita de rotina para verificar funcionamento do sistema. Tudo operando normalmente.', '2025-01-26 10:00:00', 90, 'completed'),
(4, NULL, 5, 'email', 'outbound', 'Proposta de upgrade', 'Enviada proposta para upgrade do plano atual. Cliente vai avaliar com a diretoria.', '2025-01-25 16:00:00', NULL, 'completed'),
(5, NULL, 4, 'call', 'outbound', 'Agendamento de treinamento', 'Ligação para agendar treinamento da nova equipe. Marcado para próxima semana.', '2025-01-24 11:00:00', 15, 'completed'),

-- Interações com leads
(NULL, 4, 4, 'email', 'outbound', 'Envio de proposta comercial', 'Enviada proposta detalhada para TechStart Solutions. Aguardando retorno em 5 dias úteis.', '2025-01-28 15:00:00', NULL, 'completed'),
(NULL, 5, 5, 'call', 'outbound', 'Primeira abordagem', 'Ligação inicial para InovaCorp. Diretora interessada, agendada reunião para próxima semana.', '2025-01-27 10:30:00', 25, 'completed'),
(NULL, 6, 4, 'email', 'outbound', 'Material informativo', 'Enviado material sobre compliance na área médica. Lead demonstrou interesse específico em LGPD.', '2025-01-26 13:00:00', NULL, 'completed'),
(NULL, 7, 5, 'call', 'inbound', 'Solicitação de informações', 'Lead ligou solicitando informações sobre preços. Enviado material por email.', '2025-01-25 09:15:00', 18, 'completed'),
(NULL, 8, 4, 'meeting', 'outbound', 'Visita comercial', 'Primeira visita à Construtora Lima. Apresentado overview da solução. Interesse confirmado.', '2025-01-24 14:00:00', 120, 'completed'),

-- Mais interações variadas
(6, NULL, 5, 'sms', 'outbound', 'Lembrete de pagamento', 'SMS enviado lembrando vencimento da fatura. Cliente confirmou pagamento para hoje.', '2025-01-23 08:00:00', NULL, 'completed'),
(NULL, 9, 5, 'email', 'outbound', 'Follow-up pós-demo', 'Email de follow-up após demonstração. Lead solicitou proposta personalizada.', '2025-01-22 17:00:00', NULL, 'completed'),
(7, NULL, 4, 'call', 'inbound', 'Suporte técnico', 'Startup ligou com dúvida sobre integração. Problema resolvido remotamente.', '2025-01-21 15:30:00', 35, 'completed'),
(NULL, 10, 5, 'meeting', 'outbound', 'Apresentação comercial', 'Reunião na RetailMax para apresentar solução para varejo. Muito interesse demonstrado.', '2025-01-20 11:00:00', 90, 'completed'),
(8, NULL, 4, 'email', 'outbound', 'Newsletter mensal', 'Enviado newsletter com dicas de uso e novidades do sistema.', '2025-01-19 12:00:00', NULL, 'completed');

-- ============================================================================
-- CONTRATOS ADICIONAIS (8 contratos)
-- ============================================================================

INSERT INTO contracts (contract_number, title, description, client_id, type, status, value, currency, start_date, end_date, created_by, assigned_to, notes) VALUES 
('CONT-2025-001', 'Licença Anual CRM - TechCorp', 'Contrato de licenciamento anual do sistema CRM com suporte técnico incluído', 1, 'service', 'active', 48000.00, 'BRL', '2025-01-01', '2025-12-31', 1, 4, 'Renovação automática, desconto de 10% aplicado'),
('CONT-2025-002', 'Consultoria CRM - Marina Fernandes', 'Serviços de consultoria e customização do sistema CRM', 2, 'consulting', 'signed', 15000.00, 'BRL', '2025-01-15', '2025-04-15', 1, 5, 'Projeto de 3 meses, pagamento em 3x'),
('CONT-2025-003', 'Sistema Industrial - Metalúrgica ABC', 'Implementação de sistema CRM para indústria metalúrgica', 3, 'service', 'active', 85000.00, 'BRL', '2025-02-01', '2026-01-31', 1, 4, 'Inclui módulos especiais para produção'),
('CONT-2025-004', 'CRM Médico - Clínica RM', 'Sistema CRM especializado para clínicas médicas', 4, 'service', 'signed', 36000.00, 'BRL', '2025-01-20', '2026-01-19', 1, 5, 'Compliance LGPD e integração TISS'),
('CONT-2025-005', 'Sistema Educacional - Escola Futuro', 'CRM para gestão educacional e relacionamento com pais', 5, 'service', 'draft', 24000.00, 'BRL', '2025-02-15', '2026-02-14', 1, 4, 'Aguardando aprovação da diretoria'),
('CONT-2025-006', 'CRM Varejo - Loja Moda & Estilo', 'Sistema para gestão de clientes e vendas no varejo', 6, 'service', 'sent', 28000.00, 'BRL', '2025-02-01', '2026-01-31', 1, 5, 'Proposta enviada, aguardando assinatura'),
('CONT-2025-007', 'Startup Package - StartupTech', 'Pacote especial para startups com desconto progressivo', 7, 'service', 'draft', 12000.00, 'BRL', '2025-03-01', '2026-02-28', 1, 4, 'Desconto de 50% no primeiro ano'),
('CONT-2025-008', 'CRM Delivery - Restaurante Sabor Caseiro', 'Sistema integrado para restaurante com delivery', 8, 'service', 'draft', 18000.00, 'BRL', '2025-02-10', '2026-02-09', 1, 5, 'Inclui app mobile para delivery');

-- ============================================================================
-- COBRANÇAS E PAGAMENTOS ADICIONAIS
-- ============================================================================

INSERT INTO charges (invoice_number, client_id, contract_id, description, amount, total_amount, due_date, issue_date, status, created_by) VALUES 
('INV-2025-001', 1, 1, 'Licença CRM - Janeiro 2025', 4000.00, 4000.00, '2025-02-05', '2025-01-05', 'paid', 1),
('INV-2025-002', 2, 2, 'Consultoria CRM - 1ª Parcela', 5000.00, 5000.00, '2025-01-30', '2025-01-15', 'paid', 1),
('INV-2025-003', 3, 3, 'Sistema Industrial - Setup', 25000.00, 25000.00, '2025-02-15', '2025-02-01', 'sent', 1),
('INV-2025-004', 4, 4, 'CRM Médico - 1ª Parcela', 12000.00, 12000.00, '2025-02-10', '2025-01-20', 'sent', 1),
('INV-2025-005', 1, 1, 'Licença CRM - Fevereiro 2025', 4000.00, 4000.00, '2025-03-05', '2025-02-05', 'draft', 1);

INSERT INTO payments (charge_id, amount, method, status, transaction_id, processed_at) VALUES 
(1, 4000.00, 'pix', 'completed', 'PIX123456789', '2025-02-03 10:31:00'),
(2, 5000.00, 'credit_card', 'completed', 'TED987654321', '2025-01-29 14:25:00');

-- ============================================================================
-- ATUALIZAR ESTATÍSTICAS
-- ============================================================================

-- Atualizar status de cobranças pagas
UPDATE charges SET status = 'paid', paid_at = '2025-02-03 10:31:00' WHERE id = 1;
UPDATE charges SET status = 'paid', paid_at = '2025-01-29 14:25:00' WHERE id = 2;

-- ============================================================================
-- COMENTÁRIOS FINAIS
-- ============================================================================
/*
Este arquivo adiciona:
- 4 usuários adicionais (vendedores, gerente, usuário)
- 20 leads em diferentes estágios do funil
- 10 clientes ativos com informações completas
- 15 agendamentos distribuídos nas próximas semanas
- 15 interações variadas (calls, emails, meetings)
- 8 contratos em diferentes status
- 5 cobranças e 2 pagamentos

Todos os dados são realistas e representam um CRM em funcionamento
com atividade comercial ativa e diversificada.
*/