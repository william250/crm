<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Models\User;

class AuthMiddleware
{
    private $jwtSecret;
    private $userModel;

    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production';
        
        // Initialize database connection
        require_once __DIR__ . '/../../config/database.php';
        $database = new \Database();
        $db = $database->getConnection();
        
        $this->userModel = new User($db);
    }

    /**
     * JWT Authentication Middleware
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = new \Slim\Psr7\Response();
        
        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return $this->unauthorizedResponse($response, 'Authorization header missing');
        }

        // Extract token from Bearer header
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse($response, 'Invalid authorization header format');
        }

        $token = $matches[1];

        try {
            // Decode JWT token
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Get user from database
            $user = $this->userModel->findById($decoded->user_id);
            
            if (!$user) {
                return $this->unauthorizedResponse($response, 'User not found');
            }

            // Add user to request attributes
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('user_id', $user['id']);
            $request = $request->withAttribute('user_role', $user['role']);

            // Continue to next middleware/route
            return $handler->handle($request);

        } catch (ExpiredException $e) {
            return $this->unauthorizedResponse($response, 'Token has expired');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorizedResponse($response, 'Invalid token signature');
        } catch (\Exception $e) {
            return $this->unauthorizedResponse($response, 'Invalid token');
        }
    }

    /**
     * Role-based authorization middleware
     */
    public function requireRole(array $allowedRoles)
    {
        return function (Request $request, RequestHandler $handler) use ($allowedRoles): Response {
            $response = new \Slim\Psr7\Response();
            $userRole = $request->getAttribute('user_role');

            if (!in_array($userRole, $allowedRoles)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'required_roles' => $allowedRoles,
                    'user_role' => $userRole
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            return $handler->handle($request);
        };
    }

    /**
     * Admin only middleware
     */
    public function requireAdmin()
    {
        return $this->requireRole(['admin']);
    }

    /**
     * Manager or Admin middleware
     */
    public function requireManager()
    {
        return $this->requireRole(['admin', 'manager']);
    }

    /**
     * Sales team middleware (salesperson, manager, admin)
     */
    public function requireSales()
    {
        return $this->requireRole(['admin', 'manager', 'salesperson']);
    }

    /**
     * Generate JWT token for user
     */
    public function generateToken($user)
    {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Validate token without middleware (for manual validation)
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return [
                'valid' => true,
                'data' => $decoded
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract user ID from token
     */
    public function getUserIdFromToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return $decoded->user_id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired($token)
    {
        try {
            JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return false;
        } catch (ExpiredException $e) {
            return true;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Refresh token (generate new token with extended expiry)
     */
    public function refreshToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Get fresh user data
            $user = $this->userModel->findById($decoded->user_id);
            
            if (!$user || $user['status'] !== 'active') {
                return null;
            }

            return $this->generateToken($user);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(Response $response, $message = 'Unauthorized')
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'code' => 'UNAUTHORIZED'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    /**
     * CORS Middleware
     */
    public function cors()
    {
        return function (Request $request, RequestHandler $handler): Response {
            $response = $handler->handle($request);
            
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        };
    }

    /**
     * API Logger Middleware
     */
    public function apiLogger()
    {
        return function (Request $request, RequestHandler $handler): Response {
            $start = microtime(true);
            $response = $handler->handle($request);
            $duration = microtime(true) - $start;
            
            // Log API request
            error_log(sprintf(
                '[API] %s %s - %d - %.3fs',
                $request->getMethod(),
                $request->getUri()->getPath(),
                $response->getStatusCode(),
                $duration
            ));
            
            return $response;
        };
    }

    /**
     * Rate Limiting Middleware
     */
    public function rateLimit($maxRequests = 100, $timeWindow = 3600)
    {
        return function (Request $request, RequestHandler $handler) use ($maxRequests, $timeWindow): Response {
            // Simple rate limiting based on IP
            $clientIp = $this->getClientIp($request);
            $key = 'rate_limit_' . md5($clientIp);
            
            // For now, just pass through - implement proper rate limiting with Redis/Memcached in production
            return $handler->handle($request);
        };
    }

    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $serverParams['HTTP_X_FORWARDED_FOR'])[0];
        }
        
        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }
        
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}