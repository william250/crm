$(document).ready(function() {
    // Initialize page
    initializePage();
    
    // Event listeners
    $('#searchInput').on('input', debounce(filterInvoices, 300));
    $('#statusFilter').on('change', filterInvoices);
    $('#dateFrom, #dateTo').on('change', filterInvoices);
    $('#resetFilters').on('click', resetFilters);
    $('#createInvoiceForm').on('submit', handleCreateInvoice);
    $('#exportBtn').on('click', exportInvoices);
    
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
        window.location.href = 'login.php';
        return;
    }
    
    // Set up axios defaults
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    showLoading();
    
    // Load invoices
    loadInvoices();
    
    // Load clients for dropdown
    loadClients();
    
    // Generate next invoice number
    generateInvoiceNumber();
}

// Removed loadUserInfo function as it's handled by header component

function loadInvoices() {
    axios.get('/api/billing/invoices')
        .then(response => {
            console.log('API Response:', response.data); // Debug log
            allInvoices = response.data.data || [];
            filteredInvoices = [...allInvoices];
            
            updateStatistics();
            displayInvoices();
            hideLoading();
            $('#billingContent').show();
        })
        .catch(error => {
            console.error('Error loading invoices:', error);
            showAlert('Error loading invoices. Please try again.', 'error');
            hideLoading();
        });
}

function loadClients() {
    axios.get('/api/crm/clients')
        .then(response => {
            const clients = response.data.clients || [];
            const clientSelect = $('#clientSelect');
            clientSelect.empty().append('<option value="">Select a client</option>');
            
            clients.forEach(client => {
                clientSelect.append(`<option value="${client.id}">${client.name}</option>`);
            });
        })
        .catch(error => {
            console.error('Error loading clients:', error);
            showAlert('Error loading clients. Please try again.', 'error');
        });
}

function updateStatistics() {
    const totalRevenue = allInvoices
        .filter(invoice => invoice.status === 'paid')
        .reduce((sum, invoice) => sum + parseFloat(invoice.amount || 0), 0);
    
    const paidCount = allInvoices.filter(invoice => invoice.status === 'paid').length;
    const pendingCount = allInvoices.filter(invoice => invoice.status === 'pending').length;
    const overdueCount = allInvoices.filter(invoice => invoice.status === 'overdue').length;
    
    $('#totalRevenue').text(`R$ ${totalRevenue.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`);
    $('#paidInvoices').text(paidCount);
    $('#pendingInvoices').text(pendingCount);
    $('#overdueInvoices').text(overdueCount);
}

function displayInvoices() {
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const invoicesToShow = filteredInvoices.slice(startIndex, endIndex);
    
    const tbody = $('#invoicesTableBody');
    tbody.empty();
    
    if (invoicesToShow.length === 0) {
        showEmptyState();
        $('#paginationContainer').hide();
        return;
    }
    
    hideEmptyState();
    
    invoicesToShow.forEach(invoice => {
        const statusClass = getStatusClass(invoice.status);
        const statusText = getStatusText(invoice.status);
        
        const row = `
            <tr>
                <td>
                    <div class="fw-bold">${invoice.invoice_number || 'N/A'}</div>
                </td>
                <td>${invoice.client_name || 'N/A'}</td>
                <td>
                    <div class="fw-bold">R$ ${parseFloat(invoice.amount || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                </td>
                <td>${formatDate(invoice.created_at)}</td>
                <td>${formatDate(invoice.due_date)}</td>
                <td>
                    <span class="badge ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewInvoice(${invoice.id})" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="editInvoice(${invoice.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="downloadInvoice(${invoice.id})" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteInvoice(${invoice.id})" title="Excluir">
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
    const paginationContainer = $('#paginationContainer');
    
    pagination.empty();
    
    if (totalPages <= 1) {
        paginationContainer.hide();
        return;
    }
    
    paginationContainer.show();
    
    // Previous button
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    pagination.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`);
        if (startPage > 2) {
            pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        pagination.append(`
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
        pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a></li>`);
    }
    
    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    pagination.append(`
        <li class="page-item ${nextDisabled}">
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

async function handleCreateInvoice(e) {
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
    
    invoiceData.subtotal = subtotal;
    invoiceData.tax_amount = taxAmount;
    invoiceData.total = totalAmount;
    
    try {
        const response = await axios.post('/api/billing/invoices', invoiceData);
        
        // Reset form and close modal
        $('#createInvoiceForm')[0].reset();
        $('#createInvoiceModal').modal('hide');
        
        // Reload invoices
        await loadInvoices();
        
        showAlert('Invoice created successfully!', 'success');
    } catch (error) {
        console.error('Error creating invoice:', error);
        showAlert('Error creating invoice. Please try again.', 'error');
    }
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

function downloadInvoice(id) {
    const invoice = allInvoices.find(inv => inv.id === id);
    if (invoice) {
        showAlert(`Downloading invoice: ${invoice.invoice_number}`, 'success');
        // Here you would generate and download the PDF invoice
    }
}

function exportInvoices() {
    showAlert('Exporting invoices...', 'info');
    // Here you would implement the export functionality
}

async function editInvoice(invoiceId) {
    try {
        const response = await axios.get(`/api/billing/invoices/${invoiceId}`);
        const invoice = response.data;
        
        // Populate form with invoice data
        $('#clientSelect').val(invoice.client_id);
        $('#invoiceNumber').val(invoice.invoice_number);
        $('#issueDate').val(invoice.issue_date);
        $('#dueDate').val(invoice.due_date);
        $('#taxRate').val(invoice.tax_rate || 0);
        $('#discount').val(invoice.discount || 0);
        $('#notes').val(invoice.notes || '');
        
        // Set form to edit mode
        $('#newInvoiceModal').data('edit-id', invoiceId);
        $('#newInvoiceModalLabel').text('Editar Fatura');
        $('#newInvoiceModal').modal('show');
        
    } catch (error) {
        console.error('Erro ao carregar fatura:', error);
        alert('Erro ao carregar dados da fatura.');
    }
}

async function deleteInvoice(invoiceId) {
    if (!confirm('Tem certeza que deseja excluir esta fatura?')) {
        return;
    }
    
    try {
        await axios.delete(`/api/billing/invoices/${invoiceId}`);
        await loadInvoices();
        alert('Fatura excluída com sucesso!');
    } catch (error) {
        console.error('Erro ao excluir fatura:', error);
        alert('Erro ao excluir fatura. Tente novamente.');
    }
}

function viewInvoice(invoiceId) {
    // Redirect to invoice view page or open modal
    window.open(`/invoice-view.php?id=${invoiceId}`, '_blank');
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
        window.location.href = 'login.php';
    }
}

// Utility function for debouncing
function getStatusClass(status) {
    const statusClasses = {
        'paid': 'bg-success',
        'pending': 'bg-warning',
        'overdue': 'bg-danger',
        'draft': 'bg-secondary'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getStatusText(status) {
    const statusTexts = {
        'paid': 'Pago',
        'pending': 'Pendente',
        'overdue': 'Vencido',
        'draft': 'Rascunho'
    };
    return statusTexts[status] || 'Desconhecido';
}

function generateInvoiceNumber() {
    const nextNumber = allInvoices.length + 1;
    const invoiceNumber = `INV-2024-${String(nextNumber).padStart(3, '0')}`;
    $('#invoiceNumber').val(invoiceNumber);
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