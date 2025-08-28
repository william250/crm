// CRM Pipeline Management
$(document).ready(function() {
    // Initialize page
    initializePage();
    loadPipeline();
    
    // Event listeners
    $('#addDealForm').on('submit', handleAddDeal);
    $('#editDealForm').on('submit', handleEditDeal);
    $('#logoutBtn').on('click', handleLogout);
});

// Global variables
let allDeals = [];
const pipelineStages = {
    'lead': { name: 'Lead', color: '#6c757d' },
    'qualified': { name: 'Qualified', color: '#17a2b8' },
    'proposal': { name: 'Proposal', color: '#ffc107' },
    'negotiation': { name: 'Negotiation', color: '#fd7e14' },
    'closed-won': { name: 'Closed Won', color: '#28a745' },
    'closed-lost': { name: 'Closed Lost', color: '#dc3545' }
};

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

// Load pipeline data from API
async function loadPipeline() {
    try {
        showLoading();
        
        // Mock data for demonstration
        const mockDeals = [
            {
                id: 1,
                title: 'Website Redesign Project',
                company: 'Acme Corp',
                contact: 'John Doe',
                value: 25000,
                stage: 'qualified',
                priority: 'high',
                probability: 75,
                close_date: '2024-02-15',
                notes: 'Large enterprise client, high potential',
                created_at: '2024-01-01',
                updated_at: '2024-01-15'
            },
            {
                id: 2,
                title: 'Mobile App Development',
                company: 'TechCorp Solutions',
                contact: 'Jane Smith',
                value: 45000,
                stage: 'proposal',
                priority: 'high',
                probability: 60,
                close_date: '2024-02-28',
                notes: 'iOS and Android app development',
                created_at: '2024-01-05',
                updated_at: '2024-01-14'
            },
            {
                id: 3,
                title: 'E-commerce Platform',
                company: 'Startup Inc',
                contact: 'Mike Johnson',
                value: 35000,
                stage: 'negotiation',
                priority: 'medium',
                probability: 80,
                close_date: '2024-02-10',
                notes: 'Custom e-commerce solution',
                created_at: '2024-01-08',
                updated_at: '2024-01-16'
            },
            {
                id: 4,
                title: 'CRM Integration',
                company: 'Business Solutions Ltd',
                contact: 'Sarah Wilson',
                value: 15000,
                stage: 'lead',
                priority: 'low',
                probability: 25,
                close_date: '2024-03-15',
                notes: 'Initial inquiry, needs qualification',
                created_at: '2024-01-12',
                updated_at: '2024-01-12'
            },
            {
                id: 5,
                title: 'Digital Marketing Campaign',
                company: 'Marketing Pro',
                contact: 'David Brown',
                value: 8000,
                stage: 'closed-won',
                priority: 'medium',
                probability: 100,
                close_date: '2024-01-20',
                notes: 'Successfully closed deal',
                created_at: '2024-01-01',
                updated_at: '2024-01-20'
            }
        ];
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        allDeals = mockDeals;
        
        displayPipeline();
        updateStatistics();
        hideLoading();
        
    } catch (error) {
        console.error('Error loading pipeline:', error);
        showError('Failed to load pipeline data. Please try again.');
        hideLoading();
    }
}

// Display pipeline stages and deals
function displayPipeline() {
    const container = $('#pipelineContainer');
    container.empty();
    
    // Create columns for each stage
    Object.keys(pipelineStages).forEach(stageKey => {
        const stage = pipelineStages[stageKey];
        const stageDeals = allDeals.filter(deal => deal.stage === stageKey);
        const stageValue = stageDeals.reduce((sum, deal) => sum + deal.value, 0);
        
        const stageHtml = `
            <div class="col-md-2 mb-4">
                <div class="pipeline-stage">
                    <div class="pipeline-stage-header" style="background: ${stage.color};">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${stage.name}</span>
                            <span class="stage-total">${stageDeals.length} deals</span>
                        </div>
                        <div class="stage-total mt-1">$${formatNumber(stageValue)}</div>
                    </div>
                    <div class="p-3" id="stage-${stageKey}">
                        ${stageDeals.length === 0 ? 
                            '<div class="empty-stage"><i class="fas fa-inbox fa-2x mb-2"></i><br>No deals</div>' :
                            stageDeals.map(deal => createDealCard(deal)).join('')
                        }
                    </div>
                </div>
            </div>
        `;
        
        container.append(stageHtml);
    });
}

// Create deal card HTML
function createDealCard(deal) {
    const priorityClass = `priority-${deal.priority}`;
    const daysUntilClose = deal.close_date ? getDaysUntilDate(deal.close_date) : null;
    
    return `
        <div class="deal-card ${priorityClass}" onclick="viewDeal(${deal.id})">
            <div class="deal-company">${deal.company}</div>
            <div class="deal-title mb-2">${deal.title}</div>
            <div class="deal-value mb-2">$${formatNumber(deal.value)}</div>
            ${deal.contact ? `<div class="deal-contact mb-1"><i class="fas fa-user me-1"></i>${deal.contact}</div>` : ''}
            <div class="d-flex justify-content-between align-items-center">
                <div class="deal-date">
                    ${deal.probability}% probability
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); editDeal(${deal.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); deleteDeal(${deal.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            ${daysUntilClose !== null ? `
                <div class="deal-date mt-2">
                    <i class="fas fa-calendar me-1"></i>
                    ${daysUntilClose > 0 ? `${daysUntilClose} days left` : 
                      daysUntilClose === 0 ? 'Due today' : 
                      `${Math.abs(daysUntilClose)} days overdue`}
                </div>
            ` : ''}
        </div>
    `;
}

