<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\AuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\CrmController;
use App\Controllers\SchedulingController;
use App\Controllers\InteractionController;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Create Slim app
$app = AppFactory::create();

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Initialize middleware
$authMiddleware = new AuthMiddleware();

// Add CORS middleware
$app->add($authMiddleware->cors());

// Add API logging middleware
$app->add($authMiddleware->apiLogger());

// Add rate limiting middleware (100 requests per hour)
$app->add($authMiddleware->rateLimit(100, 3600));

// Initialize database connection
require_once __DIR__ . '/../config/database.php';
$db = \Database::getInstance()->getConnection();

// Initialize controllers
$authController = new AuthController($db);
$crmController = new CrmController();
$schedulingController = new SchedulingController();
$interactionController = new InteractionController($db);

// ============================================================================
// PUBLIC ROUTES (No authentication required)
// ============================================================================

// Serve login page as default
$app->get('/', function (Request $request, Response $response) {
    $filePath = __DIR__ . '/login.html';
    
    if (!file_exists($filePath)) {
        $response->getBody()->write('Login page not found');
        return $response->withStatus(404);
    }
    
    $response->getBody()->write(file_get_contents($filePath));
    return $response->withHeader('Content-Type', 'text/html');
});

// API health check
$app->get('/api/health', function (Request $request, Response $response) {
    $data = [
        'success' => true,
        'message' => 'API is healthy',
        'services' => [
            'database' => 'connected',
            'auth' => 'active',
            'storage' => 'available'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Authentication routes (public)
$app->group('/api/auth', function (RouteCollectorProxy $group) use ($authController) {
    $group->post('/login', [$authController, 'login']);
    $group->post('/refresh', [$authController, 'refreshToken']);
});

// Handle preflight OPTIONS requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// ============================================================================
// PROTECTED ROUTES (Authentication required)
// ============================================================================

$app->group('/api', function (RouteCollectorProxy $group) use (
    $authController, 
    $crmController, 
    $schedulingController, 
    $interactionController,
    $authMiddleware
) {
    
    // ========================================================================
    // AUTH ROUTES (Authenticated users)
    // ========================================================================
    $group->group('/auth', function (RouteCollectorProxy $authGroup) use ($authController, $authMiddleware) {
        $authGroup->post('/logout', [$authController, 'logout']);
        $authGroup->get('/profile', [$authController, 'profile']);
        $authGroup->put('/profile', [$authController, 'updateProfile']);
        
        // User management (Admin/Manager only)
        $authGroup->group('/users', function (RouteCollectorProxy $userGroup) use ($authController) {
            $userGroup->get('', [$authController, 'getUsers']);
            $userGroup->post('', [$authController, 'register']);
            $userGroup->get('/{id:[0-9]+}', [$authController, 'getUser']);
            $userGroup->put('/{id:[0-9]+}', [$authController, 'updateUser']);
            $userGroup->delete('/{id:[0-9]+}', [$authController, 'deleteUser']);
        })->add($authMiddleware->requireManager());
    });
    
    // ========================================================================
    // CRM ROUTES
    // ========================================================================
    $group->group('/crm', function (RouteCollectorProxy $crmGroup) use ($crmController, $authMiddleware) {
        
        // Dashboard
        $crmGroup->get('/dashboard', [$crmController, 'getDashboard']);
        
        // Leads management
        $crmGroup->group('/leads', function (RouteCollectorProxy $leadGroup) use ($crmController) {
            $leadGroup->get('', [$crmController, 'getLeads']);
            $leadGroup->post('', [$crmController, 'createLead']);
            $leadGroup->get('/recent', [$crmController, 'getRecentLeads']);
            $leadGroup->get('/by-status', [$crmController, 'getLeadsByStatus']);
            $leadGroup->get('/by-source', [$crmController, 'getLeadsBySource']);
            $leadGroup->get('/{id:[0-9]+}', [$crmController, 'getLead']);
            $leadGroup->put('/{id:[0-9]+}', [$crmController, 'updateLead']);
            $leadGroup->delete('/{id:[0-9]+}', [$crmController, 'deleteLead']);
            $leadGroup->post('/{id:[0-9]+}/convert', [$crmController, 'convertLead']);
        });
        
        // Pipeline
        $crmGroup->get('/pipeline', [$crmController, 'getPipeline']);
        
        // Salespeople
        $crmGroup->get('/salespeople', [$crmController, 'getSalespeople']);
        
    })->add($authMiddleware->requireSales());
    
    // ========================================================================
    // SCHEDULING ROUTES
    // ========================================================================
    $group->group('/scheduling', function (RouteCollectorProxy $schedGroup) use ($schedulingController, $authMiddleware) {
        
        // Appointments
        $schedGroup->group('/appointments', function (RouteCollectorProxy $apptGroup) use ($schedulingController) {
            $apptGroup->get('', [$schedulingController, 'getAppointments']);
            $apptGroup->post('', [$schedulingController, 'createAppointment']);
            $apptGroup->get('/upcoming', [$schedulingController, 'getUpcomingAppointments']);
            $apptGroup->get('/statistics', [$schedulingController, 'getAppointmentStatistics']);
            $apptGroup->get('/reminders', [$schedulingController, 'getAppointmentReminders']);
            $apptGroup->get('/{id:[0-9]+}', [$schedulingController, 'getAppointment']);
            $apptGroup->put('/{id:[0-9]+}', [$schedulingController, 'updateAppointment']);
            $apptGroup->delete('/{id:[0-9]+}', [$schedulingController, 'deleteAppointment']);
            $apptGroup->patch('/{id:[0-9]+}/status', [$schedulingController, 'updateAppointmentStatus']);
        });
        
        // Calendar views
        $schedGroup->get('/calendar/month/{year:[0-9]{4}}/{month:[0-9]{1,2}}', [$schedulingController, 'getMonthlyAppointments']);
        $schedGroup->get('/calendar/week/{date}', [$schedulingController, 'getWeeklyAppointments']);
        $schedGroup->get('/calendar/day/{date}', [$schedulingController, 'getDailyAppointments']);
        
        // Client appointments
        $schedGroup->get('/clients/{clientId:[0-9]+}/appointments', [$schedulingController, 'getClientAppointments']);
        
    });
    
    // ========================================================================
    // INTERACTIONS ROUTES
    // ========================================================================
    $group->group('/interactions', function (RouteCollectorProxy $intGroup) use ($interactionController) {
        $intGroup->get('', [$interactionController, 'getInteractions']);
        $intGroup->post('', [$interactionController, 'createInteraction']);
        $intGroup->get('/recent', [$interactionController, 'getRecentInteractions']);
        $intGroup->get('/follow-up', [$interactionController, 'getFollowUpRequired']);
        $intGroup->get('/upcoming', [$interactionController, 'getUpcomingInteractions']);
        $intGroup->get('/statistics', [$interactionController, 'getInteractionStatistics']);
        $intGroup->get('/{id:[0-9]+}', [$interactionController, 'getInteraction']);
        $intGroup->put('/{id:[0-9]+}', [$interactionController, 'updateInteraction']);
        $intGroup->delete('/{id:[0-9]+}', [$interactionController, 'deleteInteraction']);
        $intGroup->patch('/{id:[0-9]+}/status', [$interactionController, 'updateInteractionStatus']);
        $intGroup->post('/{id:[0-9]+}/complete', [$interactionController, 'markInteractionCompleted']);
        $intGroup->post('/{id:[0-9]+}/follow-up', [$interactionController, 'scheduleFollowUp']);
        
        // Client/Lead interactions
        $intGroup->get('/clients/{clientId:[0-9]+}', [$interactionController, 'getClientInteractions']);
        $intGroup->get('/leads/{leadId:[0-9]+}', [$interactionController, 'getLeadInteractions']);
    });
    
    // ========================================================================
    // BILLING ROUTES (Future implementation)
    // ========================================================================
    $group->group('/billing', function (RouteCollectorProxy $billGroup) {
        // Placeholder for billing endpoints
        $billGroup->get('/charges', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Billing module - Coming soon',
                'data' => []
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });
    });
    
    // ========================================================================
    // CONTRACTS ROUTES (Future implementation)
    // ========================================================================
    $group->group('/contracts', function (RouteCollectorProxy $contractGroup) {
        // Placeholder for contract endpoints
        $contractGroup->get('', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Contracts module - Coming soon',
                'data' => []
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });
    });
    
})->add($authMiddleware); // Apply authentication to all /api routes

// ============================================================================
// STATIC FILE SERVING (for development)
// ============================================================================

// Serve static files (CSS, JS, images) - only for development
$app->get('/assets/{type}/{file}', function (Request $request, Response $response, $args) {
    $type = $args['type'];
    $file = $args['file'];
    
    $allowedTypes = ['css', 'js', 'images', 'fonts'];
    if (!in_array($type, $allowedTypes)) {
        $response->getBody()->write('File type not allowed');
        return $response->withStatus(403);
    }
    
    $filePath = __DIR__ . "/assets/{$type}/{$file}";
    
    if (!file_exists($filePath)) {
        $response->getBody()->write('File not found');
        return $response->withStatus(404);
    }
    
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    $response->getBody()->write(file_get_contents($filePath));
    return $response->withHeader('Content-Type', $mimeType);
});

// Serve HTML pages (for development)
$app->get('/{page}', function (Request $request, Response $response, $args) {
    $page = $args['page'];
    
    // List of allowed pages
    $allowedPages = [
        'login', 'dashboard', 'crm-leads', 'scheduling', 
        'billing', 'contracts', 'profile', 'users'
    ];
    
    if (!in_array($page, $allowedPages)) {
        $response->getBody()->write('Page not found');
        return $response->withStatus(404);
    }
    
    $filePath = __DIR__ . "/{$page}.html";
    
    if (!file_exists($filePath)) {
        $response->getBody()->write('Page not found');
        return $response->withStatus(404);
    }
    
    $response->getBody()->write(file_get_contents($filePath));
    return $response->withHeader('Content-Type', 'text/html');
});

// ============================================================================
// ERROR HANDLERS
// ============================================================================

// Custom error handler
$errorHandler = function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    
    $errorData = [
        'success' => false,
        'message' => 'An error occurred',
        'code' => $exception->getCode() ?: 500
    ];
    
    if ($displayErrorDetails) {
        $errorData['details'] = $exception->getMessage();
        $errorData['file'] = $exception->getFile();
        $errorData['line'] = $exception->getLine();
        $errorData['trace'] = $exception->getTraceAsString();
    }
    
    $response->getBody()->write(json_encode($errorData));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
};

$errorMiddleware->setDefaultErrorHandler($errorHandler);

// ============================================================================
// 404 NOT FOUND HANDLER
// ============================================================================

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'success' => false,
        'message' => 'Endpoint not found',
        'method' => $request->getMethod(),
        'uri' => (string) $request->getUri()
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

// ============================================================================
// RUN APPLICATION
// ============================================================================

$app->run();

?>