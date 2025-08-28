$(document).ready(function() {
    // Initialize the page
    initializePage();
    
    // Load contracts data
    loadContracts();
    
    // Load clients for dropdowns
    loadClients();
    
    // Event listeners
    setupEventListeners();
});

// Global variables
let contracts = [];
let filteredContracts = [];
let currentPage = 1;
const itemsPerPage = 10;

// Initialize page
function initializePage() {
    // Check authentication
    checkAuthentication();
    
    // Set user name
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    if (userData.name) {
        $('#userName').text(userData.name);
    }
    
    // Show admin links if user is admin
    if (userData.role === 'admin') {
        $('#usersLink').show();
    }
}

// Check authentication
function checkAuthentication() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Set axios default header
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Load contracts data
function loadContracts() {
    showLoading();
    
    // Mock data for demonstration
    setTimeout(() => {
        contracts = [
            {
                id: 1,
                title: 'Website Development Contract',
                client_id: 1,
                client_name: 'Tech Solutions Inc.',
                type: 'development',
                value: 25000.00,
                start_date: '2024-01-15',
                end_date: '2024-06-15',
                status: 'active',
                progress: 65,
                renewal_type: 'manual',
                description: 'Complete website development and maintenance',
                created_at: '2024-01-10'
            },
            {
                id: 2,
                title: 'IT Support Services',
                client_id: 2,
                client_name: 'Global Corp Ltd.',
                type: 'service',
                value: 15000.00,
                start_date: '2024-02-01',
                end_date: '2025-01-31',
                status: 'active',
                progress: 40,
                renewal_type: 'automatic',
                description: '24/7 IT support and maintenance services',
                created_at: '2024-01-25'
            },
            {
                id: 3,
                title: 'Software Maintenance Agreement',
                client_id: 3,
                client_name: 'StartupXYZ',
                type: 'maintenance',
                value: 8000.00,
                start_date: '2024-03-01',
                end_date: '2024-12-31',
                status: 'pending',
                progress: 0,
                renewal_type: 'manual',
                description: 'Monthly software updates and bug fixes',
                created_at: '2024-02-28'
            },
            {
                id: 4,
                title: 'Business Consulting Contract',
                client_id: 4,
                client_name: 'Enterprise Solutions',
                type: 'consulting',
                value: 35000.00,
                start_date: '2023-12-01',
                end_date: '2024-02-29',
                status: 'expired',
                progress: 100,
                renewal_type: 'none',
                description: 'Strategic business consulting and planning',
                created_at: '2023-11-25'
            },
            {
                id: 5,
                title: 'Mobile App Development',
                client_id: 5,
                client_name: 'Innovation Hub',
                type: 'development',
                value: 45000.00,
                start_date: '2024-04-01',
                end_date: '2024-10-31',
                status: 'draft',
                progress: 0,
                renewal_type: 'manual',
                description: 'Cross-platform mobile application development',
                created_at: '2024-03-20'
            }
        ];
        
        filteredContracts = [...contracts];
        displayContracts();
        updateStatistics();
        hideLoading();
    }, 1000);
}

// Load clients for dropdowns
function loadClients() {
    // Mock clients data
    const clients = [
        { id: 1, name: 'Tech Solutions Inc.' },
        { id: 2, name: 'Global Corp Ltd.' },
        { id: 3, name: 'StartupXYZ' },
        { id: 4, name: 'Enterprise Solutions' },
        { id: 5, name: 'Innovation Hub' }
    ];
    
    // Populate client dropdowns
    const clientSelects = ['#contractClient', '#clientFilter'];
    clientSelects.forEach(selector => {
        const $select = $(selector);
        clients.forEach(client => {
            $select.append(`<option value="${client.id}">${client.name}</option>`);
        });
    });
}

