// CRM Leads JavaScript
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};
let leadToConvert = null;

$(document).ready(function() {
    // Initialize page
    initializePage();
    
    // Load initial data
    loadLeads();
    loadSalespeople();
    
    // Set up event listeners
    setupEventListeners();
});

function initializePage() {
    // Check authentication
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (!token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Set up axios defaults
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    // Load user info
    loadUserInfo();
}

function loadUserInfo() {
    const userData = JSON.parse(localStorage.getItem('userData') || sessionStorage.getItem('userData') || '{}');
    if (userData.name) {
        $('#userName').text(userData.name);
    }
}

function loadLeads(page = 1) {
    currentPage = page;
    showLoading();
    
    // Build query parameters
    const params = {
        page: page,
        limit: 20,
        ...currentFilters
    };
    
    axios.get('/api/crm/leads', { params })
        .then(response => {
            const data = response.data.data;
            displayLeads(data.leads || []);
            updatePagination(data.pagination || {});
            hideLoading();
        })
        .catch(error => {
            console.error('Error loading leads:', error);
            hideLoading();
            showAlert('Error loading leads', 'danger');
            showEmptyState();
        });
}

function displayLeads(leads) {
    const tbody = $('#leadsTableBody');
    tbody.empty();
    
    if (leads.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    leads.forEach(lead => {
        const row = createLeadRow(lead);
        tbody.append(row);
    });
}

function createLeadRow(lead) {
    const statusBadge = getStatusBadge(lead.status);
    const createdDate = new Date(lead.created_at).toLocaleDateString();
    const value = lead.value ? `$${formatNumber(lead.value)}` : '-';
    
    return `
        <tr>
            <td>
                <div class="fw-bold">${escapeHtml(lead.name)}</div>
                ${lead.position ? `<small class="text-muted">${escapeHtml(lead.position)}</small>` : ''}
            </td>
            <td>${escapeHtml(lead.email)}</td>
            <td>${escapeHtml(lead.phone)}</td>
            <td>${lead.company ? escapeHtml(lead.company) : '-'}</td>
            <td>${statusBadge}</td>
            <td><span class="badge bg-secondary">${capitalizeFirst(lead.source)}</span></td>
            <td>${value}</td>
            <td>${lead.assigned_name || '-'}</td>
            <td>${createdDate}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editLead(${lead.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${lead.status !== 'converted' ? `
                        <button class="btn btn-outline-success" onclick="showConvertModal(${lead.id})" title="Convert">
                            <i class="fas fa-user-plus"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-outline-danger" onclick="deleteLead(${lead.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

function getStatusBadge(status) {
    const badges = {
        'new': '<span class="badge badge-new">New</span>',
        'contacted': '<span class="badge badge-contacted">Contacted</span>',
        'qualified': '<span class="badge badge-qualified">Qualified</span>',
        'proposal': '<span class="badge badge-proposal">Proposal</span>',
        'negotiation': '<span class="badge badge-negotiation">Negotiation</span>',
        'converted': '<span class="badge badge-converted">Converted</span>',
        'lost': '<span class="badge badge-lost">Lost</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function updatePagination(pagination) {
    totalPages = pagination.totalPages || 1;
    const paginationContainer = $('#pagination');
    paginationContainer.empty();
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    paginationContainer.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="loadLeads(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        paginationContainer.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="loadLeads(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    paginationContainer.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="loadLeads(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

function loadSalespeople() {
    axios.get('/api/crm/salespeople')
        .then(response => {
            const salespeople = response.data.data;
            const assignedFilter = $('#assignedFilter');
            const leadAssigned = $('#leadAssigned');
            
            // Clear existing options (except "All Salespeople" for filter)
            assignedFilter.find('option:not(:first)').remove();
            leadAssigned.empty();
            
            salespeople.forEach(person => {
                assignedFilter.append(`<option value="${person.id}">${escapeHtml(person.name)}</option>`);
                leadAssigned.append(`<option value="${person.id}">${escapeHtml(person.name)}</option>`);
            });
        })
        .catch(error => {
            console.error('Error loading salespeople:', error);
        });
}

function setupEventListeners() {
    // Logout functionality
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // Filter functionality
    $('#applyFilters').on('click', applyFilters);
    $('#clearFilters').on('click', clearFilters);
    
    // Search on Enter key
    $('#searchFilter').on('keypress', function(e) {
        if (e.which === 13) {
            applyFilters();
        }
    });
    
    // Refresh button
    $('#refreshBtn').on('click', function() {
        loadLeads(currentPage);
    });
    
    // Lead form submission
    $('#leadForm').on('submit', handleLeadSubmit);
    
    // Convert lead confirmation
    $('#confirmConvertBtn').on('click', convertLead);
    
    // Reset form when modal is hidden
    $('#leadModal').on('hidden.bs.modal', function() {
        resetLeadForm();
    });
}

function applyFilters() {
    currentFilters = {
        status: $('#statusFilter').val(),
        source: $('#sourceFilter').val(),
        assigned_to: $('#assignedFilter').val(),
        search: $('#searchFilter').val().trim()
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    loadLeads(1);
}

function clearFilters() {
    $('#statusFilter').val('');
    $('#sourceFilter').val('');
    $('#assignedFilter').val('');
    $('#searchFilter').val('');
    currentFilters = {};
    loadLeads(1);
}

function handleLeadSubmit(e) {
    e.preventDefault();
    
    const leadId = $('#leadId').val();
    const isEdit = leadId !== '';
    
    const leadData = {
        name: $('#leadName').val().trim(),
        email: $('#leadEmail').val().trim(),
        phone: $('#leadPhone').val().trim(),
        company: $('#leadCompany').val().trim(),
        position: $('#leadPosition').val().trim(),
        source: $('#leadSource').val(),
        status: $('#leadStatus').val(),
        value: parseFloat($('#leadValue').val()) || 0,
        assigned_to: $('#leadAssigned').val(),
        notes: $('#leadNotes').val().trim()
    };
    
    // Show loading state
    const saveBtn = $('#saveLeadBtn');
    const spinner = saveBtn.find('.spinner-border');
    saveBtn.prop('disabled', true);
    spinner.show();
    
    const request = isEdit 
        ? axios.put(`/api/crm/leads/${leadId}`, leadData)
        : axios.post('/api/crm/leads', leadData);
    
    request
        .then(response => {
            const message = isEdit ? 'Lead updated successfully' : 'Lead created successfully';
            showAlert(message, 'success');
            $('#leadModal').modal('hide');
            loadLeads(currentPage);
        })
        .catch(error => {
            console.error('Error saving lead:', error);
            const message = error.response?.data?.data?.error || 'Error saving lead';
            showAlert(message, 'danger');
        })
        .finally(() => {
            saveBtn.prop('disabled', false);
            spinner.hide();
        });
}

function editLead(leadId) {
    axios.get(`/api/crm/leads/${leadId}`)
        .then(response => {
            const lead = response.data.data;
            
            // Populate form
            $('#leadId').val(lead.id);
            $('#leadName').val(lead.name);
            $('#leadEmail').val(lead.email);
            $('#leadPhone').val(lead.phone);
            $('#leadCompany').val(lead.company || '');
            $('#leadPosition').val(lead.position || '');
            $('#leadSource').val(lead.source);
            $('#leadStatus').val(lead.status);
            $('#leadValue').val(lead.value || '');
            $('#leadAssigned').val(lead.assigned_to || '');
            $('#leadNotes').val(lead.notes || '');
            
            // Update modal title
            $('#leadModalTitle').text('Edit Lead');
            
            // Show modal
            $('#leadModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading lead:', error);
            showAlert('Error loading lead details', 'danger');
        });
}

function showConvertModal(leadId) {
    leadToConvert = leadId;
    $('#convertModal').modal('show');
}

function convertLead() {
    if (!leadToConvert) return;
    
    const confirmBtn = $('#confirmConvertBtn');
    const spinner = confirmBtn.find('.spinner-border');
    confirmBtn.prop('disabled', true);
    spinner.show();
    
    axios.post(`/api/crm/leads/${leadToConvert}/convert`)
        .then(response => {
            showAlert('Lead converted to client successfully', 'success');
            $('#convertModal').modal('hide');
            loadLeads(currentPage);
        })
        .catch(error => {
            console.error('Error converting lead:', error);
            const message = error.response?.data?.data?.error || 'Error converting lead';
            showAlert(message, 'danger');
        })
        .finally(() => {
            confirmBtn.prop('disabled', false);
            spinner.hide();
            leadToConvert = null;
        });
}

function deleteLead(leadId) {
    if (!confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
        return;
    }
    
    axios.delete(`/api/crm/leads/${leadId}`)
        .then(response => {
            showAlert('Lead deleted successfully', 'success');
            loadLeads(currentPage);
        })
        .catch(error => {
            console.error('Error deleting lead:', error);
            const message = error.response?.data?.data?.error || 'Error deleting lead';
            showAlert(message, 'danger');
        });
}

function resetLeadForm() {
    $('#leadForm')[0].reset();
    $('#leadId').val('');
    $('#leadModalTitle').text('Add New Lead');
}

function showLoading() {
    $('.loading').show();
    $('.table-responsive').hide();
    $('.empty-state').hide();
}

function hideLoading() {
    $('.loading').hide();
    $('.table-responsive').show();
}

function showEmptyState() {
    $('.table-responsive').hide();
    $('.empty-state').show();
}

function hideEmptyState() {
    $('.empty-state').hide();
}

function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertContainer').html(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

function logout() {
    // Clear stored data
    localStorage.removeItem('token');
    localStorage.removeItem('userData');
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('userData');
    
    // Redirect to login
    window.location.href = 'login.html';
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
}

// Error handling for axios
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Token expired or invalid
            logout();
        }
        return Promise.reject(error);
    }
);