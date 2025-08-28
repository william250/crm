// Reports Page JavaScript
$(document).ready(function() {
    // Initialize the page
    initializePage();
    
    // Set default date range (last 30 days)
    setDefaultDateRange();
    
    // Load initial report data
    generateReport();
    
    // Event listeners
    setupEventListeners();
});

function initializePage() {
    // Check authentication
    checkAuthentication();
    
    // Load user info
    loadUserInfo();
    
    // Initialize charts
    initializeCharts();
}

function checkAuthentication() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Set axios default header
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

function loadUserInfo() {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    if (userData.name) {
        $('#userName').text(userData.name);
    }
    
    // Show users link for admin
    if (userData.role === 'admin') {
        $('#usersLink').show();
    }
}

function setDefaultDateRange() {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    
    $('#startDate').val(formatDate(startDate));
    $('#endDate').val(formatDate(endDate));
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function setupEventListeners() {
    // Quick filter buttons
    $('.quick-filter-btn').click(function() {
        $('.quick-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        const period = $(this).data('period');
        if (period !== 'custom') {
            setQuickDateRange(period);
            generateReport();
        }
    });
    
    // Report type change
    $('#reportType').change(function() {
        generateReport();
    });
    
    // Group by change
    $('#groupBy').change(function() {
        generateReport();
    });
    
    // Logout
    $('#logoutBtn').click(function(e) {
        e.preventDefault();
        logout();
    });
    
    // Tab change events
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        loadTabData(target);
    });
}

function setQuickDateRange(days) {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);
    
    $('#startDate').val(formatDate(startDate));
    $('#endDate').val(formatDate(endDate));
}

function generateReport() {
    showLoading();
    
    const reportData = {
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        reportType: $('#reportType').val(),
        groupBy: $('#groupBy').val()
    };
    
    // Simulate API call with mock data
    setTimeout(() => {
        loadReportData(reportData);
        hideLoading();
    }, 1000);
}

function loadReportData(filters) {
    // Mock data for demonstration
    const mockData = generateMockReportData(filters);
    
    // Update KPIs
    updateKPIs(mockData.kpis);
    
    // Update charts based on active tab
    const activeTab = $('.tab-pane.active').attr('id');
    loadTabData('#' + activeTab, mockData);
}

function generateMockReportData(filters) {
    const startDate = new Date(filters.startDate);
    const endDate = new Date(filters.endDate);
    const daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    
    return {
        kpis: {
            totalRevenue: 125000 + Math.random() * 50000,
            totalDeals: 45 + Math.floor(Math.random() * 20),
            conversionRate: 15 + Math.random() * 10,
            avgDealSize: 2500 + Math.random() * 1000,
            revenueChange: (Math.random() - 0.5) * 30,
            dealsChange: (Math.random() - 0.5) * 40,
            conversionChange: (Math.random() - 0.5) * 20,
            dealSizeChange: (Math.random() - 0.5) * 25
        },
        revenue: generateTimeSeriesData(daysDiff, 1000, 5000),
        deals: generateDealStatusData(),
        pipeline: generatePipelineData(),
        clients: generateClientData(),
        financial: generateFinancialData(daysDiff)
    };
}

function generateTimeSeriesData(days, min, max) {
    const data = [];
    const labels = [];
    const endDate = new Date($('#endDate').val());
    
    for (let i = days - 1; i >= 0; i--) {
        const date = new Date(endDate);
        date.setDate(date.getDate() - i);
        labels.push(date.toLocaleDateString());
        data.push(min + Math.random() * (max - min));
    }
    
    return { labels, data };
}

function generateDealStatusData() {
    return {
        labels: ['Won', 'In Progress', 'Lost', 'Qualified'],
        data: [35, 25, 15, 25]
    };
}

function generatePipelineData() {
    return {
        labels: ['Lead', 'Qualified', 'Proposal', 'Negotiation', 'Closed Won'],
        data: [120000, 85000, 65000, 45000, 25000]
    };
}

function generateClientData() {
    return [
        { name: 'Acme Corp', revenue: 45000, deals: 8, lastActivity: '2024-01-15', status: 'Active' },
        { name: 'TechStart Inc', revenue: 32000, deals: 5, lastActivity: '2024-01-14', status: 'Active' },
        { name: 'Global Solutions', revenue: 28000, deals: 6, lastActivity: '2024-01-13', status: 'Active' },
        { name: 'Innovation Labs', revenue: 22000, deals: 4, lastActivity: '2024-01-12', status: 'Inactive' },
        { name: 'Future Systems', revenue: 18000, deals: 3, lastActivity: '2024-01-11', status: 'Active' }
    ];
}

