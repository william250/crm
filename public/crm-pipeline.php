<?php
$pageTitle = 'Sales Pipeline - CRM System';
$currentPage = 'crm';
$currentSubPage = 'pipeline';
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'components/head.php'; ?>
<body>
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">Sales Pipeline</h1>
                        <p class="text-muted mb-0">Manage your sales opportunities and track deal progress</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDealModal">
                            <i class="fas fa-plus me-1"></i>
                            Add Deal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pipeline Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h5 mb-0" id="totalDeals">0</div>
                                <div class="small">Total Deals</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-handshake fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h5 mb-0" id="totalValue">$0</div>
                                <div class="small">Pipeline Value</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h5 mb-0" id="avgDealSize">$0</div>
                                <div class="small">Avg Deal Size</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="h5 mb-0" id="conversionRate">0%</div>
                                <div class="small">Conversion Rate</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-percentage fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pipeline Stages -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Sales Pipeline</h6>
                    </div>
                    <div class="card-body">
                        <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading pipeline...</p>
                        </div>
                        <div id="pipelineContainer" class="row">
                            <!-- Pipeline stages will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
    <!-- Add Deal Modal -->
    <div class="modal fade" id="addDealModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Add New Deal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addDealForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealTitle" class="form-label">Deal Title *</label>
                                    <input type="text" class="form-control" id="dealTitle" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealValue" class="form-label">Deal Value *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="dealValue" name="value" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealClient" class="form-label">Client *</label>
                                    <select class="form-select" id="dealClient" name="client_id" required>
                                        <option value="">Select Client</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealContact" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="dealContact" name="contact">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealStage" class="form-label">Stage *</label>
                                    <select class="form-select" id="dealStage" name="stage" required>
                                        <option value="lead">Lead</option>
                                        <option value="qualified">Qualified</option>
                                        <option value="proposal">Proposal</option>
                                        <option value="negotiation">Negotiation</option>
                                        <option value="closed-won">Closed Won</option>
                                        <option value="closed-lost">Closed Lost</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealPriority" class="form-label">Priority</label>
                                    <select class="form-select" id="dealPriority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealCloseDate" class="form-label">Expected Close Date</label>
                                    <input type="date" class="form-control" id="dealCloseDate" name="close_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dealProbability" class="form-label">Probability (%)</label>
                                    <input type="number" class="form-control" id="dealProbability" name="probability" min="0" max="100" value="50">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="dealNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="dealNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Deal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Deal Modal -->
    <div class="modal fade" id="editDealModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Deal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editDealForm">
                    <input type="hidden" id="editDealId" name="deal_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealTitle" class="form-label">Deal Title *</label>
                                    <input type="text" class="form-control" id="editDealTitle" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealValue" class="form-label">Deal Value *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="editDealValue" name="value" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealClient" class="form-label">Client *</label>
                                    <select class="form-select" id="editDealClient" name="client_id" required>
                                        <option value="">Select Client</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealContact" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="editDealContact" name="contact">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealStage" class="form-label">Stage *</label>
                                    <select class="form-select" id="editDealStage" name="stage" required>
                                        <option value="lead">Lead</option>
                                        <option value="qualified">Qualified</option>
                                        <option value="proposal">Proposal</option>
                                        <option value="negotiation">Negotiation</option>
                                        <option value="closed-won">Closed Won</option>
                                        <option value="closed-lost">Closed Lost</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealPriority" class="form-label">Priority</label>
                                    <select class="form-select" id="editDealPriority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealCloseDate" class="form-label">Expected Close Date</label>
                                    <input type="date" class="form-control" id="editDealCloseDate" name="close_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDealProbability" class="form-label">Probability (%)</label>
                                    <input type="number" class="form-control" id="editDealProbability" name="probability" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDealNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="editDealNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="deleteDealBtn">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Deal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <?php
    $page_scripts = ['assets/js/crm-pipeline.js'];
    include 'components/scripts.php';
    ?>
</body>
</html>