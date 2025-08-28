/**
 * Dashboard JavaScript
 * Handles dashboard functionality, charts, and data loading
 */

// Global variables
let leadPipelineChart = null;
let leadSourcesChart = null;
let dashboardData = {};

// Initialize dashboard when DOM is loaded
$(document).ready(function() {
    initializeDashboard();
});

/**
 * Initialize dashboard
 */
function initializeDashboard() {
    // Check authentication
    if (!isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Load user info
    loadUserInfo();
    
    // Load dashboard data
    loadDashboard();
    
    // Setup event listeners
    setupEventListeners();
    
    // Setup axios interceptors
    setupAxiosInterceptors();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Refresh button
    $('#refreshBtn').on('click', function() {
        loadDashboard();
    });
    
    // Logout button
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        logout();
    });
}

/**
 * Load dashboard data
 */
function loadDashboard() {
    showLoading();
    
    // Load all dashboard data
    Promise.all([
        loadDashboardStats(),
        loadRecentLeads(),
        loadUpcomingAppointments(),
        loadPipelineData(),
        loadLeadSources()
    ]).then(() => {
        hideLoading();
        showDashboard();
    }).catch(error => {
        console.error('Error loading dashboard:', error);
        hideLoading();
        showError();
    });
}

/**
 * Load dashboard statistics
 */
