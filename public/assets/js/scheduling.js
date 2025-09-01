// Scheduling JavaScript
// Initialize with January 2025 where we have test data
let currentDate = new Date('2025-01-29');
let currentView = 'month';
let appointments = [];
let clients = [];
let currentPage = 1;
let totalPages = 1;

$(document).ready(function() {
    // Initialize page
    initializePage();
    
    // Load initial data
    loadClients();
    loadAppointments();
    
    // Set up event listeners
    setupEventListeners();
    
    // Initialize calendar
    updateCalendarView();
});

function initializePage() {
    // Check authentication (temporarily disabled for testing)
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    // if (!token) {
    //     window.location.href = 'login.php';
    //     return;
    // }
    
    // Set up axios defaults (only if token exists)
    if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
    
    // Load user info
    loadUserInfo();
    
    // Set default date for new appointments
    const today = new Date().toISOString().split('T')[0];
    $('#appointmentDate').val(today);
}

function loadUserInfo() {
    const userData = JSON.parse(localStorage.getItem('userData') || sessionStorage.getItem('userData') || '{}');
    if (userData.name) {
        $('#userName').text(userData.name);
    }
}

function loadAppointments() {
    showLoading();
    
    const params = {
        view: currentView,
        date: currentDate.toISOString().split('T')[0]
    };
    
    if (currentView === 'list') {
        params.page = currentPage;
        params.limit = 20;
    }
    
    axios.get('/api/scheduling/appointments', { params })
        .then(response => {
            const data = response.data;
            // Fix: appointments are in data.data.appointments, not data.appointments
            appointments = (data.data && data.data.appointments) ? data.data.appointments : [];
            
            if (currentView === 'list' && data.data && data.data.pagination) {
                updatePagination(data.data.pagination);
            }
            
            updateCalendarView();
            hideLoading();
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            hideLoading();
            showAlert('Error loading appointments', 'danger');
        });
}

function loadClients() {
    axios.get('/api/crm/clients')
        .then(response => {
            clients = response.data.data || [];
            const clientSelect = $('#appointmentClient');
            clientSelect.find('option:not(:first)').remove();
            
            clients.forEach(client => {
                clientSelect.append(`<option value="${client.id}">${escapeHtml(client.name)}</option>`);
            });
        })
        .catch(error => {
            console.error('Error loading clients:', error);
        });
}

function setupEventListeners() {
    // Logout functionality
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // View type change
    $('input[name="viewType"]').on('change', function() {
        currentView = $(this).val();
        currentPage = 1;
        loadAppointments();
    });
    
    // Calendar navigation
    $('#prevBtn').on('click', function() {
        navigateCalendar(-1);
    });
    
    $('#nextBtn').on('click', function() {
        navigateCalendar(1);
    });
    
    $('#todayBtn').on('click', function() {
        currentDate = new Date();
        currentPage = 1;
        loadAppointments();
    });
    
    // Refresh button
    $('#refreshBtn').on('click', function() {
        loadAppointments();
    });
    
    // Appointment form submission
    $('#appointmentForm').on('submit', handleAppointmentSubmit);
    
    // Edit appointment from details modal
    $('#editAppointmentBtn').on('click', function() {
        const appointmentId = $(this).data('appointment-id');
        if (appointmentId) {
            $('#appointmentDetailsModal').modal('hide');
            editAppointment(appointmentId);
        }
    });
    
    // Reset form when modal is hidden
    $('#appointmentModal').on('hidden.bs.modal', function() {
        resetAppointmentForm();
    });
}

function navigateCalendar(direction) {
    switch (currentView) {
        case 'month':
            currentDate.setMonth(currentDate.getMonth() + direction);
            break;
        case 'week':
            currentDate.setDate(currentDate.getDate() + (direction * 7));
            break;
        case 'day':
            currentDate.setDate(currentDate.getDate() + direction);
            break;
        case 'list':
            if (direction > 0 && currentPage < totalPages) {
                currentPage++;
            } else if (direction < 0 && currentPage > 1) {
                currentPage--;
            } else {
                return;
            }
            break;
    }
    
    loadAppointments();
}

function updateCalendarView() {
    // Hide all views
    $('.calendar-view').hide();
    
    // Update period display
    updatePeriodDisplay();
    
    // Show appropriate view
    switch (currentView) {
        case 'month':
            $('#monthViewContainer').show();
            renderMonthView();
            break;
        case 'week':
            $('#weekViewContainer').show();
            renderWeekView();
            break;
        case 'day':
            $('#dayViewContainer').show();
            renderDayView();
            break;
        case 'list':
            $('#listViewContainer').show();
            renderListView();
            break;
    }
}

