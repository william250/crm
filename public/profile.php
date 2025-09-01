<?php
$page_title = 'Perfil - Sistema CRM';
$current_page = 'profile';
$page_scripts = ['assets/js/profile.js'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/head.php'; ?>
    <link rel="stylesheet" href="assets/css/profile.css">
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
                        <h1 class="h3 mb-0"><i class="fas fa-user me-2"></i>Meu Perfil</h1>
                        <p class="text-muted mb-0">Gerencie suas informações pessoais e configurações de conta.</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" id="refreshBtn">
                            <i class="fas fa-sync-alt me-1"></i>
                            Atualizar
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
            <p class="mt-3 text-muted">Carregando informações do perfil...</p>
        </div>

        <!-- Profile Content -->
        <div id="profileContent" style="display: none;">
            <div class="row">
                <!-- Profile Card -->
                <div class="col-lg-4 mb-4">
                    <div class="card profile-card">
                        <div class="card-body text-center">
                            <div class="profile-avatar mb-3">
                                <img id="avatarImage" src="" alt="Avatar" class="rounded-circle" style="display: none;">
                                <div id="avatarPlaceholder" class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="avatar-upload" onclick="triggerAvatarUpload()">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                            </div>
                            <h4 id="profileName" class="mb-1">Carregando...</h4>
                            <p id="profileRole" class="text-muted mb-3">Carregando...</p>
                            
                            <!-- Profile Stats -->
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h5 id="statDeals" class="mb-0">0</h5>
                                        <small class="text-muted">Negócios</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h5 id="statRevenue" class="mb-0">R$ 0</h5>
                                        <small class="text-muted">Receita</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h5 id="statClients" class="mb-0">0</h5>
                                        <small class="text-muted">Clientes</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Ações Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="changePassword()">
                                    <i class="fas fa-key me-2"></i>
                                    Alterar Senha
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="downloadData()">
                                    <i class="fas fa-download me-2"></i>
                                    Baixar Dados
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteAccount()">
                                    <i class="fas fa-trash me-2"></i>
                                    Excluir Conta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Details -->
                <div class="col-lg-8">
                    <!-- Profile Tabs -->
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                                        <i class="fas fa-user me-2"></i>
                                        Informações Pessoais
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        Segurança
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                        <i class="fas fa-bell me-2"></i>
                                        Notificações
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                                        <i class="fas fa-history me-2"></i>
                                        Atividade
                                    </button>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card-body">
                            <div class="tab-content" id="profileTabsContent">
                                <!-- Personal Info Tab -->
                                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                    <form id="personalInfoForm">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="firstName" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Sobrenome</label>
                                                <input type="text" class="form-control" id="lastName" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Telefone</label>
                                                <input type="tel" class="form-control" id="phone">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Cargo</label>
                                                <input type="text" class="form-control" id="jobTitle">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Departamento</label>
                                                <select class="form-control" id="department">
                                                    <option value="">Selecionar Departamento</option>
                                                    <option value="sales">Vendas</option>
                                                    <option value="marketing">Marketing</option>
                                                    <option value="support">Suporte</option>
                                                    <option value="management">Gerência</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Biografia</label>
                                            <textarea class="form-control" id="bio" rows="4" placeholder="Conte-nos sobre você..."></textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Endereço</label>
                                            <input type="text" class="form-control" id="address">
                                        </div>
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-4">
                                                <label class="form-label">Cidade</label>
                                                <input type="text" class="form-control" id="city">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Estado</label>
                                                <input type="text" class="form-control" id="state">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">CEP</label>
                                                <input type="text" class="form-control" id="zipCode">
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>
                                                Salvar Alterações
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="resetPersonalForm()">
                                                <i class="fas fa-undo me-2"></i>
                                                Resetar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Security Tab -->
                                <div class="tab-pane fade" id="security" role="tabpanel">
                                    <h6 class="mb-3">Alterar Senha</h6>
                                    <form id="passwordForm">
                                        <div class="mb-3">
                                            <label class="form-label">Senha Atual</label>
                                            <input type="password" class="form-control" id="currentPassword" required>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nova Senha</label>
                                                <input type="password" class="form-control" id="newPassword" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Confirmar Nova Senha</label>
                                                <input type="password" class="form-control" id="confirmPassword" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i>
                                            Atualizar Senha
                                        </button>
                                    </form>
                                    
                                    <hr class="my-4">
                                    
                                    <h6 class="mb-3">Configurações de Segurança</h6>
                                    <div id="securitySettings">
                                        <div class="security-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Autenticação de Dois Fatores</h6>
                                                    <small class="text-muted">Adicione uma camada extra de segurança à sua conta</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="security-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Notificações de Login</h6>
                                                    <small class="text-muted">Receba alertas quando alguém fizer login na sua conta</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="loginNotifications" checked>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notifications Tab -->
                                <div class="tab-pane fade" id="notifications" role="tabpanel">
                                    <h6 class="mb-3">Notificações por Email</h6>
                                    <div id="emailNotifications">
                                        <div class="notification-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Novos Leads</h6>
                                                    <small class="text-muted">Receba notificações quando novos leads forem adicionados</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="newLeadsEmail" checked>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Negócios Fechados</h6>
                                                    <small class="text-muted">Receba notificações quando negócios forem fechados</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="dealsClosedEmail" checked>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Relatórios Semanais</h6>
                                                    <small class="text-muted">Receba um resumo semanal das suas atividades</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="weeklyReportsEmail">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h6 class="mb-3">Notificações Push</h6>
                                    <div id="pushNotifications">
                                        <div class="notification-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Lembretes de Tarefas</h6>
                                                    <small class="text-muted">Receba lembretes sobre tarefas pendentes</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="taskReminders" checked>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Compromissos</h6>
                                                    <small class="text-muted">Receba lembretes sobre compromissos agendados</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="appointmentReminders" checked>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button class="btn btn-primary" onclick="saveNotificationSettings()">
                                            <i class="fas fa-save me-2"></i>
                                            Salvar Configurações
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Activity Tab -->
                                <div class="tab-pane fade" id="activity" role="tabpanel">
                                    <h6 class="mb-3">Atividade Recente</h6>
                                    <div id="activityList">
                                        <div class="activity-item">
                                            <div class="activity-icon login">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title">Login realizado</div>
                                                <div class="activity-time">Há 2 horas</div>
                                            </div>
                                        </div>
                                        
                                        <div class="activity-item">
                                            <div class="activity-icon update">
                                                <i class="fas fa-edit"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title">Perfil atualizado</div>
                                                <div class="activity-time">Ontem</div>
                                            </div>
                                        </div>
                                        
                                        <div class="activity-item">
                                            <div class="activity-icon security">
                                                <i class="fas fa-shield-alt"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title">Senha alterada</div>
                                                <div class="activity-time">Há 3 dias</div>
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

    <?php include 'components/footer.php'; ?>
    <?php include 'components/scripts.php'; ?>
</body>
</html>