// Display contracts
function displayContracts() {
    const $tbody = $('#contractsTableBody');
    $tbody.empty();
    
    if (filteredContracts.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    // Calculate pagination
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedContracts = filteredContracts.slice(startIndex, endIndex);
    
    paginatedContracts.forEach(contract => {
        const statusClass = getStatusClass(contract.status);
        const statusText = getStatusText(contract.status);
        const typeText = getTypeText(contract.type);
        const duration = calculateDuration(contract.start_date, contract.end_date);
        
        const row = `
            <tr>
                <td>
                    <div class="contract-title">${contract.title}</div>
                    <div class="contract-dates">Created: ${formatDate(contract.created_at)}</div>
                </td>
                <td>
                    <div class="contract-client">${contract.client_name}</div>
                </td>
                <td>
                    <span class="badge bg-secondary">${typeText}</span>
                </td>
                <td>
                    <div class="contract-value text-success">$${formatNumber(contract.value)}</div>
                </td>
                <td>
                    <div class="contract-dates">
                        <div><strong>Start:</strong> ${formatDate(contract.start_date)}</div>
                        <div><strong>End:</strong> ${formatDate(contract.end_date)}</div>
                        <div class="text-muted">${duration}</div>
                    </div>
                </td>
                <td>
                    <span class="badge ${statusClass}">${statusText}</span>
                </td>
                <td>
                    <div class="contract-progress">
                        <div class="contract-progress-label">${contract.progress}% Complete</div>
                        <div class="progress progress-bar-custom">
                            <div class="progress-bar" role="progressbar" style="width: ${contract.progress}%"></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="contract-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewContract(${contract.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="editContract(${contract.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="downloadContract(${contract.id})" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteContract(${contract.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        
        $tbody.append(row);
    });
    
    updatePagination();
}

// Update statistics
function updateStatistics() {
    const totalContracts = contracts.length;
    const activeContracts = contracts.filter(c => c.status === 'active').length;
    const totalValue = contracts.reduce((sum, c) => sum + c.value, 0);
    
    // Calculate expiring contracts (within 30 days)
    const today = new Date();
    const thirtyDaysFromNow = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));
    const expiringContracts = contracts.filter(c => {
        const endDate = new Date(c.end_date);
        return c.status === 'active' && endDate <= thirtyDaysFromNow && endDate >= today;
    }).length;
    
    $('#totalContracts').text(totalContracts);
    $('#activeContracts').text(activeContracts);
    $('#contractValue').text('$' + formatNumber(totalValue));
    $('#expiringContracts').text(expiringContracts);
}

// Filter contracts
function filterContracts() {
    const searchTerm = $('#searchInput').val().toLowerCase();
    const statusFilter = $('#statusFilter').val();
    const clientFilter = $('#clientFilter').val();
    const typeFilter = $('#typeFilter').val();
    
    filteredContracts = contracts.filter(contract => {
        const matchesSearch = contract.title.toLowerCase().includes(searchTerm) ||
                            contract.client_name.toLowerCase().includes(searchTerm) ||
                            contract.description.toLowerCase().includes(searchTerm);
        
        const matchesStatus = !statusFilter || contract.status === statusFilter;
        const matchesClient = !clientFilter || contract.client_id.toString() === clientFilter;
        const matchesType = !typeFilter || contract.type === typeFilter;
        
        return matchesSearch && matchesStatus && matchesClient && matchesType;
    });
    
    currentPage = 1;
    displayContracts();
}

// Setup event listeners
function setupEventListeners() {
    // Search input
    $('#searchInput').on('input', debounce(filterContracts, 300));
    
    // Filter dropdowns
    $('#statusFilter, #clientFilter, #typeFilter').on('change', filterContracts);
    
    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#searchInput').val('');
        $('#statusFilter').val('');
        $('#clientFilter').val('');
        $('#typeFilter').val('');
        filteredContracts = [...contracts];
        currentPage = 1;
        displayContracts();
    });
    
    // Create contract form
    $('#createContractForm').on('submit', function(e) {
        e.preventDefault();
        createContract();
    });
    
    // Logout
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        logout();
    });
}

// Create contract
function createContract() {
    const formData = {
        title: $('#contractTitle').val(),
        client_id: parseInt($('#contractClient').val()),
        type: $('#contractType').val(),
        value: parseFloat($('#contractValue').val()),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        status: $('#contractStatus').val(),
        renewal_type: $('#renewalType').val(),
        description: $('#contractDescription').val(),
        terms: $('#contractTerms').val()
    };
    
    // Validate form
    if (!formData.title || !formData.client_id || !formData.type || !formData.value || !formData.start_date || !formData.end_date) {
        showAlert('Please fill in all required fields.', 'error');
        return;
    }
    
    // Validate dates
    if (new Date(formData.start_date) >= new Date(formData.end_date)) {
        showAlert('End date must be after start date.', 'error');
        return;
    }
    
    // Mock API call
    showAlert('Creating contract...', 'info');
    
    setTimeout(() => {
        // Find client name
        const clientName = $('#contractClient option:selected').text();
        
        // Create new contract object
        const newContract = {
            id: contracts.length + 1,
            ...formData,
            client_name: clientName,
            progress: 0,
            created_at: new Date().toISOString().split('T')[0]
        };
        
        // Add to contracts array
        contracts.unshift(newContract);
        filteredContracts = [...contracts];
        
        // Update display
        displayContracts();
        updateStatistics();
        
        // Close modal and reset form
        $('#createContractModal').modal('hide');
        $('#createContractForm')[0].reset();
        
        showAlert('Contract created successfully!', 'success');
    }, 1000);
}

// View contract
function viewContract(contractId) {
    const contract = contracts.find(c => c.id === contractId);
    if (contract) {
        // In a real application, this would open a detailed view modal
        showAlert(`Viewing contract: ${contract.title}`, 'info');
    }
}

// Edit contract
function editContract(contractId) {
    const contract = contracts.find(c => c.id === contractId);
    if (contract) {
        // In a real application, this would open an edit modal
        showAlert(`Editing contract: ${contract.title}`, 'info');
    }
}

// Download contract
function downloadContract(contractId) {
    const contract = contracts.find(c => c.id === contractId);
    if (contract) {
        // In a real application, this would generate and download a PDF
        showAlert(`Downloading contract: ${contract.title}`, 'info');
    }
}

// Delete contract
function deleteContract(contractId) {
    const contract = contracts.find(c => c.id === contractId);
    if (contract && confirm(`Are you sure you want to delete the contract "${contract.title}"?`)) {
        // Mock API call
        showAlert('Deleting contract...', 'info');
        
        setTimeout(() => {
            // Remove from arrays
            contracts = contracts.filter(c => c.id !== contractId);
            filteredContracts = filteredContracts.filter(c => c.id !== contractId);
            
            // Update display
            displayContracts();
            updateStatistics();
            
            showAlert('Contract deleted successfully!', 'success');
        }, 500);
    }
}

// Export contracts
function exportContracts() {
    // In a real application, this would generate and download a CSV/Excel file
    showAlert('Exporting contracts...', 'info');
    
    setTimeout(() => {
        showAlert('Contracts exported successfully!', 'success');
    }, 1000);
}

// Update pagination
function updatePagination() {
    const totalPages = Math.ceil(filteredContracts.length / itemsPerPage);
    const $pagination = $('#pagination');
    $pagination.empty();
    
    // Update showing info
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, filteredContracts.length);
    $('#showingStart').text(filteredContracts.length > 0 ? startIndex : 0);
    $('#showingEnd').text(endIndex);
    $('#totalRecords').text(filteredContracts.length);
    
    if (totalPages <= 1) return;
    
    // Previous button
    $pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            $pagination.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            $pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    // Next button
    $pagination.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// Change page
function changePage(page) {
    const totalPages = Math.ceil(filteredContracts.length / itemsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        displayContracts();
    }
}

// Utility functions
function getStatusClass(status) {
    const statusClasses = {
        'active': 'bg-success',
        'pending': 'bg-warning',
        'expired': 'bg-danger',
        'draft': 'bg-secondary',
        'terminated': 'bg-dark'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getStatusText(status) {
    const statusTexts = {
        'active': 'Active',
        'pending': 'Pending',
        'expired': 'Expired',
        'draft': 'Draft',
        'terminated': 'Terminated'
    };
    return statusTexts[status] || 'Unknown';
}

function getTypeText(type) {
    const typeTexts = {
        'service': 'Service Agreement',
        'maintenance': 'Maintenance',
        'development': 'Development',
        'consulting': 'Consulting'
    };
    return typeTexts[type] || 'Other';
}

function calculateDuration(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 30) {
        return `${diffDays} days`;
    } else if (diffDays < 365) {
        const months = Math.floor(diffDays / 30);
        return `${months} month${months > 1 ? 's' : ''}`;
    } else {
        const years = Math.floor(diffDays / 365);
        const remainingMonths = Math.floor((diffDays % 365) / 30);
        let duration = `${years} year${years > 1 ? 's' : ''}`;
        if (remainingMonths > 0) {
            duration += ` ${remainingMonths} month${remainingMonths > 1 ? 's' : ''}`;
        }
        return duration;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatNumber(number) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}

function showLoading() {
    $('#loadingState').show();
    $('#contractsTable').closest('.contracts-card').hide();
    $('#emptyState').hide();
}

function hideLoading() {
    $('#loadingState').hide();
    $('#contractsTable').closest('.contracts-card').show();
}

function showEmptyState() {
    $('#contractsTable').closest('.contracts-card').hide();
    $('#emptyState').show();
}

function hideEmptyState() {
    $('#contractsTable').closest('.contracts-card').show();
    $('#emptyState').hide();
}

function showAlert(message, type = 'info') {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of main content
    $('.main-content').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function logout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('userData');
    window.location.href = 'login.html';
}