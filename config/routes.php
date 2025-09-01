<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CrmController;
use App\Controllers\SchedulingController;
use App\Controllers\BillingController;
use App\Controllers\ContractController;
use App\Controllers\ReportController;
use App\Middleware\AuthMiddleware;

// Authentication routes (no middleware required)
$app->group('/api/auth', function (RouteCollectorProxy $group) {
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/register', [AuthController::class, 'register']);
    $group->post('/forgot-password', [AuthController::class, 'forgotPassword']);
    $group->post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected API routes
$app->group('/api', function (RouteCollectorProxy $group) {
    
    // Dashboard
    $group->get('/dashboard', [DashboardController::class, 'index']);
    $group->get('/dashboard/metrics', [DashboardController::class, 'getMetrics']);
    
    // CRM Module
    $group->group('/crm', function (RouteCollectorProxy $group) {
        // Leads
        $group->get('/leads', [CrmController::class, 'getLeads']);
        $group->post('/leads', [CrmController::class, 'createLead']);
        $group->get('/leads/{id}', [CrmController::class, 'getLead']);
        $group->put('/leads/{id}', [CrmController::class, 'updateLead']);
        $group->delete('/leads/{id}', [CrmController::class, 'deleteLead']);
        
        // Clients
        $group->get('/clients', [CrmController::class, 'getClients']);
        $group->post('/clients', [CrmController::class, 'createClient']);
        $group->get('/clients/{id}', [CrmController::class, 'getClient']);
        $group->put('/clients/{id}', [CrmController::class, 'updateClient']);
        $group->delete('/clients/{id}', [CrmController::class, 'deleteClient']);
        
        // Pipeline
        $group->get('/pipeline', [CrmController::class, 'getPipeline']);
        $group->put('/pipeline/{id}/status', [CrmController::class, 'updateLeadStatus']);
        
        // Interactions
        $group->get('/interactions', [CrmController::class, 'getInteractions']);
        $group->post('/interactions', [CrmController::class, 'createInteraction']);
    });
    
    // Scheduling Module
    $group->group('/scheduling', function (RouteCollectorProxy $group) {
        $group->get('/appointments', [SchedulingController::class, 'getAppointments']);
        $group->post('/appointments', [SchedulingController::class, 'createAppointment']);
        $group->get('/appointments/{id}', [SchedulingController::class, 'getAppointment']);
        $group->put('/appointments/{id}', [SchedulingController::class, 'updateAppointment']);
        $group->delete('/appointments/{id}', [SchedulingController::class, 'deleteAppointment']);
        $group->get('/calendar', [SchedulingController::class, 'getCalendar']);
    });
    
    // Billing Module
    $group->group('/billing', function (RouteCollectorProxy $group) {
        $group->get('/charges', [BillingController::class, 'getCharges']);
        $group->post('/charges', [BillingController::class, 'createCharge']);
        $group->get('/charges/{id}', [BillingController::class, 'getCharge']);
        $group->put('/charges/{id}', [BillingController::class, 'updateCharge']);
        $group->delete('/charges/{id}', [BillingController::class, 'deleteCharge']);
        $group->post('/payment-link', [BillingController::class, 'generatePaymentLink']);
        
        // Invoice routes
        $group->get('/invoices', [BillingController::class, 'getInvoices']);
        $group->post('/invoices', [BillingController::class, 'createInvoice']);
        $group->get('/invoices/{id}', [BillingController::class, 'getInvoice']);
        $group->put('/invoices/{id}', [BillingController::class, 'updateInvoice']);
        $group->delete('/invoices/{id}', [BillingController::class, 'deleteInvoice']);
    });
    
    // Contracts Module
    $group->group('/contracts', function (RouteCollectorProxy $group) {
        $group->get('', [ContractController::class, 'getContracts']);
        $group->post('', [ContractController::class, 'createContract']);
        $group->get('/{id}', [ContractController::class, 'getContract']);
        $group->put('/{id}', [ContractController::class, 'updateContract']);
        $group->delete('/{id}', [ContractController::class, 'deleteContract']);
        $group->post('/{id}/sign', [ContractController::class, 'signContract']);
        $group->get('/{id}/signatures', [ContractController::class, 'getSignatures']);
    });
    
    // Reports Module
    $group->get('/reports/sales', [ReportController::class, 'getSalesReport']);
    $group->get('/reports/financial', [ReportController::class, 'getFinancialReport']);
    $group->get('/reports/performance', [ReportController::class, 'getPerformanceReport']);
    
})->add(new AuthMiddleware());

// Frontend routes (serve HTML pages)
$app->get('/', function ($request, $response, $args) {
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
});

$app->get('/login', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/login.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/dashboard', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/dashboard.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/crm/{page}', function ($request, $response, $args) {
    $page = $args['page'];
    $allowedPages = ['leads', 'clients', 'pipeline', 'tasks'];
    
    if (in_array($page, $allowedPages)) {
        $html = file_get_contents(__DIR__ . "/../app/views/crm/{$page}.html");
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
    
    return $response->withStatus(404);
});

$app->get('/scheduling', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/scheduling.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/billing', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/billing.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/contracts', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/contracts.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/reports', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/reports.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/settings', function ($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/../app/views/settings.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});