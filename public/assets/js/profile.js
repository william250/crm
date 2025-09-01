// Profile Management JavaScript

// Global variables
let currentUser = null;
let originalFormData = {};
let notificationSettings = {};

// Initialize page when DOM is loaded
$(document).ready(function() {
    initializePage();
});

// Initialize the profile page
function initializePage() {
    checkAuthentication();
    loadUserInfo();
    setupEventListeners();
    loadProfileData();
    loadSecuritySettings();
    loadNotificationSettings();
    loadActivityHistory();
}

// Check if user is authenticated
function checkAuthentication() {
    const token = localStorage.getItem('token');
    console.log('Token from localStorage:', token);
    
    if (!token) {
        console.log('No token found, redirecting to login');
        window.location.href = 'login.php';
        return false;
    }
    
    // Set up axios defaults
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    console.log('Authorization header set:', axios.defaults.headers.common['Authorization']);
    
    // Handle token expiration
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                console.log('Token expired or invalid, removing and redirecting');
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = 'login.php';
            }
            return Promise.reject(error);
        }
    );
    
    return true;
}

// Load user information
function loadUserInfo() {
    const userInfo = JSON.parse(localStorage.getItem('userInfo') || '{}');
    currentUser = userInfo;
    
    if (userInfo.name) {
        $('#userName').text(userInfo.name);
    }
    
    // Show admin links if user is admin
    if (userInfo.role === 'admin') {
        $('#usersLink').show();
    }
}

// Setup event listeners
function setupEventListeners() {
    // Logout functionality
    $('#logoutBtn').click(function(e) {
        e.preventDefault();
        logout();
    });
    
    // Personal info form submission
    $('#personalInfoForm').submit(function(e) {
        e.preventDefault();
        savePersonalInfo();
    });
    
    // Password form submission
    $('#passwordForm').submit(function(e) {
        e.preventDefault();
        changePassword();
    });
    
    // Avatar upload
    $('#avatarInput').change(function(e) {
        handleAvatarUpload(e);
    });
    
    // Tab switching
    $('#profileTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#activity') {
            loadActivityHistory();
        }
    });
}

