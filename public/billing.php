<?php
$page_title = 'Faturamento - Sistema CRM';
$current_page = 'billing';
$page_scripts = ['assets/js/billing.js'];
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
                        <h1 class="h3 mb-0">Faturamento</h1>
                        <p class="text-muted mb-0">Gerencie suas faturas e controle financeiro</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary me-2" id="exportBtn">
                            <i class="fas fa-download me-1"></i>
                            Exportar
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
                            <i class="fas fa-plus me-1"></i>
                            Nova Fatura
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
            <p class="mt-3 text-muted">Carregando dados de faturamento...</p>
        </div>

        <!-- Billing Content -->
        <div id="billingContent" style="display: none;">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Faturado
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRevenue">
                                        R$ 0,00
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                        Faturas Pagas
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="paidInvoices">
                                        0
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        Faturas Pendentes
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingInvoices">
                                        0
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Faturas Vencidas
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="overdueInvoices">
                                        0
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="searchInput" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Número da fatura ou cliente...">
                        </div>
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Todos</option>
                                <option value="draft">Rascunho</option>
                                <option value="pending">Pendente</option>
                                <option value="paid">Pago</option>
                                <option value="overdue">Vencido</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="dateFrom" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="dateFrom">
                        </div>
                        <div class="col-md-2">
                            <label for="dateTo" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="dateTo">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-outline-secondary" id="resetFilters">
                                <i class="fas fa-undo me-1"></i>
                                Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Faturas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="invoicesTable">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Data Emissão</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="invoicesTableBody">
                                <!-- Invoices will be loaded here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Navegação de páginas" id="paginationContainer" style="display: none;">
                        <ul class="pagination justify-content-center" id="pagination">
                            <!-- Pagination will be generated here -->
                        </ul>
                    </nav>

                    <!-- Empty State -->
                    <div id="emptyState" class="text-center py-5" style="display: none;">
                        <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-600">Nenhuma fatura encontrada</h5>
                        <p class="text-muted">Crie sua primeira fatura clicando no botão "Nova Fatura"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-labelledby="createInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createInvoiceModalLabel">Nova Fatura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createInvoiceForm">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="clientSelect" class="form-label">Cliente *</label>
                                <select class="form-select" id="clientSelect" name="client_id" required>
                                    <option value="">Selecione um cliente</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="invoiceNumber" class="form-label">Número da Fatura</label>
                                <input type="text" class="form-control" id="invoiceNumber" name="invoice_number" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="invoiceDate" class="form-label">Data de Emissão *</label>
                                <input type="date" class="form-control" id="invoiceDate" name="issue_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dueDate" class="form-label">Data de Vencimento *</label>
                                <input type="date" class="form-control" id="dueDate" name="due_date" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Itens da Fatura</label>
                            <div id="invoiceItems">
                                <div class="row invoice-item mb-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="item_description[]" placeholder="Descrição" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control" name="item_quantity[]" placeholder="Qtd" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" class="form-control" name="item_price[]" placeholder="Preço" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeInvoiceItem(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addInvoiceItem()">
                                <i class="fas fa-plus me-1"></i>
                                Adicionar Item
                            </button>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="taxRate" class="form-label">Taxa (%)</label>
                                <input type="number" class="form-control" id="taxRate" name="tax_rate" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="discount" class="form-label">Desconto (R$)</label>
                                <input type="number" class="form-control" id="discount" name="discount" step="0.01" min="0" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Observações</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="text-end">
                            <h5>Total: <span id="invoiceTotal">R$ 0,00</span></h5>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Fatura</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Page Scripts -->
    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>