<?php
$page_title = 'Dashboard - Sistema CRM';
$current_page = 'dashboard';
$include_chartjs = true;
$page_scripts = ['assets/js/dashboard.js'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/head.php'; ?>
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
                        <h1 class="h3 mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
                        <p class="text-muted mb-0">Bem-vindo de volta! Aqui está o que está acontecendo com seu negócio hoje.</p>
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
            <p class="mt-3 text-muted">Carregando dados do dashboard...</p>
        </div>

        <!-- Dashboard Content -->
        <div id="dashboardContent" style="display: none;">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total de Leads
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalLeads">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Leads Convertidos
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="convertedLeads">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Agendamentos Hoje
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="todayAppointments">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Tarefas Pendentes
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingTasks">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Lead Pipeline Chart -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Pipeline de Leads</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="leadPipelineChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lead Sources Chart -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Fontes de Leads</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="leadSourcesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Upcoming Appointments -->
            <div class="row">
                <!-- Recent Leads -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Leads Recentes</h6>
                        </div>
                        <div class="card-body">
                            <div id="recentLeadsList">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Próximos Agendamentos</h6>
                        </div>
                        <div class="card-body">
                            <div id="upcomingAppointmentsList">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="errorState" class="text-center py-5" style="display: none;">
            <div class="text-danger mb-3">
                <i class="fas fa-exclamation-triangle fa-3x"></i>
            </div>
            <h4>Erro ao Carregar Dashboard</h4>
            <p class="text-muted mb-3">Houve um erro ao carregar os dados do dashboard. Tente novamente.</p>
            <button class="btn btn-primary" onclick="loadDashboard()">
                <i class="fas fa-retry me-1"></i>
                Tentar Novamente
            </button>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/scripts.php'; ?>
</body>
</html>