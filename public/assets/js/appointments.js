/**
 * Appointments Management System
 * Modern JavaScript for appointments functionality
 */

class AppointmentsManager {
    constructor() {
        this.currentDate = new Date();
        this.appointments = [];
        this.clients = [];
        this.currentView = 'list';
        this.filters = {
            status: '',
            startDate: '',
            endDate: '',
            client: ''
        };
        this.pagination = {
            currentPage: 1,
            itemsPerPage: 10,
            totalItems: 0
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadClients();
        this.loadAppointments();
        this.initializeCalendar();
        this.loadTodayAppointments();
    }

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                this.currentView = target.replace('#', '').replace('-view', '');
                this.handleViewChange();
            });
        });

        // New appointment button
        const newAppointmentBtn = document.getElementById('newAppointmentBtn');
        if (newAppointmentBtn) {
            newAppointmentBtn.addEventListener('click', () => this.openAppointmentModal());
        }

        // Filters
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.applyFilters();
        });

        document.getElementById('startDate')?.addEventListener('change', (e) => {
            this.filters.startDate = e.target.value;
            this.applyFilters();
        });

        document.getElementById('endDate')?.addEventListener('change', (e) => {
            this.filters.endDate = e.target.value;
            this.applyFilters();
        });

        document.getElementById('clientFilter')?.addEventListener('input', (e) => {
            this.filters.client = e.target.value;
            this.debounce(() => this.applyFilters(), 300)();
        });

        document.getElementById('clearFilters')?.addEventListener('click', () => {
            this.clearFilters();
        });

        // Search
        document.getElementById('searchAppointments')?.addEventListener('input', (e) => {
            this.debounce(() => this.searchAppointments(e.target.value), 300)();
        });

        // Calendar navigation
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendar();
        });

        document.getElementById('nextMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendar();
        });

        document.getElementById('currentMonth')?.addEventListener('click', () => {
            this.currentDate = new Date();
            this.renderCalendar();
        });

        // Form submission
        document.getElementById('appointmentForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveAppointment();
        });
    }

    handleViewChange() {
        switch (this.currentView) {
            case 'list':
                this.loadAppointments();
                break;
            case 'calendar':
                this.renderCalendar();
                break;
            case 'today':
                this.loadTodayAppointments();
                break;
        }
    }

    async loadClients() {
        try {
            // Simulated API call - replace with actual endpoint
            const response = await fetch('/api/clients');
            if (response.ok) {
                this.clients = await response.json();
            } else {
                // Fallback with sample data
                this.clients = this.getSampleClients();
            }
            this.populateClientSelect();
        } catch (error) {
            console.error('Error loading clients:', error);
            this.clients = this.getSampleClients();
            this.populateClientSelect();
        }
    }

    getSampleClients() {
        return [
            { id: 1, name: 'João Silva', email: 'joao@email.com' },
            { id: 2, name: 'Maria Santos', email: 'maria@email.com' },
            { id: 3, name: 'Pedro Oliveira', email: 'pedro@email.com' },
            { id: 4, name: 'Ana Costa', email: 'ana@email.com' },
            { id: 5, name: 'Carlos Ferreira', email: 'carlos@email.com' }
        ];
    }

    populateClientSelect() {
        const clientSelect = document.getElementById('clientSelect');
        if (clientSelect) {
            clientSelect.innerHTML = '<option value="">Selecione o cliente</option>';
            this.clients.forEach(client => {
                const option = document.createElement('option');
                option.value = client.id;
                option.textContent = client.name;
                clientSelect.appendChild(option);
            });
        }
    }

    async loadAppointments() {
        try {
            this.showLoading('appointmentsTableBody');
            
            // Simulated API call - replace with actual endpoint
            const response = await fetch('/api/appointments');
            if (response.ok) {
                this.appointments = await response.json();
            } else {
                // Fallback with sample data
                this.appointments = this.getSampleAppointments();
            }
            
            this.renderAppointmentsList();
        } catch (error) {
            console.error('Error loading appointments:', error);
            this.appointments = this.getSampleAppointments();
            this.renderAppointmentsList();
        }
    }

    getSampleAppointments() {
        const today = new Date();
        return [
            {
                id: 1,
                client_id: 1,
                client_name: 'João Silva',
                date: this.formatDate(today),
                start_time: '09:00',
                end_time: '10:00',
                type: 'consultation',
                status: 'confirmed',
                notes: 'Consulta inicial',
                value: 150.00
            },
            {
                id: 2,
                client_id: 2,
                client_name: 'Maria Santos',
                date: this.formatDate(new Date(today.getTime() + 86400000)),
                start_time: '14:00',
                end_time: '15:30',
                type: 'meeting',
                status: 'scheduled',
                notes: 'Reunião de acompanhamento',
                value: 200.00
            },
            {
                id: 3,
                client_id: 3,
                client_name: 'Pedro Oliveira',
                date: this.formatDate(new Date(today.getTime() + 172800000)),
                start_time: '10:30',
                end_time: '12:00',
                type: 'presentation',
                status: 'completed',
                notes: 'Apresentação de proposta',
                value: 300.00
            }
        ];
    }

    renderAppointmentsList() {
        const tbody = document.getElementById('appointmentsTableBody');
        if (!tbody) return;

        if (this.appointments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <h5>Nenhum agendamento encontrado</h5>
                            <p>Clique em "Novo Agendamento" para criar o primeiro.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.appointments.map(appointment => `
            <tr class="fade-in">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-3">
                            ${appointment.client_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="fw-semibold">${appointment.client_name}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-semibold">${this.formatDisplayDate(appointment.date)}</div>
                    <small class="text-muted">${appointment.start_time} - ${appointment.end_time}</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${this.getTypeLabel(appointment.type)}</span>
                </td>
                <td>
                    <span class="status-badge status-${appointment.status}">
                        ${this.getStatusLabel(appointment.status)}
                    </span>
                </td>
                <td>
                    <span class="text-truncate" style="max-width: 150px; display: inline-block;" title="${appointment.notes}">
                        ${appointment.notes || '-'}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary action-btn" onclick="appointmentsManager.editAppointment(${appointment.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger action-btn" onclick="appointmentsManager.deleteAppointment(${appointment.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    initializeCalendar() {
        this.renderCalendar();
    }

    renderCalendar() {
        const calendarGrid = document.getElementById('calendarGrid');
        const currentMonthText = document.getElementById('currentMonthText');
        
        if (!calendarGrid) return;

        // Update month display
        if (currentMonthText) {
            currentMonthText.textContent = this.currentDate.toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        }

        // Generate calendar
        const firstDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
        const lastDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());

        let calendarHTML = '';
        
        // Day headers
        const dayHeaders = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        calendarHTML += '<div class="calendar-header">';
        dayHeaders.forEach(day => {
            calendarHTML += `<div class="day-header">${day}</div>`;
        });
        calendarHTML += '</div>';

        // Calendar days
        const currentDate = new Date(startDate);
        for (let week = 0; week < 6; week++) {
            for (let day = 0; day < 7; day++) {
                const isCurrentMonth = currentDate.getMonth() === this.currentDate.getMonth();
                const isToday = this.isSameDate(currentDate, new Date());
                const dayAppointments = this.getAppointmentsForDate(currentDate);

                let dayClass = 'calendar-day';
                if (!isCurrentMonth) dayClass += ' other-month';
                if (isToday) dayClass += ' today';

                calendarHTML += `
                    <div class="${dayClass}" data-date="${this.formatDate(currentDate)}">
                        <div class="day-number">${currentDate.getDate()}</div>
                        <div class="day-appointments">
                            ${dayAppointments.map(apt => `
                                <div class="appointment-item" title="${apt.client_name} - ${apt.start_time}">
                                    ${apt.client_name}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;

                currentDate.setDate(currentDate.getDate() + 1);
            }
        }

        calendarGrid.innerHTML = calendarHTML;
    }

    getAppointmentsForDate(date) {
        const dateStr = this.formatDate(date);
        return this.appointments.filter(apt => apt.date === dateStr);
    }

    loadTodayAppointments() {
        const today = this.formatDate(new Date());
        const todayAppointments = this.appointments.filter(apt => apt.date === today);
        
        this.renderTodayAppointments(todayAppointments);
        this.updateTodayStats(todayAppointments);
    }

    renderTodayAppointments(appointments) {
        const container = document.getElementById('todayAppointmentsList');
        if (!container) return;

        if (appointments.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-calendar-check fa-3x mb-3"></i>
                    <h5>Nenhum agendamento para hoje</h5>
                    <p>Você está livre hoje!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = appointments
            .sort((a, b) => a.start_time.localeCompare(b.start_time))
            .map(apt => `
                <div class="appointment-card fade-in">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="appointment-time">${apt.start_time} - ${apt.end_time}</div>
                            <div class="appointment-client">${apt.client_name}</div>
                            <div class="appointment-type">${this.getTypeLabel(apt.type)}</div>
                        </div>
                        <div>
                            <span class="status-badge status-${apt.status}">
                                ${this.getStatusLabel(apt.status)}
                            </span>
                        </div>
                    </div>
                    ${apt.notes ? `<div class="mt-2"><small class="text-muted">${apt.notes}</small></div>` : ''}
                </div>
            `).join('');
    }

    updateTodayStats(appointments) {
        const totalEl = document.getElementById('todayTotal');
        const completedEl = document.getElementById('todayCompleted');
        const pendingEl = document.getElementById('todayPending');

        if (totalEl) totalEl.textContent = appointments.length;
        if (completedEl) completedEl.textContent = appointments.filter(apt => apt.status === 'completed').length;
        if (pendingEl) pendingEl.textContent = appointments.filter(apt => ['scheduled', 'confirmed'].includes(apt.status)).length;
    }

    openAppointmentModal(appointmentId = null) {
        const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
        const form = document.getElementById('appointmentForm');
        const title = document.getElementById('appointmentModalTitle');
        
        if (appointmentId) {
            const appointment = this.appointments.find(apt => apt.id === appointmentId);
            if (appointment) {
                title.textContent = 'Editar Agendamento';
                this.populateForm(appointment);
            }
        } else {
            title.textContent = 'Novo Agendamento';
            form.reset();
            document.getElementById('appointmentId').value = '';
        }
        
        modal.show();
    }

    populateForm(appointment) {
        document.getElementById('appointmentId').value = appointment.id;
        document.getElementById('clientSelect').value = appointment.client_id;
        document.getElementById('appointmentType').value = appointment.type;
        document.getElementById('appointmentDate').value = appointment.date;
        document.getElementById('appointmentStartTime').value = appointment.start_time;
        document.getElementById('appointmentEndTime').value = appointment.end_time;
        document.getElementById('appointmentStatus').value = appointment.status;
        document.getElementById('appointmentValue').value = appointment.value;
        document.getElementById('appointmentNotes').value = appointment.notes;
    }

    async saveAppointment() {
        const form = document.getElementById('appointmentForm');
        const formData = new FormData(form);
        const saveBtn = document.getElementById('saveAppointmentBtn');
        const spinner = saveBtn.querySelector('.spinner-border');
        
        // Show loading
        saveBtn.disabled = true;
        spinner.style.display = 'inline-block';
        
        try {
            const appointmentData = {
                id: document.getElementById('appointmentId').value,
                client_id: document.getElementById('clientSelect').value,
                type: document.getElementById('appointmentType').value,
                date: document.getElementById('appointmentDate').value,
                start_time: document.getElementById('appointmentStartTime').value,
                end_time: document.getElementById('appointmentEndTime').value,
                status: document.getElementById('appointmentStatus').value,
                value: document.getElementById('appointmentValue').value,
                notes: document.getElementById('appointmentNotes').value
            };

            // Validate
            if (!this.validateAppointment(appointmentData)) {
                return;
            }

            // Simulated API call - replace with actual endpoint
            const response = await fetch('/api/appointments', {
                method: appointmentData.id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(appointmentData)
            });

            if (response.ok) {
                this.showNotification('Agendamento salvo com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
                this.loadAppointments();
                this.loadTodayAppointments();
            } else {
                throw new Error('Erro ao salvar agendamento');
            }
        } catch (error) {
            console.error('Error saving appointment:', error);
            this.showNotification('Erro ao salvar agendamento. Tente novamente.', 'error');
        } finally {
            saveBtn.disabled = false;
            spinner.style.display = 'none';
        }
    }

    validateAppointment(data) {
        if (!data.client_id) {
            this.showNotification('Selecione um cliente', 'error');
            return false;
        }
        if (!data.type) {
            this.showNotification('Selecione o tipo de agendamento', 'error');
            return false;
        }
        if (!data.date) {
            this.showNotification('Selecione a data', 'error');
            return false;
        }
        if (!data.start_time || !data.end_time) {
            this.showNotification('Defina o horário de início e fim', 'error');
            return false;
        }
        if (data.start_time >= data.end_time) {
            this.showNotification('O horário de fim deve ser posterior ao de início', 'error');
            return false;
        }
        return true;
    }

    editAppointment(id) {
        this.openAppointmentModal(id);
    }

    async deleteAppointment(id) {
        if (!confirm('Tem certeza que deseja excluir este agendamento?')) {
            return;
        }

        try {
            // Simulated API call - replace with actual endpoint
            const response = await fetch(`/api/appointments/${id}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                this.showNotification('Agendamento excluído com sucesso!', 'success');
                this.loadAppointments();
                this.loadTodayAppointments();
            } else {
                throw new Error('Erro ao excluir agendamento');
            }
        } catch (error) {
            console.error('Error deleting appointment:', error);
            this.showNotification('Erro ao excluir agendamento. Tente novamente.', 'error');
        }
    }

    applyFilters() {
        // Implementation for filtering appointments
        this.loadAppointments();
    }

    clearFilters() {
        this.filters = {
            status: '',
            startDate: '',
            endDate: '',
            client: ''
        };
        
        document.getElementById('statusFilter').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('clientFilter').value = '';
        
        this.loadAppointments();
    }

    searchAppointments(query) {
        // Implementation for searching appointments
        console.log('Searching for:', query);
    }

    // Utility methods
    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    formatDisplayDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('pt-BR');
    }

    isSameDate(date1, date2) {
        return date1.toDateString() === date2.toDateString();
    }

    getStatusLabel(status) {
        const labels = {
            scheduled: 'Agendado',
            confirmed: 'Confirmado',
            completed: 'Concluído',
            cancelled: 'Cancelado'
        };
        return labels[status] || status;
    }

    getTypeLabel(type) {
        const labels = {
            consultation: 'Consulta',
            meeting: 'Reunião',
            presentation: 'Apresentação',
            follow_up: 'Follow-up',
            other: 'Outro'
        };
        return labels[type] || type;
    }

    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    showNotification(message, type = 'info') {
        // Simple notification - you can replace with a more sophisticated solution
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    debounce(func, wait) {
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.appointmentsManager = new AppointmentsManager();
});

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AppointmentsManager;
}