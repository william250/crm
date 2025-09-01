<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\AuthMiddleware;
use App\Controllers\ReportController;

return function (App $app) {
    // Get database connection
    $container = $app->getContainer();
    $db = $container->get('db');
    
    // Initialize controllers
    $authController = new AuthController($db);
    $leadController = new LeadController($db);
    $clientController = new ClientController($db);
    $appointmentController = new AppointmentController($db);
    $interactionController = new InteractionController($db);
    $dashboardController = new DashboardController($db);
    $userController = new UserController($db);
    $contractController = new ContractController($db);
    $chargeController = new ChargeController($db);
    $paymentController = new PaymentController($db);
    $reportController = new ReportController($db);
    
    // Authentication routes (no middleware required)
    $app->group('/api/auth', function (RouteCollectorProxy $group) use ($authController) {
        $group->post('/login', [$authController, 'login']);
        $group->post('/register', [$authController, 'register']);
        $group->post('/refresh', [$authController, 'refreshToken']);
    });
    
    // Protected API routes (require JWT authentication)
    $app->group('/api', function (RouteCollectorProxy $group) use (
        $leadController, $clientController, $appointmentController, 
        $interactionController, $dashboardController, $userController,
        $contractController, $chargeController, $paymentController, $authController, $reportController
    ) {
        
        // Auth routes (protected)
        $group->post('/auth/logout', [$authController, 'logout']);
        $group->get('/auth/me', [$authController, 'getProfile']);
        
        // Dashboard routes
        $group->get('/dashboard/stats', [$dashboardController, 'getDashboardStats']);
        $group->get('/dashboard/lead-pipeline', [$dashboardController, 'getLeadPipeline']);
        $group->get('/dashboard/lead-sources', [$dashboardController, 'getLeadSources']);
        $group->get('/dashboard/recent-leads', [$dashboardController, 'getRecentLeads']);
        $group->get('/dashboard/upcoming-appointments', [$dashboardController, 'getUpcomingAppointments']);
        $group->get('/dashboard/revenue-trends', [$dashboardController, 'getRevenueTrends']);
        $group->get('/dashboard/activity-feed', [$dashboardController, 'getActivityFeed']);
        $group->get('/dashboard/user-performance', [$dashboardController, 'getUserPerformance']);
        $group->get('/dashboard/monthly-summary', [$dashboardController, 'getMonthlySummary']);
        
        // Lead routes
        $group->get('/leads', [$leadController, 'getLeads']);
        $group->get('/leads/{id}', [$leadController, 'getLead']);
        $group->post('/leads', [$leadController, 'createLead']);
        $group->put('/leads/{id}', [$leadController, 'updateLead']);
        $group->delete('/leads/{id}', [$leadController, 'deleteLead']);
        $group->post('/leads/{id}/convert', [$leadController, 'convertLead']);
        $group->get('/leads/stats/overview', [$leadController, 'getLeadStats']);
        
        // Client routes
        $group->get('/clients', [$clientController, 'getClients']);
        $group->get('/clients/{id}', [$clientController, 'getClient']);
        $group->post('/clients', [$clientController, 'createClient']);
        $group->put('/clients/{id}', [$clientController, 'updateClient']);
        $group->delete('/clients/{id}', [$clientController, 'deleteClient']);
        $group->get('/clients/stats/overview', [$clientController, 'getClientStats']);
        $group->get('/clients/{id}/appointments', [$clientController, 'getClientAppointments']);
        $group->get('/clients/{id}/interactions', [$clientController, 'getClientInteractions']);
        
        // Appointment routes
        $group->get('/appointments', [$appointmentController, 'getAppointments']);
        $group->get('/appointments/{id}', [$appointmentController, 'getAppointment']);
        $group->post('/appointments', [$appointmentController, 'createAppointment']);
        $group->put('/appointments/{id}', [$appointmentController, 'updateAppointment']);
        $group->delete('/appointments/{id}', [$appointmentController, 'deleteAppointment']);
        $group->get('/appointments/calendar/view', [$appointmentController, 'getCalendar']);
        $group->get('/appointments/today/list', [$appointmentController, 'getTodayAppointments']);
        $group->get('/appointments/upcoming/list', [$appointmentController, 'getUpcomingAppointments']);
        $group->post('/appointments/{id}/complete', [$appointmentController, 'completeAppointment']);
        $group->get('/appointments/stats/overview', [$appointmentController, 'getAppointmentStats']);
        
        // Interaction routes
        $group->get('/interactions', [$interactionController, 'getInteractions']);
        $group->get('/interactions/{id}', [$interactionController, 'getInteraction']);
        $group->post('/interactions', [$interactionController, 'createInteraction']);
        $group->put('/interactions/{id}', [$interactionController, 'updateInteraction']);
        $group->delete('/interactions/{id}', [$interactionController, 'deleteInteraction']);
        $group->get('/interactions/entity/{entity_type}/{entity_id}', [$interactionController, 'getEntityInteractions']);
        $group->get('/interactions/stats/overview', [$interactionController, 'getInteractionStats']);
        $group->get('/interactions/recent/list', [$interactionController, 'getRecentInteractions']);
        
        // User routes
        $group->get('/users', [$userController, 'getUsers']);
        $group->get('/users/{id}', [$userController, 'getUser']);
        $group->post('/users', [$userController, 'createUser']);
        $group->put('/users/{id}', [$userController, 'updateUser']);
        $group->delete('/users/{id}', [$userController, 'deleteUser']);
        $group->get('/users/stats/overview', [$userController, 'getUserStats']);
        $group->put('/users/profile/update', [$userController, 'updateProfile']);
        $group->get('/salespeople', [$userController, 'getSalespeople']);
        
        // Contract routes
        $group->get('/contracts', [$contractController, 'getContracts']);
        $group->get('/contracts/{id}', [$contractController, 'getContract']);
        $group->post('/contracts', [$contractController, 'createContract']);
        $group->put('/contracts/{id}', [$contractController, 'updateContract']);
        $group->delete('/contracts/{id}', [$contractController, 'deleteContract']);
        $group->get('/contracts/stats/overview', [$contractController, 'getContractStats']);
        $group->post('/contracts/{id}/sign', [$contractController, 'signContract']);
        $group->get('/contracts/{id}/signatures', [$contractController, 'getContractSignatures']);
        
        // Charge routes
        $group->get('/charges', [$chargeController, 'getCharges']);
        $group->get('/charges/{id}', [$chargeController, 'getCharge']);
        $group->post('/charges', [$chargeController, 'createCharge']);
        $group->put('/charges/{id}', [$chargeController, 'updateCharge']);
        $group->delete('/charges/{id}', [$chargeController, 'deleteCharge']);
        $group->get('/charges/stats/overview', [$chargeController, 'getChargeStats']);
        $group->post('/charges/{id}/mark-sent', [$chargeController, 'markAsSent']);
        
        // Payment routes
        $group->get('/payments', [$paymentController, 'getPayments']);
        $group->get('/payments/{id}', [$paymentController, 'getPayment']);
        $group->post('/payments', [$paymentController, 'createPayment']);
        $group->put('/payments/{id}', [$paymentController, 'updatePayment']);
        $group->delete('/payments/{id}', [$paymentController, 'deletePayment']);
        $group->get('/payments/stats/overview', [$paymentController, 'getPaymentStats']);
        $group->post('/payments/{id}/refund', [$paymentController, 'refundPayment']);
        
    })->add(new AuthMiddleware());
    
    // Health check endpoint (no authentication required)
    $app->get('/api/health', function ($request, $response, $args) {
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // Report routes
    $group->get('/reports/sales', [$reportController, 'getSalesReport']);
    $group->get('/reports/financial', [$reportController, 'getFinancialReport']);
    $group->get('/reports/performance', [$reportController, 'getPerformanceReport']);
    
    // Catch-all route for API 404s
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/api/{routes:.+}', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'API endpoint not found',
            'error' => 'NOT_FOUND'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    });
};