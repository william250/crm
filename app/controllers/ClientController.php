<?php

class ClientController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all clients with filters and pagination
    public function getClients($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause based on filters
            $whereConditions = [];
            $bindParams = [];
            
            if (!empty($params['status'])) {
                $whereConditions[] = "status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['assigned_to'])) {
                $whereConditions[] = "assigned_to = :assigned_to";
                $bindParams[':assigned_to'] = $params['assigned_to'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(name LIKE :search OR email LIKE :search OR phone LIKE :search OR company LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "created_at >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "created_at <= :date_to";
                $bindParams[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM clients $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get clients with pagination
            $sql = "SELECT c.*, u.name as assigned_name 
                    FROM clients c 
                    LEFT JOIN users u ON c.assigned_to = u.id 
                    $whereClause 
                    ORDER BY c.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $clients,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching clients: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single client by ID
    public function getClient($request, $response, $args) {
        try {
            $clientId = $args['id'];
            
            $sql = "SELECT c.*, u.name as assigned_name 
                    FROM clients c 
                    LEFT JOIN users u ON c.assigned_to = u.id 
                    WHERE c.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $clientId);
            $stmt->execute();
            
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $client
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching client: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new client
    public function createClient($request, $response, $args) {
        try {
            $data = json_decode($request->getBody(), true);
            
            // Validate required fields
            $required = ['name', 'email', 'phone'];
            foreach ($required as $field) {
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
            
            // Check if email already exists
            $checkSql = "SELECT id FROM clients WHERE email = :email";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':email', $data['email']);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
            }
            
            $sql = "INSERT INTO clients (name, email, phone, company, position, address, notes, status, assigned_to, created_at, updated_at) 
                    VALUES (:name, :email, :phone, :company, :position, :address, :notes, :status, :assigned_to, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':company', $data['company'] ?? null);
            $stmt->bindParam(':position', $data['position'] ?? null);
            $stmt->bindParam(':address', $data['address'] ?? null);
            $stmt->bindParam(':notes', $data['notes'] ?? null);
            $stmt->bindParam(':status', $data['status'] ?? 'active');
            $stmt->bindParam(':assigned_to', $data['assigned_to'] ?? null);
            
            $stmt->execute();
            $clientId = $this->db->lastInsertId();
            
            // Get the created client
            $getSql = "SELECT c.*, u.name as assigned_name 
                       FROM clients c 
                       LEFT JOIN users u ON c.assigned_to = u.id 
                       WHERE c.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $clientId);
            $getStmt->execute();
            $client = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Client created successfully',
                'data' => $client
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating client: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update client
    public function updateClient($request, $response, $args) {
        try {
            $clientId = $args['id'];
            $data = json_decode($request->getBody(), true);
            
            // Check if client exists
            $checkSql = "SELECT id FROM clients WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $clientId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            // Validate email if provided
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid email format'
                ], 400);
            }
            
            // Check if email already exists (excluding current client)
            if (!empty($data['email'])) {
                $emailCheckSql = "SELECT id FROM clients WHERE email = :email AND id != :id";
                $emailCheckStmt = $this->db->prepare($emailCheckSql);
                $emailCheckStmt->bindParam(':email', $data['email']);
                $emailCheckStmt->bindParam(':id', $clientId);
                $emailCheckStmt->execute();
                
                if ($emailCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 400);
                }
            }
            
            // Build update query dynamically
            $updateFields = [];
            $bindParams = [':id' => $clientId];
            
            $allowedFields = ['name', 'email', 'phone', 'company', 'position', 'address', 'notes', 'status', 'assigned_to'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateFields[] = "$field = :$field";
                    $bindParams[":$field"] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ], 400);
            }
            
            $updateFields[] = "updated_at = NOW()";
            
            $sql = "UPDATE clients SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated client
            $getSql = "SELECT c.*, u.name as assigned_name 
                       FROM clients c 
                       LEFT JOIN users u ON c.assigned_to = u.id 
                       WHERE c.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $clientId);
            $getStmt->execute();
            $client = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Client updated successfully',
                'data' => $client
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating client: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete client
    public function deleteClient($request, $response, $args) {
        try {
            $clientId = $args['id'];
            
            // Check if client exists
            $checkSql = "SELECT id FROM clients WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $clientId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            // Check for related records
            $relatedSql = "SELECT 
                            (SELECT COUNT(*) FROM appointments WHERE client_id = :id) as appointments,
                            (SELECT COUNT(*) FROM contracts WHERE client_id = :id) as contracts,
                            (SELECT COUNT(*) FROM interactions WHERE client_id = :id) as interactions";
            $relatedStmt = $this->db->prepare($relatedSql);
            $relatedStmt->bindParam(':id', $clientId);
            $relatedStmt->execute();
            $related = $relatedStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($related['appointments'] > 0 || $related['contracts'] > 0 || $related['interactions'] > 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot delete client with related records (appointments, contracts, or interactions)'
                ], 400);
            }
            
            // Delete client
            $sql = "DELETE FROM clients WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $clientId);
            $stmt->execute();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Client deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting client: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get client statistics
    public function getClientStats($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND created_at >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND created_at <= :date_to";
                $bindParams[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            // Get total clients
            $totalSql = "SELECT COUNT(*) as total FROM clients WHERE 1=1 $dateFilter";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute($bindParams);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get clients by status
            $statusSql = "SELECT status, COUNT(*) as count FROM clients WHERE 1=1 $dateFilter GROUP BY status";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute($bindParams);
            $statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get active clients
            $activeSql = "SELECT COUNT(*) as active FROM clients WHERE status = 'active' $dateFilter";
            $activeStmt = $this->db->prepare($activeSql);
            $activeStmt->execute($bindParams);
            $active = $activeStmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            // Get clients with recent activity (last 30 days)
            $recentActivitySql = "SELECT COUNT(DISTINCT c.id) as recent_activity 
                                  FROM clients c 
                                  LEFT JOIN interactions i ON c.id = i.client_id 
                                  LEFT JOIN appointments a ON c.id = a.client_id 
                                  WHERE (i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                         OR a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) 
                                        $dateFilter";
            $recentActivityStmt = $this->db->prepare($recentActivitySql);
            $recentActivityStmt->execute($bindParams);
            $recentActivity = $recentActivityStmt->fetch(PDO::FETCH_ASSOC)['recent_activity'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total' => (int)$total,
                    'active' => (int)$active,
                    'recent_activity' => (int)$recentActivity,
                    'by_status' => $statusStats
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching client statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get client appointments
    public function getClientAppointments($request, $response, $args) {
        try {
            $clientId = $args['id'];
            $params = $request->getQueryParams();
            
            // Check if client exists
            $checkSql = "SELECT id FROM clients WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $clientId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            $whereConditions = ["client_id = :client_id"];
            $bindParams = [':client_id' => $clientId];
            
            if (!empty($params['status'])) {
                $whereConditions[] = "status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "appointment_date >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "appointment_date <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT a.*, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    $whereClause 
                    ORDER BY a.appointment_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $appointments
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching client appointments: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get client interactions
    public function getClientInteractions($request, $response, $args) {
        try {
            $clientId = $args['id'];
            $params = $request->getQueryParams();
            
            // Check if client exists
            $checkSql = "SELECT id FROM clients WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $clientId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            $whereConditions = ["client_id = :client_id"];
            $bindParams = [':client_id' => $clientId];
            
            if (!empty($params['type'])) {
                $whereConditions[] = "type = :type";
                $bindParams[':type'] = $params['type'];
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "interaction_date >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "interaction_date <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT i.*, u.name as user_name 
                    FROM interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    $whereClause 
                    ORDER BY i.interaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $interactions
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching client interactions: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>