function generateFinancialData(days) {
    const revenue = generateTimeSeriesData(days, 8000, 15000);
    const expenses = generateTimeSeriesData(days, 5000, 10000);
    
    return {
        labels: revenue.labels,
        revenue: revenue.data,
        expenses: expenses.data
    };
}

function updateKPIs(kpis) {
    $('#totalRevenue').text('$' + formatNumber(kpis.totalRevenue));
    $('#totalDeals').text(Math.round(kpis.totalDeals));
    $('#conversionRate').text(kpis.conversionRate.toFixed(1) + '%');
    $('#avgDealSize').text('$' + formatNumber(kpis.avgDealSize));
    
    // Update changes
    updateKPIChange('#revenueChange', kpis.revenueChange);
    updateKPIChange('#dealsChange', kpis.dealsChange);
    updateKPIChange('#conversionChange', kpis.conversionChange);
    updateKPIChange('#dealSizeChange', kpis.dealSizeChange);
}

function updateKPIChange(selector, change) {
    const element = $(selector);
    const isPositive = change >= 0;
    
    element.removeClass('positive negative');
    element.addClass(isPositive ? 'positive' : 'negative');
    element.text((isPositive ? '+' : '') + change.toFixed(1) + '%');
}

function loadTabData(tabId, data = null) {
    if (!data) {
        // Generate mock data if not provided
        data = generateMockReportData({
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            reportType: $('#reportType').val(),
            groupBy: $('#groupBy').val()
        });
    }
    
    switch (tabId) {
        case '#overview':
            updateRevenueChart(data.revenue);
            updateDealStatusChart(data.deals);
            break;
        case '#sales':
            updatePipelineChart(data.pipeline);
            updateSalesComparisonChart(data.revenue);
            updateSalesMetrics();
            break;
        case '#clients':
            updateClientAcquisitionChart(data.revenue);
            updateTopClientsChart(data.clients);
            updateClientsTable(data.clients);
            break;
        case '#financial':
            updateFinancialChart(data.financial);
            updateProfitMarginChart(data.financial);
            updateFinancialSummary(data.financial);
            break;
    }
}

// Chart initialization and updates
let charts = {};

function initializeCharts() {
    // Initialize empty charts
    charts.revenue = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: getLineChartOptions('Revenue ($)')
    });
    
    charts.dealStatus = new Chart(document.getElementById('dealStatusChart'), {
        type: 'doughnut',
        data: { labels: [], datasets: [] },
        options: getDoughnutChartOptions()
    });
    
    charts.pipeline = new Chart(document.getElementById('pipelineChart'), {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: getBarChartOptions('Pipeline Value ($)')
    });
    
    charts.salesComparison = new Chart(document.getElementById('salesComparisonChart'), {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: getBarChartOptions('Sales ($)')
    });
    
    charts.clientAcquisition = new Chart(document.getElementById('clientAcquisitionChart'), {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: getLineChartOptions('New Clients')
    });
    
    charts.topClients = new Chart(document.getElementById('topClientsChart'), {
        type: 'horizontalBar',
        data: { labels: [], datasets: [] },
        options: getHorizontalBarChartOptions('Revenue ($)')
    });
    
    charts.financial = new Chart(document.getElementById('financialChart'), {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: getLineChartOptions('Amount ($)')
    });
    
    charts.profitMargin = new Chart(document.getElementById('profitMarginChart'), {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: getLineChartOptions('Profit Margin (%)')
    });
}

