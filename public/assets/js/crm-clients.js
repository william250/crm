// CRM Clients Management
$(document).ready(function() {
    // Initialize page
    initializePage();
    loadClients();
    
    // Event listeners
    $('#searchInput').on('input', debounce(filterClients, 300));
    $('#statusFilter, #sortBy').on('change', filterClients);
    $('#resetFilters').on('click', resetFilters);
    $('#addClientForm').on('submit', handleAddClient);
    $('#editClientForm').on('submit', handleEditClient);
    $('#logoutBtn').on('click', handleLogout);
});

// Global variables
let allClients = [];
let filteredClients = [];
let currentPage = 1;
const itemsPerPage = 10;

// Initialize page
function initializePage() {
    // Check authentication
    if (!isAuthenticated()) {
        window.location.href = 'login.php';
        return;
    }
    
    // Load user info
    loadUserInfo();
    
    // Setup axios interceptors
    setupAxiosInterceptors();
}

// Check if user is authenticated
function isAuthenticated() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    return token && user;
}

// Load user information
function loadUserInfo() {
    try {
        const userData = JSON.parse(localStorage.getItem('user'));
        if (userData && userData.name) {
            $('#userName').text(userData.name);
            
            // Show admin links if user is admin
            if (userData.role === 'admin') {
                $('#usersLink').show();
            }
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Setup axios interceptors
function setupAxiosInterceptors() {
    // Request interceptor to add auth token
    axios.interceptors.request.use(
        config => {
            const token = localStorage.getItem('token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
            return config;
        },
        error => Promise.reject(error)
    );
    
    // Response interceptor to handle auth errors
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = 'login.php';
            }
            return Promise.reject(error);
        }
    );
}

// Load clients from API
async function loadClients() {
    try {
        showLoading();
        
        const response = await axios.get('/api/crm/clients');
        
        if (response.data.success) {
            allClients = response.data.data || [];
            filteredClients = [...allClients];
            displayClients();
        } else {
            showError(response.data.message || 'Failed to load clients');
        }
        
        hideLoading();
        
    } catch (error) {
        console.error('Error loading clients:', error);
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = 'login.php';
        } else {
            showError('Failed to load clients. Please try again.');
        }
        hideLoading();
    }
}

