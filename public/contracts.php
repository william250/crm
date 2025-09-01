<?php
$page_title = 'Contratos - Sistema CRM';
$current_page = 'contracts';
$page_scripts = ['assets/js/contracts.js'];
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
                        <h1 class="h3 mb-0">Contratos</h1>
                        <p class="text-muted mb-0">Gerencie contratos, acordos e documentos legais</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary me-2" id="exportBtn">
                            <i class="fas fa-download me-1"></i>
                            Exportar
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createContractModal">
                            <i class="fas fa-plus me-1"></i>
                            Novo Contrato
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
            <p class="mt-3 text-muted">Carregando dados dos contratos...</p>
        </div>

        <!-- Contracts Content -->
        <div id="contractsContent" style="display: none;">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-gradient rounded-3 p-3">
                                        <i class="fas fa-file-contract text-white fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Total de Contratos</div>
                                    <div class="h4 mb-0" id="totalContracts">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-gradient rounded-3 p-3">
                                        <i class="fas fa-check-circle text-white fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Contratos Ativos</div>
                                    <div class="h4 mb-0" id="activeContracts">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-gradient rounded-3 p-3">
                                        <i class="fas fa-dollar-sign text-white fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Valor Total</div>
                                    <div class="h4 mb-0" id="totalValue">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-gradient rounded-3 p-3">
                                        <i class="fas fa-exclamation-triangle text-white fa-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Vencendo em Breve</div>
                                    <div class="h4 mb-0" id="expiringContracts">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="searchInput" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Buscar contratos...">
                        </div>
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Todos os Status</option>
                                <option value="active">Ativo</option>
                                <option value="pending">Pendente</option>
                                <option value="expired">Expirado</option>
                                <option value="draft">Rascunho</option>
                                <option value="terminated">Encerrado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="clientFilter" class="form-label">Cliente</label>
                            <select class="form-select" id="clientFilter">
                                <option value="">Todos os Clientes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="typeFilter" class="form-label">Tipo</label>
                            <select class="form-select" id="typeFilter">
                                <option value="">Todos os Tipos</option>
                                <option value="service">Acordo de Serviço</option>
                                <option value="maintenance">Manutenção</option>
                                <option value="development">Desenvolvimento</option>
                                <option value="consulting">Consultoria</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-outline-secondary me-2" id="resetFilters">
                                <i class="fas fa-undo me-1"></i>
                                Limpar
                            </button>
                            <button class="btn btn-primary" id="applyFilters">
                                <i class="fas fa-search me-1"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contracts Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Lista de Contratos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="contractsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Título do Contrato</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Duração</th>
                                    <th>Status</th>
                                    <th>Progresso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="contractsTableBody">
                                <!-- Contracts will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <span id="showingStart">0</span> a <span id="showingEnd">0</span> de <span id="totalRecords">0</span> contratos
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                <!-- Pagination will be generated here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="card border-0 shadow-sm" style="display: none;">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-file-contract fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted">Nenhum contrato encontrado</h4>
                    <p class="text-muted mb-4">Você ainda não criou nenhum contrato. Crie seu primeiro contrato para começar.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createContractModal">
                        <i class="fas fa-plus me-2"></i>
                        Criar Contrato
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Contract Modal -->
    <div class="modal fade" id="createContractModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Criar Novo Contrato
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createContractForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contractTitle" class="form-label">Título do Contrato *</label>
                                    <input type="text" class="form-control" id="contractTitle" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contractClient" class="form-label">Cliente *</label>
                                    <select class="form-select" id="contractClient" name="client_id" required>
                                        <option value="">Selecione um cliente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contractType" class="form-label">Tipo de Contrato *</label>
                                    <select class="form-select" id="contractType" name="type" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="service">Acordo de Serviço</option>
                                        <option value="maintenance">Contrato de Manutenção</option>
                                        <option value="development">Contrato de Desenvolvimento</option>
                                        <option value="consulting">Acordo de Consultoria</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contractValue" class="form-label">Valor do Contrato (R$) *</label>
                                    <input type="number" class="form-control" id="contractValue" name="value" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Data de Início *</label>
                                    <input type="date" class="form-control" id="startDate" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="endDate" class="form-label">Data de Término *</label>
                                    <input type="date" class="form-control" id="endDate" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contractStatus" class="form-label">Status</label>
                                    <select class="form-select" id="contractStatus" name="status">
                                        <option value="draft" selected>Rascunho</option>
                                        <option value="pending">Aguardando Aprovação</option>
                                        <option value="active">Ativo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="renewalType" class="form-label">Tipo de Renovação</label>
                                    <select class="form-select" id="renewalType" name="renewal_type">
                                        <option value="none">Sem Renovação</option>
                                        <option value="manual">Renovação Manual</option>
                                        <option value="automatic">Renovação Automática</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contractDescription" class="form-label">Descrição</label>
                            <textarea class="form-control" id="contractDescription" name="description" rows="4" placeholder="Descreva os termos e condições do contrato..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contractTerms" class="form-label">Termos e Condições</label>
                            <textarea class="form-control" id="contractTerms" name="terms" rows="4" placeholder="Digite os termos e condições específicos..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Contrato</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/scripts.php'; ?>
</body>
</html>