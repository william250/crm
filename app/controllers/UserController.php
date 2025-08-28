<?php

class UserController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all users with filtering and pagination
    public function getUsers($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $whereConditions = [];
            $bindParams = [];
            
            // Apply filters
            if (!empty($params['role'])) {
                $whereConditions[] = "role = :role";
                $bindParams[':role'] = $params['role'];
            }
            
            if (!empty($params['status'])) {
                $whereConditions[] = "status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(name LIKE :search OR email LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get users
            $sql = "SELECT id, name, email, role, status, created_at, updated_at, last_login 
                    FROM users 
                    $whereClause 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$totalUsers,
                    'total_pages' => ceil($totalUsers / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single user by ID
    public function getUser($request, $response, $args) {
        try {
            $userId = $args['id'];
            
            $sql = "SELECT id, name, email, role, status, created_at, updated_at, last_login 
                    FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userId]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new user
    public function createUser($request, $response, $args) {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate required fields
            $requiredFields = ['name', 'email', 'password', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid email format'
                ], 400);
            }
            
            // Validate role
            $validRoles = ['admin', 'manager', 'sales', 'user'];
            if (!in_array($data['role'], $validRoles)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid role. Must be one of: ' . implode(', ', $validRoles)
                ], 400);
            }
            
            // Check if email already exists
            $checkSql = "SELECT id FROM users WHERE email = :email";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':email' => $data['email']]);
            
            if ($checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (name, email, password, role, status, created_at, updated_at) 
                    VALUES (:name, :email, :password, :role, :status, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $hashedPassword,
                ':role' => $data['role'],
                ':status' => isset($data['status']) ? $data['status'] : 'active'
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Get created user
            $getUserSql = "SELECT id, name, email, role, status, created_at, updated_at 
                           FROM users WHERE id = :id";
            $getUserStmt = $this->db->prepare($getUserSql);
            $getUserStmt->execute([':id' => $userId]);
            $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update user
    public function updateUser($request, $response, $args) {
        try {
            $userId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if user exists
            $checkSql = "SELECT id FROM users WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $userId]);
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            $updateFields = [];
            $bindParams = [':id' => $userId];
            
            // Update name if provided
            if (isset($data['name']) && !empty($data['name'])) {
                $updateFields[] = "name = :name";
                $bindParams[':name'] = $data['name'];
            }
            
            // Update email if provided
            if (isset($data['email']) && !empty($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid email format'
                    ], 400);
                }
                
                // Check if email already exists for another user
                $emailCheckSql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                $emailCheckStmt = $this->db->prepare($emailCheckSql);
                $emailCheckStmt->execute([':email' => $data['email'], ':user_id' => $userId]);
                
                if ($emailCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 400);
                }
                
                $updateFields[] = "email = :email";
                $bindParams[':email'] = $data['email'];
            }
            
            // Update password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $updateFields[] = "password = :password";
                $bindParams[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Update role if provided
            if (isset($data['role'])) {
                $validRoles = ['admin', 'manager', 'sales', 'user'];
                if (!in_array($data['role'], $validRoles)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid role. Must be one of: ' . implode(', ', $validRoles)
                    ], 400);
                }
                
                $updateFields[] = "role = :role";
                $bindParams[':role'] = $data['role'];
            }
            
            // Update status if provided
            if (isset($data['status'])) {
                $validStatuses = ['active', 'inactive'];
                if (!in_array($data['status'], $validStatuses)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
                    ], 400);
                }
                
                $updateFields[] = "status = :status";
                $bindParams[':status'] = $data['status'];
            }
            
            if (empty($updateFields)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No fields to update'
                ], 400);
            }
            
            // Add updated_at
            $updateFields[] = "updated_at = NOW()";
            
            // Update user
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated user
            $getUserSql = "SELECT id, name, email, role, status, created_at, updated_at 
                           FROM users WHERE id = :id";
            $getUserStmt = $this->db->prepare($getUserSql);
            $getUserStmt->execute([':id' => $userId]);
            $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete user
    public function deleteUser($request, $response, $args) {
        try {
            $userId = $args['id'];
            
            // Check if user exists
            $checkSql = "SELECT id FROM users WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $userId]);
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Check if user has related records
            $relatedChecks = [
                'leads' => 'SELECT COUNT(*) as count FROM leads WHERE assigned_to = :user_id',
                'appointments' => 'SELECT COUNT(*) as count FROM appointments WHERE assigned_to = :user_id',
                'interactions' => 'SELECT COUNT(*) as count FROM interactions WHERE user_id = :user_id'
            ];
            
            foreach ($relatedChecks as $table => $sql) {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':user_id' => $userId]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count > 0) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => "Cannot delete user. User has related records in $table table."
                    ], 400);
                }
            }
            
            // Delete user
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get user statistics
    public function getUserStats($request, $response, $args) {
        try {
            // Get total users
            $totalSql = "SELECT COUNT(*) as total FROM users";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute();
            $totalUsers = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get active users
            $activeSql = "SELECT COUNT(*) as active FROM users WHERE status = 'active'";
            $activeStmt = $this->db->prepare($activeSql);
            $activeStmt->execute();
            $activeUsers = $activeStmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            // Get users by role
            $roleSql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
            $roleStmt = $this->db->prepare($roleSql);
            $roleStmt->execute();
            $usersByRole = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get recent registrations (last 30 days)
            $recentSql = "SELECT COUNT(*) as recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $recentStmt = $this->db->prepare($recentSql);
            $recentStmt->execute();
            $recentUsers = $recentStmt->fetch(PDO::FETCH_ASSOC)['recent'];
            
            // Get users with recent activity (last login within 7 days)
            $activelySql = "SELECT COUNT(*) as actively_used FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $activelyStmt = $this->db->prepare($activelySql);
            $activelyStmt->execute();
            $activelyUsed = $activelyStmt->fetch(PDO::FETCH_ASSOC)['actively_used'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total_users' => (int)$totalUsers,
                    'active_users' => (int)$activeUsers,
                    'recent_registrations' => (int)$recentUsers,
                    'actively_used' => (int)$activelyUsed,
                    'users_by_role' => $usersByRole
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching user statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update user profile (for current user)
    public function updateProfile($request, $response, $args) {
        try {
            $userId = $request->getAttribute('user_id'); // From JWT middleware
            $data = json_decode($request->getBody()->getContents(), true);
            
            $updateFields = [];
            $bindParams = [':id' => $userId];
            
            // Update name if provided
            if (isset($data['name']) && !empty($data['name'])) {
                $updateFields[] = "name = :name";
                $bindParams[':name'] = $data['name'];
            }
            
            // Update email if provided
            if (isset($data['email']) && !empty($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid email format'
                    ], 400);
                }
                
                // Check if email already exists for another user
                $emailCheckSql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                $emailCheckStmt = $this->db->prepare($emailCheckSql);
                $emailCheckStmt->execute([':email' => $data['email'], ':user_id' => $userId]);
                
                if ($emailCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 400);
                }
                
                $updateFields[] = "email = :email";
                $bindParams[':email'] = $data['email'];
            }
            
            // Update password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $updateFields[] = "password = :password";
                $bindParams[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No fields to update'
                ], 400);
            }
            
            // Add updated_at
            $updateFields[] = "updated_at = NOW()";
            
            // Update user
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated user
            $getUserSql = "SELECT id, name, email, role, status, created_at, updated_at 
                           FROM users WHERE id = :id";
            $getUserStmt = $this->db->prepare($getUserSql);
            $getUserStmt->execute([':id' => $userId]);
            $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>