// Display clients in table
function displayClients() {
    const tbody = $('#clientsTableBody');
    tbody.empty();
    
    if (filteredClients.length === 0) {
        showEmptyState();
        return;
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredClients.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const clientsToShow = filteredClients.slice(startIndex, endIndex);
    
    // Generate table rows
    clientsToShow.forEach(client => {
        // Handle both API format (name) and legacy format (first_name, last_name)
        const clientName = client.name || `${client.first_name || ''} ${client.last_name || ''}`.trim();
        const initials = clientName.split(' ').map(n => n.charAt(0)).join('').substring(0, 2).toUpperCase();
        
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            ${initials}
                        </div>
                        <div>
                            <div class="fw-bold">${clientName}</div>
                        </div>
                    </div>
                </td>
                <td>${client.company || '-'}</td>
                <td>${client.email}</td>
                <td>${client.phone || '-'}</td>
                <td>
                    <span class="badge bg-${getStatusColor(client.status)}">
                        ${client.status.charAt(0).toUpperCase() + client.status.slice(1)}
                    </span>
                </td>
                <td>${formatDate(client.last_contact || client.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editClient(${client.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="viewClient(${client.id})" title="View">
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
    
    // Update pagination
    updatePagination(totalPages);
    
    // Show table
    $('#clientsTable').show();
    $('#emptyState').hide();
}

// Get status color for badge
function getStatusColor(status) {
    switch (status) {
        case 'active': return 'success';
        case 'inactive': return 'secondary';
        case 'prospect': return 'warning';
        default: return 'secondary';
    }
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Update pagination
function updatePagination(totalPages) {
    const pagination = $('#pagination');
    pagination.empty();
    
    if (totalPages <= 1) return;
    
    // Previous button
    pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            pagination.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    // Next button
    pagination.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// Change page
function changePage(page) {
    const totalPages = Math.ceil(filteredClients.length / itemsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        displayClients();
    }
}

// Filter clients
function filterClients() {
    const searchTerm = $('#searchInput').val().toLowerCase();
    const statusFilter = $('#statusFilter').val();
    const sortBy = $('#sortBy').val();
    
    // Filter clients
    filteredClients = allClients.filter(client => {
        // Handle both API format (name) and legacy format (first_name, last_name)
        const clientName = client.name || `${client.first_name || ''} ${client.last_name || ''}`.trim();
        
        const matchesSearch = !searchTerm || 
            clientName.toLowerCase().includes(searchTerm) ||
            client.email.toLowerCase().includes(searchTerm) ||
            (client.company && client.company.toLowerCase().includes(searchTerm)) ||
            (client.phone && client.phone.includes(searchTerm));
        
        const matchesStatus = !statusFilter || client.status === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    // Sort clients
    filteredClients.sort((a, b) => {
        switch (sortBy) {
            case 'name':
                return `${a.first_name} ${a.last_name}`.localeCompare(`${b.first_name} ${b.last_name}`);
            case 'company':
                return (a.company || '').localeCompare(b.company || '');
            case 'created_at':
                return new Date(b.created_at) - new Date(a.created_at);
            case 'last_contact':
                return new Date(b.last_contact) - new Date(a.last_contact);
            default:
                return 0;
        }
    });
    
    // Reset to first page
    currentPage = 1;
    
    // Display filtered results
    displayClients();
}

// Reset filters
function resetFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('');
    $('#sortBy').val('name');
    filteredClients = [...allClients];
    currentPage = 1;
    displayClients();
}

// Handle add client form submission
async function handleAddClient(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const clientData = Object.fromEntries(formData.entries());
        
        // Combine first_name and last_name into name for API
        const apiData = {
            name: `${clientData.first_name} ${clientData.last_name}`,
            email: clientData.email,
            phone: clientData.phone,
            company: clientData.company || '',
            address: clientData.address || '',
            notes: clientData.notes || '',
            status: clientData.status || 'active'
        };
        
        const response = await axios.post('/api/crm/clients', apiData);
        
        if (response.data.success) {
            // Close modal and refresh display
            $('#addClientModal').modal('hide');
            $('#addClientForm')[0].reset();
            
            // Reload clients to get updated data
            await loadClients();
            
            showAlert('success', 'Client added successfully!');
        } else {
            showAlert('danger', response.data.message || 'Failed to add client');
        }
        
    } catch (error) {
        console.error('Error adding client:', error);
        if (error.response && error.response.data && error.response.data.message) {
            showAlert('danger', error.response.data.message);
        } else {
            showAlert('danger', 'Failed to add client. Please try again.');
        }
    }
}

// Handle edit client form submission
async function handleEditClient(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const clientData = Object.fromEntries(formData.entries());
        const clientId = parseInt(clientData.client_id);
        
        // Combine first_name and last_name into name for API
        const apiData = {
            name: `${clientData.first_name} ${clientData.last_name}`,
            email: clientData.email,
            phone: clientData.phone,
            company: clientData.company || '',
            address: clientData.address || '',
            notes: clientData.notes || '',
            status: clientData.status || 'active'
        };
        
        const response = await axios.put(`/api/crm/clients/${clientId}`, apiData);
        
        if (response.data.success) {
            // Close modal and refresh display
            $('#editClientModal').modal('hide');
            
            // Reload clients to get updated data
            await loadClients();
            
            showAlert('success', 'Client updated successfully!');
        } else {
            showAlert('danger', response.data.message || 'Failed to update client');
        }
        
    } catch (error) {
        console.error('Error updating client:', error);
        if (error.response && error.response.data && error.response.data.message) {
            showAlert('danger', error.response.data.message);
        } else {
            showAlert('danger', 'Failed to update client. Please try again.');
        }
    }
}

// Edit client
function editClient(clientId) {
    const client = allClients.find(c => c.id === clientId);
    if (!client) return;
    
    // Handle both API format (name) and legacy format (first_name, last_name)
    let firstName = client.first_name || '';
    let lastName = client.last_name || '';
    
    if (client.name && !firstName && !lastName) {
        const nameParts = client.name.split(' ');
        firstName = nameParts[0] || '';
        lastName = nameParts.slice(1).join(' ') || '';
    }
    
    // Populate form
    const form = $('#editClientForm');
    form.find('[name="client_id"]').val(client.id);
    form.find('[name="first_name"]').val(firstName);
    form.find('[name="last_name"]').val(lastName);
    form.find('[name="email"]').val(client.email);
    form.find('[name="phone"]').val(client.phone || '');
    form.find('[name="company"]').val(client.company || '');
    form.find('[name="status"]').val(client.status);
    form.find('[name="address"]').val(client.address || '');
    form.find('[name="notes"]').val(client.notes || '');
    
    // Show modal
    $('#editClientModal').modal('show');
}

// View client details
function viewClient(clientId) {
    const client = allClients.find(c => c.id === clientId);
    if (!client) return;
    
    // For now, just show an alert with client info
    // In a real app, this would open a detailed view modal
    alert(`Client Details:\n\nName: ${client.first_name} ${client.last_name}\nEmail: ${client.email}\nCompany: ${client.company || 'N/A'}\nStatus: ${client.status}`);
}

// Delete client
async function deleteClient(clientId) {
    const client = allClients.find(c => c.id === clientId);
    if (!client) return;
    
    const clientName = client.name || `${client.first_name || ''} ${client.last_name || ''}`.trim();
    
    if (confirm(`Are you sure you want to delete ${clientName}? This action cannot be undone.`)) {
        try {
            const response = await axios.delete(`/api/crm/clients/${clientId}`);
            
            if (response.data.success) {
                // Reload clients to get updated data
                await loadClients();
                showAlert('success', 'Client deleted successfully!');
            } else {
                showAlert('danger', response.data.message || 'Failed to delete client');
            }
        } catch (error) {
            console.error('Error deleting client:', error);
            if (error.response && error.response.data && error.response.data.message) {
                showAlert('danger', error.response.data.message);
            } else {
                showAlert('danger', 'Failed to delete client. Please try again.');
            }
        }
    }
}

// Show loading state
function showLoading() {
    $('#loadingState').show();
    $('#clientsTable').hide();
    $('#emptyState').hide();
}

// Hide loading state
function hideLoading() {
    $('#loadingState').hide();
}

// Show empty state
function showEmptyState() {
    $('#emptyState').show();
    $('#clientsTable').hide();
}

// Show error message
function showError(message) {
    showAlert('danger', message);
}

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
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

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.php';
    }
}

// Debounce function for search
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