function updatePeriodDisplay() {
    let periodText = '';
    
    switch (currentView) {
        case 'month':
            periodText = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            break;
        case 'week':
            const weekStart = getWeekStart(currentDate);
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            periodText = `${weekStart.toLocaleDateString()} - ${weekEnd.toLocaleDateString()}`;
            break;
        case 'day':
            periodText = currentDate.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            break;
        case 'list':
            periodText = `Page ${currentPage} of ${totalPages}`;
            break;
    }
    
    $('#currentPeriod').text(periodText);
}

function renderMonthView() {
    const calendarBody = $('#calendarBody');
    calendarBody.empty();
    
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const startDate = getWeekStart(firstDay);
    
    let currentWeekDate = new Date(startDate);
    
    // Generate 6 weeks
    for (let week = 0; week < 6; week++) {
        const weekRow = $('<div class="calendar-week"></div>');
        
        for (let day = 0; day < 7; day++) {
            const dayCell = createDayCell(new Date(currentWeekDate));
            weekRow.append(dayCell);
            currentWeekDate.setDate(currentWeekDate.getDate() + 1);
        }
        
        calendarBody.append(weekRow);
        
        // Stop if we've passed the current month and completed a week
        if (currentWeekDate > lastDay && week >= 4) {
            break;
        }
    }
}

function createDayCell(date) {
    const isCurrentMonth = date.getMonth() === currentDate.getMonth();
    const isToday = isDateToday(date);
    const dayAppointments = getAppointmentsForDate(date);
    
    const dayCell = $(`
        <div class="calendar-day ${isCurrentMonth ? '' : 'other-month'} ${isToday ? 'today' : ''}" data-date="${date.toISOString().split('T')[0]}">
            <div class="day-number">${date.getDate()}</div>
            <div class="day-appointments"></div>
        </div>
    `);
    
    const appointmentsContainer = dayCell.find('.day-appointments');
    
    dayAppointments.slice(0, 3).forEach(appointment => {
        const appointmentElement = $(`
            <div class="appointment-item status-${appointment.status}" data-appointment-id="${appointment.id}">
                <div class="appointment-time">${formatTime(appointment.start_time)}</div>
                <div class="appointment-title">${escapeHtml(appointment.client_name)}</div>
            </div>
        `);
        
        appointmentElement.on('click', function(e) {
            e.stopPropagation();
            showAppointmentDetails(appointment.id);
        });
        
        appointmentsContainer.append(appointmentElement);
    });
    
    if (dayAppointments.length > 3) {
        appointmentsContainer.append(`<div class="more-appointments">+${dayAppointments.length - 3} more</div>`);
    }
    
    // Add click handler for creating new appointment
    dayCell.on('click', function() {
        const dateStr = $(this).data('date');
        $('#appointmentDate').val(dateStr);
        $('#appointmentModal').modal('show');
    });
    
    return dayCell;
}

function renderWeekView() {
    const weekGrid = $('#weekGrid');
    weekGrid.empty();
    
    const weekStart = getWeekStart(currentDate);
    const hours = [];
    
    // Generate time slots (8 AM to 8 PM)
    for (let hour = 8; hour <= 20; hour++) {
        hours.push(hour);
    }
    
    // Create header
    const header = $('<div class="week-header"></div>');
    header.append('<div class="time-column">Time</div>');
    
    for (let day = 0; day < 7; day++) {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + day);
        const dayHeader = $(`
            <div class="day-column">
                <div class="day-name">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                <div class="day-date">${date.getDate()}</div>
            </div>
        `);
        header.append(dayHeader);
    }
    
    weekGrid.append(header);
    
    // Create time slots
    hours.forEach(hour => {
        const timeRow = $('<div class="time-row"></div>');
        timeRow.append(`<div class="time-column">${formatHour(hour)}</div>`);
        
        for (let day = 0; day < 7; day++) {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + day);
            
            const timeSlot = $(`<div class="time-slot" data-date="${date.toISOString().split('T')[0]}" data-hour="${hour}"></div>`);
            
            // Add appointments for this time slot
            const slotAppointments = getAppointmentsForTimeSlot(date, hour);
            slotAppointments.forEach(appointment => {
                const appointmentElement = $(`
                    <div class="appointment-block status-${appointment.status}" data-appointment-id="${appointment.id}">
                        <div class="appointment-title">${escapeHtml(appointment.client_name)}</div>
                        <div class="appointment-time">${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}</div>
                    </div>
                `);
                
                appointmentElement.on('click', function(e) {
                    e.stopPropagation();
                    showAppointmentDetails(appointment.id);
                });
                
                timeSlot.append(appointmentElement);
            });
            
            // Add click handler for creating new appointment
            timeSlot.on('click', function() {
                const dateStr = $(this).data('date');
                const hour = $(this).data('hour');
                $('#appointmentDate').val(dateStr);
                $('#appointmentStartTime').val(`${hour.toString().padStart(2, '0')}:00`);
                $('#appointmentModal').modal('show');
            });
            
            timeRow.append(timeSlot);
        }
        
        weekGrid.append(timeRow);
    });
}

