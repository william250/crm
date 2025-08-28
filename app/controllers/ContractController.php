<?php

class ContractController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all contracts with filtering and pagination
    public function getContracts($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $whereConditions = [];
            $bindParams = [];
            
            // Apply filters
            if (!empty($params['status'])) {
                $whereConditions[] = "c.status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['client_id'])) {
                $whereConditions[] = "c.client_id = :client_id";
                $bindParams[':client_id'] = $params['client_id'];
            }
            
            if (!empty($params['type'])) {
                $whereConditions[] = "c.type = :type";
                $bindParams[':type'] = $params['type'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(c.title LIKE :search OR cl.name LIKE :search OR c.description LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "DATE(c.created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "DATE(c.created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM contracts c 
                         LEFT JOIN clients cl ON c.client_id = cl.id 
                         $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $totalContracts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get contracts
            $sql = "SELECT c.*, cl.name as client_name, cl.email as client_email,
                           u.name as created_by_name
                    FROM contracts c 
                    LEFT JOIN clients cl ON c.client_id = cl.id 
                    LEFT JOIN users u ON c.created_by = u.id
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
            
            $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $contracts,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$totalContracts,
                    'total_pages' => ceil($totalContracts / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching contracts: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single contract by ID
    public function getContract($request, $response, $args) {
        try {
            $contractId = $args['id'];
            
            $sql = "SELECT c.*, cl.name as client_name, cl.email as client_email,
                           u.name as created_by_name
                    FROM contracts c 
                    LEFT JOIN clients cl ON c.client_id = cl.id 
                    LEFT JOIN users u ON c.created_by = u.id
                    WHERE c.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $contractId]);
            
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contract) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Contract not found'
                ], 404);
            }
            
            // Get contract signatures
            $signaturesSql = "SELECT s.*, u.name as signer_name 
                              FROM signatures s 
                              LEFT JOIN users u ON s.signer_id = u.id 
                              WHERE s.contract_id = :contract_id 
                              ORDER BY s.signed_at DESC";
            $signaturesStmt = $this->db->prepare($signaturesSql);
            $signaturesStmt->execute([':contract_id' => $contractId]);
            $signatures = $signaturesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $contract['signatures'] = $signatures;
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $contract
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching contract: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new contract
    public function createContract($request, $response, $args) {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $userId = $request->getAttribute('user_id'); // From JWT middleware
            
            // Validate required fields
            $requiredFields = ['title', 'client_id', 'type', 'content', 'value'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Validate client exists
            $clientCheckSql = "SELECT id FROM clients WHERE id = :client_id";
            $clientCheckStmt = $this->db->prepare($clientCheckSql);
            $clientCheckStmt->execute([':client_id' => $data['client_id']]);
            
            if (!$clientCheckStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 400);
            }
            
            // Validate contract type
            $validTypes = ['service', 'product', 'maintenance', 'consulting', 'other'];
            if (!in_array($data['type'], $validTypes)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid contract type. Must be one of: ' . implode(', ', $validTypes)
                ], 400);
            }
            
            // Validate dates if provided
            if (!empty($data['start_date']) && !$this->isValidDate($data['start_date'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid start date format. Use YYYY-MM-DD'
                ], 400);
            }
            
            if (!empty($data['end_date']) && !$this->isValidDate($data['end_date'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid end date format. Use YYYY-MM-DD'
                ], 400);
            }
            
            // Insert contract
            $sql = "INSERT INTO contracts (title, description, client_id, type, content, value, 
                                         start_date, end_date, status, created_by, created_at, updated_at) 
                    VALUES (:title, :description, :client_id, :type, :content, :value, 
                            :start_date, :end_date, :status, :created_by, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => isset($data['description']) ? $data['description'] : null,
                ':client_id' => $data['client_id'],
                ':type' => $data['type'],
                ':content' => $data['content'],
                ':value' => $data['value'],
                ':start_date' => isset($data['start_date']) ? $data['start_date'] : null,
                ':end_date' => isset($data['end_date']) ? $data['end_date'] : null,
                ':status' => isset($data['status']) ? $data['status'] : 'draft',
                ':created_by' => $userId
            ]);
            
            $contractId = $this->db->lastInsertId();
            
            // Get created contract
            $getContractSql = "SELECT c.*, cl.name as client_name, cl.email as client_email,
                                      u.name as created_by_name
                               FROM contracts c 
                               LEFT JOIN clients cl ON c.client_id = cl.id 
                               LEFT JOIN users u ON c.created_by = u.id
                               WHERE c.id = :id";
            $getContractStmt = $this->db->prepare($getContractSql);
            $getContractStmt->execute([':id' => $contractId]);
            $contract = $getContractStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => $contract
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating contract: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update contract
    public function updateContract($request, $response, $args) {
        try {
            $contractId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if contract exists
            $checkSql = "SELECT id, status FROM contracts WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $contractId]);
            $existingContract = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingContract) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Contract not found'
                ], 404);
            }
            
            // Prevent editing signed contracts
            if ($existingContract['status'] === 'signed') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot edit signed contracts'
                ], 400);
            }
            
            $updateFields = [];
            $bindParams = [':id' => $contractId];
            
            // Update fields if provided
            if (isset($data['title'])) {
                $updateFields[] = "title = :title";
                $bindParams[':title'] = $data['title'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = :description";
                $bindParams[':description'] = $data['description'];
            }
            
            if (isset($data['client_id'])) {
                // Validate client exists
                $clientCheckSql = "SELECT id FROM clients WHERE id = :client_id";
                $clientCheckStmt = $this->db->prepare($clientCheckSql);
                $clientCheckStmt->execute([':client_id' => $data['client_id']]);
                
                if (!$clientCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Client not found'
                    ], 400);
                }
                
                $updateFields[] = "client_id = :client_id";
                $bindParams[':client_id'] = $data['client_id'];
            }
            
            if (isset($data['type'])) {
                $validTypes = ['service', 'product', 'maintenance', 'consulting', 'other'];
                if (!in_array($data['type'], $validTypes)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid contract type. Must be one of: ' . implode(', ', $validTypes)
                    ], 400);
                }
                
                $updateFields[] = "type = :type";
                $bindParams[':type'] = $data['type'];
            }
            
            if (isset($data['content'])) {
                $updateFields[] = "content = :content";
                $bindParams[':content'] = $data['content'];
            }
            
            if (isset($data['value'])) {
                $updateFields[] = "value = :value";
                $bindParams[':value'] = $data['value'];
            }
            
            if (isset($data['start_date'])) {
                if (!empty($data['start_date']) && !$this->isValidDate($data['start_date'])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid start date format. Use YYYY-MM-DD'
                    ], 400);
                }
                
                $updateFields[] = "start_date = :start_date";
                $bindParams[':start_date'] = $data['start_date'];
            }
            
            if (isset($data['end_date'])) {
                if (!empty($data['end_date']) && !$this->isValidDate($data['end_date'])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid end date format. Use YYYY-MM-DD'
                    ], 400);
                }
                
                $updateFields[] = "end_date = :end_date";
                $bindParams[':end_date'] = $data['end_date'];
            }
            
            if (isset($data['status'])) {
                $validStatuses = ['draft', 'pending', 'active', 'signed', 'expired', 'cancelled'];
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
            
            // Update contract
            $sql = "UPDATE contracts SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated contract
            $getContractSql = "SELECT c.*, cl.name as client_name, cl.email as client_email,
                                      u.name as created_by_name
                               FROM contracts c 
                               LEFT JOIN clients cl ON c.client_id = cl.id 
                               LEFT JOIN users u ON c.created_by = u.id
                               WHERE c.id = :id";
            $getContractStmt = $this->db->prepare($getContractSql);
            $getContractStmt->execute([':id' => $contractId]);
            $contract = $getContractStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Contract updated successfully',
                'data' => $contract
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating contract: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete contract
    public function deleteContract($request, $response, $args) {
        try {
            $contractId = $args['id'];
            
            // Check if contract exists
            $checkSql = "SELECT id, status FROM contracts WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $contractId]);
            $contract = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contract) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Contract not found'
                ], 404);
            }
            
            // Prevent deleting signed or active contracts
            if (in_array($contract['status'], ['signed', 'active'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot delete signed or active contracts'
                ], 400);
            }
            
            // Delete related signatures first
            $deleteSignaturesSql = "DELETE FROM signatures WHERE contract_id = :contract_id";
            $deleteSignaturesStmt = $this->db->prepare($deleteSignaturesSql);
            $deleteSignaturesStmt->execute([':contract_id' => $contractId]);
            
            // Delete contract
            $sql = "DELETE FROM contracts WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $contractId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Contract deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting contract: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get contract statistics
    public function getContractStats($request, $response, $args) {
        try {
            // Get total contracts
            $totalSql = "SELECT COUNT(*) as total FROM contracts";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute();
            $totalContracts = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get contracts by status
            $statusSql = "SELECT status, COUNT(*) as count FROM contracts GROUP BY status";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute();
            $contractsByStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get contracts by type
            $typeSql = "SELECT type, COUNT(*) as count FROM contracts GROUP BY type";
            $typeStmt = $this->db->prepare($typeSql);
            $typeStmt->execute();
            $contractsByType = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total contract value
            $valueSql = "SELECT COALESCE(SUM(value), 0) as total_value FROM contracts WHERE status IN ('active', 'signed')";
            $valueStmt = $this->db->prepare($valueSql);
            $valueStmt->execute();
            $totalValue = $valueStmt->fetch(PDO::FETCH_ASSOC)['total_value'];
            
            // Get expiring contracts (within 30 days)
            $expiringSql = "SELECT COUNT(*) as expiring FROM contracts 
                            WHERE status = 'active' AND end_date IS NOT NULL 
                            AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            $expiringStmt = $this->db->prepare($expiringSql);
            $expiringStmt->execute();
            $expiringContracts = $expiringStmt->fetch(PDO::FETCH_ASSOC)['expiring'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total_contracts' => (int)$totalContracts,
                    'total_value' => (float)$totalValue,
                    'expiring_soon' => (int)$expiringContracts,
                    'contracts_by_status' => $contractsByStatus,
                    'contracts_by_type' => $contractsByType
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching contract statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Sign contract
    public function signContract($request, $response, $args) {
        try {
            $contractId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            $userId = $request->getAttribute('user_id'); // From JWT middleware
            
            // Check if contract exists and is pending
            $checkSql = "SELECT id, status FROM contracts WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $contractId]);
            $contract = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contract) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Contract not found'
                ], 404);
            }
            
            if ($contract['status'] !== 'pending') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Contract is not available for signing'
                ], 400);
            }
            
            // Check if user already signed
            $signatureCheckSql = "SELECT id FROM signatures WHERE contract_id = :contract_id AND signer_id = :signer_id";
            $signatureCheckStmt = $this->db->prepare($signatureCheckSql);
            $signatureCheckStmt->execute([':contract_id' => $contractId, ':signer_id' => $userId]);
            
            if ($signatureCheckStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'You have already signed this contract'
                ], 400);
            }
            
            $this->db->beginTransaction();
            
            try {
                // Create signature record
                $signatureSql = "INSERT INTO signatures (contract_id, signer_id, signature_data, signed_at, ip_address) 
                                 VALUES (:contract_id, :signer_id, :signature_data, NOW(), :ip_address)";
                $signatureStmt = $this->db->prepare($signatureSql);
                $signatureStmt->execute([
                    ':contract_id' => $contractId,
                    ':signer_id' => $userId,
                    ':signature_data' => isset($data['signature_data']) ? $data['signature_data'] : null,
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                // Update contract status to signed
                $updateContractSql = "UPDATE contracts SET status = 'signed', updated_at = NOW() WHERE id = :id";
                $updateContractStmt = $this->db->prepare($updateContractSql);
                $updateContractStmt->execute([':id' => $contractId]);
                
                $this->db->commit();
                
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Contract signed successfully'
                ]);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error signing contract: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get contract signatures
    public function getContractSignatures($request, $response, $args) {
        try {
            $contractId = $args['id'];
            
            // Check if contract exists
            $checkSql = "SELECT id FROM contracts WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $contractId]);
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Contract not found'
                ], 404);
            }
            
            // Get signatures
            $sql = "SELECT s.*, u.name as signer_name, u.email as signer_email 
                    FROM signatures s 
                    LEFT JOIN users u ON s.signer_id = u.id 
                    WHERE s.contract_id = :contract_id 
                    ORDER BY s.signed_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':contract_id' => $contractId]);
            
            $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $signatures
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching contract signatures: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>