/**
 * CRM JavaScript
 * Handles CRM functionality including leads, clients, pipeline, and interactions
 */

// Global variables
let currentPage = {
    leads: 1,
    clients: 1
};
let currentFilters = {
    leads: {},
    clients: {}
};
let editingId = null;
let editingType = null;

// Initialize CRM page when DOM is loaded
$(document).ready(function() {
    initializeCRM();
});

/**
 * Initialize CRM page
 */
function initializeCRM() {
    // Check authentication
    if (!isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Load user information
    loadUserInfo();
    
    // Setup event listeners
    setupEventListeners();
    
    // Setup axios interceptors
    setupAxiosInterceptors();
    
    // Load initial data
    loadLeads();
    loadCounts();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Tab switching
    $('#crmTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        
        switch(target) {
            case '#leads':
                loadLeads();
                break;
            case '#clients':
                loadClients();
                break;
            case '#pipeline':
                loadPipeline();
                break;
            case '#interactions':
                loadInteractions();
                break;
        }
    });
    
    // Add buttons
    $('#addLeadBtn').on('click', function() {
        openLeadModal();
    });
    
    $('#addClientBtn').on('click', function() {
        openClientModal();
    });
    
    $('#addInteractionBtn').on('click', function() {
        // TODO: Implement interaction modal
        showAlert('info', 'Interaction logging will be available soon.');
    });
    
    // Save buttons
    $('#saveLeadBtn').on('click', function() {
        saveLead();
    });
    
    $('#saveClientBtn').on('click', function() {
        saveClient();
    });
    
    // Filter events
    $('#leadStatusFilter, #leadSourceFilter').on('change', function() {
        applyLeadFilters();
    });
    
    $('#clientStatusFilter, #clientTypeFilter').on('change', function() {
        applyClientFilters();
    });
    
    // Search events
    let leadSearchTimeout;
    $('#leadSearchInput').on('input', function() {
        clearTimeout(leadSearchTimeout);
        leadSearchTimeout = setTimeout(() => {
            applyLeadFilters();
        }, 500);
    });
    
    let clientSearchTimeout;
    $('#clientSearchInput').on('input', function() {
        clearTimeout(clientSearchTimeout);
        clientSearchTimeout = setTimeout(() => {
            applyClientFilters();
        }, 500);
    });
    
    // Clear filters
    $('#clearLeadFilters').on('click', function() {
        clearLeadFilters();
    });
    
    $('#clearClientFilters').on('click', function() {
        clearClientFilters();
    });
    
    // Logout
    $('#logoutLink').on('click', function(e) {
        e.preventDefault();
        logout();
    });
}

/**
 * Load counts for tabs
 */
function loadCounts() {
    // Load leads count
    axios.get('/api/leads/count')
        .then(response => {
            if (response.data.success) {
                $('#leadsCount').text(response.data.count || 0);
            }
        })
        .catch(error => {
            console.error('Error loading leads count:', error);
        });
    
    // Load clients count
    axios.get('/api/clients/count')
        .then(response => {
            if (response.data.success) {
                $('#clientsCount').text(response.data.count || 0);
            }
        })
        .catch(error => {
            console.error('Error loading clients count:', error);
        });
}

/**
 * Load leads data
 */