function loadDashboardStats() {
    return axios.get('/api/crm/dashboard')
        .then(response => {
            if (response.data.success) {
                const stats = response.data.data;
                dashboardData.stats = stats;
                
                // Update stat cards
                $('#totalLeads').text(stats.total_leads || 0);
                $('#convertedLeads').text(stats.converted_leads || 0);
                $('#todayAppointments').text(stats.today_appointments || 0);
                $('#pendingTasks').text(stats.pending_tasks || 0);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard stats:', error);
            throw error;
        });
}

/**
 * Load recent leads
 */
function loadRecentLeads() {
    return axios.get('/api/crm/leads/recent')
        .then(response => {
            if (response.data.success) {
                const leads = response.data.data;
                dashboardData.recentLeads = leads;
                renderRecentLeads(leads);
            }
        })
        .catch(error => {
            console.error('Error loading recent leads:', error);
            $('#recentLeadsList').html('<div class="text-center text-muted py-3">Error loading recent leads</div>');
        });
}

/**
 * Load upcoming appointments
 */
function loadUpcomingAppointments() {
    return axios.get('/api/scheduling/appointments/upcoming')
        .then(response => {
            if (response.data.success) {
                const appointments = response.data.data;
                dashboardData.upcomingAppointments = appointments;
                renderUpcomingAppointments(appointments);
            }
        })
        .catch(error => {
            console.error('Error loading upcoming appointments:', error);
            $('#upcomingAppointmentsList').html('<div class="text-center text-muted py-3">Error loading appointments</div>');
        });
}

/**
 * Load pipeline data for chart
 */
function loadPipelineData() {
    return axios.get('/api/crm/pipeline')
        .then(response => {
            if (response.data.success) {
                const pipeline = response.data.data;
                dashboardData.pipeline = pipeline;
                renderPipelineChart(pipeline);
            }
        })
        .catch(error => {
            console.error('Error loading pipeline data:', error);
        });
}

/**
 * Load lead sources for chart
 */
function loadLeadSources() {
    return axios.get('/api/crm/leads/by-source')
        .then(response => {
            if (response.data.success) {
                const sources = response.data.data;
                dashboardData.leadSources = sources;
                renderLeadSourcesChart(sources);
            }
        })
        .catch(error => {
            console.error('Error loading lead sources:', error);
        });
}

/**
 * Render recent leads list
 */
function renderRecentLeads(leads) {
    const container = $('#recentLeadsList');
    
    if (!leads || leads.length === 0) {
        container.html('<div class="text-center text-muted py-3">No recent leads</div>');
        return;
    }
    
    let html = '';
    leads.forEach(lead => {
        const statusBadge = getStatusBadge(lead.status);
        const timeAgo = getTimeAgo(lead.created_at);
        
        html += `
            <div class="d-flex align-items-center py-2 border-bottom">
                <div class="flex-grow-1">
                    <div class="fw-bold">${escapeHtml(lead.name)}</div>
                    <div class="text-muted small">${escapeHtml(lead.email)}</div>
                </div>
                <div class="text-end">
                    ${statusBadge}
                    <div class="text-muted small">${timeAgo}</div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

/**
 * Render upcoming appointments list
 */
function renderUpcomingAppointments(appointments) {
    const container = $('#upcomingAppointmentsList');
    
    if (!appointments || appointments.length === 0) {
        container.html('<div class="text-center text-muted py-3">No upcoming appointments</div>');
        return;
    }
    
    let html = '';
    appointments.forEach(appointment => {
        const statusBadge = getAppointmentStatusBadge(appointment.status);
        const dateTime = formatDateTime(appointment.appointment_date, appointment.appointment_time);
        
        html += `
            <div class="d-flex align-items-center py-2 border-bottom">
                <div class="flex-grow-1">
                    <div class="fw-bold">${escapeHtml(appointment.title)}</div>
                    <div class="text-muted small">${escapeHtml(appointment.client_name || 'No client')}</div>
                </div>
                <div class="text-end">
                    ${statusBadge}
                    <div class="text-muted small">${dateTime}</div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

/**
 * Render pipeline chart
 */
function renderPipelineChart(pipeline) {
    const ctx = document.getElementById('leadPipelineChart');
    
    if (leadPipelineChart) {
        leadPipelineChart.destroy();
    }
    
    const labels = pipeline.map(item => item.status);
    const data = pipeline.map(item => item.count);
    
    leadPipelineChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Leads',
                data: data,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Render lead sources chart
 */
function renderLeadSourcesChart(sources) {
    const ctx = document.getElementById('leadSourcesChart');
    
    if (leadSourcesChart) {
        leadSourcesChart.destroy();
    }
    
    const labels = sources.map(item => item.source);
    const data = sources.map(item => item.count);
    
    leadSourcesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

/**
 * Load user info and update navbar
 */
function loadUserInfo() {
    axios.get('/api/auth/profile')
        .then(response => {
            if (response.data.success) {
                const user = response.data.data;
                $('#userName').text(user.name);
                
                // Show users link for admin/manager
                if (user.role === 'admin' || user.role === 'manager') {
                    $('#usersLink').show();
                }
            }
        })
        .catch(error => {
            console.error('Error loading user info:', error);
        });
}

/**
 * Show loading state
 */
function showLoading() {
    $('#loadingState').show();
    $('#dashboardContent').hide();
    $('#errorState').hide();
}

/**
 * Hide loading state and show dashboard
 */
function hideLoading() {
    $('#loadingState').hide();
}

/**
 * Show dashboard content
 */
function showDashboard() {
    $('#dashboardContent').show();
    $('#errorState').hide();
}

/**
 * Show error state
 */
function showError() {
    $('#loadingState').hide();
    $('#dashboardContent').hide();
    $('#errorState').show();
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const badges = {
        'new': 'badge bg-primary',
        'contacted': 'badge bg-info',
        'qualified': 'badge bg-warning',
        'proposal': 'badge bg-secondary',
        'negotiation': 'badge bg-warning',
        'closed_won': 'badge bg-success',
        'closed_lost': 'badge bg-danger'
    };
    
    const badgeClass = badges[status] || 'badge bg-secondary';
    return `<span class="${badgeClass}">${status.replace('_', ' ').toUpperCase()}</span>`;
}

/**
 * Get appointment status badge HTML
 */
function getAppointmentStatusBadge(status) {
    const badges = {
        'scheduled': 'badge bg-primary',
        'confirmed': 'badge bg-success',
        'completed': 'badge bg-info',
        'cancelled': 'badge bg-danger',
        'no_show': 'badge bg-warning'
    };
    
    const badgeClass = badges[status] || 'badge bg-secondary';
    return `<span class="${badgeClass}">${status.replace('_', ' ').toUpperCase()}</span>`;
}

/**
 * Format date and time
 */
function formatDateTime(date, time) {
    try {
        const dateObj = new Date(`${date} ${time}`);
        return dateObj.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    } catch (error) {
        return `${date} ${time}`;
    }
}

/**
 * Get time ago string
 */
function getTimeAgo(dateString) {
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes}m ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours}h ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days}d ago`;
        }
    } catch (error) {
        return dateString;
    }
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
    return localStorage.getItem('token') !== null;
}

/**
 * Logout user
 */
function logout() {
    // Call logout API
    axios.post('/api/auth/logout')
        .then(() => {
            // Clear local storage
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            
            // Redirect to login
            window.location.href = 'login.html';
        })
        .catch(error => {
            console.error('Logout error:', error);
            // Clear local storage anyway
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        });
}

/**
 * Setup axios interceptors for authentication
 */
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
        error => {
            return Promise.reject(error);
        }
    );
    
    // Response interceptor to handle auth errors
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = 'login.html';
            }
            return Promise.reject(error);
        }
    );
}

// Export functions for global access
window.loadDashboard = loadDashboard;
window.logout = logout;