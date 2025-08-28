// Settings Page JavaScript
$(document).ready(function() {
    // Initialize the page
    initializePage();
    
    // Setup event listeners
    setupEventListeners();
    
    // Load settings data
    loadSettingsData();
});

function initializePage() {
    // Check authentication
    checkAuthentication();
    
    // Load user info
    loadUserInfo();
    
    // Setup theme previews
    setupThemePreviews();
    
    // Setup file upload
    setupFileUpload();
}

function setupEventListeners() {
    // Logout button
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // Tab switching
    $('[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        loadTabContent(target);
    });
    
    // Theme selection
    $('.theme-preview').on('click', function() {
        $('.theme-preview').removeClass('selected');
        $(this).addClass('selected');
    });
    
    // File upload for backup restore
    $('#backupFile').on('change', function() {
        const file = this.files[0];
        if (file) {
            restoreFromBackup(file);
        }
    });
}

function setupThemePreviews() {
    // Add click handlers for theme previews
    $('.theme-preview').each(function() {
        $(this).on('click', function() {
            const theme = $(this).data('theme');
            applyTheme(theme);
        });
    });
}

function setupFileUpload() {
    // Setup drag and drop for backup files
    const dropZone = $('#backup');
    
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    dropZone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    dropZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            restoreFromBackup(files[0]);
        }
    });
}

function checkAuthentication() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Set up axios defaults
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    // Handle token expiration
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                localStorage.removeItem('authToken');
                window.location.href = 'login.html';
            }
            return Promise.reject(error);
        }
    );
}

function loadUserInfo() {
    // Mock user data - replace with actual API call
    const userData = {
        name: 'John Doe',
        role: 'Administrator'
    };
    
    $('#userName').text(userData.name);
    
    // Show users link for admin
    if (userData.role === 'Administrator') {
        $('#usersLink').show();
    }
}

function loadSettingsData() {
    showLoading();
    
    // Simulate API call
    setTimeout(() => {
        // Load general settings
        loadGeneralSettings();
        
        // Load notification settings
        loadNotificationSettings();
        
        // Load security settings
        loadSecuritySettings();
        
        // Load integrations
        loadIntegrations();
        
        // Load backups list
        loadBackupsList();
        
        hideLoading();
    }, 1000);
}

function loadTabContent(target) {
    switch(target) {
        case '#notifications':
            loadNotificationSettings();
            break;
        case '#security':
            loadSecuritySettings();
            break;
        case '#integrations':
            loadIntegrations();
            break;
        case '#backup':
            loadBackupsList();
            break;
    }
}

function loadGeneralSettings() {
    // Mock settings data - replace with actual API call
    const settings = {
        companyName: 'Acme Corporation',
        timeZone: 'America/New_York',
        dateFormat: 'MM/DD/YYYY',
        currency: 'USD',
        language: 'en'
    };
    
    $('#companyName').val(settings.companyName);
    $('#timeZone').val(settings.timeZone);
    $('#dateFormat').val(settings.dateFormat);
    $('#currency').val(settings.currency);
    $('#language').val(settings.language);
}

