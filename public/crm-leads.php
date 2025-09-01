<?php
$page_title = 'CRM - Leads | Sistema CRM';
$current_page = 'crm';
$current_subpage = 'leads';
$page_scripts = ['assets/js/crm-leads.js'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/head.php'; ?>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Gerenciamento de Leads</h1>
                    <p class="text-muted">Gerencie e acompanhe seus leads de vendas</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leadModal">
                    <i class="fas fa-plus me-2"></i>Adicionar Novo Lead
                </button>
            </div>

            <!-- Alert Container -->
            <div id="alertContainer"></div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Todos os Status</option>
                                <option value="new">Novo</option>
                                <option value="contacted">Contatado</option>
                                <option value="qualified">Qualificado</option>
                                <option value="proposal">Proposta</option>
                                <option value="negotiation">Negociação</option>
                                <option value="converted">Convertido</option>
                                <option value="lost">Perdido</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fonte</label>
                            <select class="form-select" id="sourceFilter">
                                <option value="">Todas as Fontes</option>
                                <option value="website">Website</option>
                                <option value="referral">Indicação</option>
                                <option value="social_media">Redes Sociais</option>
                                <option value="email">Email</option>
                                <option value="phone">Telefone</option>
                                <option value="other">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Responsável</label>
                            <select class="form-select" id="assignedFilter">
                                <option value="">Todos os Vendedores</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="searchFilter" placeholder="Buscar leads...">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button class="btn btn-outline-primary" id="applyFilters">
                                <i class="fas fa-filter me-2"></i>Aplicar Filtros
                            </button>
                            <button class="btn btn-outline-secondary" id="clearFilters">
                                <i class="fas fa-times me-2"></i>Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leads Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Leads</h6>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>Empresa</th>
                                    <th>Status</th>
                                    <th>Fonte</th>
                                    <th>Valor</th>
                                    <th>Responsável</th>
                                    <th>Criado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="leadsTableBody">
                                <!-- Leads will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Loading State -->
                    <div class="loading text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2 text-muted">Carregando leads...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div class="empty-state text-center py-5" style="display: none;">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum lead encontrado</h5>
                        <p class="text-muted">Comece adicionando seu primeiro lead ou ajuste seus filtros.</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer">
                    <nav>
                        <ul class="pagination justify-content-center mb-0" id="pagination">
                            <!-- Pagination will be generated here -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Modal -->
    <div class="modal fade" id="leadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leadModalTitle">Adicionar Novo Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="leadForm">
                    <div class="modal-body">
                        <input type="hidden" id="leadId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome *</label>
                                <input type="text" class="form-control" id="leadName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" id="leadEmail" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefone *</label>
                                <input type="tel" class="form-control" id="leadPhone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="leadCompany">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <input type="text" class="form-control" id="leadPosition">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fonte</label>
                                <select class="form-select" id="leadSource">
                                    <option value="website">Website</option>
                                    <option value="referral">Indicação</option>
                                    <option value="social_media">Redes Sociais</option>
                                    <option value="email">Email</option>
                                    <option value="phone">Telefone</option>
                                    <option value="other">Outros</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="leadStatus">
                                    <option value="new">Novo</option>
                                    <option value="contacted">Contatado</option>
                                    <option value="qualified">Qualificado</option>
                                    <option value="proposal">Proposta</option>
                                    <option value="negotiation">Negociação</option>
                                    <option value="converted">Convertido</option>
                                    <option value="lost">Perdido</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor Estimado</label>
                                <input type="number" class="form-control" id="leadValue" min="0" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Responsável</label>
                                <select class="form-select" id="leadAssigned">
                                    <!-- Options will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" id="leadNotes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveLeadBtn">
                            <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
                            Salvar Lead
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Convert Lead Modal -->
    <div class="modal fade" id="convertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Converter Lead em Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza de que deseja converter este lead em cliente?</p>
                    <p class="text-muted">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmConvertBtn">
                        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
                        Converter em Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/scripts.php'; ?>
</body>
</html>