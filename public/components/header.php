<?php
// Determinar a página atual para destacar no menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Header with Inline Styles -->
<style>
:root {
    --primary-color: #CA773B;
    --primary-dark: #B8662F;
    --primary-hover: #D4834A;
}

.navbar-custom {
    background: #282c3d;
    padding: 0.75rem 0;
    transition: all 0.3s ease;
    gap: 10px;
    display: flex;
}

.navbar-custom .navbar-brand {
    font-weight: 700;
    font-size: 1.25rem;
    color: #ffffff !important;
    transition: transform 0.3s ease;
}

.navbar-custom .navbar-brand:hover {
    transform: scale(1.05);
}

.navbar-custom .navbar-brand img {
    filter: brightness(1.1);
    transition: filter 0.3s ease;
}

.navbar-custom .navbar-nav .nav-link {
    color: #ecf0f1 !important;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    margin: 0 0.25rem;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-custom .navbar-nav .nav-link:hover {
    color: var(--primary-color) !important;
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

.navbar-custom .navbar-nav .nav-link.active {
    color: var(--primary-color) !important;
    background-color: rgba(202, 119, 59, 0.15);
    font-weight: 600;
}

.navbar-custom .navbar-nav .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -0.75rem;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 3px;
    background-color: var(--primary-color);
    border-radius: 2px;
}

.navbar-custom .dropdown-menu {
    background-color: #34495e;
    border: 1px solid rgba(202, 119, 59, 0.3);
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    margin-top: 0.5rem;
}

.navbar-custom .dropdown-item {
    color: #ecf0f1;
    padding: 0.75rem 1.25rem;
    transition: all 0.3s ease;
    border-radius: 6px;
    margin: 0.25rem;
}

.navbar-custom .dropdown-item:hover {
    background-color: var(--primary-color);
    color: #ffffff;
    transform: translateX(5px);
}

.navbar-custom .dropdown-item.active {
    background-color: rgba(202, 119, 59, 0.2);
    color: var(--primary-color);
}

.navbar-custom .dropdown-divider {
    border-color: rgba(202, 119, 59, 0.3);
}

.navbar-custom .navbar-toggler {
    border: 2px solid var(--primary-color);
    padding: 0.5rem;
}

.navbar-custom .navbar-toggler:focus {
    box-shadow: 0 0 0 0.2rem rgba(202, 119, 59, 0.25);
}

.navbar-custom .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23CA773B' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

@media (max-width: 991.98px) {
    .navbar-custom .navbar-nav {
        padding-top: 1rem;
    }
    
    .navbar-custom .navbar-nav .nav-link {
        margin: 0.25rem 0;
    }
}
</style>

<!-- Navigation Header -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="assets/images/logo-light.svg" alt="CloutHub" height="32" class="me-2">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['crm-leads.php', 'crm-clients.php', 'crm-pipeline.php', 'crm.php'])) ? 'active' : ''; ?>" 
                       href="#" id="crmDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-users me-1"></i>
                        CRM
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo ($current_page == 'crm-leads.php') ? 'active' : ''; ?>" href="crm-leads.php">Leads</a></li>
                        <li><a class="dropdown-item <?php echo ($current_page == 'crm-clients.php') ? 'active' : ''; ?>" href="crm-clients.php">Clientes</a></li>
                        <li><a class="dropdown-item <?php echo ($current_page == 'crm-pipeline.php') ? 'active' : ''; ?>" href="crm-pipeline.php">Pipeline</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['scheduling.php', 'appointments.php'])) ? 'active' : ''; ?>" 
                       href="#" id="schedulingDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Agendamento
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo ($current_page == 'scheduling.php') ? 'active' : ''; ?>" href="scheduling.php">Agenda</a></li>
                        <li><a class="dropdown-item <?php echo ($current_page == 'appointments.php') ? 'active' : ''; ?>" href="appointments.php">Compromissos</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'billing.php') ? 'active' : ''; ?>" href="billing.php">
                        <i class="fas fa-file-invoice-dollar me-1"></i>
                        Faturamento
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'contracts.php') ? 'active' : ''; ?>" href="contracts.php">
                        <i class="fas fa-file-contract me-1"></i>
                        Contratos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-1"></i>
                        Relatórios
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <span id="userName">Usuário</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user-edit me-2"></i>Perfil
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Configurações
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="logout()">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navbar -->
<div style="height: 80px;"></div>