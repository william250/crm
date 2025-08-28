/**
 * Login JavaScript
 * Handles user authentication and login functionality
 */

// Initialize login page when DOM is loaded
$(document).ready(function() {
    initializeLogin();
});

/**
 * Initialize login page
 */
function initializeLogin() {
    // Check if user is already logged in
    if (isAuthenticated()) {
        window.location.href = 'dashboard.html';
        return;
    }

    // Setup event listeners
    setupEventListeners();
    
    // Setup axios interceptors
    setupAxiosInterceptors();
    
    // Focus on email field
    $('#email').focus();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Login form submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        handleLogin();
    });
    
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        togglePasswordVisibility();
    });
    
    // Demo credentials click
    $('.demo-card').on('click', function() {
        const email = $(this).data('email');
        const password = $(this).data('password');
        
        $('#email').val(email);
        $('#password').val(password);
        
        // Add visual feedback
        $(this).addClass('selected');
        setTimeout(() => {
            $(this).removeClass('selected');
        }, 200);
    });
    
    // Forgot password link
    $('#forgotPasswordLink').on('click', function(e) {
        e.preventDefault();
        showAlert('info', 'Password reset functionality will be available soon. Please contact your administrator.');
    });
    
    // Enter key handling
    $('#email, #password').on('keypress', function(e) {
        if (e.which === 13) {
            $('#loginForm').submit();
        }
    });
    
    // Clear validation on input
    $('#email, #password').on('input', function() {
        clearFieldValidation($(this));
    });
}

/**
 * Handle login form submission
 */
function handleLogin() {
    // Clear previous alerts and validation
    clearAlerts();
    clearAllValidation();
    
    // Get form data
    const email = $('#email').val().trim();
    const password = $('#password').val();
    const rememberMe = $('#rememberMe').is(':checked');
    
    // Validate form
    if (!validateLoginForm(email, password)) {
        return;
    }
    
    // Show loading state
    setLoadingState(true);
    
    // Prepare login data
    const loginData = {
        email: email,
        password: password,
        remember_me: rememberMe
    };
    
    // Make login request
    axios.post('/api/auth/login', loginData)
        .then(response => {
            if (response.data.success) {
                handleLoginSuccess(response.data);
            } else {
                handleLoginError(response.data.message || 'Login failed');
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            
            let errorMessage = 'An error occurred during login. Please try again.';
            
            if (error.response) {
                if (error.response.status === 401) {
                    errorMessage = 'Invalid email or password. Please check your credentials and try again.';
                } else if (error.response.status === 429) {
                    errorMessage = 'Too many login attempts. Please wait a moment and try again.';
                } else if (error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                }
            } else if (error.request) {
                errorMessage = 'Unable to connect to the server. Please check your internet connection.';
            }
            
            handleLoginError(errorMessage);
        })
        .finally(() => {
            setLoadingState(false);
        });
}

/**
 * Validate login form
 */
function validateLoginForm(email, password) {
    let isValid = true;
    
    // Validate email
    if (!email) {
        setFieldValidation('#email', false, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        setFieldValidation('#email', false, 'Please enter a valid email address');
        isValid = false;
    }
    
    // Validate password
    if (!password) {
        setFieldValidation('#password', false, 'Password is required');
        isValid = false;
    } else if (password.length < 6) {
        setFieldValidation('#password', false, 'Password must be at least 6 characters long');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Handle successful login
 */
function handleLoginSuccess(data) {
    // Store authentication data - token and user are nested in data.data
    if (data.data && data.data.token) {
        localStorage.setItem('token', data.data.token);
    }
    
    if (data.data && data.data.user) {
        localStorage.setItem('user', JSON.stringify(data.data.user));
    }
    
    // Show success message
    showAlert('success', 'Login successful! Redirecting to dashboard...');
    
    // Redirect to dashboard after a short delay
    setTimeout(() => {
        window.location.href = 'dashboard.html';
    }, 1500);
}

/**
 * Handle login error
 */
function handleLoginError(message) {
    showAlert('danger', message);
    
    // Focus on email field for retry
    $('#email').focus();
}

/**
 * Toggle password visibility
 */
function togglePasswordVisibility() {
    const passwordField = $('#password');
    const toggleIcon = $('#togglePassword i');
    
    if (passwordField.attr('type') === 'password') {
        passwordField.attr('type', 'text');
        toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        passwordField.attr('type', 'password');
        toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

/**
 * Set loading state
 */
function setLoadingState(loading) {
    const loginBtn = $('#loginBtn');
    const btnText = loginBtn.find('.btn-text');
    const btnLoading = loginBtn.find('.btn-loading');
    
    if (loading) {
        loginBtn.prop('disabled', true);
        btnText.hide();
        btnLoading.show();
    } else {
        loginBtn.prop('disabled', false);
        btnText.show();
        btnLoading.hide();
    }
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertContainer').html(alertHtml);
    
    // Auto-dismiss success alerts
    if (type === 'success') {
        setTimeout(() => {
            $('.alert').alert('close');
        }, 3000);
    }
}

/**
 * Clear all alerts
 */
function clearAlerts() {
    $('#alertContainer').empty();
}

/**
 * Get alert icon based on type
 */
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Set field validation state
 */
function setFieldValidation(fieldSelector, isValid, message = '') {
    const field = $(fieldSelector);
    const feedback = field.siblings('.invalid-feedback');
    
    if (isValid) {
        field.removeClass('is-invalid').addClass('is-valid');
        feedback.text('');
    } else {
        field.removeClass('is-valid').addClass('is-invalid');
        feedback.text(message);
    }
}

/**
 * Clear field validation
 */
function clearFieldValidation(field) {
    field.removeClass('is-valid is-invalid');
    field.siblings('.invalid-feedback').text('');
}

/**
 * Clear all validation
 */
function clearAllValidation() {
    $('#email, #password').removeClass('is-valid is-invalid');
    $('.invalid-feedback').text('');
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    const token = localStorage.getItem('token');
    return token !== null && token !== '';
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
 * Setup axios interceptors
 */
function setupAxiosInterceptors() {
    // Request interceptor
    axios.interceptors.request.use(
        config => {
            // Add any default headers or configuration
            config.headers['Content-Type'] = 'application/json';
            return config;
        },
        error => {
            return Promise.reject(error);
        }
    );
    
    // Response interceptor
    axios.interceptors.response.use(
        response => response,
        error => {
            // Handle network errors
            if (!error.response) {
                console.error('Network error:', error);
            }
            return Promise.reject(error);
        }
    );
}

// Export functions for global access
window.handleLogin = handleLogin;
window.togglePasswordVisibility = togglePasswordVisibility;