function updateRevenueChart(data) {
    charts.revenue.data = {
        labels: data.labels,
        datasets: [{
            label: 'Revenue',
            data: data.data,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    charts.revenue.update();
}

function updateDealStatusChart(data) {
    charts.dealStatus.data = {
        labels: data.labels,
        datasets: [{
            data: data.data,
            backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
        }]
    };
    charts.dealStatus.update();
}

function updatePipelineChart(data) {
    charts.pipeline.data = {
        labels: data.labels,
        datasets: [{
            label: 'Pipeline Value',
            data: data.data,
            backgroundColor: '#667eea'
        }]
    };
    charts.pipeline.update();
}

function updateSalesComparisonChart(data) {
    // Create comparison data (current vs previous period)
    const currentData = data.data;
    const previousData = currentData.map(val => val * (0.8 + Math.random() * 0.4));
    
    charts.salesComparison.data = {
        labels: data.labels.slice(-7), // Last 7 days
        datasets: [{
            label: 'Current Period',
            data: currentData.slice(-7),
            backgroundColor: '#667eea'
        }, {
            label: 'Previous Period',
            data: previousData.slice(-7),
            backgroundColor: '#764ba2'
        }]
    };
    charts.salesComparison.update();
}

function updateClientAcquisitionChart(data) {
    // Convert revenue data to client acquisition data
    const clientData = data.data.map(val => Math.floor(val / 5000));
    
    charts.clientAcquisition.data = {
        labels: data.labels,
        datasets: [{
            label: 'New Clients',
            data: clientData,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    charts.clientAcquisition.update();
}

function updateTopClientsChart(clients) {
    const topClients = clients.slice(0, 5);
    
    charts.topClients.data = {
        labels: topClients.map(c => c.name),
        datasets: [{
            label: 'Revenue',
            data: topClients.map(c => c.revenue),
            backgroundColor: '#667eea'
        }]
    };
    charts.topClients.update();
}

function updateFinancialChart(data) {
    charts.financial.data = {
        labels: data.labels,
        datasets: [{
            label: 'Revenue',
            data: data.revenue,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: false
        }, {
            label: 'Expenses',
            data: data.expenses,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: false
        }]
    };
    charts.financial.update();
}

function updateProfitMarginChart(data) {
    const profitMargin = data.revenue.map((rev, i) => {
        const exp = data.expenses[i];
        return ((rev - exp) / rev * 100).toFixed(1);
    });
    
    charts.profitMargin.data = {
        labels: data.labels,
        datasets: [{
            label: 'Profit Margin (%)',
            data: profitMargin,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };
    charts.profitMargin.update();
}

function updateSalesMetrics() {
    const metrics = [
        { name: 'Leads Generated', current: 245, previous: 198, change: 23.7 },
        { name: 'Qualified Leads', current: 89, previous: 76, change: 17.1 },
        { name: 'Proposals Sent', current: 34, previous: 41, change: -17.1 },
        { name: 'Deals Closed', current: 18, previous: 15, change: 20.0 },
        { name: 'Average Deal Cycle', current: 28, previous: 32, change: -12.5 }
    ];
    
    let html = '';
    metrics.forEach(metric => {
        const isPositive = metric.change >= 0;
        const changeClass = isPositive ? 'positive' : 'negative';
        const changeIcon = isPositive ? '↗' : '↘';
        
        html += `
            <div class="metric-comparison">
                <div>
                    <div class="metric-name">${metric.name}</div>
                    <div class="metric-value">${metric.current}</div>
                </div>
                <div class="metric-change ${changeClass}">
                    ${changeIcon} ${Math.abs(metric.change).toFixed(1)}%
                </div>
            </div>
        `;
    });
    
    $('#salesMetrics').html(html);
}

function updateClientsTable(clients) {
    let html = '';
    clients.forEach(client => {
        const statusClass = client.status === 'Active' ? 'success' : 'secondary';
        html += `
            <tr>
                <td>${client.name}</td>
                <td>$${formatNumber(client.revenue)}</td>
                <td>${client.deals}</td>
                <td>${formatDate(new Date(client.lastActivity))}</td>
                <td><span class="badge bg-${statusClass}">${client.status}</span></td>
            </tr>
        `;
    });
    
    $('#clientsReportTable').html(html);
}

function updateFinancialSummary(data) {
    const totalRevenue = data.revenue.reduce((sum, val) => sum + val, 0);
    const totalExpenses = data.expenses.reduce((sum, val) => sum + val, 0);
    const netProfit = totalRevenue - totalExpenses;
    
    $('#financialRevenue').text('$' + formatNumber(totalRevenue));
    $('#financialExpenses').text('$' + formatNumber(totalExpenses));
    $('#financialProfit').text('$' + formatNumber(netProfit));
}

// Chart options
function getLineChartOptions(yAxisLabel) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: yAxisLabel
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    };
}

function getDoughnutChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    };
}

function getBarChartOptions(yAxisLabel) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: yAxisLabel
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    };
}

function getHorizontalBarChartOptions(xAxisLabel) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: xAxisLabel
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    };
}

// Export functions
function exportReport(format) {
    showAlert('info', `Exporting report in ${format.toUpperCase()} format...`);
    
    // Simulate export process
    setTimeout(() => {
        showAlert('success', `Report exported successfully in ${format.toUpperCase()} format!`);
    }, 2000);
}

function scheduleReport() {
    // Show modal or form for scheduling
    showAlert('info', 'Report scheduling feature coming soon!');
}

function resetFilters() {
    setDefaultDateRange();
    $('#reportType').val('overview');
    $('#groupBy').val('month');
    $('.quick-filter-btn').removeClass('active');
    $('.quick-filter-btn[data-period="30"]').addClass('active');
    generateReport();
}

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('en-US').format(Math.round(num));
}

function showLoading() {
    $('#loadingState').show();
    $('#reportTabsContent').hide();
}

function hideLoading() {
    $('#loadingState').hide();
    $('#reportTabsContent').show();
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
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

function logout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('userData');
    window.location.href = 'login.html';
}

// Error handling
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            logout();
        }
        return Promise.reject(error);
    }
);