// Update pipeline statistics
function updateStatistics() {
    const totalDeals = allDeals.length;
    const totalValue = allDeals.reduce((sum, deal) => sum + deal.value, 0);
    const avgDealSize = totalDeals > 0 ? totalValue / totalDeals : 0;
    
    // Calculate win rate (closed-won deals vs all closed deals)
    const closedDeals = allDeals.filter(deal => deal.stage === 'closed-won' || deal.stage === 'closed-lost');
    const wonDeals = allDeals.filter(deal => deal.stage === 'closed-won');
    const winRate = closedDeals.length > 0 ? (wonDeals.length / closedDeals.length) * 100 : 0;
    
    $('#totalDeals').text(totalDeals);
    $('#totalValue').text('$' + formatNumber(totalValue));
    $('#avgDealSize').text('$' + formatNumber(avgDealSize));
    $('#winRate').text(Math.round(winRate) + '%');
}

// Handle add deal form submission
async function handleAddDeal(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const dealData = Object.fromEntries(formData.entries());
        
        // Convert value to number
        dealData.value = parseFloat(dealData.value);
        dealData.probability = parseInt(dealData.probability);
        
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Add to local data (in real app, this would be handled by API response)
        const newDeal = {
            id: Date.now(),
            ...dealData,
            created_at: new Date().toISOString().split('T')[0],
            updated_at: new Date().toISOString().split('T')[0]
        };
        
        allDeals.push(newDeal);
        
        // Close modal and refresh display
        $('#addDealModal').modal('hide');
        $('#addDealForm')[0].reset();
        displayPipeline();
        updateStatistics();
        
        showAlert('success', 'Deal added successfully!');
        
    } catch (error) {
        console.error('Error adding deal:', error);
        showAlert('danger', 'Failed to add deal. Please try again.');
    }
}

// Handle edit deal form submission
async function handleEditDeal(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const dealData = Object.fromEntries(formData.entries());
        const dealId = parseInt(dealData.deal_id);
        
        // Convert value to number
        dealData.value = parseFloat(dealData.value);
        dealData.probability = parseInt(dealData.probability);
        
        // Mock API call
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Update local data
        const dealIndex = allDeals.findIndex(d => d.id === dealId);
        if (dealIndex !== -1) {
            allDeals[dealIndex] = { 
                ...allDeals[dealIndex], 
                ...dealData,
                updated_at: new Date().toISOString().split('T')[0]
            };
        }
        
        // Close modal and refresh display
        $('#editDealModal').modal('hide');
        displayPipeline();
        updateStatistics();
        
        showAlert('success', 'Deal updated successfully!');
        
    } catch (error) {
        console.error('Error updating deal:', error);
        showAlert('danger', 'Failed to update deal. Please try again.');
    }
}

// Edit deal
function editDeal(dealId) {
    const deal = allDeals.find(d => d.id === dealId);
    if (!deal) return;
    
    // Populate form
    const form = $('#editDealForm');
    form.find('[name="deal_id"]').val(deal.id);
    form.find('[name="title"]').val(deal.title);
    form.find('[name="value"]').val(deal.value);
    form.find('[name="company"]').val(deal.company);
    form.find('[name="contact"]').val(deal.contact || '');
    form.find('[name="stage"]').val(deal.stage);
    form.find('[name="priority"]').val(deal.priority);
    form.find('[name="close_date"]').val(deal.close_date || '');
    form.find('[name="probability"]').val(deal.probability || 50);
    form.find('[name="notes"]').val(deal.notes || '');
    
    // Show modal
    $('#editDealModal').modal('show');
}

// View deal details
function viewDeal(dealId) {
    const deal = allDeals.find(d => d.id === dealId);
    if (!deal) return;
    
    // For now, just show an alert with deal info
    // In a real app, this would open a detailed view modal
    const closeDate = deal.close_date ? formatDate(deal.close_date) : 'Not set';
    alert(`Deal Details:\n\nTitle: ${deal.title}\nCompany: ${deal.company}\nValue: $${formatNumber(deal.value)}\nStage: ${pipelineStages[deal.stage].name}\nProbability: ${deal.probability}%\nClose Date: ${closeDate}\nNotes: ${deal.notes || 'No notes'}`);
}

// Delete deal
function deleteDeal(dealId) {
    const deal = allDeals.find(d => d.id === dealId);
    if (!deal) return;
    
    if (confirm(`Are you sure you want to delete the deal "${deal.title}"?`)) {
        // Remove from local data
        allDeals = allDeals.filter(d => d.id !== dealId);
        
        displayPipeline();
        updateStatistics();
        showAlert('success', 'Deal deleted successfully!');
    }
}

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('en-US').format(num);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getDaysUntilDate(dateString) {
    if (!dateString) return null;
    const today = new Date();
    const targetDate = new Date(dateString);
    const diffTime = targetDate - today;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

// Show loading state
function showLoading() {
    $('#loadingState').show();
    $('#pipelineContainer').hide();
}

// Hide loading state
function hideLoading() {
    $('#loadingState').hide();
    $('#pipelineContainer').show();
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