function loadLeads(page = 1) {
    showLoading();
    
    const params = {
        page: page,
        limit: 10,
        ...currentFilters.leads
    };
    
    axios.get('/api/leads', { params })
        .then(response => {
            if (response.data.success) {
                renderLeadsTable(response.data.leads || []);
                renderPagination('leads', response.data.pagination || {});
                currentPage.leads = page;
            } else {
                showAlert('danger', response.data.message || 'Failed to load leads');
            }
        })
        .catch(error => {
            console.error('Error loading leads:', error);
            showAlert('danger', 'Error loading leads. Please try again.');
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Load clients data
 */
function loadClients(page = 1) {
    showLoading();
    
    const params = {
        page: page,
        limit: 10,
        ...currentFilters.clients
    };
    
    axios.get('/api/clients', { params })
        .then(response => {
            if (response.data.success) {
                renderClientsTable(response.data.clients || []);
                renderPagination('clients', response.data.pagination || {});
                currentPage.clients = page;
            } else {
                showAlert('danger', response.data.message || 'Failed to load clients');
            }
        })
        .catch(error => {
            console.error('Error loading clients:', error);
            showAlert('danger', 'Error loading clients. Please try again.');
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Load pipeline data
 */
function loadPipeline() {
    showLoading();
    
    axios.get('/api/leads/pipeline')
        .then(response => {
            if (response.data.success) {
                renderPipeline(response.data.pipeline || []);
            } else {
                showAlert('danger', response.data.message || 'Failed to load pipeline');
            }
        })
        .catch(error => {
            console.error('Error loading pipeline:', error);
            showAlert('danger', 'Error loading pipeline. Please try again.');
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Load interactions data
 */
function loadInteractions() {
    showLoading();
    
    axios.get('/api/interactions/recent')
        .then(response => {
            if (response.data.success) {
                renderInteractions(response.data.interactions || []);
            } else {
                showAlert('danger', response.data.message || 'Failed to load interactions');
            }
        })
        .catch(error => {
            console.error('Error loading interactions:', error);
            showAlert('danger', 'Error loading interactions. Please try again.');
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Render leads table
 */
function renderLeadsTable(leads) {
    const tbody = $('#leadsTableBody');
    tbody.empty();
    
    if (leads.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center py-4">
                    <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No leads found</p>
                </td>
            </tr>
        `);
        return;
    }
    
    leads.forEach(lead => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                            ${getInitials(lead.first_name, lead.last_name)}
                        </div>
                        <div>
                            <div class="fw-semibold">${escapeHtml(lead.first_name)} ${escapeHtml(lead.last_name)}</div>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(lead.email || '')}</td>
                <td>${escapeHtml(lead.phone || '-')}</td>
                <td>${escapeHtml(lead.company || '-')}</td>
                <td>${getStatusBadge(lead.status)}</td>
                <td>${getSourceBadge(lead.source)}</td>
                <td>${lead.estimated_value ? '$' + formatNumber(lead.estimated_value) : '-'}</td>
                <td>${formatDate(lead.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editLead(${lead.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="convertLead(${lead.id})" title="Convert to Client">
                            <i class="fas fa-user-check"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteLead(${lead.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Render clients table
 */
function renderClientsTable(clients) {
    const tbody = $('#clientsTableBody');
    tbody.empty();
    
    if (clients.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No clients found</p>
                </td>
            </tr>
        `);
        return;
    }
    
    clients.forEach(client => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                            ${getInitials(client.first_name, client.last_name)}
                        </div>
                        <div>
                            <div class="fw-semibold">${escapeHtml(client.first_name)} ${escapeHtml(client.last_name)}</div>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(client.email || '')}</td>
                <td>${escapeHtml(client.phone || '-')}</td>
                <td>${escapeHtml(client.company || '-')}</td>
                <td>${getTypeBadge(client.type)}</td>
                <td>${getClientStatusBadge(client.status)}</td>
                <td>${formatDate(client.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editClient(${client.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="viewClient(${client.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteClient(${client.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Render pipeline
 */
function renderPipeline(pipeline) {
    const container = $('#pipelineContainer');
    container.empty();
    
    if (pipeline.length === 0) {
        container.append(`
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <p class="text-muted">No pipeline data available</p>
            </div>
        `);
        return;
    }
    
    const stages = [
        { key: 'new', label: 'New', color: 'secondary' },
        { key: 'contacted', label: 'Contacted', color: 'info' },
        { key: 'qualified', label: 'Qualified', color: 'warning' },
        { key: 'proposal', label: 'Proposal', color: 'primary' },
        { key: 'negotiation', label: 'Negotiation', color: 'success' },
        { key: 'closed_won', label: 'Closed Won', color: 'success' },
        { key: 'closed_lost', label: 'Closed Lost', color: 'danger' }
    ];
    
    const pipelineHtml = `
        <div class="row">
            ${stages.map(stage => {
                const stageData = pipeline.find(p => p.status === stage.key) || { count: 0, total_value: 0 };
                return `
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="pipeline-stage card border-${stage.color}">
                            <div class="card-body text-center">
                                <h6 class="card-title text-${stage.color}">${stage.label}</h6>
                                <h3 class="mb-2">${stageData.count}</h3>
                                <p class="text-muted mb-0">$${formatNumber(stageData.total_value || 0)}</p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
    
    container.html(pipelineHtml);
}

/**
 * Render interactions timeline
 */
function renderInteractions(interactions) {
    const container = $('#interactionsTimeline');
    container.empty();
    
    if (interactions.length === 0) {
        container.append(`
            <div class="text-center py-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <p class="text-muted">No interactions found</p>
            </div>
        `);
        return;
    }
    
    const timelineHtml = interactions.map(interaction => `
        <div class="timeline-item">
            <div class="timeline-marker bg-${getInteractionTypeColor(interaction.type)}"></div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">${escapeHtml(interaction.subject || 'No Subject')}</h6>
                    <small class="text-muted">${formatDateTime(interaction.created_at)}</small>
                </div>
                <p class="text-muted mb-2">${escapeHtml(interaction.description || '')}</p>
                <div class="d-flex align-items-center">
                    <span class="badge bg-${getInteractionTypeColor(interaction.type)} me-2">
                        ${getInteractionTypeLabel(interaction.type)}
                    </span>
                    <small class="text-muted">
                        ${interaction.client_name ? 'Client: ' + escapeHtml(interaction.client_name) : ''}
                        ${interaction.lead_name ? 'Lead: ' + escapeHtml(interaction.lead_name) : ''}
                    </small>
                </div>
            </div>
        </div>
    `).join('');
    
    container.html(`<div class="timeline">${timelineHtml}</div>`);
}

/**
 * Render pagination
 */
function renderPagination(type, pagination) {
    const container = $(`#${type}Pagination`);
    container.empty();
    
    if (!pagination || pagination.total_pages <= 1) {
        return;
    }
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    let paginationHtml = '';
    
    // Previous button
    paginationHtml += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="load${type.charAt(0).toUpperCase() + type.slice(1)}(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="load${type.charAt(0).toUpperCase() + type.slice(1)}(${i})">${i}</a>
            </li>
        `;
    }
    
    // Next button
    paginationHtml += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="load${type.charAt(0).toUpperCase() + type.slice(1)}(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    container.html(paginationHtml);
}

/**
 * Open lead modal
 */
function openLeadModal(leadId = null) {
    editingId = leadId;
    editingType = 'lead';
    
    if (leadId) {
        $('#leadModalTitle').html('<i class="fas fa-edit me-2"></i>Edit Lead');
        loadLeadData(leadId);
    } else {
        $('#leadModalTitle').html('<i class="fas fa-user-plus me-2"></i>Add New Lead');
        $('#leadForm')[0].reset();
    }
    
    $('#leadModal').modal('show');
}

/**
 * Open client modal
 */
function openClientModal(clientId = null) {
    editingId = clientId;
    editingType = 'client';
    
    if (clientId) {
        $('#clientModalTitle').html('<i class="fas fa-edit me-2"></i>Edit Client');
        loadClientData(clientId);
    } else {
        $('#clientModalTitle').html('<i class="fas fa-users me-2"></i>Add New Client');
        $('#clientForm')[0].reset();
    }
    
    $('#clientModal').modal('show');
}

/**
 * Load lead data for editing
 */
function loadLeadData(leadId) {
    axios.get(`/api/leads/${leadId}`)
        .then(response => {
            if (response.data.success) {
                const lead = response.data.lead;
                $('#leadFirstName').val(lead.first_name || '');
                $('#leadLastName').val(lead.last_name || '');
                $('#leadEmail').val(lead.email || '');
                $('#leadPhone').val(lead.phone || '');
                $('#leadCompany').val(lead.company || '');
                $('#leadPosition').val(lead.position || '');
                $('#leadStatus').val(lead.status || 'new');
                $('#leadSource').val(lead.source || 'website');
                $('#leadValue').val(lead.estimated_value || '');
                $('#leadNotes').val(lead.notes || '');
            } else {
                showAlert('danger', response.data.message || 'Failed to load lead data');
            }
        })
        .catch(error => {
            console.error('Error loading lead data:', error);
            showAlert('danger', 'Error loading lead data. Please try again.');
        });
}

/**
 * Load client data for editing
 */
function loadClientData(clientId) {
    axios.get(`/api/clients/${clientId}`)
        .then(response => {
            if (response.data.success) {
                const client = response.data.client;
                $('#clientFirstName').val(client.first_name || '');
                $('#clientLastName').val(client.last_name || '');
                $('#clientEmail').val(client.email || '');
                $('#clientPhone').val(client.phone || '');
                $('#clientCompany').val(client.company || '');
                $('#clientPosition').val(client.position || '');
                $('#clientType').val(client.type || 'individual');
                $('#clientStatus').val(client.status || 'active');
                $('#clientAddress').val(client.address || '');
                $('#clientNotes').val(client.notes || '');
            } else {
                showAlert('danger', response.data.message || 'Failed to load client data');
            }
        })
        .catch(error => {
            console.error('Error loading client data:', error);
            showAlert('danger', 'Error loading client data. Please try again.');
        });
}

/**
 * Save lead
 */
function saveLead() {
    const leadData = {
        first_name: $('#leadFirstName').val().trim(),
        last_name: $('#leadLastName').val().trim(),
        email: $('#leadEmail').val().trim(),
        phone: $('#leadPhone').val().trim(),
        company: $('#leadCompany').val().trim(),
        position: $('#leadPosition').val().trim(),
        status: $('#leadStatus').val(),
        source: $('#leadSource').val(),
        estimated_value: $('#leadValue').val() || null,
        notes: $('#leadNotes').val().trim()
    };
    
    // Validate required fields
    if (!leadData.first_name || !leadData.last_name || !leadData.email) {
        showAlert('danger', 'Please fill in all required fields.');
        return;
    }
    
    const url = editingId ? `/api/leads/${editingId}` : '/api/leads';
    const method = editingId ? 'put' : 'post';
    
    axios[method](url, leadData)
        .then(response => {
            if (response.data.success) {
                showAlert('success', editingId ? 'Lead updated successfully!' : 'Lead created successfully!');
                $('#leadModal').modal('hide');
                loadLeads(currentPage.leads);
                loadCounts();
            } else {
                showAlert('danger', response.data.message || 'Failed to save lead');
            }
        })
        .catch(error => {
            console.error('Error saving lead:', error);
            showAlert('danger', 'Error saving lead. Please try again.');
        });
}

/**
 * Save client
 */
function saveClient() {
    const clientData = {
        first_name: $('#clientFirstName').val().trim(),
        last_name: $('#clientLastName').val().trim(),
        email: $('#clientEmail').val().trim(),
        phone: $('#clientPhone').val().trim(),
        company: $('#clientCompany').val().trim(),
        position: $('#clientPosition').val().trim(),
        type: $('#clientType').val(),
        status: $('#clientStatus').val(),
        address: $('#clientAddress').val().trim(),
        notes: $('#clientNotes').val().trim()
    };
    
    // Validate required fields
    if (!clientData.first_name || !clientData.last_name || !clientData.email) {
        showAlert('danger', 'Please fill in all required fields.');
        return;
    }
    
    const url = editingId ? `/api/clients/${editingId}` : '/api/clients';
    const method = editingId ? 'put' : 'post';
    
    axios[method](url, clientData)
        .then(response => {
            if (response.data.success) {
                showAlert('success', editingId ? 'Client updated successfully!' : 'Client created successfully!');
                $('#clientModal').modal('hide');
                loadClients(currentPage.clients);
                loadCounts();
            } else {
                showAlert('danger', response.data.message || 'Failed to save client');
            }
        })
        .catch(error => {
            console.error('Error saving client:', error);
            showAlert('danger', 'Error saving client. Please try again.');
        });
}

/**
 * Apply lead filters
 */
function applyLeadFilters() {
    currentFilters.leads = {
        status: $('#leadStatusFilter').val(),
        source: $('#leadSourceFilter').val(),
        search: $('#leadSearchInput').val().trim()
    };
    
    // Remove empty filters
    Object.keys(currentFilters.leads).forEach(key => {
        if (!currentFilters.leads[key]) {
            delete currentFilters.leads[key];
        }
    });
    
    loadLeads(1);
}

/**
 * Apply client filters
 */
function applyClientFilters() {
    currentFilters.clients = {
        status: $('#clientStatusFilter').val(),
        type: $('#clientTypeFilter').val(),
        search: $('#clientSearchInput').val().trim()
    };
    
    // Remove empty filters
    Object.keys(currentFilters.clients).forEach(key => {
        if (!currentFilters.clients[key]) {
            delete currentFilters.clients[key];
        }
    });
    
    loadClients(1);
}

/**
 * Clear lead filters
 */
function clearLeadFilters() {
    $('#leadStatusFilter').val('');
    $('#leadSourceFilter').val('');
    $('#leadSearchInput').val('');
    currentFilters.leads = {};
    loadLeads(1);
}

/**
 * Clear client filters
 */
function clearClientFilters() {
    $('#clientStatusFilter').val('');
    $('#clientTypeFilter').val('');
    $('#clientSearchInput').val('');
    currentFilters.clients = {};
    loadClients(1);
}

/**
 * Edit lead
 */
function editLead(leadId) {
    openLeadModal(leadId);
}

/**
 * Edit client
 */
function editClient(clientId) {
    openClientModal(clientId);
}

/**
 * Convert lead to client
 */
function convertLead(leadId) {
    if (confirm('Are you sure you want to convert this lead to a client?')) {
        axios.post(`/api/leads/${leadId}/convert`)
            .then(response => {
                if (response.data.success) {
                    showAlert('success', 'Lead converted to client successfully!');
                    loadLeads(currentPage.leads);
                    loadCounts();
                } else {
                    showAlert('danger', response.data.message || 'Failed to convert lead');
                }
            })
            .catch(error => {
                console.error('Error converting lead:', error);
                showAlert('danger', 'Error converting lead. Please try again.');
            });
    }
}

/**
 * Delete lead
 */
function deleteLead(leadId) {
    if (confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
        axios.delete(`/api/leads/${leadId}`)
            .then(response => {
                if (response.data.success) {
                    showAlert('success', 'Lead deleted successfully!');
                    loadLeads(currentPage.leads);
                    loadCounts();
                } else {
                    showAlert('danger', response.data.message || 'Failed to delete lead');
                }
            })
            .catch(error => {
                console.error('Error deleting lead:', error);
                showAlert('danger', 'Error deleting lead. Please try again.');
            });
    }
}

/**
 * Delete client
 */
function deleteClient(clientId) {
    if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
        axios.delete(`/api/clients/${clientId}`)
            .then(response => {
                if (response.data.success) {
                    showAlert('success', 'Client deleted successfully!');
                    loadClients(currentPage.clients);
                    loadCounts();
                } else {
                    showAlert('danger', response.data.message || 'Failed to delete client');
                }
            })
            .catch(error => {
                console.error('Error deleting client:', error);
                showAlert('danger', 'Error deleting client. Please try again.');
            });
    }
}

/**
 * View client details
 */
function viewClient(clientId) {
    // TODO: Implement client details view
    showAlert('info', 'Client details view will be available soon.');
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const badges = {
        'new': 'secondary',
        'contacted': 'info',
        'qualified': 'warning',
        'proposal': 'primary',
        'negotiation': 'success',
        'closed_won': 'success',
        'closed_lost': 'danger'
    };
    
    const color = badges[status] || 'secondary';
    const label = status ? status.replace('_', ' ').toUpperCase() : 'NEW';
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

/**
 * Get source badge HTML
 */
function getSourceBadge(source) {
    const badges = {
        'website': 'primary',
        'referral': 'success',
        'social_media': 'info',
        'email_campaign': 'warning',
        'cold_call': 'secondary',
        'trade_show': 'dark',
        'other': 'light'
    };
    
    const color = badges[source] || 'secondary';
    const label = source ? source.replace('_', ' ').toUpperCase() : 'WEBSITE';
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

/**
 * Get client status badge HTML
 */
function getClientStatusBadge(status) {
    const badges = {
        'active': 'success',
        'inactive': 'secondary',
        'prospect': 'warning'
    };
    
    const color = badges[status] || 'secondary';
    const label = status ? status.toUpperCase() : 'ACTIVE';
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

/**
 * Get type badge HTML
 */
function getTypeBadge(type) {
    const badges = {
        'individual': 'info',
        'business': 'primary'
    };
    
    const color = badges[type] || 'info';
    const label = type ? type.toUpperCase() : 'INDIVIDUAL';
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

/**
 * Get interaction type color
 */
function getInteractionTypeColor(type) {
    const colors = {
        'call': 'primary',
        'email': 'info',
        'meeting': 'success',
        'note': 'secondary',
        'task': 'warning'
    };
    
    return colors[type] || 'secondary';
}

/**
 * Get interaction type label
 */
function getInteractionTypeLabel(type) {
    const labels = {
        'call': 'Call',
        'email': 'Email',
        'meeting': 'Meeting',
        'note': 'Note',
        'task': 'Task'
    };
    
    return labels[type] || 'Note';
}

/**
 * Get initials from names
 */
function getInitials(firstName, lastName) {
    const first = firstName ? firstName.charAt(0).toUpperCase() : '';
    const last = lastName ? lastName.charAt(0).toUpperCase() : '';
    return first + last;
}

/**
 * Load user information
 */
function loadUserInfo() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (user.first_name && user.last_name) {
        $('#userName').text(`${user.first_name} ${user.last_name}`);
    }
}

/**
 * Show loading overlay
 */
function showLoading() {
    $('#loadingOverlay').addClass('show');
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    $('#loadingOverlay').removeClass('show');
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    // Create alert element
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Add to body
    $('body').append(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

/**
 * Get alert icon based on type
 */
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Format date
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

/**
 * Format date and time
 */
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString();
}

/**
 * Format number with commas
 */
function formatNumber(number) {
    if (!number) return '0';
    return parseFloat(number).toLocaleString();
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    const token = localStorage.getItem('token');
    return token !== null && token !== '';
}

/**
 * Logout user
 */
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

/**
 * Setup axios interceptors
 */
function setupAxiosInterceptors() {
    // Request interceptor
    axios.interceptors.request.use(
        config => {
            const token = localStorage.getItem('token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
            return config;
        },
        error => {
            return Promise.reject(error);
        }
    );
    
    // Response interceptor
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                logout();
            }
            return Promise.reject(error);
        }
    );
}

// Export functions for global access
window.loadLeads = loadLeads;
window.loadClients = loadClients;
window.editLead = editLead;
window.editClient = editClient;
window.convertLead = convertLead;
window.deleteLead = deleteLead;
window.deleteClient = deleteClient;
window.viewClient = viewClient;