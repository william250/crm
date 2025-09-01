<?php
$page_title = 'Agendamentos - Sistema CRM';
$current_page = 'scheduling';
$page_scripts = ['assets/js/scheduling.js'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/head.php'; ?>
    <style>
        .calendar-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .calendar-grid {
            width: 100%;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .calendar-header, .calendar-week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .day-header {
            width: 100%;
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
        }
        
        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 0.5rem;
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .calendar-day:hover {
            background: #f8f9fa;
        }
        
        .calendar-day.other-month {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
        
        .day-number {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .appointment-item {
            background: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .appointment-item.confirmed {
            background: #28a745;
        }
        
        .appointment-item.completed {
            background: #6c757d;
        }
        
        .appointment-item.cancelled {
            background: #dc3545;
        }
        
        .view-toggle {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .appointments-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-scheduled {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-confirmed {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-completed {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <!-- Alert Container -->
        <div id="alertContainer"></div>
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">Agendamentos</h1>
                        <p class="text-muted mb-0">Gerencie compromissos e eventos do calendário</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal">
                            <i class="fas fa-plus me-2"></i>Novo Agendamento
                        </button>
                        <button class="btn btn-outline-secondary ms-2" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="row">
            <div class="col-12">
                <div class="view-toggle">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="viewType" id="monthView" value="month" checked>
                            <label class="btn btn-outline-primary" for="monthView">
                                <i class="fas fa-calendar me-1"></i>Mês
                            </label>
                            
                            <input type="radio" class="btn-check" name="viewType" id="listView" value="list">
                            <label class="btn btn-outline-primary" for="listView">
                                <i class="fas fa-list me-1"></i>Lista
                            </label>
                        </div>
                        
                        <div class="calendar-navigation">
                            <button class="btn btn-outline-secondary" id="prevBtn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="current-period mx-3" id="currentPeriod">Carregando...</span>
                            <button class="btn btn-outline-secondary" id="nextBtn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button class="btn btn-outline-primary ms-2" id="todayBtn">Hoje</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 text-muted">Carregando agendamentos...</p>
        </div>

        <!-- Calendar Container -->
        <div class="row">
            <div class="col-12">
                <!-- Month View -->
                <div id="monthViewContainer" class="calendar-view">
                    <div class="calendar-container">
                        <div class="calendar-grid">
                            <div id="calendarHeader" class="calendar-header">
                                <div class="day-header">Dom</div>
                                <div class="day-header">Seg</div>
                                <div class="day-header">Ter</div>
                                <div class="day-header">Qua</div>
                                <div class="day-header">Qui</div>
                                <div class="day-header">Sex</div>
                                <div class="day-header">Sáb</div>
                            </div>
                            <div id="calendarBody" class="calendar-body">
                                <!-- Calendar days will be generated here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- List View -->
                <div id="listViewContainer" class="calendar-view" style="display: none;">
                    <div class="appointments-list">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data & Hora</th>
                                        <th>Cliente</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Valor</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <!-- Appointments will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="p-3 border-top">
                            <nav>
                                <ul class="pagination mb-0" id="appointmentsPagination">
                                    <!-- Pagination will be generated here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Modal -->
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
                                <label for="appointmentClient" class="form-label">Cliente *</label>
                                <select class="form-select" id="appointmentClient" required>
                                    <option value="">Selecione o Cliente</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appointmentType" class="form-label">Tipo *</label>
                                <select class="form-select" id="appointmentType" required>
                                    <option value="">Selecione o Tipo</option>
                                    <option value="consultation">Consulta</option>
                                    <option value="meeting">Reunião</option>
                                    <option value="presentation">Apresentação</option>
                                    <option value="follow_up">Follow Up</option>
                                    <option value="other">Outro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="appointmentDate" class="form-label">Data *</label>
                                <input type="date" class="form-control" id="appointmentDate" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="appointmentStartTime" class="form-label">Hora Início *</label>
                                <input type="time" class="form-control" id="appointmentStartTime" required>
                            </div>
                            <div class="col-md-3 mb-3">
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
                                <label for="appointmentFee" class="form-label">Valor (R$)</label>
                                <input type="number" class="form-control" id="appointmentFee" step="0.01" min="0" placeholder="0,00">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointmentNotes" class="form-label">Observações</label>
                            <textarea class="form-control" id="appointmentNotes" rows="3" placeholder="Observações adicionais sobre o agendamento"></textarea>
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

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="appointmentDetailsBody">
                    <!-- Appointment details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="editAppointmentBtn">Editar</button>
                    <button type="button" class="btn btn-danger" id="deleteAppointmentBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/scripts.php'; ?>
</body>
</html>