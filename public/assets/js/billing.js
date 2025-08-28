$(document).ready(function() {
    // Initialize page
    initializePage();
    
    // Event listeners
    $('#searchInput').on('input', debounce(filterInvoices, 300));
    $('#statusFilter').on('change', filterInvoices);
    $('#dateFrom, #dateTo').on('change', filterInvoices);
    $('#resetFilters').on('click', resetFilters);
    $('#createInvoiceForm').on('submit', handleCreateInvoice);
    $('#logoutBtn').on('click', handleLogout);
    
    // Calculate total when invoice items change
    $(document).on('input', 'input[name="item_quantity[]"], input[name="item_price[]"], #taxRate, #discount', calculateTotal);
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('#invoiceDate').val(today);
    
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 30);
    $('#dueDate').val(dueDate.toISOString().split('T')[0]);
});

// Global variables
let currentPage = 1;
let itemsPerPage = 10;
let totalPages = 1;
let allInvoices = [];
let filteredInvoices = [];

// Mock data for invoices
const mockInvoices = [
    {
        id: 1,
        invoice_number: 'INV-2024-001',
        client_name: 'Acme Corporation',
        client_id: 1,
        amount: 2500.00,
        issue_date: '2024-01-15',
        due_date: '2024-02-15',
        status: 'paid',
        items: [
            { description: 'Web Development', quantity: 1, price: 2000.00 },
            { description: 'Hosting Setup', quantity: 1, price: 500.00 }
        ],
        tax_rate: 10,
        discount: 0,
        notes: 'Initial website development project'
    },
    {
        id: 2,
        invoice_number: 'INV-2024-002',
        client_name: 'Tech Solutions Ltd',
        client_id: 2,
        amount: 1800.00,
        issue_date: '2024-01-20',
        due_date: '2024-02-20',
        status: 'pending',
        items: [
            { description: 'Mobile App Development', quantity: 1, price: 1800.00 }
        ],
        tax_rate: 8,
        discount: 200,
        notes: 'Mobile application for iOS and Android'
    },
    {
        id: 3,
        invoice_number: 'INV-2024-003',
        client_name: 'Global Enterprises',
        client_id: 3,
        amount: 3200.00,
        issue_date: '2024-01-10',
        due_date: '2024-01-25',
        status: 'overdue',
        items: [
            { description: 'E-commerce Platform', quantity: 1, price: 3000.00 },
            { description: 'Payment Integration', quantity: 1, price: 200.00 }
        ],
        tax_rate: 12,
        discount: 0,
        notes: 'Complete e-commerce solution with payment gateway'
    },
    {
        id: 4,
        invoice_number: 'INV-2024-004',
        client_name: 'StartUp Inc',
        client_id: 4,
        amount: 1200.00,
        issue_date: '2024-01-25',
        due_date: '2024-02-25',
        status: 'draft',
        items: [
            { description: 'Logo Design', quantity: 1, price: 500.00 },
            { description: 'Brand Guidelines', quantity: 1, price: 700.00 }
        ],
        tax_rate: 5,
        discount: 0,
        notes: 'Brand identity package for startup'
    }
];

// Mock clients data
const mockClients = [
    { id: 1, name: 'Acme Corporation' },
    { id: 2, name: 'Tech Solutions Ltd' },
    { id: 3, name: 'Global Enterprises' },
    { id: 4, name: 'StartUp Inc' },
    { id: 5, name: 'Digital Agency' }
];

function initializePage() {
    // Check authentication
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Set up axios defaults
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    // Load user info
    loadUserInfo();
    
    // Load invoices
    loadInvoices();
    
    // Load clients for dropdown
    loadClients();
}