function renderDayView() {
    const daySchedule = $('#daySchedule');
    daySchedule.empty();
    
    const dayAppointments = getAppointmentsForDate(currentDate);
    
    if (dayAppointments.length === 0) {
        daySchedule.append(`
            <div class="empty-day">
                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                <h5>No appointments scheduled</h5>
                <p class="text-muted">Click the button below to schedule a new appointment</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal">
                    <i class="fas fa-plus me-2"></i>New Appointment
                </button>
            </div>
        `);
        return;
    }
    
    // Sort appointments by time
    dayAppointments.sort((a, b) => a.start_time.localeCompare(b.start_time));
    
    dayAppointments.forEach(appointment => {
        const appointmentCard = $(`
            <div class="appointment-card status-${appointment.status}" data-appointment-id="${appointment.id}">
                <div class="appointment-time-range">
                    ${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}
                </div>
                <div class="appointment-details">
                    <h6 class="appointment-client">${escapeHtml(appointment.client_name)}</h6>
                    <p class="appointment-type">${capitalizeFirst(appointment.type)}</p>
                    ${appointment.location ? `<p class="appointment-location"><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(appointment.location)}</p>` : ''}
                    ${appointment.notes ? `<p class="appointment-notes">${escapeHtml(appointment.notes)}</p>` : ''}
                </div>
                <div class="appointment-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="editAppointment(${appointment.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAppointment(${appointment.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `);
        
        appointmentCard.on('click', function() {
            showAppointmentDetails(appointment.id);
        });
        
        daySchedule.append(appointmentCard);
    });
}

