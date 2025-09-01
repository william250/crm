<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Customer Relationship Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/crm.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.html">
                <i class="fas fa-chart-line me-2"></i>
                CRM System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.html">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="crm.html">
                            <i class="fas fa-users me-1"></i>
                            CRM
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="scheduling.html">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Scheduling
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="billing.html">
                            <i class="fas fa-file-invoice-dollar me-1"></i>
                            Billing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contracts.html">
                            <i class="fas fa-file-contract me-1"></i>
                            Contracts
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <span id="userName">User</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" id="profileLink"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#" id="settingsLink"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="logoutLink"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-users me-3"></i>
                        Customer Relationship Management
                    </h1>
                    <p class="page-subtitle">Manage leads, clients, and customer interactions</p>
                </div>
            </div>
        </div>

        <!-- CRM Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs crm-tabs" id="crmTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="leads-tab" data-bs-toggle="tab" data-bs-target="#leads" type="button" role="tab">
                            <i class="fas fa-user-plus me-2"></i>
                            Leads
                            <span class="badge bg-primary ms-2" id="leadsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>
                            Clients
                            <span class="badge bg-success ms-2" id="clientsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pipeline-tab" data-bs-toggle="tab" data-bs-target="#pipeline" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>
                            Pipeline
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="interactions-tab" data-bs-toggle="tab" data-bs-target="#interactions" type="button" role="tab">
                            <i class="fas fa-comments me-2"></i>
                            Interactions
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="crmTabContent">
            <!-- Leads Tab -->
            <div class="tab-pane fade show active" id="leads" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            Leads Management
                        </h5>
                        <button class="btn btn-primary" id="addLeadBtn">
                            <i class="fas fa-plus me-2"></i>
                            Add Lead
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-select" id="leadStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="proposal">Proposal</option>
                                    <option value="negotiation">Negotiation</option>
                                    <option value="closed_won">Closed Won</option>
                                    <option value="closed_lost">Closed Lost</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="leadSourceFilter">
                                    <option value="">All Sources</option>
                                    <option value="website">Website</option>
                                    <option value="referral">Referral</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="email_campaign">Email Campaign</option>
                                    <option value="cold_call">Cold Call</option>
                                    <option value="trade_show">Trade Show</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="leadSearchInput" placeholder="Search leads...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" id="clearLeadFilters">
                                    <i class="fas fa-times me-1"></i>
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Leads Table -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="leadsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th>Value</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="leadsTableBody">
                                    <!-- Dynamic content -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Leads pagination">
                            <ul class="pagination justify-content-center" id="leadsPagination">
                                <!-- Dynamic pagination -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Clients Tab -->
            <div class="tab-pane fade" id="clients" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            Clients Management
                        </h5>
                        <button class="btn btn-success" id="addClientBtn">
                            <i class="fas fa-plus me-2"></i>
                            Add Client
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-select" id="clientStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="prospect">Prospect</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="clientTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="individual">Individual</option>
                                    <option value="business">Business</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="clientSearchInput" placeholder="Search clients...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" id="clearClientFilters">
                                    <i class="fas fa-times me-1"></i>
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Clients Table -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="clientsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsTableBody">
                                    <!-- Dynamic content -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Clients pagination">
                            <ul class="pagination justify-content-center" id="clientsPagination">
                                <!-- Dynamic pagination -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Pipeline Tab -->
            <div class="tab-pane fade" id="pipeline" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Sales Pipeline
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="pipeline-container" id="pipelineContainer">
                            <!-- Dynamic pipeline stages -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactions Tab -->
            <div class="tab-pane fade" id="interactions" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Customer Interactions
                        </h5>
                        <button class="btn btn-info" id="addInteractionBtn">
                            <i class="fas fa-plus me-2"></i>
                            Log Interaction
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Interactions Timeline -->
                        <div class="interactions-timeline" id="interactionsTimeline">
                            <!-- Dynamic interactions -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Lead Modal -->
    <div class="modal fade" id="leadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leadModalTitle">
                        <i class="fas fa-user-plus me-2"></i>
                        Add New Lead
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="leadForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leadFirstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="leadFirstName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leadLastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="leadLastName" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leadEmail" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="leadEmail" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leadPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="leadPhone">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leadCompany" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="leadCompany">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leadPosition" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="leadPosition">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="leadStatus" class="form-label">Status</label>
                                    <select class="form-select" id="leadStatus">
                                        <option value="new">New</option>
                                        <option value="contacted">Contacted</option>
                                        <option value="qualified">Qualified</option>
                                        <option value="proposal">Proposal</option>
                                        <option value="negotiation">Negotiation</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="leadSource" class="form-label">Source</label>
                                    <select class="form-select" id="leadSource">
                                        <option value="website">Website</option>
                                        <option value="referral">Referral</option>
                                        <option value="social_media">Social Media</option>
                                        <option value="email_campaign">Email Campaign</option>
                                        <option value="cold_call">Cold Call</option>
                                        <option value="trade_show">Trade Show</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="leadValue" class="form-label">Estimated Value</label>
                                    <input type="number" class="form-control" id="leadValue" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="leadNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="leadNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveLeadBtn">
                        <i class="fas fa-save me-2"></i>
                        Save Lead
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalTitle">
                        <i class="fas fa-users me-2"></i>
                        Add New Client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientFirstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="clientFirstName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientLastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="clientLastName" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientEmail" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="clientEmail" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="clientPhone">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientCompany" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="clientCompany">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientPosition" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="clientPosition">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientType" class="form-label">Type</label>
                                    <select class="form-select" id="clientType">
                                        <option value="individual">Individual</option>
                                        <option value="business">Business</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clientStatus" class="form-label">Status</label>
                                    <select class="form-select" id="clientStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="prospect">Prospect</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="clientAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="clientAddress" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="clientNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="clientNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveClientBtn">
                        <i class="fas fa-save me-2"></i>
                        Save Client
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/crm.js"></script>
</body>
</html>