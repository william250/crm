<?php

class LeadController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all leads with filters and pagination
    public function getLeads($request, $response, $args) {
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
            
            if (!empty($params['source'])) {
                $whereConditions[] = "source = :source";
                $bindParams[':source'] = $params['source'];
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
            $countSql = "SELECT COUNT(*) as total FROM leads $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get leads with pagination
            $sql = "SELECT l.*, u.name as assigned_name 
                    FROM leads l 
                    LEFT JOIN users u ON l.assigned_to = u.id 
                    $whereClause 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $leads,
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
                'message' => 'Error fetching leads: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single lead by ID
    public function getLead($request, $response, $args) {
        try {
            $leadId = $args['id'];
            
            $sql = "SELECT l.*, u.name as assigned_name 
                    FROM leads l 
                    LEFT JOIN users u ON l.assigned_to = u.id 
                    WHERE l.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $leadId);
            $stmt->execute();
            
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $lead
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching lead: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new lead
    public function createLead($request, $response, $args) {
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
            $checkSql = "SELECT id FROM leads WHERE email = :email";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':email', $data['email']);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
            }
            
            $sql = "INSERT INTO leads (name, email, phone, company, position, source, status, notes, assigned_to, created_at, updated_at) 
                    VALUES (:name, :email, :phone, :company, :position, :source, :status, :notes, :assigned_to, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':company', $data['company'] ?? null);
            $stmt->bindParam(':position', $data['position'] ?? null);
            $stmt->bindParam(':source', $data['source'] ?? 'website');
            $stmt->bindParam(':status', $data['status'] ?? 'new');
            $stmt->bindParam(':notes', $data['notes'] ?? null);
            $stmt->bindParam(':assigned_to', $data['assigned_to'] ?? null);
            
            $stmt->execute();
            $leadId = $this->db->lastInsertId();
            
            // Get the created lead
            $getSql = "SELECT l.*, u.name as assigned_name 
                       FROM leads l 
                       LEFT JOIN users u ON l.assigned_to = u.id 
                       WHERE l.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $leadId);
            $getStmt->execute();
            $lead = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Lead created successfully',
                'data' => $lead
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating lead: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update lead
    public function updateLead($request, $response, $args) {
        try {
            $leadId = $args['id'];
            $data = json_decode($request->getBody(), true);
            
            // Check if lead exists
            $checkSql = "SELECT id FROM leads WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $leadId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }
            
            // Validate email if provided
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid email format'
                ], 400);
            }
            
            // Check if email already exists (excluding current lead)
            if (!empty($data['email'])) {
                $emailCheckSql = "SELECT id FROM leads WHERE email = :email AND id != :id";
                $emailCheckStmt = $this->db->prepare($emailCheckSql);
                $emailCheckStmt->bindParam(':email', $data['email']);
                $emailCheckStmt->bindParam(':id', $leadId);
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
            $bindParams = [':id' => $leadId];
            
            $allowedFields = ['name', 'email', 'phone', 'company', 'position', 'source', 'status', 'notes', 'assigned_to'];
            
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
            
            $sql = "UPDATE leads SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated lead
            $getSql = "SELECT l.*, u.name as assigned_name 
                       FROM leads l 
                       LEFT JOIN users u ON l.assigned_to = u.id 
                       WHERE l.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $leadId);
            $getStmt->execute();
            $lead = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Lead updated successfully',
                'data' => $lead
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating lead: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete lead
    public function deleteLead($request, $response, $args) {
        try {
            $leadId = $args['id'];
            
            // Check if lead exists
            $checkSql = "SELECT id FROM leads WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $leadId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }
            
            // Delete related interactions first
            $deleteInteractionsSql = "DELETE FROM interactions WHERE lead_id = :lead_id";
            $deleteInteractionsStmt = $this->db->prepare($deleteInteractionsSql);
            $deleteInteractionsStmt->bindParam(':lead_id', $leadId);
            $deleteInteractionsStmt->execute();
            
            // Delete lead
            $sql = "DELETE FROM leads WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $leadId);
            $stmt->execute();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Lead deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting lead: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Convert lead to client
    public function convertLead($request, $response, $args) {
        try {
            $leadId = $args['id'];
            
            // Get lead data
            $leadSql = "SELECT * FROM leads WHERE id = :id";
            $leadStmt = $this->db->prepare($leadSql);
            $leadStmt->bindParam(':id', $leadId);
            $leadStmt->execute();
            $lead = $leadStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }
            
            if ($lead['status'] === 'converted') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Lead is already converted'
                ], 400);
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            try {
                // Create client
                $clientSql = "INSERT INTO clients (name, email, phone, company, position, address, notes, assigned_to, created_at, updated_at) 
                              VALUES (:name, :email, :phone, :company, :position, :address, :notes, :assigned_to, NOW(), NOW())";
                
                $clientStmt = $this->db->prepare($clientSql);
                $clientStmt->bindParam(':name', $lead['name']);
                $clientStmt->bindParam(':email', $lead['email']);
                $clientStmt->bindParam(':phone', $lead['phone']);
                $clientStmt->bindParam(':company', $lead['company']);
                $clientStmt->bindParam(':position', $lead['position']);
                $clientStmt->bindParam(':address', $lead['address'] ?? null);
                $clientStmt->bindParam(':notes', $lead['notes']);
                $clientStmt->bindParam(':assigned_to', $lead['assigned_to']);
                $clientStmt->execute();
                
                $clientId = $this->db->lastInsertId();
                
                // Update lead status
                $updateLeadSql = "UPDATE leads SET status = 'converted', client_id = :client_id, updated_at = NOW() WHERE id = :id";
                $updateLeadStmt = $this->db->prepare($updateLeadSql);
                $updateLeadStmt->bindParam(':client_id', $clientId);
                $updateLeadStmt->bindParam(':id', $leadId);
                $updateLeadStmt->execute();
                
                // Update interactions to reference client
                $updateInteractionsSql = "UPDATE interactions SET client_id = :client_id WHERE lead_id = :lead_id";
                $updateInteractionsStmt = $this->db->prepare($updateInteractionsSql);
                $updateInteractionsStmt->bindParam(':client_id', $clientId);
                $updateInteractionsStmt->bindParam(':lead_id', $leadId);
                $updateInteractionsStmt->execute();
                
                $this->db->commit();
                
                // Get created client
                $getClientSql = "SELECT c.*, u.name as assigned_name 
                                 FROM clients c 
                                 LEFT JOIN users u ON c.assigned_to = u.id 
                                 WHERE c.id = :id";
                $getClientStmt = $this->db->prepare($getClientSql);
                $getClientStmt->bindParam(':id', $clientId);
                $getClientStmt->execute();
                $client = $getClientStmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Lead converted to client successfully',
                    'data' => [
                        'client' => $client,
                        'lead_id' => $leadId
                    ]
                ]);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error converting lead: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get lead statistics
    public function getLeadStats($request, $response, $args) {
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
            
            // Get total leads
            $totalSql = "SELECT COUNT(*) as total FROM leads WHERE 1=1 $dateFilter";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute($bindParams);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get leads by status
            $statusSql = "SELECT status, COUNT(*) as count FROM leads WHERE 1=1 $dateFilter GROUP BY status";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute($bindParams);
            $statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get leads by source
            $sourceSql = "SELECT source, COUNT(*) as count FROM leads WHERE 1=1 $dateFilter GROUP BY source";
            $sourceStmt = $this->db->prepare($sourceSql);
            $sourceStmt->execute($bindParams);
            $sourceStats = $sourceStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get conversion rate
            $convertedSql = "SELECT COUNT(*) as converted FROM leads WHERE status = 'converted' $dateFilter";
            $convertedStmt = $this->db->prepare($convertedSql);
            $convertedStmt->execute($bindParams);
            $converted = $convertedStmt->fetch(PDO::FETCH_ASSOC)['converted'];
            
            $conversionRate = $total > 0 ? round(($converted / $total) * 100, 2) : 0;
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total' => (int)$total,
                    'converted' => (int)$converted,
                    'conversion_rate' => $conversionRate,
                    'by_status' => $statusStats,
                    'by_source' => $sourceStats
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching lead statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get pipeline data
    public function getPipeline($request, $response, $args) {
        try {
            $sql = "SELECT 
                        status,
                        COUNT(*) as count,
                        SUM(CASE WHEN estimated_value IS NOT NULL THEN estimated_value ELSE 0 END) as total_value
                    FROM leads 
                    WHERE status != 'converted' 
                    GROUP BY status 
                    ORDER BY 
                        CASE status 
                            WHEN 'new' THEN 1 
                            WHEN 'contacted' THEN 2 
                            WHEN 'qualified' THEN 3 
                            WHEN 'proposal' THEN 4 
                            WHEN 'negotiation' THEN 5 
                            WHEN 'closed_won' THEN 6 
                            WHEN 'closed_lost' THEN 7 
                            ELSE 8 
                        END";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $pipeline = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $pipeline
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching pipeline data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>