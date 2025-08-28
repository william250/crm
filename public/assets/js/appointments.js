/**
 * Appointments Module - Frontend Logic
 * Handles scheduling, calendar view, and appointment management
 */

// Global variables
let currentAppointments = [];
let currentClients = [];
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};
let currentDate = new Date();
let editingAppointmentId = null;

// Initialize the appointments page
$(document).ready(function() {
    initializeAppointments();
});

function initializeAppointments() {
    // Check authentication
    if (!checkAuth()) {
        window.location.href = 'login.html';
        return;
    }

    // Load user info
    loadUserInfo();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load initial data
    loadClients();
    loadAppointments();
    loadTodaySchedule();
    initializeCalendar();
    
    // Set default date filters
    setDefaultDateFilters();
}

function setupEventListeners() {
    // Tab switching
    $('#schedulingTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#calendar') {
            renderCalendar();
        } else if (target === '#today') {
            loadTodaySchedule();
        }
    });
    
    // Add appointment button
    $('#addAppointmentBtn').on('click', function() {
        openAppointmentModal();
    });
    
    // Save appointment button
    $('#saveAppointmentBtn').on('click', function() {
        saveAppointment();
    });
    
    // Filter changes
    $('#appointmentStatusFilter, #startDateFilter, #endDateFilter').on('change', function() {
        applyFilters();
    });
    
    // Search input
    $('#appointmentSearchInput, #clientSearchInput').on('input', debounce(function() {
        applyFilters();
    }, 300));
    
    // Clear filters
    $('#clearAppointmentFilters').on('click', function() {
        clearFilters();
    });
    
    // Calendar navigation
    $('#prevMonth').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });
    
    $('#nextMonth').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });
    
    $('#currentMonth').on('click', function() {
        currentDate = new Date();
        renderCalendar();
    });
    
    // Logout
    $('#logoutLink').on('click', function(e) {
        e.preventDefault();
        logout();
    });
}

function loadUserInfo() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (user.name) {
        $('#userName').text(user.name);
    }
}

function loadClients() {
    showLoading();
    
    axios.get('/api/clients')
        .then(response => {
            if (response.data.success) {
                currentClients = response.data.data || [];
                populateClientSelect();
            }
        })
        .catch(error => {
            console.error('Error loading clients:', error);
            showAlert('Error loading clients', 'danger');
        })
        .finally(() => {
            hideLoading();
        });
}

function populateClientSelect() {
    const select = $('#appointmentClient');
    select.empty().append('<option value="">Select Client</option>');
    
    currentClients.forEach(client => {
        select.append(`<option value="${client.id}">${escapeHtml(client.name)} - ${escapeHtml(client.company || 'Individual')}</option>`);
    });
}

function loadAppointments(page = 1) {
    showLoading();
    
    const params = {
        page: page,
        limit: 10,
        ...currentFilters
    };
    
    axios.get('/api/appointments', { params })
        .then(response => {
            if (response.data.success) {
                currentAppointments = response.data.data || [];
                totalPages = Math.ceil((response.data.total || 0) / 10);
                currentPage = page;
                
                renderAppointmentsTable();
                renderPagination();
                updateAppointmentsCount();
            }
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            showAlert('Error loading appointments', 'danger');
        })
        .finally(() => {
            hideLoading();
        });
}