function loadNotificationSettings() {
    // Mock notification settings
    const notifications = [
        {
            id: 'email_new_lead',
            title: 'New Lead Notifications',
            description: 'Receive email when a new lead is created',
            type: 'email',
            enabled: true
        },
        {
            id: 'email_deal_closed',
            title: 'Deal Closed Notifications',
            description: 'Receive email when a deal is closed',
            type: 'email',
            enabled: true
        },
        {
            id: 'push_task_reminder',
            title: 'Task Reminders',
            description: 'Receive push notifications for task reminders',
            type: 'push',
            enabled: false
        },
        {
            id: 'sms_urgent_alerts',
            title: 'Urgent Alerts',
            description: 'Receive SMS for urgent system alerts',
            type: 'sms',
            enabled: false
        }
    ];
    
    let html = '';
    notifications.forEach(notification => {
        html += `
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">${notification.title}</div>
                    <div class="setting-description">${notification.description}</div>
                </div>
                <div class="setting-control">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="${notification.id}" ${notification.enabled ? 'checked' : ''}>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#notificationSettings').html(html);
}

function loadSecuritySettings() {
    // Mock security settings
    const securitySettings = [
        {
            id: 'two_factor_auth',
            title: 'Two-Factor Authentication',
            description: 'Add an extra layer of security to your account',
            enabled: false
        },
        {
            id: 'login_alerts',
            title: 'Login Alerts',
            description: 'Get notified of new login attempts',
            enabled: true
        },
        {
            id: 'password_expiry',
            title: 'Password Expiry',
            description: 'Require password changes every 90 days',
            enabled: false
        },
        {
            id: 'ip_whitelist',
            title: 'IP Whitelist',
            description: 'Restrict access to specific IP addresses',
            enabled: false
        }
    ];
    
    let html = '';
    securitySettings.forEach(setting => {
        html += `
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">${setting.title}</div>
                    <div class="setting-description">${setting.description}</div>
                </div>
                <div class="setting-control">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="${setting.id}" ${setting.enabled ? 'checked' : ''}>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#securitySettings').html(html);
}

function loadIntegrations() {
    // Mock integrations data
    const integrations = [
        {
            id: 'gmail',
            name: 'Gmail',
            description: 'Sync emails and contacts with Gmail',
            icon: 'fab fa-google',
            iconColor: '#db4437',
            status: 'connected'
        },
        {
            id: 'slack',
            name: 'Slack',
            description: 'Send notifications to Slack channels',
            icon: 'fab fa-slack',
            iconColor: '#4a154b',
            status: 'disconnected'
        },
        {
            id: 'mailchimp',
            name: 'Mailchimp',
            description: 'Sync contacts with Mailchimp lists',
            icon: 'fab fa-mailchimp',
            iconColor: '#ffe01b',
            status: 'disconnected'
        },
        {
            id: 'zapier',
            name: 'Zapier',
            description: 'Connect with 3000+ apps via Zapier',
            icon: 'fas fa-bolt',
            iconColor: '#ff4a00',
            status: 'disconnected'
        }
    ];
    
    let html = '';
    integrations.forEach(integration => {
        const statusClass = integration.status === 'connected' ? 'connected' : 'disconnected';
        const buttonText = integration.status === 'connected' ? 'Disconnect' : 'Connect';
        const buttonClass = integration.status === 'connected' ? 'btn-outline-danger' : 'btn-outline-primary';
        
        html += `
            <div class="integration-item">
                <div class="integration-icon" style="background: ${integration.iconColor}; color: white;">
                    <i class="${integration.icon}"></i>
                </div>
                <div class="integration-info">
                    <div class="integration-name">${integration.name}</div>
                    <div class="integration-description">${integration.description}</div>
                </div>
                <div class="integration-status">
                    <span class="status-badge ${statusClass}">
                        ${integration.status === 'connected' ? 'Connected' : 'Disconnected'}
                    </span>
                    <button class="btn btn-sm ${buttonClass}" onclick="toggleIntegration('${integration.id}', '${integration.status}')">
                        ${buttonText}
                    </button>
                </div>
            </div>
        `;
    });
    
    $('#integrationsList').html(html);
}

function loadBackupsList() {
    // Mock backups data
    const backups = [
        {
            id: 'backup_001',
            name: 'Daily Backup - January 15, 2024',
            date: '2024-01-15 02:00:00',
            size: '45.2 MB',
            type: 'automatic'
        },
        {
            id: 'backup_002',
            name: 'Manual Backup - January 14, 2024',
            date: '2024-01-14 14:30:00',
            size: '44.8 MB',
            type: 'manual'
        },
        {
            id: 'backup_003',
            name: 'Daily Backup - January 14, 2024',
            date: '2024-01-14 02:00:00',
            size: '44.5 MB',
            type: 'automatic'
        }
    ];
    
    let html = '';
    backups.forEach(backup => {
        html += `
            <div class="backup-item">
                <div class="backup-info">
                    <div class="backup-name">
                        <i class="fas fa-database me-2"></i>
                        ${backup.name}
                    </div>
                    <div class="backup-date">
                        ${formatDateTime(backup.date)} • ${backup.type}
                    </div>
                </div>
                <div class="backup-size">${backup.size}</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="downloadBackup('${backup.id}')">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="restoreBackup('${backup.id}')">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('${backup.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    $('#backupsList').html(html);
}

// Save Functions
function saveGeneralSettings() {
    const settings = {
        companyName: $('#companyName').val(),
        timeZone: $('#timeZone').val(),
        dateFormat: $('#dateFormat').val(),
        currency: $('#currency').val(),
        language: $('#language').val()
    };
    
    // Simulate API call
    showAlert('General settings saved successfully!', 'success');
    console.log('Saving general settings:', settings);
}

function saveAppearanceSettings() {
    const selectedTheme = $('.theme-preview.selected').data('theme');
    const settings = {
        theme: selectedTheme,
        primaryColor: $('#primaryColor').val(),
        sidebarCollapsed: $('#sidebarCollapsed').is(':checked'),
        animations: $('#animations').is(':checked')
    };
    
    // Simulate API call
    showAlert('Appearance settings saved successfully!', 'success');
    console.log('Saving appearance settings:', settings);
}

function saveNotificationSettings() {
    const settings = {};
    $('#notificationSettings input[type="checkbox"]').each(function() {
        settings[$(this).attr('id')] = $(this).is(':checked');
    });
    
    // Simulate API call
    showAlert('Notification settings saved successfully!', 'success');
    console.log('Saving notification settings:', settings);
}

function saveSecuritySettings() {
    const settings = {};
    $('#securitySettings input[type="checkbox"]').each(function() {
        settings[$(this).attr('id')] = $(this).is(':checked');
    });
    
    // Simulate API call
    showAlert('Security settings saved successfully!', 'success');
    console.log('Saving security settings:', settings);
}

function saveApiSettings() {
    const settings = {
        apiAccess: $('#apiAccess').is(':checked'),
        rateLimit: $('#rateLimit').val()
    };
    
    // Simulate API call
    showAlert('API settings saved successfully!', 'success');
    console.log('Saving API settings:', settings);
}

function saveAdvancedSettings() {
    const settings = {
        debugMode: $('#debugMode').is(':checked'),
        cacheDuration: $('#cacheDuration').val(),
        sessionTimeout: $('#sessionTimeout').val()
    };
    
    // Simulate API call
    showAlert('Advanced settings saved successfully!', 'success');
    console.log('Saving advanced settings:', settings);
}

// Theme Functions
function applyTheme(theme) {
    // Apply theme changes to the interface
    console.log('Applying theme:', theme);
    
    // This would typically update CSS variables or classes
    switch(theme) {
        case 'dark':
            // Apply dark theme
            break;
        case 'green':
            // Apply green theme
            break;
        default:
            // Apply default theme
            break;
    }
}

// Integration Functions
function toggleIntegration(integrationId, currentStatus) {
    const newStatus = currentStatus === 'connected' ? 'disconnected' : 'connected';
    
    // Simulate API call
    console.log(`Toggling integration ${integrationId} to ${newStatus}`);
    
    // Reload integrations to reflect changes
    setTimeout(() => {
        loadIntegrations();
        showAlert(`Integration ${newStatus} successfully!`, 'success');
    }, 1000);
}

// Backup Functions
function createBackup() {
    showAlert('Creating backup...', 'info');
    
    // Simulate backup creation
    setTimeout(() => {
        showAlert('Backup created successfully!', 'success');
        loadBackupsList();
    }, 3000);
}

function uploadBackup() {
    $('#backupFile').click();
}

function restoreFromBackup(file) {
    if (!file) return;
    
    const fileName = file.name;
    const fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
    
    if (confirm(`Are you sure you want to restore from backup "${fileName}" (${fileSize})? This will overwrite all current data.`)) {
        showAlert('Restoring from backup...', 'info');
        
        // Simulate restore process
        setTimeout(() => {
            showAlert('Backup restored successfully!', 'success');
        }, 5000);
    }
}

function downloadBackup(backupId) {
    showAlert('Downloading backup...', 'info');
    
    // Simulate download
    setTimeout(() => {
        showAlert('Backup downloaded successfully!', 'success');
    }, 2000);
    
    console.log('Downloading backup:', backupId);
}

function restoreBackup(backupId) {
    if (confirm('Are you sure you want to restore this backup? This will overwrite all current data.')) {
        showAlert('Restoring backup...', 'info');
        
        // Simulate restore
        setTimeout(() => {
            showAlert('Backup restored successfully!', 'success');
        }, 5000);
    }
    
    console.log('Restoring backup:', backupId);
}

function deleteBackup(backupId) {
    if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        // Simulate deletion
        setTimeout(() => {
            showAlert('Backup deleted successfully!', 'success');
            loadBackupsList();
        }, 1000);
    }
    
    console.log('Deleting backup:', backupId);
}

// API Functions
function copyApiKey() {
    const apiKey = $('#apiKey').text();
    navigator.clipboard.writeText(apiKey).then(() => {
        showAlert('API key copied to clipboard!', 'success');
    }).catch(() => {
        showAlert('Failed to copy API key', 'error');
    });
}

function regenerateApiKey() {
    if (confirm('Are you sure you want to regenerate the API key? This will invalidate the current key.')) {
        // Generate new API key
        const newApiKey = 'sk-' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        $('#apiKey').text(newApiKey);
        showAlert('API key regenerated successfully!', 'success');
    }
}

// Advanced Functions
function clearCache() {
    if (confirm('Are you sure you want to clear the cache? This may temporarily slow down the system.')) {
        showAlert('Clearing cache...', 'info');
        
        // Simulate cache clearing
        setTimeout(() => {
            showAlert('Cache cleared successfully!', 'success');
        }, 2000);
    }
}

function resetSettings() {
    if (confirm('Are you sure you want to reset all settings to default? This action cannot be undone.')) {
        showAlert('Resetting settings...', 'info');
        
        // Simulate settings reset
        setTimeout(() => {
            showAlert('Settings reset successfully!', 'success');
            loadSettingsData();
        }, 3000);
    }
}

function deleteAllData() {
    const confirmation = prompt('This will permanently delete ALL data. Type "DELETE ALL DATA" to confirm:');
    
    if (confirmation === 'DELETE ALL DATA') {
        showAlert('Deleting all data...', 'warning');
        
        // Simulate data deletion
        setTimeout(() => {
            showAlert('All data deleted successfully!', 'success');
            // Redirect to setup page or login
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        }, 5000);
    } else if (confirmation !== null) {
        showAlert('Confirmation text did not match. Data deletion cancelled.', 'error');
    }
}

// Utility Functions
function showLoading() {
    $('#loadingState').show();
    $('#settingsContent').hide();
}

function hideLoading() {
    $('#loadingState').hide();
    $('#settingsContent').show();
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('authToken');
        window.location.href = 'login.html';
    }
}

// Export functions for global access
window.saveGeneralSettings = saveGeneralSettings;
window.saveAppearanceSettings = saveAppearanceSettings;
window.saveNotificationSettings = saveNotificationSettings;
window.saveSecuritySettings = saveSecuritySettings;
window.saveApiSettings = saveApiSettings;
window.saveAdvancedSettings = saveAdvancedSettings;
window.toggleIntegration = toggleIntegration;
window.createBackup = createBackup;
window.uploadBackup = uploadBackup;
window.downloadBackup = downloadBackup;
window.restoreBackup = restoreBackup;
window.deleteBackup = deleteBackup;
window.copyApiKey = copyApiKey;
window.regenerateApiKey = regenerateApiKey;
window.clearCache = clearCache;
window.resetSettings = resetSettings;
window.deleteAllData = deleteAllData;