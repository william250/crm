<?php

// Script para criar dados de exemplo para relatórios e dashboards

$host = 'localhost';
$dbname = 'crm_system';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado ao banco de dados\n\n";
    
    // Criar dados de atividades para relatórios
    echo "=== CRIANDO DADOS DE ATIVIDADES ===\n";
    
    // Inserir mais interações para relatórios
    $interactions = [
        [1, 1, 'call', 'Ligação de follow-up', 'Ligação realizada para acompanhar interesse', '2024-01-15 09:30:00'],
        [2, 2, 'email', 'Email de proposta', 'Enviada proposta comercial detalhada', '2024-01-16 14:20:00'],
        [3, 3, 'meeting', 'Reunião de apresentação', 'Apresentação do sistema CRM', '2024-01-17 10:00:00'],
        [4, 4, 'call', 'Ligação de qualificação', 'Qualificação do lead', '2024-01-18 11:15:00'],
        [5, 5, 'email', 'Email informativo', 'Informações sobre funcionalidades', '2024-01-19 16:45:00'],
        [6, 6, 'meeting', 'Demo do produto', 'Demonstração prática do CRM', '2024-01-20 15:30:00'],
        [7, 7, 'call', 'Negociação de preços', 'Discussão sobre valores e condições', '2024-01-21 13:20:00'],
        [8, 8, 'email', 'Contrato enviado', 'Envio do contrato para assinatura', '2024-01-22 09:10:00'],
        [1, 9, 'call', 'Confirmação de interesse', 'Cliente confirmou interesse', '2024-01-23 10:45:00'],
        [2, 10, 'meeting', 'Reunião de fechamento', 'Reunião para fechar negócio', '2024-01-24 14:00:00']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO interactions (user_id, lead_id, type, subject, notes, interaction_date) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($interactions as $interaction) {
        try {
            $stmt->execute($interaction);
            echo "✓ Interação '{$interaction[3]}' inserida\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir interação '{$interaction[3]}': " . $e->getMessage() . "\n";
        }
    }
    
    // Criar dados de vendas mensais para dashboard
    echo "\n=== CRIANDO DADOS DE VENDAS MENSAIS ===\n";
    
    // Atualizar alguns contratos com datas variadas para relatórios
    $contract_updates = [
        [1, '2024-01-15', 15000.00, 'signed'],
        [2, '2024-01-20', 25000.00, 'signed'],
        [3, '2024-02-05', 45000.00, 'signed'],
        [4, '2024-02-12', 8000.00, 'signed'],
        [5, '2024-02-18', 12000.00, 'signed'],
        [6, '2024-03-01', 35000.00, 'signed']
    ];
    
    $stmt = $pdo->prepare("UPDATE contracts SET signed_at = ?, value = ?, status = ? WHERE id = ?");
    
    foreach ($contract_updates as $update) {
        try {
            $stmt->execute([$update[1], $update[2], $update[3], $update[0]]);
            echo "✓ Contrato {$update[0]} atualizado - Valor: R$ {$update[2]}\n";
        } catch (PDOException $e) {
            echo "Erro ao atualizar contrato {$update[0]}: " . $e->getMessage() . "\n";
        }
    }
    
    // Atualizar leads com diferentes status para relatórios de funil
    echo "\n=== ATUALIZANDO STATUS DOS LEADS ===\n";
    
    $lead_updates = [
        [1, 'new'], // Novo Lead
        [2, 'contacted'], // Contatado
        [3, 'qualified'], // Qualificado
        [4, 'proposal'], // Proposta
        [5, 'won'], // Ganho
        [6, 'lost'], // Perdido
        [7, 'contacted'], // Contatado
        [8, 'qualified'], // Qualificado
        [9, 'proposal'], // Proposta
        [10, 'won'], // Ganho
        [11, 'new'], // Novo Lead
        [12, 'contacted'], // Contatado
        [13, 'qualified'], // Qualificado
        [14, 'proposal']  // Proposta
    ];
    
    $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
    
    foreach ($lead_updates as $update) {
        try {
            $stmt->execute([$update[1], $update[0]]);
            echo "✓ Lead {$update[0]} atualizado para status {$update[1]}\n";
        } catch (PDOException $e) {
            echo "Erro ao atualizar lead {$update[0]}: " . $e->getMessage() . "\n";
        }
    }
    
    // Criar histórico de pipeline para relatórios
    echo "\n=== CRIANDO HISTÓRICO DE PIPELINE ===\n";
    
    $pipeline_history = [
        [1, 1, 1, 2, '2024-01-10 08:00:00', 1, 'Lead movido para qualificação', 'Lead demonstrou interesse'],
        [2, 1, 1, 3, '2024-01-11 09:15:00', 1, 'Lead movido para proposta', 'Cliente solicitou proposta'],
        [3, 1, 2, 4, '2024-01-13 11:45:00', 1, 'Lead movido para negociação', 'Iniciando negociação de preços'],
        [4, 1, 3, 5, '2024-01-15 07:50:00', 1, 'Lead fechado como ganho', 'Cliente aceitou proposta'],
        [5, 1, 1, 2, '2024-01-16 16:10:00', 1, 'Lead qualificado', 'Lead passou na qualificação']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO pipeline_history (lead_id, pipeline_id, from_stage_id, to_stage_id, move_date, moved_by, reason, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($pipeline_history as $history) {
        try {
            $stmt->execute($history);
            echo "✓ Histórico criado para lead {$history[0]}\n";
        } catch (PDOException $e) {
            echo "Erro ao criar histórico: " . $e->getMessage() . "\n";
        }
    }
    
    // Verificação final
    echo "\n=== VERIFICAÇÃO FINAL ===\n";
    
    $tables = ['interactions', 'contracts', 'leads', 'pipeline_history'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "Tabela $table: $count registros\n";
    }
    
    // Estatísticas para dashboard
    echo "\n=== ESTATÍSTICAS PARA DASHBOARD ===\n";
    
    // Total de vendas por mês
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(signed_at, '%Y-%m') as mes,
            COUNT(*) as total_vendas,
            SUM(value) as valor_total
        FROM contracts 
        WHERE status = 'signed' AND signed_at IS NOT NULL
        GROUP BY DATE_FORMAT(signed_at, '%Y-%m')
        ORDER BY mes
    ");
    
    echo "Vendas por mês:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['mes']}: {$row['total_vendas']} vendas, R$ " . number_format($row['valor_total'], 2, ',', '.') . "\n";
    }
    
    // Leads por status
    $stmt = $pdo->query("
        SELECT 
            status as status_lead,
            COUNT(id) as total_leads
        FROM leads
        GROUP BY status
        ORDER BY 
            CASE status 
                WHEN 'new' THEN 1
                WHEN 'contacted' THEN 2
                WHEN 'qualified' THEN 3
                WHEN 'proposal' THEN 4
                WHEN 'won' THEN 5
                WHEN 'lost' THEN 6
            END
    ");
    
    echo "\nLeads por status:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['status_lead']}: {$row['total_leads']} leads\n";
    }
    
    echo "\n✅ Dados de relatórios criados com sucesso!\n";
    
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}

?>