function renderAppointmentsTable() {
    const tbody = $('#appointmentsTableBody');
    tbody.empty();
    
    if (currentAppointments.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No appointments found</p>
                    </div>
                </td>
            </tr>
        `);
        return;
    }
    
    currentAppointments.forEach(appointment => {
        const client = currentClients.find(c => c.id == appointment.client_id);
        const clientName = client ? client.name : 'Unknown Client';
        const appointmentDate = new Date(appointment.appointment_date);
        const formattedDate = formatDateTime(appointmentDate);
        
        tbody.append(`
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <span class="avatar-text">${getInitials(clientName)}</span>
                        </div>
                        <div>
                            <div class="fw-medium">${escapeHtml(clientName)}</div>
                            <small class="text-muted">${escapeHtml(client?.company || 'Individual')}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-medium">${formattedDate}</div>
                    <small class="text-muted">${formatTime(appointmentDate)}</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${appointment.duration} min</span>
                </td>
                <td>
                    <span class="badge bg-info">${escapeHtml(appointment.type)}</span>
                </td>
                <td>
                    ${getStatusBadge(appointment.status)}
                </td>
                <td>
                    <span class="text-truncate" style="max-width: 150px; display: inline-block;" title="${escapeHtml(appointment.notes || '')}">
                        ${escapeHtml(appointment.notes || '-')}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editAppointment(${appointment.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="markCompleted(${appointment.id})" title="Mark Completed" ${appointment.status === 'completed' ? 'disabled' : ''}>
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteAppointment(${appointment.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function renderPagination() {
    const pagination = $('#appointmentsPagination');
    pagination.empty();
    
    if (totalPages <= 1) return;
    
    // Previous button
    pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAppointments(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // Page numbers
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        pagination.append(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadAppointments(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    pagination.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAppointments(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

function updateAppointmentsCount() {
    $('#appointmentsCount').text(currentAppointments.length);
}

function applyFilters() {
    currentFilters = {
        status: $('#appointmentStatusFilter').val(),
        start_date: $('#startDateFilter').val(),
        end_date: $('#endDateFilter').val(),
        search: $('#appointmentSearchInput').val(),
        client_search: $('#clientSearchInput').val()
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    loadAppointments(1);
}

function clearFilters() {
    $('#appointmentStatusFilter').val('');
    $('#startDateFilter').val('');
    $('#endDateFilter').val('');
    $('#appointmentSearchInput').val('');
    $('#clientSearchInput').val('');
    
    currentFilters = {};
    loadAppointments(1);
}

function setDefaultDateFilters() {
    const today = new Date();
    const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, today.getDate());
    
    $('#startDateFilter').val(formatDate(today));
    $('#endDateFilter').val(formatDate(nextMonth));
}

function openAppointmentModal(appointment = null) {
    editingAppointmentId = appointment ? appointment.id : null;
    
    if (appointment) {
        $('#appointmentModalTitle').html('<i class="fas fa-edit me-2"></i>Edit Appointment');
        populateAppointmentForm(appointment);
    } else {
        $('#appointmentModalTitle').html('<i class="fas fa-calendar-plus me-2"></i>Add New Appointment');
        clearAppointmentForm();
    }
    
    $('#appointmentModal').modal('show');
}

function populateAppointmentForm(appointment) {
    $('#appointmentClient').val(appointment.client_id);
    $('#appointmentType').val(appointment.type);
    $('#appointmentDate').val(formatDate(new Date(appointment.appointment_date)));
    $('#appointmentTime').val(formatTimeInput(new Date(appointment.appointment_date)));
    $('#appointmentDuration').val(appointment.duration);
    $('#appointmentStatus').val(appointment.status);
    $('#appointmentLocation').val(appointment.location || '');
    $('#appointmentNotes').val(appointment.notes || '');
}

function clearAppointmentForm() {
    $('#appointmentForm')[0].reset();
    $('#appointmentStatus').val('scheduled');
}

function saveAppointment() {
    const formData = {
        client_id: $('#appointmentClient').val(),
        type: $('#appointmentType').val(),
        appointment_date: $('#appointmentDate').val() + ' ' + $('#appointmentTime').val(),
        duration: parseInt($('#appointmentDuration').val()),
        status: $('#appointmentStatus').val(),
        location: $('#appointmentLocation').val(),
        notes: $('#appointmentNotes').val()
    };
    
    // Validate required fields
    if (!formData.client_id || !formData.type || !formData.appointment_date || !formData.duration) {
        showAlert('Please fill in all required fields', 'warning');
        return;
    }
    
    showLoading();
    
    const request = editingAppointmentId
        ? axios.put(`/api/appointments/${editingAppointmentId}`, formData)
        : axios.post('/api/appointments', formData);
    
    request
        .then(response => {
            if (response.data.success) {
                showAlert(
                    editingAppointmentId ? 'Appointment updated successfully' : 'Appointment created successfully',
                    'success'
                );
                $('#appointmentModal').modal('hide');
                loadAppointments(currentPage);
                loadTodaySchedule();
            } else {
                showAlert(response.data.message || 'Error saving appointment', 'danger');
            }
        })
        .catch(error => {
            console.error('Error saving appointment:', error);
            showAlert('Error saving appointment', 'danger');
        })
        .finally(() => {
            hideLoading();
        });
}

function editAppointment(id) {
    const appointment = currentAppointments.find(a => a.id === id);
    if (appointment) {
        openAppointmentModal(appointment);
    }
}

function markCompleted(id) {
    if (confirm('Mark this appointment as completed?')) {
        showLoading();
        
        axios.put(`/api/appointments/${id}`, { status: 'completed' })
            .then(response => {
                if (response.data.success) {
                    showAlert('Appointment marked as completed', 'success');
                    loadAppointments(currentPage);
                    loadTodaySchedule();
                } else {
                    showAlert(response.data.message || 'Error updating appointment', 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating appointment:', error);
                showAlert('Error updating appointment', 'danger');
            })
            .finally(() => {
                hideLoading();
            });
    }
}

function deleteAppointment(id) {
    if (confirm('Are you sure you want to delete this appointment?')) {
        showLoading();
        
        axios.delete(`/api/appointments/${id}`)
            .then(response => {
                if (response.data.success) {
                    showAlert('Appointment deleted successfully', 'success');
                    loadAppointments(currentPage);
                    loadTodaySchedule();
                } else {
                    showAlert(response.data.message || 'Error deleting appointment', 'danger');
                }
            })
            .catch(error => {
                console.error('Error deleting appointment:', error);
                showAlert('Error deleting appointment', 'danger');
            })
            .finally(() => {
                hideLoading();
            });
    }
}

function loadTodaySchedule() {
    const today = formatDate(new Date());
    
    axios.get('/api/appointments/today')
        .then(response => {
            if (response.data.success) {
                renderTodaySchedule(response.data.data || []);
                updateTodayStats(response.data.data || []);
            }
        })
        .catch(error => {
            console.error('Error loading today\'s schedule:', error);
        });
    
    // Load upcoming appointments
    axios.get('/api/appointments/upcoming')
        .then(response => {
            if (response.data.success) {
                renderUpcomingAppointments(response.data.data || []);
            }
        })
        .catch(error => {
            console.error('Error loading upcoming appointments:', error);
        });
}

function renderTodaySchedule(appointments) {
    const container = $('#todayAppointments');
    container.empty();
    
    if (appointments.length === 0) {
        container.append(`
            <div class="empty-state text-center py-4">
                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                <p class="text-muted">No appointments scheduled for today</p>
            </div>
        `);
        return;
    }
    
    appointments.forEach(appointment => {
        const client = currentClients.find(c => c.id == appointment.client_id);
        const clientName = client ? client.name : 'Unknown Client';
        const appointmentTime = new Date(appointment.appointment_date);
        
        container.append(`
            <div class="appointment-item mb-3">
                <div class="d-flex align-items-center">
                    <div class="time-badge me-3">
                        <div class="time">${formatTime(appointmentTime)}</div>
                        <div class="duration">${appointment.duration}m</div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1">${escapeHtml(clientName)}</h6>
                                <p class="mb-1 text-muted">${escapeHtml(appointment.type)}</p>
                                ${appointment.location ? `<small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(appointment.location)}</small>` : ''}
                            </div>
                            <div>
                                ${getStatusBadge(appointment.status)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    });
    
    $('#todayCount').text(appointments.length);
}

function updateTodayStats(appointments) {
    const stats = {
        total: appointments.length,
        completed: appointments.filter(a => a.status === 'completed').length,
        pending: appointments.filter(a => ['scheduled', 'confirmed'].includes(a.status)).length,
        cancelled: appointments.filter(a => ['cancelled', 'no_show'].includes(a.status)).length
    };
    
    $('#todayTotal').text(stats.total);
    $('#todayCompleted').text(stats.completed);
    $('#todayPending').text(stats.pending);
    $('#todayCancelled').text(stats.cancelled);
}

function renderUpcomingAppointments(appointments) {
    const container = $('#upcomingAppointments');
    container.empty();
    
    if (appointments.length === 0) {
        container.append('<p class="text-muted text-center">No upcoming appointments</p>');
        return;
    }
    
    appointments.slice(0, 5).forEach(appointment => {
        const client = currentClients.find(c => c.id == appointment.client_id);
        const clientName = client ? client.name : 'Unknown Client';
        const appointmentDate = new Date(appointment.appointment_date);
        
        container.append(`
            <div class="upcoming-item mb-2">
                <div class="d-flex align-items-center">
                    <div class="avatar-xs me-2">
                        <span class="avatar-text">${getInitials(clientName)}</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium">${escapeHtml(clientName)}</div>
                        <small class="text-muted">${formatDate(appointmentDate)} at ${formatTime(appointmentDate)}</small>
                    </div>
                </div>
            </div>
        `);
    });
}

function initializeCalendar() {
    renderCalendar();
}

function renderCalendar() {
    const container = $('#calendarContainer');
    const monthText = $('#currentMonthText');
    
    // Update month text
    monthText.text(currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }));
    
    // Generate calendar HTML
    const calendar = generateCalendarHTML(currentDate);
    container.html(calendar);
    
    // Load appointments for the current month
    loadMonthAppointments();
}

function generateCalendarHTML(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    let html = '<div class="calendar-grid">';
    
    // Header
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weekdays.forEach(day => {
        html += `<div class="calendar-header">${day}</div>`;
    });
    
    // Calendar days
    const currentDate = new Date(startDate);
    for (let i = 0; i < 42; i++) {
        const isCurrentMonth = currentDate.getMonth() === month;
        const isToday = currentDate.toDateString() === new Date().toDateString();
        
        html += `
            <div class="calendar-day ${isCurrentMonth ? 'current-month' : 'other-month'} ${isToday ? 'today' : ''}" data-date="${formatDate(currentDate)}">
                <div class="day-number">${currentDate.getDate()}</div>
                <div class="day-appointments" id="appointments-${formatDate(currentDate)}"></div>
            </div>
        `;
        
        currentDate.setDate(currentDate.getDate() + 1);
    }
    
    html += '</div>';
    return html;
}

function loadMonthAppointments() {
    const startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const endDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    
    const params = {
        start_date: formatDate(startDate),
        end_date: formatDate(endDate)
    };
    
    axios.get('/api/appointments', { params })
        .then(response => {
            if (response.data.success) {
                displayCalendarAppointments(response.data.data || []);
            }
        })
        .catch(error => {
            console.error('Error loading month appointments:', error);
        });
}

function displayCalendarAppointments(appointments) {
    // Clear existing appointments
    $('.day-appointments').empty();
    
    appointments.forEach(appointment => {
        const date = formatDate(new Date(appointment.appointment_date));
        const container = $(`#appointments-${date}`);
        
        if (container.length) {
            const client = currentClients.find(c => c.id == appointment.client_id);
            const clientName = client ? client.name : 'Unknown';
            const time = formatTime(new Date(appointment.appointment_date));
            
            container.append(`
                <div class="calendar-appointment ${appointment.status}" title="${escapeHtml(clientName)} - ${time}">
                    <small>${escapeHtml(clientName.split(' ')[0])}</small>
                </div>
            `);
        }
    });
}

// Utility functions
function getStatusBadge(status) {
    const badges = {
        scheduled: '<span class="badge bg-secondary">Scheduled</span>',
        confirmed: '<span class="badge bg-primary">Confirmed</span>',
        completed: '<span class="badge bg-success">Completed</span>',
        cancelled: '<span class="badge bg-danger">Cancelled</span>',
        no_show: '<span class="badge bg-warning">No Show</span>'
    };
    return badges[status] || '<span class="badge bg-light text-dark">Unknown</span>';
}

function formatDateTime(date) {
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatTime(date) {
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

function formatTimeInput(date) {
    return date.toTimeString().slice(0, 5);
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

function showLoading() {
    $('#loadingOverlay').show();
}

function hideLoading() {
    $('#loadingOverlay').hide();
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Add to page
    $('body').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

function checkAuth() {
    const token = localStorage.getItem('token');
    return token && token !== 'null';
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

// Axios interceptors
axios.interceptors.request.use(function(config) {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

axios.interceptors.response.use(
    function(response) {
        return response;
    },
    function(error) {
        if (error.response && error.response.status === 401) {
            logout();
        }
        return Promise.reject(error);
    }
);