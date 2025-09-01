<?php
$page_title = 'Agendamentos - Sistema CRM';
$current_page = 'appointments';
$additional_css = ['assets/css/appointments.css'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/head.php'; ?>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container-fluid main-content">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="fas fa-calendar-alt me-3"></i>
                        Agendamentos
                    </h1>
                    <p class="page-subtitle">Gerencie seus compromissos e eventos</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" id="newAppointmentBtn">
                        <i class="fas fa-plus me-2"></i>
                        Novo Agendamento
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="appointments-nav mb-4">
            <ul class="nav nav-pills" id="appointmentsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="pill" data-bs-target="#list-view" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>
                        Lista de Agendamentos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="pill" data-bs-target="#calendar-view" type="button" role="tab">
                        <i class="fas fa-calendar me-2"></i>
                        Visualização do Calendário
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="today-tab" data-bs-toggle="pill" data-bs-target="#today-view" type="button" role="tab">
                        <i class="fas fa-clock me-2"></i>
                        Agenda de Hoje
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="appointmentsTabContent">
            <!-- Lista de Agendamentos -->
            <div class="tab-pane fade show active" id="list-view" role="tabpanel">
                <div class="row">
                    <!-- Filtros -->
                    <div class="col-lg-3 mb-4">
                        <div class="card filters-card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-filter me-2"></i>
                                    Filtros
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="statusFilter">
                                        <option value="">Todos os Status</option>
                                        <option value="scheduled">Agendado</option>
                                        <option value="confirmed">Confirmado</option>
                                        <option value="completed">Concluído</option>
                                        <option value="cancelled">Cancelado</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Data Inicial</label>
                                    <input type="date" class="form-control" id="startDate">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Data Final</label>
                                    <input type="date" class="form-control" id="endDate">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cliente</label>
                                    <input type="text" class="form-control" id="clientFilter" placeholder="Buscar cliente...">
                                </div>
                                <button class="btn btn-outline-secondary w-100" id="clearFilters">
                                    <i class="fas fa-times me-2"></i>
                                    Limpar Filtros
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Agendamentos -->
                    <div class="col-lg-9">
                        <div class="card appointments-list-card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-check me-2"></i>
                                            Lista de Agendamentos
                                        </h6>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="searchAppointments" placeholder="Buscar agendamentos...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Data & Hora</th>
                                                <th>Tipo</th>
                                                <th>Status</th>
                                                <th>Observações</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="appointmentsTableBody">
                                            <!-- Agendamentos serão carregados aqui -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="appointmentsPagination">
                                        <!-- Paginação será gerada aqui -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visualização do Calendário -->
            <div class="tab-pane fade" id="calendar-view" role="tabpanel">
                <div class="card calendar-card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>
                                    Calendário de Agendamentos
                                </h6>
                            </div>
                            <div class="col-auto">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary" id="prevMonth">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="currentMonth">
                                        <span id="currentMonthText">Carregando...</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="nextMonth">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="calendar-container">
                            <div class="calendar-grid" id="calendarGrid">
                                <!-- Calendário será gerado aqui -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agenda de Hoje -->
            <div class="tab-pane fade" id="today-view" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card today-appointments-card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Agendamentos de Hoje
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="todayAppointmentsList">
                                    <!-- Agendamentos de hoje serão carregados aqui -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card today-stats-card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Estatísticas do Dia
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="stat-item">
                                    <div class="stat-number" id="todayTotal">0</div>
                                    <div class="stat-label">Total de Agendamentos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="todayCompleted">0</div>
                                    <div class="stat-label">Concluídos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="todayPending">0</div>
                                    <div class="stat-label">Pendentes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Agendamento -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalTitle">Novo Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="appointmentForm">
                    <div class="modal-body">
                        <input type="hidden" id="appointmentId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clientSelect" class="form-label">Cliente *</label>
                                <select class="form-select" id="clientSelect" required>
                                    <option value="">Selecione o cliente</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appointmentType" class="form-label">Tipo *</label>
                                <select class="form-select" id="appointmentType" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="consultation">Consulta</option>
                                    <option value="meeting">Reunião</option>
                                    <option value="presentation">Apresentação</option>
                                    <option value="follow_up">Follow-up</option>
                                    <option value="other">Outro</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="appointmentDate" class="form-label">Data *</label>
                                <input type="date" class="form-control" id="appointmentDate" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="appointmentStartTime" class="form-label">Hora Início *</label>
                                <input type="time" class="form-control" id="appointmentStartTime" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="appointmentEndTime" class="form-label">Hora Fim *</label>
                                <input type="time" class="form-control" id="appointmentEndTime" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="appointmentStatus" class="form-label">Status</label>
                                <select class="form-select" id="appointmentStatus">
                                    <option value="scheduled">Agendado</option>
                                    <option value="confirmed">Confirmado</option>
                                    <option value="completed">Concluído</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appointmentValue" class="form-label">Valor (R$)</label>
                                <input type="number" class="form-control" id="appointmentValue" step="0.01" min="0" placeholder="0,00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentNotes" class="form-label">Observações</label>
                            <textarea class="form-control" id="appointmentNotes" rows="3" placeholder="Observações sobre o agendamento..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveAppointmentBtn">
                            <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
                            Salvar Agendamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/scripts.php'; ?>
    <script src="assets/js/appointments.js"></script>
</body>
</html>