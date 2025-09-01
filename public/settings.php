<?php
$pageTitle = 'Configurações - Sistema CRM';
$current_page = 'settings';
$page_scripts = ['assets/js/settings.js'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<?php include 'components/head.php'; ?>
<head>
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <!-- Main Content -->
    <div class="container-fluid main-content">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i>Configurações</h1>
                        <p class="text-muted mb-0">Gerencie as preferências e configurações do seu sistema CRM.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" id="resetSettings">
                            <i class="fas fa-undo me-1"></i>
                            Restaurar
                        </button>
                        <button type="button" class="btn btn-primary" id="saveAllSettings">
                            <i class="fas fa-save me-1"></i>
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 text-muted">Carregando configurações...</p>
        </div>

        <!-- Settings Content -->
        <div id="settingsContent" style="display: none;">
                    <div class="row">
                        <!-- Settings Navigation -->
                        <div class="col-lg-3 col-md-4">
                            <div class="settings-nav-card">
                                <div class="nav flex-column nav-pills" id="settingsNav" role="tablist">
                                    <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                                        <i class="fas fa-cog"></i>
                                        <span>Geral</span>
                                    </button>
                                    <button class="nav-link" id="company-tab" data-bs-toggle="pill" data-bs-target="#company" type="button" role="tab">
                                        <i class="fas fa-building"></i>
                                        <span>Empresa</span>
                                    </button>
                                    <button class="nav-link" id="appearance-tab" data-bs-toggle="pill" data-bs-target="#appearance" type="button" role="tab">
                                        <i class="fas fa-palette"></i>
                                        <span>Aparência</span>
                                    </button>
                                    <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" type="button" role="tab">
                                        <i class="fas fa-bell"></i>
                                        <span>Notificações</span>
                                    </button>
                                    <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Segurança</span>
                                    </button>
                                    <button class="nav-link" id="integrations-tab" data-bs-toggle="pill" data-bs-target="#integrations" type="button" role="tab">
                                        <i class="fas fa-plug"></i>
                                        <span>Integrações</span>
                                    </button>
                                    <button class="nav-link" id="backup-tab" data-bs-toggle="pill" data-bs-target="#backup" type="button" role="tab">
                                        <i class="fas fa-database"></i>
                                        <span>Backup</span>
                                    </button>
                                    <button class="nav-link" id="advanced-tab" data-bs-toggle="pill" data-bs-target="#advanced" type="button" role="tab">
                                        <i class="fas fa-tools"></i>
                                        <span>Avançado</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Content -->
                        <div class="col-lg-9 col-md-8">
                            <div class="tab-content" id="settingsTabContent">
                                <!-- General Settings -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Configurações Gerais</h3>
                                            <p>Configure as preferências básicas do sistema</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Idioma do Sistema</h5>
                                                    <p>Selecione o idioma padrão da interface</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="systemLanguage">
                                                        <option value="pt-BR" selected>Português (Brasil)</option>
                                                        <option value="en-US">English (US)</option>
                                                        <option value="es-ES">Español</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Fuso Horário</h5>
                                                    <p>Configure o fuso horário para timestamps precisos</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="timeZone">
                                                        <option value="America/Sao_Paulo" selected>São Paulo (GMT-3)</option>
                                                        <option value="America/New_York">Nova York (GMT-5)</option>
                                                        <option value="Europe/London">Londres (GMT+0)</option>
                                                        <option value="UTC">UTC</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Formato de Data</h5>
                                                    <p>Como as datas são exibidas no sistema</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="dateFormat">
                                                        <option value="DD/MM/YYYY" selected>DD/MM/AAAA</option>
                                                        <option value="MM/DD/YYYY">MM/DD/AAAA</option>
                                                        <option value="YYYY-MM-DD">AAAA-MM-DD</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Moeda Padrão</h5>
                                                    <p>Moeda utilizada para valores financeiros</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="currency">
                                                        <option value="BRL" selected>Real Brasileiro (R$)</option>
                                                        <option value="USD">Dólar Americano ($)</option>
                                                        <option value="EUR">Euro (€)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Company Settings -->
                                <div class="tab-pane fade" id="company" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Informações da Empresa</h3>
                                            <p>Configure os dados da sua empresa</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card full-width">
                                                <div class="setting-header">
                                                    <h5>Nome da Empresa</h5>
                                                    <p>Nome oficial da sua empresa</p>
                                                </div>
                                                <div class="setting-control">
                                                    <input type="text" class="form-control" id="companyName" placeholder="Digite o nome da empresa">
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>CNPJ</h5>
                                                    <p>Cadastro Nacional da Pessoa Jurídica</p>
                                                </div>
                                                <div class="setting-control">
                                                    <input type="text" class="form-control" id="companyCNPJ" placeholder="00.000.000/0000-00">
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Telefone</h5>
                                                    <p>Telefone principal da empresa</p>
                                                </div>
                                                <div class="setting-control">
                                                    <input type="text" class="form-control" id="companyPhone" placeholder="(11) 99999-9999">
                                                </div>
                                            </div>

                                            <div class="setting-card full-width">
                                                <div class="setting-header">
                                                    <h5>Endereço</h5>
                                                    <p>Endereço completo da empresa</p>
                                                </div>
                                                <div class="setting-control">
                                                    <textarea class="form-control" id="companyAddress" rows="3" placeholder="Digite o endereço completo"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appearance Settings -->
                                <div class="tab-pane fade" id="appearance" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Aparência</h3>
                                            <p>Personalize a aparência do sistema</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card full-width">
                                                <div class="setting-header">
                                                    <h5>Tema</h5>
                                                    <p>Escolha o tema da interface</p>
                                                </div>
                                                <div class="theme-selector">
                                                    <div class="theme-option active" data-theme="light">
                                                        <div class="theme-preview light-theme">
                                                            <div class="theme-colors">
                                                                <span class="color" style="background: #ffffff"></span>
                                                                <span class="color" style="background: #f8f9fa"></span>
                                                                <span class="color" style="background: #6c757d"></span>
                                                            </div>
                                                        </div>
                                                        <span class="theme-name">Claro</span>
                                                    </div>
                                                    <div class="theme-option" data-theme="dark">
                                                        <div class="theme-preview dark-theme">
                                                            <div class="theme-colors">
                                                                <span class="color" style="background: #212529"></span>
                                                                <span class="color" style="background: #343a40"></span>
                                                                <span class="color" style="background: #6c757d"></span>
                                                            </div>
                                                        </div>
                                                        <span class="theme-name">Escuro</span>
                                                    </div>
                                                    <div class="theme-option" data-theme="auto">
                                                        <div class="theme-preview auto-theme">
                                                            <div class="theme-colors">
                                                                <span class="color" style="background: linear-gradient(45deg, #ffffff 50%, #212529 50%)"></span>
                                                                <span class="color" style="background: linear-gradient(45deg, #f8f9fa 50%, #343a40 50%)"></span>
                                                                <span class="color" style="background: #6c757d"></span>
                                                            </div>
                                                        </div>
                                                        <span class="theme-name">Automático</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Cor Primária</h5>
                                                    <p>Cor principal da interface</p>
                                                </div>
                                                <div class="setting-control">
                                                    <input type="color" class="form-control form-control-color" id="primaryColor" value="#0d6efd">
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Densidade da Interface</h5>
                                                    <p>Espaçamento entre elementos</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="interfaceDensity">
                                                        <option value="compact">Compacta</option>
                                                        <option value="normal" selected>Normal</option>
                                                        <option value="comfortable">Confortável</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notifications Settings -->
                                <div class="tab-pane fade" id="notifications" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Notificações</h3>
                                            <p>Configure como e quando receber notificações</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Notificações por Email</h5>
                                                    <p>Receber notificações por email</p>
                                                </div>
                                                <div class="setting-control">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                                        <label class="form-check-label" for="emailNotifications">Ativado</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Notificações Push</h5>
                                                    <p>Notificações no navegador</p>
                                                </div>
                                                <div class="setting-control">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="pushNotifications">
                                                        <label class="form-check-label" for="pushNotifications">Ativado</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Novos Leads</h5>
                                                    <p>Notificar sobre novos leads</p>
                                                </div>
                                                <div class="setting-control">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="newLeadsNotifications" checked>
                                                        <label class="form-check-label" for="newLeadsNotifications">Ativado</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Compromissos</h5>
                                                    <p>Lembrete de compromissos</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="appointmentReminders">
                                                        <option value="none">Desativado</option>
                                                        <option value="15" selected>15 minutos antes</option>
                                                        <option value="30">30 minutos antes</option>
                                                        <option value="60">1 hora antes</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Settings -->
                                <div class="tab-pane fade" id="security" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Segurança</h3>
                                            <p>Configure as opções de segurança do sistema</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Autenticação de Dois Fatores</h5>
                                                    <p>Adicione uma camada extra de segurança</p>
                                                </div>
                                                <div class="setting-control">
                                                    <button class="btn btn-outline-primary" id="setup2FA">
                                                        <i class="fas fa-shield-alt me-2"></i>
                                                        Configurar 2FA
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Sessão Automática</h5>
                                                    <p>Tempo limite para logout automático</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="sessionTimeout">
                                                        <option value="30">30 minutos</option>
                                                        <option value="60" selected>1 hora</option>
                                                        <option value="120">2 horas</option>
                                                        <option value="480">8 horas</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Log de Atividades</h5>
                                                    <p>Registrar ações dos usuários</p>
                                                </div>
                                                <div class="setting-control">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="activityLog" checked>
                                                        <label class="form-check-label" for="activityLog">Ativado</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Alterar Senha</h5>
                                                    <p>Altere sua senha de acesso</p>
                                                </div>
                                                <div class="setting-control">
                                                    <button class="btn btn-outline-primary" id="changePassword">
                                                        <i class="fas fa-key me-2"></i>
                                                        Alterar Senha
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Integrations Settings -->
                                <div class="tab-pane fade" id="integrations" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Integrações</h3>
                                            <p>Conecte o CRM com outras ferramentas</p>
                                        </div>
                                        
                                        <div class="integrations-grid">
                                            <div class="integration-card">
                                                <div class="integration-icon">
                                                    <i class="fab fa-google" style="color: #4285f4;"></i>
                                                </div>
                                                <div class="integration-info">
                                                    <h5>Google Workspace</h5>
                                                    <p>Sincronize calendário e contatos</p>
                                                    <span class="badge bg-success">Conectado</span>
                                                </div>
                                                <div class="integration-actions">
                                                    <button class="btn btn-sm btn-outline-danger">Desconectar</button>
                                                </div>
                                            </div>

                                            <div class="integration-card">
                                                <div class="integration-icon">
                                                    <i class="fab fa-whatsapp" style="color: #25d366;"></i>
                                                </div>
                                                <div class="integration-info">
                                                    <h5>WhatsApp Business</h5>
                                                    <p>Envie mensagens automáticas</p>
                                                    <span class="badge bg-secondary">Desconectado</span>
                                                </div>
                                                <div class="integration-actions">
                                                    <button class="btn btn-sm btn-primary">Conectar</button>
                                                </div>
                                            </div>

                                            <div class="integration-card">
                                                <div class="integration-icon">
                                                    <i class="fas fa-envelope" style="color: #ea4335;"></i>
                                                </div>
                                                <div class="integration-info">
                                                    <h5>Email Marketing</h5>
                                                    <p>Campanhas de email automatizadas</p>
                                                    <span class="badge bg-secondary">Desconectado</span>
                                                </div>
                                                <div class="integration-actions">
                                                    <button class="btn btn-sm btn-primary">Conectar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Backup Settings -->
                                <div class="tab-pane fade" id="backup" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Backup e Restauração</h3>
                                            <p>Gerencie backups dos seus dados</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Backup Automático</h5>
                                                    <p>Backup automático dos dados</p>
                                                </div>
                                                <div class="setting-control">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="autoBackup" checked>
                                                        <label class="form-check-label" for="autoBackup">Ativado</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Frequência</h5>
                                                    <p>Com que frequência fazer backup</p>
                                                </div>
                                                <div class="setting-control">
                                                    <select class="form-select" id="backupFrequency">
                                                        <option value="daily" selected>Diário</option>
                                                        <option value="weekly">Semanal</option>
                                                        <option value="monthly">Mensal</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="setting-card full-width">
                                                <div class="setting-header">
                                                    <h5>Ações de Backup</h5>
                                                    <p>Criar ou restaurar backups manualmente</p>
                                                </div>
                                                <div class="backup-actions">
                                                    <button class="btn btn-primary me-2" id="createBackup">
                                                        <i class="fas fa-download me-2"></i>
                                                        Criar Backup
                                                    </button>
                                                    <button class="btn btn-outline-primary" id="restoreBackup">
                                                        <i class="fas fa-upload me-2"></i>
                                                        Restaurar Backup
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Settings -->
                                <div class="tab-pane fade" id="advanced" role="tabpanel">
                                    <div class="settings-section">
                                        <div class="section-header">
                                            <h3>Configurações Avançadas</h3>
                                            <p>Opções avançadas para usuários experientes</p>
                                        </div>
                                        
                                        <div class="settings-grid">
                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Modo Debug</h5>
                                                    <p>Ativar logs detalhados</p>
                                                </div>
                                                <div class="setting-control">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="debugMode">
                                                        <label class="form-check-label" for="debugMode">Ativado</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Cache do Sistema</h5>
                                                    <p>Limpar cache para melhor performance</p>
                                                </div>
                                                <div class="setting-control">
                                                    <button class="btn btn-outline-warning" id="clearCache">
                                                        <i class="fas fa-broom me-2"></i>
                                                        Limpar Cache
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="setting-card">
                                                <div class="setting-header">
                                                    <h5>Exportar Dados</h5>
                                                    <p>Exportar todos os dados do sistema</p>
                                                </div>
                                                <div class="setting-control">
                                                    <button class="btn btn-outline-info" id="exportData">
                                                        <i class="fas fa-file-export me-2"></i>
                                                        Exportar
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="setting-card danger-zone">
                                                <div class="setting-header">
                                                    <h5>Zona de Perigo</h5>
                                                    <p>Ações irreversíveis - use com cuidado</p>
                                                </div>
                                                <div class="setting-control">
                                                    <button class="btn btn-outline-danger" id="resetSystem">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        Resetar Sistema
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast" role="alert">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Sucesso</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="successMessage"></div>
        </div>
        
        <div id="errorToast" class="toast" role="alert">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">Erro</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="errorMessage"></div>
        </div>
    </div>

    <?php include 'components/scripts.php'; ?>
</body>
</html>