// Load profile data
function loadProfileData() {
    showLoading();
    
    // Make API call to get current user profile
    axios.get('/api/auth/profile')
        .then(response => {
            console.log('API Response:', response.data);
            if (response.data.success) {
                const userData = response.data.data;
                console.log('User Data:', userData);
                
                // Check if userData exists
                if (!userData) {
                    showAlert('Dados do usuário não encontrados.', 'error');
                    return;
                }
                
                // Transform API data to match expected format
                const profileData = {
                    id: userData.id || null,
                    firstName: userData.name ? userData.name.split(' ')[0] : '',
                    lastName: userData.name ? userData.name.split(' ').slice(1).join(' ') : '',
                    email: userData.email || '',
                    phone: userData.phone || '',
                    jobTitle: userData.job_title || userData.role || '',
                    department: userData.department || '',
                    bio: userData.bio || '',
                    address: userData.address || '',
                    city: userData.city || '',
                    state: userData.state || '',
                    zipCode: userData.zip_code || '',
                    avatar: userData.avatar || null,
                    stats: {
                        deals: userData.deals_count || 0,
                        revenue: userData.total_revenue || 0,
                        clients: userData.clients_count || 0
                    },
                    joinDate: userData.created_at || '',
                    lastLogin: userData.last_login || ''
                };
                
                populateProfileData(profileData);
            } else {
                showAlert('Erro ao carregar dados do perfil: ' + response.data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading profile data:', error);
            showAlert('Erro ao carregar dados do perfil. Tente novamente.', 'error');
        })
        .finally(() => {
            hideLoading();
        });
}

// Populate profile data in the form
function populateProfileData(data) {
    // Store original data for reset functionality
    originalFormData = { ...data };
    
    // Update profile header
    $('#profileName').text(`${data.firstName} ${data.lastName}`);
    $('#profileRole').text(data.jobTitle || 'Employee');
    
    // Update stats
    $('#statDeals').text(data.stats.deals);
    $('#statRevenue').text(formatCurrency(data.stats.revenue));
    $('#statClients').text(data.stats.clients);
    
    // Update avatar
    if (data.avatar) {
        $('#avatarImage').attr('src', data.avatar).show();
        $('#avatarIcon').hide();
    }
    
    // Populate form fields
    $('#firstName').val(data.firstName);
    $('#lastName').val(data.lastName);
    $('#email').val(data.email);
    $('#phone').val(data.phone);
    $('#jobTitle').val(data.jobTitle);
    $('#department').val(data.department);
    $('#bio').val(data.bio);
    $('#address').val(data.address);
    $('#city').val(data.city);
    $('#state').val(data.state);
    $('#zipCode').val(data.zipCode);
}

// Save personal information
function savePersonalInfo() {
    const formData = {
        firstName: $('#firstName').val(),
        lastName: $('#lastName').val(),
        email: $('#email').val(),
        phone: $('#phone').val(),
        jobTitle: $('#jobTitle').val(),
        department: $('#department').val(),
        bio: $('#bio').val(),
        address: $('#address').val(),
        city: $('#city').val(),
        state: $('#state').val(),
        zipCode: $('#zipCode').val()
    };
    
    // Validate required fields
    if (!formData.firstName || !formData.lastName || !formData.email) {
        showAlert('Please fill in all required fields.', 'error');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
        showAlert('Please enter a valid email address.', 'error');
        return;
    }
    
    showLoading();
    
    // Simulate API call - replace with actual API endpoint
    setTimeout(() => {
        try {
            // Update profile header
            $('#profileName').text(`${formData.firstName} ${formData.lastName}`);
            $('#profileRole').text(formData.jobTitle || 'Employee');
            
            // Update user info in localStorage
            const userInfo = JSON.parse(localStorage.getItem('userInfo') || '{}');
            userInfo.name = `${formData.firstName} ${formData.lastName}`;
            userInfo.email = formData.email;
            localStorage.setItem('userInfo', JSON.stringify(userInfo));
            
            // Update original data
            originalFormData = { ...originalFormData, ...formData };
            
            hideLoading();
            showAlert('Profile updated successfully!', 'success');
        } catch (error) {
            hideLoading();
            showAlert('Error updating profile. Please try again.', 'error');
        }
    }, 1500);
}

// Reset personal form to original data
function resetPersonalForm() {
    if (originalFormData) {
        populateProfileData(originalFormData);
        showAlert('Form reset to original values.', 'info');
    }
}

// Change password
function changePassword() {
    const currentPassword = $('#currentPassword').val();
    const newPassword = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();
    
    // Validate passwords
    if (!currentPassword || !newPassword || !confirmPassword) {
        showAlert('Please fill in all password fields.', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert('New passwords do not match.', 'error');
        return;
    }
    
    if (newPassword.length < 8) {
        showAlert('New password must be at least 8 characters long.', 'error');
        return;
    }
    
    showLoading();
    
    // Simulate API call - replace with actual API endpoint
    setTimeout(() => {
        try {
            // Clear password fields
            $('#passwordForm')[0].reset();
            
            hideLoading();
            showAlert('Password changed successfully!', 'success');
        } catch (error) {
            hideLoading();
            showAlert('Error changing password. Please try again.', 'error');
        }
    }, 1500);
}

// Handle avatar upload
function handleAvatarUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showAlert('Please select a valid image file.', 'error');
        return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('Image size must be less than 5MB.', 'error');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const imageUrl = e.target.result;
        
        // Update avatar display
        $('#avatarImage').attr('src', imageUrl).show();
        $('#avatarIcon').hide();
        
        // Simulate upload - replace with actual upload logic
        showAlert('Avatar updated successfully!', 'success');
    };
    
    reader.readAsDataURL(file);
}

// Trigger avatar upload
function triggerAvatarUpload() {
    $('#avatarInput').click();
}

// Load security settings
function loadSecuritySettings() {
    const mockSecuritySettings = [
        {
            id: 'two_factor',
            title: 'Two-Factor Authentication',
            description: 'Add an extra layer of security to your account',
            enabled: false,
            action: 'Enable'
        },
        {
            id: 'login_alerts',
            title: 'Login Alerts',
            description: 'Get notified when someone logs into your account',
            enabled: true,
            action: 'Disable'
        },
        {
            id: 'session_timeout',
            title: 'Session Timeout',
            description: 'Automatically log out after 30 minutes of inactivity',
            enabled: true,
            action: 'Disable'
        }
    ];
    
    const container = $('#securitySettings');
    container.empty();
    
    mockSecuritySettings.forEach(setting => {
        const settingHtml = `
            <div class="security-item">
                <div class="security-info">
                    <div class="security-title">${setting.title}</div>
                    <div class="security-description">${setting.description}</div>
                </div>
                <div class="security-status">
                    <span class="status-badge ${setting.enabled ? 'enabled' : 'disabled'}">
                        ${setting.enabled ? 'Enabled' : 'Disabled'}
                    </span>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleSecuritySetting('${setting.id}')">
                        ${setting.action}
                    </button>
                </div>
            </div>
        `;
        container.append(settingHtml);
    });
}

// Toggle security setting
function toggleSecuritySetting(settingId) {
    showLoading();
    
    // Simulate API call
    setTimeout(() => {
        loadSecuritySettings();
        hideLoading();
        showAlert('Security setting updated successfully!', 'success');
    }, 1000);
}

// Load notification settings
function loadNotificationSettings() {
    const mockEmailNotifications = [
        { id: 'new_deals', title: 'New Deals', description: 'When a new deal is created', enabled: true },
        { id: 'deal_updates', title: 'Deal Updates', description: 'When deals are updated or moved', enabled: true },
        { id: 'client_messages', title: 'Client Messages', description: 'When clients send messages', enabled: false },
        { id: 'weekly_reports', title: 'Weekly Reports', description: 'Weekly performance summaries', enabled: true }
    ];
    
    const mockPushNotifications = [
        { id: 'urgent_deals', title: 'Urgent Deals', description: 'High priority deal notifications', enabled: true },
        { id: 'meeting_reminders', title: 'Meeting Reminders', description: 'Upcoming meeting notifications', enabled: true },
        { id: 'task_deadlines', title: 'Task Deadlines', description: 'Task deadline reminders', enabled: false }
    ];
    
    renderNotificationSettings('#emailNotifications', mockEmailNotifications);
    renderNotificationSettings('#pushNotifications', mockPushNotifications);
    
    // Store settings for saving
    notificationSettings = {
        email: mockEmailNotifications,
        push: mockPushNotifications
    };
}

// Render notification settings
function renderNotificationSettings(containerId, settings) {
    const container = $(containerId);
    container.empty();
    
    settings.forEach(setting => {
        const settingHtml = `
            <div class="notification-item">
                <div class="notification-info">
                    <div class="notification-title">${setting.title}</div>
                    <div class="notification-description">${setting.description}</div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="${setting.id}" ${setting.enabled ? 'checked' : ''}>
                </div>
            </div>
        `;
        container.append(settingHtml);
    });
}

// Save notification settings
function saveNotificationSettings() {
    showLoading();
    
    // Collect current settings
    const emailSettings = notificationSettings.email.map(setting => ({
        ...setting,
        enabled: $(`#${setting.id}`).is(':checked')
    }));
    
    const pushSettings = notificationSettings.push.map(setting => ({
        ...setting,
        enabled: $(`#${setting.id}`).is(':checked')
    }));
    
    // Simulate API call
    setTimeout(() => {
        notificationSettings = {
            email: emailSettings,
            push: pushSettings
        };
        
        hideLoading();
        showAlert('Notification settings saved successfully!', 'success');
    }, 1000);
}

// Load activity history
function loadActivityHistory() {
    const mockActivities = [
        {
            id: 1,
            type: 'login',
            title: 'Logged in from Chrome on Windows',
            time: '2024-01-15T10:30:00Z',
            icon: 'fas fa-sign-in-alt',
            iconClass: 'login'
        },
        {
            id: 2,
            type: 'update',
            title: 'Updated profile information',
            time: '2024-01-14T15:45:00Z',
            icon: 'fas fa-user-edit',
            iconClass: 'update'
        },
        {
            id: 3,
            type: 'security',
            title: 'Changed password',
            time: '2024-01-12T09:20:00Z',
            icon: 'fas fa-key',
            iconClass: 'security'
        },
        {
            id: 4,
            type: 'login',
            title: 'Logged in from Mobile Safari',
            time: '2024-01-11T18:15:00Z',
            icon: 'fas fa-mobile-alt',
            iconClass: 'login'
        },
        {
            id: 5,
            type: 'update',
            title: 'Updated notification settings',
            time: '2024-01-10T14:30:00Z',
            icon: 'fas fa-bell',
            iconClass: 'update'
        }
    ];
    
    const container = $('#activityList');
    container.empty();
    
    if (mockActivities.length === 0) {
        container.html(`
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <p class="text-muted">No activity history available.</p>
            </div>
        `);
        return;
    }
    
    mockActivities.forEach(activity => {
        const activityHtml = `
            <div class="activity-item">
                <div class="activity-icon ${activity.iconClass}">
                    <i class="${activity.icon}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${activity.title}</div>
                    <div class="activity-time">${formatDateTime(activity.time)}</div>
                </div>
            </div>
        `;
        container.append(activityHtml);
    });
}

// Logout function
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.php';
}

// Utility functions
function showLoading() {
    $('#loadingState').show();
    $('#profileContent').hide();
}

function hideLoading() {
    $('#loadingState').hide();
    $('#profileContent').show();
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
    
    // Add new alert at the top of the main content
    $('.main-content').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now - date) / (1000 * 60 * 60);
    
    if (diffInHours < 1) {
        const diffInMinutes = Math.floor((now - date) / (1000 * 60));
        return `${diffInMinutes} minutes ago`;
    } else if (diffInHours < 24) {
        return `${Math.floor(diffInHours)} hours ago`;
    } else if (diffInHours < 48) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}