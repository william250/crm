<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Middleware\AuthMiddleware;

class AuthController
{
    private $userModel;
    private $authMiddleware;

    public function __construct($db)
    {
        $this->userModel = new User($db);
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * User login
     */
    public function login(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate input
            if (empty($data['email']) || empty($data['password'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email and password are required'
                ], 400);
            }

            // Find user by email
            $user = $this->userModel->findByEmail($data['email']);
            
            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Account is not active'
                ], 401);
            }

            // Update last login
            $this->userModel->updateLastLogin($user['id']);

            // Generate JWT token
            $token = $this->authMiddleware->generateToken($user);

            // Remove password from user data
            unset($user['password']);
            unset($user['remember_token']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'expires_in' => 24 * 60 * 60 // 24 hours in seconds
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * User logout
     */
    public function logout(Request $request, Response $response)
    {
        try {
            // In a more sophisticated implementation, you would blacklist the token
            // For now, we'll just return success (client should remove token)
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            
            // Remove sensitive data
            unset($user['password']);
            unset($user['remember_token']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to get profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request, Response $response)
    {
        try {
            $userId = $request->getAttribute('user_id');
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate input
            if (empty($data['name'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Name is required'
                ], 400);
            }

            // Prepare update data
            $updateData = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null
            ];

            // Update email if provided and different
            if (!empty($data['email'])) {
                $currentUser = $this->userModel->findById($userId);
                if ($data['email'] !== $currentUser['email']) {
                    // Check if email is already taken
                    $existingUser = $this->userModel->findByEmail($data['email']);
                    if ($existingUser && $existingUser['id'] != $userId) {
                        return $this->jsonResponse($response, [
                            'success' => false,
                            'message' => 'Email is already taken'
                        ], 400);
                    }
                    $updateData['email'] = $data['email'];
                }
            }

            // Update password if provided
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Password must be at least 6 characters'
                    ], 400);
                }
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Update user
            $success = $this->userModel->update($userId, $updateData);
            
            if (!$success) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Failed to update profile'
                ], 500);
            }

            // Get updated user data
            $updatedUser = $this->userModel->findById($userId);
            unset($updatedUser['password']);
            unset($updatedUser['remember_token']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register new user (admin only)
     */
    public function register(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate input
            $errors = $this->validateUserData($data);
            if (!empty($errors)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }

            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email is already registered'
                ], 400);
            }

            // Prepare user data
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'] ?? 'user',
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'active'
            ];

            // Create user
            $userId = $this->userModel->create($userData);
            
            if (!$userId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Failed to create user'
                ], 500);
            }

            // Get created user
            $newUser = $this->userModel->findById($userId);
            unset($newUser['password']);
            unset($newUser['remember_token']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User created successfully',
                'data' => $newUser
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users (admin/manager only)
     */
    public function getUsers(Request $request, Response $response)
    {
        try {
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);
            $role = $params['role'] ?? null;
            $status = $params['status'] ?? null;
            $search = $params['search'] ?? null;

            $users = $this->userModel->getAll([
                'page' => $page,
                'limit' => $limit,
                'role' => $role,
                'status' => $status,
                'search' => $search
            ]);

            $totalUsers = $this->userModel->getTotalCount([
                'role' => $role,
                'status' => $status,
                'search' => $search
            ]);

            // Remove sensitive data
            foreach ($users as &$user) {
                unset($user['password']);
                unset($user['remember_token']);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalUsers,
                    'total_pages' => ceil($totalUsers / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to get users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user by ID (admin/manager only)
     */
    public function getUser(Request $request, Response $response, $args)
    {
        try {
            $userId = (int)$args['id'];
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Remove sensitive data
            unset($user['password']);
            unset($user['remember_token']);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to get user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user (admin only)
     */
    public function updateUser(Request $request, Response $response, $args)
    {
        try {
            $userId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if user exists
            $existingUser = $this->userModel->findById($userId);
            if (!$existingUser) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Validate input
            $errors = $this->validateUserData($data, true, $userId);
            if (!empty($errors)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }

            // Prepare update data
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status']
            ];

            // Update password if provided
            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Update user
            $success = $this->userModel->update($userId, $updateData);
            
            if (!$success) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Failed to update user'
                ], 500);
            }

            // Get updated user
            $updatedUser = $this->userModel->findById($userId);
            unset($updatedUser['password']);
            unset($updatedUser['remember_token']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser(Request $request, Response $response, $args)
    {
        try {
            $userId = (int)$args['id'];
            $currentUserId = $request->getAttribute('user_id');
            
            // Prevent self-deletion
            if ($userId === $currentUserId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }

            // Check if user exists
            $user = $this->userModel->getById($userId);
            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Delete user
            $success = $this->userModel->delete($userId);
            
            if (!$success) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Failed to delete user'
                ], 500);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(Request $request, Response $response)
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid authorization header'
                ], 400);
            }

            $oldToken = $matches[1];
            $newToken = $this->authMiddleware->refreshToken($oldToken);
            
            if (!$newToken) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Failed to refresh token'
                ], 401);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'token' => $newToken,
                    'expires_in' => 24 * 60 * 60 // 24 hours
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to refresh token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate user data
     */
    private function validateUserData($data, $isUpdate = false, $userId = null)
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } else {
            // Check for duplicate email
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && (!$isUpdate || $existingUser['id'] != $userId)) {
                $errors['email'] = 'Email is already taken';
            }
        }

        // Password validation (required for new users, optional for updates)
        if (!$isUpdate && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        // Role validation
        if (!empty($data['role'])) {
            $validRoles = ['admin', 'manager', 'salesperson', 'user'];
            if (!in_array($data['role'], $validRoles)) {
                $errors['role'] = 'Invalid role';
            }
        }

        // Status validation
        if (!empty($data['status'])) {
            $validStatuses = ['active', 'inactive', 'suspended'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Invalid status';
            }
        }

        return $errors;
    }

    /**
     * Helper method to return JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}