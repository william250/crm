// Settings Page JavaScript

class SettingsManager {
    constructor() {
        this.currentSection = 'general';
        this.settings = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadSettings();
        // Show settings content after initialization
        setTimeout(() => {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('settingsContent').style.display = 'block';
        }, 1000);
    }

    bindEvents() {
        // Navigation events using Bootstrap tab structure
        document.querySelectorAll('#settingsNav .nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const target = link.getAttribute('data-bs-target');
                if (target) {
                    const sectionId = target.replace('#', '');
                    this.currentSection = sectionId;
                    this.loadSectionData(sectionId);
                }
            });
        });

        // Form events
        document.querySelectorAll('.settings-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveSettings(form);
            });
        });

        // Theme selector events
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', (e) => {
                this.selectTheme(e.currentTarget.dataset.theme);
            });
        });

        // Integration toggle events
        document.querySelectorAll('.integration-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                this.toggleIntegration(e.currentTarget.dataset.integration);
            });
        });

        // Backup actions
        document.getElementById('createBackup')?.addEventListener('click', () => {
            this.createBackup();
        });

        document.getElementById('restoreBackup')?.addEventListener('click', () => {
            this.restoreBackup();
        });

        document.getElementById('downloadBackup')?.addEventListener('click', () => {
            this.downloadBackup();
        });

        // API key generation
        document.getElementById('generateApiKey')?.addEventListener('click', () => {
            this.generateApiKey();
        });

        // Reset settings
        document.getElementById('resetSettings')?.addEventListener('click', () => {
            this.resetSettings();
        });

        // Export/Import settings
        document.getElementById('exportSettings')?.addEventListener('click', () => {
            this.exportSettings();
        });

        document.getElementById('importSettings')?.addEventListener('click', () => {
            this.importSettings();
        });

        // Real-time form validation
        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', (e) => {
                this.validateField(e.target);
            });
        });
    }

    showSection(sectionId) {
        // Show loading state
        const loadingState = document.getElementById('loadingState');
        if (loadingState) {
            loadingState.style.display = 'block';
        }
        
        setTimeout(() => {
            this.hideLoadingState(sectionId);
        }, 500);
    }

    hideLoadingState(sectionId) {
        const loadingState = document.getElementById('loadingState');
        if (loadingState) {
            loadingState.style.display = 'none';
        }
        
        // Update navigation using Bootstrap tab structure
        document.querySelectorAll('#settingsNav .nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeTab = document.getElementById(`${sectionId}-tab`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Show/hide sections using Bootstrap tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('show', 'active', 'fade-in');
        }

        this.currentSection = sectionId;
        
        // Load section data
        this.loadSectionData(sectionId);
        
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('section', sectionId);
        window.history.pushState({}, '', url);
    }

    loadSectionData(sectionId) {
        // Load specific data for each section if needed
        switch (sectionId) {
            case 'general':
                // Load general settings data
                break;
            case 'appearance':
                // Load appearance settings data
                break;
            case 'notifications':
                // Load notification settings data
                break;
            case 'security':
                // Load security settings data
                break;
            case 'integrations':
                // Load integrations data
                break;
            case 'backup':
                // Load backup data
                break;
            case 'api':
                // Load API settings data
                break;
        }
    }

    async loadSettings() {
        try {
            const response = await fetch('/api/settings', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            if (response.ok) {
                this.settings = await response.json();
                this.populateForm();
            }
        } catch (error) {
            console.error('Error loading settings:', error);
            this.showToast('Erro ao carregar configurações', 'error');
        }
    }

    populateForm() {
        // Populate general settings
        if (this.settings.company_name) {
            document.getElementById('companyName').value = this.settings.company_name;
        }
        if (this.settings.timezone) {
            document.getElementById('timezone').value = this.settings.timezone;
        }
        if (this.settings.date_format) {
            document.getElementById('dateFormat').value = this.settings.date_format;
        }
        if (this.settings.currency) {
            document.getElementById('currency').value = this.settings.currency;
        }
        if (this.settings.language) {
            document.getElementById('language').value = this.settings.language;
        }

        // Populate appearance settings
        if (this.settings.theme) {
            this.selectTheme(this.settings.theme);
        }
        if (this.settings.primary_color) {
            document.getElementById('primaryColor').value = this.settings.primary_color;
        }
        if (this.settings.sidebar_collapsed !== undefined) {
            document.getElementById('sidebarCollapsed').checked = this.settings.sidebar_collapsed;
        }
        if (this.settings.dark_mode !== undefined) {
            document.getElementById('darkMode').checked = this.settings.dark_mode;
        }

        // Populate notification settings
        if (this.settings.email_notifications !== undefined) {
            document.getElementById('emailNotifications').checked = this.settings.email_notifications;
        }
        if (this.settings.push_notifications !== undefined) {
            document.getElementById('pushNotifications').checked = this.settings.push_notifications;
        }
        if (this.settings.sms_notifications !== undefined) {
            document.getElementById('smsNotifications').checked = this.settings.sms_notifications;
        }

        // Populate security settings
        if (this.settings.two_factor !== undefined) {
            document.getElementById('twoFactor').checked = this.settings.two_factor;
        }
        if (this.settings.session_timeout) {
            document.getElementById('sessionTimeout').value = this.settings.session_timeout;
        }
        if (this.settings.password_expiry) {
            document.getElementById('passwordExpiry').value = this.settings.password_expiry;
        }
    }

    async saveSettings(form) {
        const formData = new FormData(form);
        const settings = {};
        
        for (let [key, value] of formData.entries()) {
            settings[key] = value;
        }

        // Handle checkboxes
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            settings[checkbox.name] = checkbox.checked;
        });

        try {
            const response = await fetch('/api/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(settings)
            });

            if (response.ok) {
                this.settings = { ...this.settings, ...settings };
                this.showToast('Configurações salvas com sucesso!', 'success');
                
                // Apply theme changes immediately
                if (settings.theme) {
                    this.applyTheme(settings.theme);
                }
            } else {
                throw new Error('Failed to save settings');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            this.showToast('Erro ao salvar configurações', 'error');
        }
    }

    selectTheme(theme) {
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.remove('active');
        });
        
        const selectedOption = document.querySelector(`[data-theme="${theme}"]`);
        if (selectedOption) {
            selectedOption.classList.add('active');
        }
        
        this.applyTheme(theme);
    }

    applyTheme(theme) {
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add(`theme-${theme}`);
        
        // Update CSS variables based on theme
        const root = document.documentElement;
        switch (theme) {
            case 'blue':
                root.style.setProperty('--primary-color', '#667eea');
                root.style.setProperty('--secondary-color', '#764ba2');
                break;
            case 'green':
                root.style.setProperty('--primary-color', '#56ab2f');
                root.style.setProperty('--secondary-color', '#a8e6cf');
                break;
            case 'purple':
                root.style.setProperty('--primary-color', '#8360c3');
                root.style.setProperty('--secondary-color', '#2ebf91');
                break;
            case 'orange':
                root.style.setProperty('--primary-color', '#f093fb');
                root.style.setProperty('--secondary-color', '#f5576c');
                break;
        }
    }

    async toggleIntegration(integration) {
        try {
            const response = await fetch(`/api/integrations/${integration}/toggle`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.showToast(`Integração ${result.enabled ? 'ativada' : 'desativada'} com sucesso!`, 'success');
                
                // Update UI
                const button = document.querySelector(`[data-integration="${integration}"]`);
                if (button) {
                    button.textContent = result.enabled ? 'Desconectar' : 'Conectar';
                    button.className = result.enabled ? 'btn btn-outline-danger btn-sm' : 'btn btn-outline-primary btn-sm';
                }
            }
        } catch (error) {
            console.error('Error toggling integration:', error);
            this.showToast('Erro ao alterar integração', 'error');
        }
    }

    async createBackup() {
        try {
            const response = await fetch('/api/backup/create', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.showToast('Backup criado com sucesso!', 'success');
            }
        } catch (error) {
            console.error('Error creating backup:', error);
            this.showToast('Erro ao criar backup', 'error');
        }
    }

    async restoreBackup() {
        if (!confirm('Tem certeza que deseja restaurar o backup? Esta ação não pode ser desfeita.')) {
            return;
        }

        try {
            const response = await fetch('/api/backup/restore', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                this.showToast('Backup restaurado com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            console.error('Error restoring backup:', error);
            this.showToast('Erro ao restaurar backup', 'error');
        }
    }

    async downloadBackup() {
        try {
            const response = await fetch('/api/backup/download', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `backup-${new Date().toISOString().split('T')[0]}.zip`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                this.showToast('Backup baixado com sucesso!', 'success');
            }
        } catch (error) {
            console.error('Error downloading backup:', error);
            this.showToast('Erro ao baixar backup', 'error');
        }
    }

    async generateApiKey() {
        try {
            const response = await fetch('/api/auth/generate-key', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const result = await response.json();
                document.getElementById('apiKey').value = result.api_key;
                this.showToast('Nova chave API gerada!', 'success');
            }
        } catch (error) {
            console.error('Error generating API key:', error);
            this.showToast('Erro ao gerar chave API', 'error');
        }
    }

    async resetSettings() {
        if (!confirm('Tem certeza que deseja resetar todas as configurações? Esta ação não pode ser desfeita.')) {
            return;
        }

        try {
            const response = await fetch('/api/settings/reset', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                this.showToast('Configurações resetadas com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            console.error('Error resetting settings:', error);
            this.showToast('Erro ao resetar configurações', 'error');
        }
    }

    exportSettings() {
        const dataStr = JSON.stringify(this.settings, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `settings-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
        this.showToast('Configurações exportadas com sucesso!', 'success');
    }

    importSettings() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const settings = JSON.parse(e.target.result);
                        this.settings = settings;
                        this.populateForm();
                        this.saveSettings(document.querySelector('.settings-form'));
                        this.showToast('Configurações importadas com sucesso!', 'success');
                    } catch (error) {
                        this.showToast('Erro ao importar configurações', 'error');
                    }
                };
                reader.readAsText(file);
            }
        };
        
        input.click();
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Remove existing validation classes
        field.classList.remove('is-valid', 'is-invalid');
        
        // Validation rules
        switch (field.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                isValid = emailRegex.test(value);
                message = 'Por favor, insira um email válido';
                break;
            case 'url':
                try {
                    new URL(value);
                } catch {
                    isValid = false;
                    message = 'Por favor, insira uma URL válida';
                }
                break;
            case 'number':
                isValid = !isNaN(value) && value !== '';
                message = 'Por favor, insira um número válido';
                break;
        }

        // Required field validation
        if (field.hasAttribute('required') && value === '') {
            isValid = false;
            message = 'Este campo é obrigatório';
        }

        // Apply validation classes
        if (value !== '') {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
            
            // Show/hide feedback
            let feedback = field.parentNode.querySelector('.invalid-feedback');
            if (!isValid && !feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                field.parentNode.appendChild(feedback);
            } else if (isValid && feedback) {
                feedback.remove();
            } else if (!isValid && feedback) {
                feedback.textContent = message;
            }
        }

        return isValid;
    }

    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        // Create toast
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
        toast.setAttribute('role', 'alert');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toast);

        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

// Initialize settings manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
    
    // Handle URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section) {
        setTimeout(() => {
            document.querySelector(`[data-section="${section}"]`)?.click();
        }, 100);
    }
});

// Handle browser back/forward buttons
window.addEventListener('popstate', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section') || 'general';
    document.querySelector(`[data-section="${section}"]`)?.click();
});