function renderListView() {
    const tbody = $('#appointmentsTableBody');
    tbody.empty();
    
    if (appointments.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No appointments found</p>
                </td>
            </tr>
        `);
        return;
    }
    
    appointments.forEach(appointment => {
        const row = $(`
            <tr data-appointment-id="${appointment.id}">
                <td>
                    <div class="fw-bold">${formatDate(appointment.date)}</div>
                    <small class="text-muted">${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}</small>
                </td>
                <td>${escapeHtml(appointment.client_name)}</td>
                <td><span class="badge bg-secondary">${capitalizeFirst(appointment.type)}</span></td>
                <td>${getStatusBadge(appointment.status)}</td>
                <td>${appointment.notes ? escapeHtml(appointment.notes.substring(0, 50)) + (appointment.notes.length > 50 ? '...' : '') : '-'}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="showAppointmentDetails(${appointment.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="editAppointment(${appointment.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteAppointment(${appointment.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
        
        tbody.append(row);
    });
}

function handleAppointmentSubmit(e) {
    e.preventDefault();
    
    const appointmentId = $('#appointmentId').val();
    const isEdit = appointmentId !== '';
    
    const appointmentData = {
        client_id: $('#appointmentClient').val(),
        type: $('#appointmentType').val(),
        date: $('#appointmentDate').val(),
        start_time: $('#appointmentStartTime').val(),
        end_time: $('#appointmentEndTime').val(),
        status: $('#appointmentStatus').val(),
        location: $('#appointmentLocation').val().trim(),
        notes: $('#appointmentNotes').val().trim(),
        reminder: $('#appointmentReminder').is(':checked')
    };
    
    // Show loading state
    const saveBtn = $('#saveAppointmentBtn');
    const spinner = saveBtn.find('.spinner-border');
    saveBtn.prop('disabled', true);
    spinner.show();
    
    const request = isEdit 
        ? axios.put(`/api/scheduling/appointments/${appointmentId}`, appointmentData)
        : axios.post('/api/scheduling/appointments', appointmentData);
    
    request
        .then(response => {
            const message = isEdit ? 'Appointment updated successfully' : 'Appointment created successfully';
            showAlert(message, 'success');
            $('#appointmentModal').modal('hide');
            loadAppointments();
        })
        .catch(error => {
            console.error('Error saving appointment:', error);
            const message = error.response?.data?.data?.error || 'Error saving appointment';
            showAlert(message, 'danger');
        })
        .finally(() => {
            saveBtn.prop('disabled', false);
            spinner.hide();
        });
}

function editAppointment(appointmentId) {
    axios.get(`/api/scheduling/appointments/${appointmentId}`)
        .then(response => {
            const appointment = response.data.data;
            
            // Populate form
            $('#appointmentId').val(appointment.id);
            $('#appointmentClient').val(appointment.client_id);
            $('#appointmentType').val(appointment.type);
            $('#appointmentDate').val(appointment.date);
            $('#appointmentStartTime').val(appointment.start_time);
            $('#appointmentEndTime').val(appointment.end_time);
            $('#appointmentStatus').val(appointment.status);
            $('#appointmentLocation').val(appointment.location || '');
            $('#appointmentNotes').val(appointment.notes || '');
            $('#appointmentReminder').prop('checked', appointment.reminder || false);
            
            // Update modal title
            $('#appointmentModalTitle').text('Edit Appointment');
            
            // Show modal
            $('#appointmentModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading appointment:', error);
            showAlert('Error loading appointment details', 'danger');
        });
}

function showAppointmentDetails(appointmentId) {
    axios.get(`/api/scheduling/appointments/${appointmentId}`)
        .then(response => {
            const appointment = response.data.data;
            
            const detailsHtml = `
                <div class="appointment-details-content">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Client:</strong></div>
                        <div class="col-sm-8">${escapeHtml(appointment.client_name)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Type:</strong></div>
                        <div class="col-sm-8">${capitalizeFirst(appointment.type)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Date:</strong></div>
                        <div class="col-sm-8">${formatDate(appointment.date)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Time:</strong></div>
                        <div class="col-sm-8">${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Status:</strong></div>
                        <div class="col-sm-8">${getStatusBadge(appointment.status)}</div>
                    </div>
                    ${appointment.location ? `
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Location:</strong></div>
                            <div class="col-sm-8">${escapeHtml(appointment.location)}</div>
                        </div>
                    ` : ''}
                    ${appointment.notes ? `
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Notes:</strong></div>
                            <div class="col-sm-8">${escapeHtml(appointment.notes)}</div>
                        </div>
                    ` : ''}
                </div>
            `;
            
            $('#appointmentDetailsBody').html(detailsHtml);
            $('#editAppointmentBtn').data('appointment-id', appointment.id);
            $('#appointmentDetailsModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading appointment details:', error);
            showAlert('Error loading appointment details', 'danger');
        });
}

function deleteAppointment(appointmentId) {
    if (!confirm('Are you sure you want to delete this appointment? This action cannot be undone.')) {
        return;
    }
    
    axios.delete(`/api/scheduling/appointments/${appointmentId}`)
        .then(response => {
            showAlert('Appointment deleted successfully', 'success');
            loadAppointments();
        })
        .catch(error => {
            console.error('Error deleting appointment:', error);
            const message = error.response?.data?.data?.error || 'Error deleting appointment';
            showAlert(message, 'danger');
        });
}

function resetAppointmentForm() {
    $('#appointmentForm')[0].reset();
    $('#appointmentId').val('');
    $('#appointmentModalTitle').text('New Appointment');
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#appointmentDate').val(today);
}

function updatePagination(pagination) {
    totalPages = pagination.totalPages || 1;
    const paginationContainer = $('#appointmentsPagination');
    paginationContainer.empty();
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    paginationContainer.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="navigateCalendar(-1)">
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
                <a class="page-link" href="#" onclick="loadAppointmentsPage(${i})">${i}</a>
            </li>
        `);
    }
    
    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    paginationContainer.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="navigateCalendar(1)">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

function loadAppointmentsPage(page) {
    currentPage = page;
    loadAppointments();
}

// Utility functions
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day;
    return new Date(d.setDate(diff));
}

function isDateToday(date) {
    const today = new Date();
    return date.toDateString() === today.toDateString();
}

function getAppointmentsForDate(date) {
    const dateStr = date.toISOString().split('T')[0];
    return appointments.filter(appointment => appointment.date === dateStr);
}

function getAppointmentsForTimeSlot(date, hour) {
    const dateStr = date.toISOString().split('T')[0];
    return appointments.filter(appointment => {
        if (appointment.date !== dateStr) return false;
        const startHour = parseInt(appointment.start_time.split(':')[0]);
        const endHour = parseInt(appointment.end_time.split(':')[0]);
        return hour >= startHour && hour < endHour;
    });
}

function formatTime(timeStr) {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function formatHour(hour) {
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:00 ${ampm}`;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'confirmed': '<span class="badge bg-success">Confirmed</span>',
        'completed': '<span class="badge bg-info">Completed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>',
        'no_show': '<span class="badge bg-warning">No Show</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function showLoading() {
    $('.loading').show();
    $('.calendar-view').hide();
}

function hideLoading() {
    $('.loading').hide();
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
        window.location.href = 'login.php';
}

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