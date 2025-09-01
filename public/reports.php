<?php
$pageTitle = 'Relatórios - CRM System';
$currentPage = 'reports';
$include_chartjs = true;
include 'components/head.php';
include 'components/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-chart-bar text-primary me-2"></i>
                Relatórios e Análises
            </h2>
            <p class="text-muted mb-0">Insights abrangentes de negócios e métricas de desempenho</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" id="exportPdfBtn">
                <i class="fas fa-file-pdf me-2"></i>
                Exportar PDF
            </button>
            <button class="btn btn-outline-success" id="exportExcelBtn">
                <i class="fas fa-file-excel me-2"></i>
                Exportar Excel
            </button>
            <button class="btn btn-primary" id="scheduleReportBtn">
                <i class="fas fa-clock me-2"></i>
                Agendar Relatório
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center" style="display: none;">
        <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" class="form-control" id="startDate">
                        <span>até</span>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de Relatório</label>
                    <select class="form-select" id="reportType">
                        <option value="overview">Visão Geral</option>
                        <option value="sales">Desempenho de Vendas</option>
                        <option value="clients">Análise de Clientes</option>
                        <option value="financial">Relatório Financeiro</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Agrupar Por</label>
                    <select class="form-select" id="groupBy">
                        <option value="day">Diário</option>
                        <option value="week">Semanal</option>
                        <option value="month" selected>Mensal</option>
                        <option value="quarter">Trimestral</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary me-2" id="generateReportBtn">
                        <i class="fas fa-chart-line me-1"></i>
                        Gerar Relatório
                    </button>
                    <button class="btn btn-outline-secondary" id="resetFiltersBtn">
                        <i class="fas fa-undo me-1"></i>
                        Limpar
                    </button>
                </div>
            </div>
            
            <!-- Quick Filters -->
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-outline-primary btn-sm active" data-period="7">Últimos 7 Dias</button>
                <button class="btn btn-outline-primary btn-sm" data-period="30">Últimos 30 Dias</button>
                <button class="btn btn-outline-primary btn-sm" data-period="90">Últimos 3 Meses</button>
                <button class="btn btn-outline-primary btn-sm" data-period="365">Último Ano</button>
                <button class="btn btn-outline-primary btn-sm" data-period="custom">Período Personalizado</button>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div id="reportsContent">
        <!-- Report Tabs -->
        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-chart-pie me-2"></i>
                    Visão Geral
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">
                    <i class="fas fa-chart-line me-2"></i>
                    Desempenho de Vendas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>
                    Análise de Clientes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                    <i class="fas fa-dollar-sign me-2"></i>
                    Relatório Financeiro
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="reportTabsContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <div class="h2 mb-2" id="totalRevenue">R$ 0</div>
                                <div class="mb-2">Receita Total</div>
                                <div class="small" id="revenueChange">+0%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <div class="h2 mb-2" id="totalDeals">0</div>
                                <div class="mb-2">Total de Negócios</div>
                                <div class="small" id="dealsChange">+0%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <div class="h2 mb-2" id="conversionRate">0%</div>
                                <div class="mb-2">Taxa de Conversão</div>
                                <div class="small" id="conversionChange">+0%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <div class="h2 mb-2" id="avgDealSize">R$ 0</div>
                                <div class="mb-2">Valor Médio por Negócio</div>
                                <div class="small" id="dealSizeChange">+0%</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Tendência de Receita</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 400px;">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Distribuição de Status</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 300px;">
                                    <canvas id="dealStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Performance Tab -->
            <div class="tab-pane fade" id="sales" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Pipeline de Vendas</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 300px;">
                                    <canvas id="pipelineChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Comparação Mensal de Vendas</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 300px;">
                                    <canvas id="salesComparisonChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Metrics -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Comparação de Métricas de Vendas</h5>
                    </div>
                    <div class="card-body" id="salesMetrics">
                        <!-- Metrics will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Client Analysis Tab -->
            <div class="tab-pane fade" id="clients" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Aquisição de Clientes</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 300px;">
                                    <canvas id="clientAcquisitionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top Clientes por Receita</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 300px;">
                                    <canvas id="topClientsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Client Table -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Relatório Detalhado de Clientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nome do Cliente</th>
                                        <th>Receita Total</th>
                                        <th>Negócios Ativos</th>
                                        <th>Última Atividade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsReportTable">
                                    <!-- Client data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financial Report Tab -->
            <div class="tab-pane fade" id="financial" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Receita vs Despesas</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 400px;">
                                    <canvas id="financialChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Margem de Lucro</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 300px;">
                                    <canvas id="profitMarginChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Summary -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Resumo Financeiro</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                                    <div>
                                        <div class="fw-bold text-muted">Receita Total</div>
                                        <div class="h5 text-success mb-0" id="financialRevenue">R$ 0</div>
                                    </div>
                                    <div class="badge bg-success">+12.5%</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                                    <div>
                                        <div class="fw-bold text-muted">Despesas Totais</div>
                                        <div class="h5 text-danger mb-0" id="financialExpenses">R$ 0</div>
                                    </div>
                                    <div class="badge bg-danger">+8.3%</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                                    <div>
                                        <div class="fw-bold text-muted">Lucro Líquido</div>
                                        <div class="h5 text-primary mb-0" id="financialProfit">R$ 0</div>
                                    </div>
                                    <div class="badge bg-success">+18.7%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'components/footer.php';
$page_scripts = ['assets/js/reports.js'];
include 'components/scripts.php';
?>