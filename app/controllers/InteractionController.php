<?php

namespace App\Controllers;

use PDO;
use Exception;

class InteractionController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all interactions with filters and pagination
    public function getInteractions($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause based on filters
            $whereConditions = [];
            $bindParams = [];
            
            if (!empty($params['type'])) {
                $whereConditions[] = "i.type = :type";
                $bindParams[':type'] = $params['type'];
            }
            
            if (!empty($params['entity_type'])) {
                $whereConditions[] = "i.entity_type = :entity_type";
                $bindParams[':entity_type'] = $params['entity_type'];
            }
            
            if (!empty($params['entity_id'])) {
                $whereConditions[] = "i.entity_id = :entity_id";
                $bindParams[':entity_id'] = $params['entity_id'];
            }
            
            if (!empty($params['user_id'])) {
                $whereConditions[] = "i.user_id = :user_id";
                $bindParams[':user_id'] = $params['user_id'];
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "DATE(i.interaction_date) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "DATE(i.interaction_date) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(i.subject LIKE :search OR i.description LIKE :search OR i.notes LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM interactions i $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get interactions with pagination
            $sql = "SELECT i.*, u.name as user_name,
                           CASE 
                               WHEN i.entity_type = 'lead' THEN l.name
                               WHEN i.entity_type = 'client' THEN c.name
                               ELSE NULL
                           END as entity_name,
                           CASE 
                               WHEN i.entity_type = 'lead' THEN l.email
                               WHEN i.entity_type = 'client' THEN c.email
                               ELSE NULL
                           END as entity_email
                    FROM interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    LEFT JOIN leads l ON i.entity_type = 'lead' AND i.entity_id = l.id
                    LEFT JOIN clients c ON i.entity_type = 'client' AND i.entity_id = c.id
                    $whereClause 
                    ORDER BY i.interaction_date DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $interactions,
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
                'message' => 'Error fetching interactions: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single interaction by ID
    public function getInteraction($request, $response, $args) {
        try {
            $interactionId = $args['id'];
            
            $sql = "SELECT i.*, u.name as user_name,
                           CASE 
                               WHEN i.entity_type = 'lead' THEN l.name
                               WHEN i.entity_type = 'client' THEN c.name
                               ELSE NULL
                           END as entity_name,
                           CASE 
                               WHEN i.entity_type = 'lead' THEN l.email
                               WHEN i.entity_type = 'client' THEN c.email
                               ELSE NULL
                           END as entity_email
                    FROM interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    LEFT JOIN leads l ON i.entity_type = 'lead' AND i.entity_id = l.id
                    LEFT JOIN clients c ON i.entity_type = 'client' AND i.entity_id = c.id
                    WHERE i.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $interactionId);
            $stmt->execute();
            
            $interaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$interaction) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Interaction not found'
                ], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $interaction
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching interaction: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new interaction
    public function createInteraction($request, $response, $args) {
        try {
            $data = json_decode($request->getBody(), true);
            
            // Validate required fields
            $required = ['type', 'entity_type', 'entity_id', 'subject', 'user_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Validate entity_type
            if (!in_array($data['entity_type'], ['lead', 'client'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Entity type must be either "lead" or "client"'
                ], 400);
            }
            
            // Validate interaction type
            $validTypes = ['call', 'email', 'meeting', 'note', 'task', 'follow_up'];
            if (!in_array($data['type'], $validTypes)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid interaction type. Valid types: ' . implode(', ', $validTypes)
                ], 400);
            }
            
            // Check if entity exists
            $entityTable = $data['entity_type'] === 'lead' ? 'leads' : 'clients';
            $entityCheckSql = "SELECT id FROM $entityTable WHERE id = :entity_id";
            $entityCheckStmt = $this->db->prepare($entityCheckSql);
            $entityCheckStmt->bindParam(':entity_id', $data['entity_id']);
            $entityCheckStmt->execute();
            
            if (!$entityCheckStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => ucfirst($data['entity_type']) . ' not found'
                ], 400);
            }
            
            // Check if user exists
            $userCheckSql = "SELECT id FROM users WHERE id = :user_id";
            $userCheckStmt = $this->db->prepare($userCheckSql);
            $userCheckStmt->bindParam(':user_id', $data['user_id']);
            $userCheckStmt->execute();
            
            if (!$userCheckStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 400);
            }
            
            // Validate interaction_date if provided
            $interactionDate = $data['interaction_date'] ?? date('Y-m-d H:i:s');
            if (!$this->isValidDateTime($interactionDate)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid interaction date format. Use YYYY-MM-DD HH:MM:SS'
                ], 400);
            }
            
            $sql = "INSERT INTO interactions (type, entity_type, entity_id, subject, description, notes, interaction_date, user_id, created_at, updated_at) 
                    VALUES (:type, :entity_type, :entity_id, :subject, :description, :notes, :interaction_date, :user_id, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':entity_type', $data['entity_type']);
            $stmt->bindParam(':entity_id', $data['entity_id']);
            $stmt->bindParam(':subject', $data['subject']);
            $stmt->bindParam(':description', $data['description'] ?? null);
            $stmt->bindParam(':notes', $data['notes'] ?? null);
            $stmt->bindParam(':interaction_date', $interactionDate);
            $stmt->bindParam(':user_id', $data['user_id']);
            
            $stmt->execute();
            $interactionId = $this->db->lastInsertId();
            
            // Get the created interaction
            $getSql = "SELECT i.*, u.name as user_name,
                              CASE 
                                  WHEN i.entity_type = 'lead' THEN l.name
                                  WHEN i.entity_type = 'client' THEN c.name
                                  ELSE NULL
                              END as entity_name,
                              CASE 
                                  WHEN i.entity_type = 'lead' THEN l.email
                                  WHEN i.entity_type = 'client' THEN c.email
                                  ELSE NULL
                              END as entity_email
                       FROM interactions i 
                       LEFT JOIN users u ON i.user_id = u.id 
                       LEFT JOIN leads l ON i.entity_type = 'lead' AND i.entity_id = l.id
                       LEFT JOIN clients c ON i.entity_type = 'client' AND i.entity_id = c.id
                       WHERE i.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $interactionId);
            $getStmt->execute();
            $interaction = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Interaction created successfully',
                'data' => $interaction
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating interaction: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update interaction
    public function updateInteraction($request, $response, $args) {
        try {
            $interactionId = $args['id'];
            $data = json_decode($request->getBody(), true);
            
            // Check if interaction exists
            $checkSql = "SELECT * FROM interactions WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $interactionId);
            $checkStmt->execute();
            $existingInteraction = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingInteraction) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Interaction not found'
                ], 404);
            }
            
            // Validate entity_type if provided
            if (!empty($data['entity_type']) && !in_array($data['entity_type'], ['lead', 'client'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Entity type must be either "lead" or "client"'
                ], 400);
            }
            
            // Validate interaction type if provided
            if (!empty($data['type'])) {
                $validTypes = ['call', 'email', 'meeting', 'note', 'task', 'follow_up'];
                if (!in_array($data['type'], $validTypes)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid interaction type. Valid types: ' . implode(', ', $validTypes)
                    ], 400);
                }
            }
            
            // Check if entity exists if entity_type or entity_id is being changed
            if (!empty($data['entity_type']) || !empty($data['entity_id'])) {
                $entityType = $data['entity_type'] ?? $existingInteraction['entity_type'];
                $entityId = $data['entity_id'] ?? $existingInteraction['entity_id'];
                
                $entityTable = $entityType === 'lead' ? 'leads' : 'clients';
                $entityCheckSql = "SELECT id FROM $entityTable WHERE id = :entity_id";
                $entityCheckStmt = $this->db->prepare($entityCheckSql);
                $entityCheckStmt->bindParam(':entity_id', $entityId);
                $entityCheckStmt->execute();
                
                if (!$entityCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => ucfirst($entityType) . ' not found'
                    ], 400);
                }
            }
            
            // Check if user exists if provided
            if (!empty($data['user_id'])) {
                $userCheckSql = "SELECT id FROM users WHERE id = :user_id";
                $userCheckStmt = $this->db->prepare($userCheckSql);
                $userCheckStmt->bindParam(':user_id', $data['user_id']);
                $userCheckStmt->execute();
                
                if (!$userCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'User not found'
                    ], 400);
                }
            }
            
            // Validate interaction_date if provided
            if (!empty($data['interaction_date']) && !$this->isValidDateTime($data['interaction_date'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid interaction date format. Use YYYY-MM-DD HH:MM:SS'
                ], 400);
            }
            
            // Build update query dynamically
            $updateFields = [];
            $bindParams = [':id' => $interactionId];
            
            $allowedFields = ['type', 'entity_type', 'entity_id', 'subject', 'description', 'notes', 'interaction_date', 'user_id'];
            
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
            
            $sql = "UPDATE interactions SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated interaction
            $getSql = "SELECT i.*, u.name as user_name,
                              CASE 
                                  WHEN i.entity_type = 'lead' THEN l.name
                                  WHEN i.entity_type = 'client' THEN c.name
                                  ELSE NULL
                              END as entity_name,
                              CASE 
                                  WHEN i.entity_type = 'lead' THEN l.email
                                  WHEN i.entity_type = 'client' THEN c.email
                                  ELSE NULL
                              END as entity_email
                       FROM interactions i 
                       LEFT JOIN users u ON i.user_id = u.id 
                       LEFT JOIN leads l ON i.entity_type = 'lead' AND i.entity_id = l.id
                       LEFT JOIN clients c ON i.entity_type = 'client' AND i.entity_id = c.id
                       WHERE i.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $interactionId);
            $getStmt->execute();
            $interaction = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Interaction updated successfully',
                'data' => $interaction
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating interaction: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete interaction
    public function deleteInteraction($request, $response, $args) {
        try {
            $interactionId = $args['id'];
            
            // Check if interaction exists
            $checkSql = "SELECT id FROM interactions WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $interactionId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Interaction not found'
                ], 404);
            }
            
            // Delete interaction
            $sql = "DELETE FROM interactions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $interactionId);
            $stmt->execute();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Interaction deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting interaction: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get interactions for specific entity (lead or client)
    public function getEntityInteractions($request, $response, $args) {
        try {
            $entityType = $args['entity_type'];
            $entityId = $args['entity_id'];
            $params = $request->getQueryParams();
            
            // Validate entity_type
            if (!in_array($entityType, ['lead', 'client'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Entity type must be either "lead" or "client"'
                ], 400);
            }
            
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ['i.entity_type = :entity_type', 'i.entity_id = :entity_id'];
            $bindParams = [':entity_type' => $entityType, ':entity_id' => $entityId];
            
            if (!empty($params['type'])) {
                $whereConditions[] = "i.type = :type";
                $bindParams[':type'] = $params['type'];
            }
            
            if (!empty($params['user_id'])) {
                $whereConditions[] = "i.user_id = :user_id";
                $bindParams[':user_id'] = $params['user_id'];
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "DATE(i.interaction_date) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "DATE(i.interaction_date) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM interactions i $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get interactions
            $sql = "SELECT i.*, u.name as user_name
                    FROM interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    $whereClause 
                    ORDER BY i.interaction_date DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $interactions,
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
                'message' => 'Error fetching entity interactions: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get interaction statistics
    public function getInteractionStats($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND interaction_date >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND interaction_date <= :date_to";
                $bindParams[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            if (!empty($params['user_id'])) {
                $dateFilter .= " AND user_id = :user_id";
                $bindParams[':user_id'] = $params['user_id'];
            }
            
            // Get total interactions
            $totalSql = "SELECT COUNT(*) as total FROM interactions WHERE 1=1 $dateFilter";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute($bindParams);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get interactions by type
            $typeSql = "SELECT type, COUNT(*) as count FROM interactions WHERE 1=1 $dateFilter GROUP BY type";
            $typeStmt = $this->db->prepare($typeSql);
            $typeStmt->execute($bindParams);
            $typeStats = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get interactions by entity type
            $entitySql = "SELECT entity_type, COUNT(*) as count FROM interactions WHERE 1=1 $dateFilter GROUP BY entity_type";
            $entityStmt = $this->db->prepare($entitySql);
            $entityStmt->execute($bindParams);
            $entityStats = $entityStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get interactions by user
            $userSql = "SELECT u.name as user_name, COUNT(i.id) as count 
                        FROM interactions i 
                        LEFT JOIN users u ON i.user_id = u.id 
                        WHERE 1=1 $dateFilter 
                        GROUP BY i.user_id, u.name 
                        ORDER BY count DESC";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute($bindParams);
            $userStats = $userStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get today's interactions count
            $todaySql = "SELECT COUNT(*) as today FROM interactions WHERE DATE(interaction_date) = CURDATE() $dateFilter";
            $todayStmt = $this->db->prepare($todaySql);
            $todayStmt->execute($bindParams);
            $today = $todayStmt->fetch(PDO::FETCH_ASSOC)['today'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total' => (int)$total,
                    'today' => (int)$today,
                    'by_type' => $typeStats,
                    'by_entity_type' => $entityStats,
                    'by_user' => $userStats
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching interaction statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get recent interactions
    public function getRecentInteractions($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            
            $whereConditions = [];
            $bindParams = [];
            
            if (!empty($params['user_id'])) {
                $whereConditions[] = "i.user_id = :user_id";
                $bindParams[':user_id'] = $params['user_id'];
            }
            
            if (!empty($params['entity_type'])) {
                $whereConditions[] = "i.entity_type = :entity_type";
                $bindParams[':entity_type'] = $params['entity_type'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT i.*, u.name as user_name,
                           CASE 
                               WHEN i.entity_type = 'lead' THEN l.name
                               WHEN i.entity_type = 'client' THEN c.name
                               ELSE NULL
                           END as entity_name,
                           CASE 
                               WHEN i.entity_type = 'lead' THEN l.email
                               WHEN i.entity_type = 'client' THEN c.email
                               ELSE NULL
                           END as entity_email
                    FROM interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    LEFT JOIN leads l ON i.entity_type = 'lead' AND i.entity_id = l.id
                    LEFT JOIN clients c ON i.entity_type = 'client' AND i.entity_id = c.id
                    $whereClause 
                    ORDER BY i.interaction_date DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $interactions
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching recent interactions: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function isValidDateTime($dateTime) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $d && $d->format('Y-m-d H:i:s') === $dateTime;
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>