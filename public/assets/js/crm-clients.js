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
        window.location.href = 'login.html';
        return;
    }
    
    // Load user info
    loadUserInfo();
    
    // Setup axios interceptors
    setupAxiosInterceptors();
}

// Check if user is authenticated
function isAuthenticated() {
    const token = localStorage.getItem('authToken');
    const user = localStorage.getItem('userData');
    return token && user;
}

// Load user information
function loadUserInfo() {
    try {
        const userData = JSON.parse(localStorage.getItem('userData'));
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
            const token = localStorage.getItem('authToken');
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
                localStorage.removeItem('authToken');
                localStorage.removeItem('userData');
                window.location.href = 'login.html';
            }
            return Promise.reject(error);
        }
    );
}

// Load clients from API
async function loadClients() {
    try {
        showLoading();
        
        // Mock data for demonstration
        const mockClients = [
            {
                id: 1,
                first_name: 'John',
                last_name: 'Doe',
                email: 'john.doe@example.com',
                phone: '+1 (555) 123-4567',
                company: 'Acme Corp',
                status: 'active',
                address: '123 Main St, New York, NY 10001',
                notes: 'Important client, handles multiple projects',
                last_contact: '2024-01-15',
                created_at: '2024-01-01'
            },
            {
                id: 2,
                first_name: 'Jane',
                last_name: 'Smith',
                email: 'jane.smith@techcorp.com',
                phone: '+1 (555) 987-6543',
                company: 'TechCorp Solutions',
                status: 'active',
                address: '456 Business Ave, San Francisco, CA 94105',
                notes: 'Tech startup, growing rapidly',
                last_contact: '2024-01-14',
                created_at: '2024-01-05'
            },
            {
                id: 3,
                first_name: 'Mike',
                last_name: 'Johnson',
                email: 'mike.j@startup.io',
                phone: '+1 (555) 456-7890',
                company: 'Startup Inc',
                status: 'prospect',
                address: '789 Innovation Dr, Austin, TX 78701',
                notes: 'Potential high-value client',
                last_contact: '2024-01-10',
                created_at: '2024-01-08'
            }
        ];
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        allClients = mockClients;
        filteredClients = [...allClients];
        
        displayClients();
        hideLoading();
        
    } catch (error) {
        console.error('Error loading clients:', error);
        showError('Failed to load clients. Please try again.');
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
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            ${client.first_name.charAt(0)}${client.last_name.charAt(0)}
                        </div>
                        <div>
                            <div class="fw-bold">${client.first_name} ${client.last_name}</div>
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
                <td>${formatDate(client.last_contact)}</td>
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
        const matchesSearch = !searchTerm || 
            client.first_name.toLowerCase().includes(searchTerm) ||
            client.last_name.toLowerCase().includes(searchTerm) ||
            client.email.toLowerCase().includes(searchTerm) ||
            (client.company && client.company.toLowerCase().includes(searchTerm));
        
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
        
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Add to local data (in real app, this would be handled by API response)
        const newClient = {
            id: Date.now(),
            ...clientData,
            last_contact: new Date().toISOString().split('T')[0],
            created_at: new Date().toISOString().split('T')[0]
        };
        
        allClients.unshift(newClient);
        filteredClients = [...allClients];
        
        // Close modal and refresh display
        $('#addClientModal').modal('hide');
        $('#addClientForm')[0].reset();
        displayClients();
        
        showAlert('success', 'Client added successfully!');
        
    } catch (error) {
        console.error('Error adding client:', error);
        showAlert('danger', 'Failed to add client. Please try again.');
    }
}

// Handle edit client form submission
async function handleEditClient(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const clientData = Object.fromEntries(formData.entries());
        const clientId = parseInt(clientData.client_id);
        
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Update local data
        const clientIndex = allClients.findIndex(c => c.id === clientId);
        if (clientIndex !== -1) {
            allClients[clientIndex] = { ...allClients[clientIndex], ...clientData };
            filteredClients = [...allClients];
        }
        
        // Close modal and refresh display
        $('#editClientModal').modal('hide');
        displayClients();
        
        showAlert('success', 'Client updated successfully!');
        
    } catch (error) {
        console.error('Error updating client:', error);
        showAlert('danger', 'Failed to update client. Please try again.');
    }
}

// Edit client
function editClient(clientId) {
    const client = allClients.find(c => c.id === clientId);
    if (!client) return;
    
    // Populate form
    const form = $('#editClientForm');
    form.find('[name="client_id"]').val(client.id);
    form.find('[name="first_name"]').val(client.first_name);
    form.find('[name="last_name"]').val(client.last_name);
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
function deleteClient(clientId) {
    const client = allClients.find(c => c.id === clientId);
    if (!client) return;
    
    if (confirm(`Are you sure you want to delete ${client.first_name} ${client.last_name}?`)) {
        // Remove from local data
        allClients = allClients.filter(c => c.id !== clientId);
        filteredClients = filteredClients.filter(c => c.id !== clientId);
        
        displayClients();
        showAlert('success', 'Client deleted successfully!');
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
        localStorage.removeItem('authToken');
        localStorage.removeItem('userData');
        window.location.href = 'login.html';
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