function loadUserInfo() {
    try {
        const userInfo = JSON.parse(localStorage.getItem('userInfo') || '{}');
        if (userInfo.name) {
            $('#userName').text(userInfo.name);
        }
        
        // Show users link for admin
        if (userInfo.role === 'admin') {
            $('#usersLink').show();
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

function loadInvoices() {
    showLoading();
    
    // Simulate API call
    setTimeout(() => {
        allInvoices = [...mockInvoices];
        filteredInvoices = [...allInvoices];
        
        updateStatistics();
        displayInvoices();
        hideLoading();
    }, 1000);
}

function loadClients() {
    const clientSelect = $('#clientSelect');
    clientSelect.empty().append('<option value="">Select a client</option>');
    
    mockClients.forEach(client => {
        clientSelect.append(`<option value="${client.id}">${client.name}</option>`);
    });
}

function updateStatistics() {
    const stats = {
        totalRevenue: 0,
        pendingAmount: 0,
        overdueAmount: 0,
        totalInvoices: allInvoices.length
    };
    
    allInvoices.forEach(invoice => {
        if (invoice.status === 'paid') {
            stats.totalRevenue += invoice.amount;
        } else if (invoice.status === 'pending') {
            stats.pendingAmount += invoice.amount;
        } else if (invoice.status === 'overdue') {
            stats.overdueAmount += invoice.amount;
        }
    });
    
    $('#totalRevenue').text(`$${stats.totalRevenue.toLocaleString()}`);
    $('#pendingAmount').text(`$${stats.pendingAmount.toLocaleString()}`);
    $('#overdueAmount').text(`$${stats.overdueAmount.toLocaleString()}`);
    $('#totalInvoices').text(stats.totalInvoices);
}

function displayInvoices() {
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const invoicesToShow = filteredInvoices.slice(startIndex, endIndex);
    
    const tbody = $('#invoicesTableBody');
    tbody.empty();
    
    if (invoicesToShow.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    invoicesToShow.forEach(invoice => {
        const statusClass = `status-${invoice.status}`;
        const statusText = invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1);
        
        const row = `
            <tr>
                <td>
                    <div class="invoice-number">${invoice.invoice_number}</div>
                </td>
                <td>${invoice.client_name}</td>
                <td>
                    <div class="invoice-amount">$${invoice.amount.toLocaleString()}</div>
                </td>
                <td>${formatDate(invoice.issue_date)}</td>
                <td>${formatDate(invoice.due_date)}</td>
                <td>
                    <span class="badge bg-light text-dark invoice-status ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td>
                    <div class="invoice-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewInvoice(${invoice.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="editInvoice(${invoice.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="downloadInvoice(${invoice.id})" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteInvoice(${invoice.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
    
    updatePagination();
}

function updatePagination() {
    totalPages = Math.ceil(filteredInvoices.length / itemsPerPage);
    
    const startRecord = filteredInvoices.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0;
    const endRecord = Math.min(currentPage * itemsPerPage, filteredInvoices.length);
    
    $('#showingStart').text(startRecord);
    $('#showingEnd').text(endRecord);
    $('#totalRecords').text(filteredInvoices.length);
    
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
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            pagination.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `);
        } else if (i === currentPage - 2 || i === currentPage + 2) {
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

function changePage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        displayInvoices();
    }
}

function filterInvoices() {
    const searchTerm = $('#searchInput').val().toLowerCase();
    const statusFilter = $('#statusFilter').val();
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();
    
    filteredInvoices = allInvoices.filter(invoice => {
        const matchesSearch = !searchTerm || 
            invoice.invoice_number.toLowerCase().includes(searchTerm) ||
            invoice.client_name.toLowerCase().includes(searchTerm);
        
        const matchesStatus = !statusFilter || invoice.status === statusFilter;
        
        const matchesDateFrom = !dateFrom || invoice.issue_date >= dateFrom;
        const matchesDateTo = !dateTo || invoice.issue_date <= dateTo;
        
        return matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo;
    });
    
    currentPage = 1;
    displayInvoices();
}

function resetFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('');
    $('#dateFrom').val('');
    $('#dateTo').val('');
    
    filteredInvoices = [...allInvoices];
    currentPage = 1;
    displayInvoices();
}

function handleCreateInvoice(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const invoiceData = {
        client_id: formData.get('client_id'),
        issue_date: formData.get('issue_date'),
        due_date: formData.get('due_date'),
        status: formData.get('status'),
        tax_rate: parseFloat(formData.get('tax_rate')) || 0,
        discount: parseFloat(formData.get('discount')) || 0,
        notes: formData.get('notes'),
        items: []
    };
    
    // Collect invoice items
    const descriptions = formData.getAll('item_description[]');
    const quantities = formData.getAll('item_quantity[]');
    const prices = formData.getAll('item_price[]');
    
    for (let i = 0; i < descriptions.length; i++) {
        if (descriptions[i] && quantities[i] && prices[i]) {
            invoiceData.items.push({
                description: descriptions[i],
                quantity: parseInt(quantities[i]),
                price: parseFloat(prices[i])
            });
        }
    }
    
    if (invoiceData.items.length === 0) {
        showAlert('Please add at least one invoice item.', 'warning');
        return;
    }
    
    // Calculate total amount
    const subtotal = invoiceData.items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
    const taxAmount = (subtotal * invoiceData.tax_rate) / 100;
    const totalAmount = subtotal + taxAmount - invoiceData.discount;
    
    // Create new invoice
    const newInvoice = {
        id: Date.now(),
        invoice_number: `INV-2024-${String(allInvoices.length + 1).padStart(3, '0')}`,
        client_name: mockClients.find(c => c.id == invoiceData.client_id)?.name || 'Unknown Client',
        amount: totalAmount,
        ...invoiceData
    };
    
    // Add to invoices array
    allInvoices.unshift(newInvoice);
    filteredInvoices = [...allInvoices];
    
    // Update display
    updateStatistics();
    displayInvoices();
    
    // Reset form and close modal
    $('#createInvoiceForm')[0].reset();
    $('#createInvoiceModal').modal('hide');
    
    showAlert('Invoice created successfully!', 'success');
}

function addInvoiceItem() {
    const itemHtml = `
        <div class="row invoice-item mb-2">
            <div class="col-md-5">
                <input type="text" class="form-control" name="item_description[]" placeholder="Description" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="item_quantity[]" placeholder="Qty" min="1" value="1" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" name="item_price[]" placeholder="Price" step="0.01" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger" onclick="removeInvoiceItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    $('#invoiceItems').append(itemHtml);
}

function removeInvoiceItem(button) {
    $(button).closest('.invoice-item').remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    
    $('.invoice-item').each(function() {
        const quantity = parseFloat($(this).find('input[name="item_quantity[]"]').val()) || 0;
        const price = parseFloat($(this).find('input[name="item_price[]"]').val()) || 0;
        subtotal += quantity * price;
    });
    
    const taxRate = parseFloat($('#taxRate').val()) || 0;
    const discount = parseFloat($('#discount').val()) || 0;
    
    const taxAmount = (subtotal * taxRate) / 100;
    const total = subtotal + taxAmount - discount;
    
    $('#totalAmount').text(total.toFixed(2));
}

function viewInvoice(id) {
    const invoice = allInvoices.find(inv => inv.id === id);
    if (invoice) {
        showAlert(`Viewing invoice: ${invoice.invoice_number}`, 'info');
        // Here you would typically open a detailed view modal or navigate to a detail page
    }
}

function editInvoice(id) {
    const invoice = allInvoices.find(inv => inv.id === id);
    if (invoice) {
        showAlert(`Editing invoice: ${invoice.invoice_number}`, 'info');
        // Here you would populate the edit form with invoice data
    }
}

function downloadInvoice(id) {
    const invoice = allInvoices.find(inv => inv.id === id);
    if (invoice) {
        showAlert(`Downloading invoice: ${invoice.invoice_number}`, 'success');
        // Here you would generate and download the PDF invoice
    }
}

function deleteInvoice(id) {
    if (confirm('Are you sure you want to delete this invoice?')) {
        allInvoices = allInvoices.filter(inv => inv.id !== id);
        filteredInvoices = filteredInvoices.filter(inv => inv.id !== id);
        
        updateStatistics();
        displayInvoices();
        
        showAlert('Invoice deleted successfully!', 'success');
    }
}

function exportInvoices() {
    showAlert('Exporting invoices...', 'info');
    // Here you would implement the export functionality
}

function showLoading() {
    $('#loadingState').show();
    $('#invoicesTable').closest('.billing-card').hide();
    $('#emptyState').hide();
}

function hideLoading() {
    $('#loadingState').hide();
    $('#invoicesTable').closest('.billing-card').show();
}

function showEmptyState() {
    $('#emptyState').show();
    $('#invoicesTable').closest('.billing-card').hide();
}

function hideEmptyState() {
    $('#emptyState').hide();
    $('#invoicesTable').closest('.billing-card').show();
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
    
    // Add new alert at the top of main content
    $('.main-content').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('token');
        localStorage.removeItem('userInfo');
        window.location.href = 'login.html';
    }
}

// Utility function for debouncing
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