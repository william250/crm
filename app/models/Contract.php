<?php

require_once __DIR__ . '/../config/database.php';

class Contract {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all contracts with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           cl.email as client_email,
                           cl.company as client_company,
                           u.name as created_by_name,
                           COUNT(DISTINCT s.id) as signature_count
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN signatures s ON c.id = s.contract_id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $sql .= " AND c.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND c.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['client_id'])) {
                $sql .= " AND c.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['created_by'])) {
                $sql .= " AND c.created_by = :created_by";
                $params[':created_by'] = $filters['created_by'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.title LIKE :search OR c.description LIKE :search OR cl.name LIKE :search OR cl.company LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND c.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND c.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['value_min'])) {
                $sql .= " AND c.value >= :value_min";
                $params[':value_min'] = $filters['value_min'];
            }
            
            if (!empty($filters['value_max'])) {
                $sql .= " AND c.value <= :value_max";
                $params[':value_max'] = $filters['value_max'];
            }
            
            $sql .= " GROUP BY c.id";
            
            // Add sorting
            $orderBy = $filters['order_by'] ?? 'created_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY c.{$orderBy} {$orderDir}";
            
            // Add pagination
            if (isset($filters['page']) && isset($filters['limit'])) {
                $offset = ($filters['page'] - 1) * $filters['limit'];
                $sql .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = $filters['limit'];
                $params[':offset'] = $offset;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting contracts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of contracts with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(DISTINCT c.id) as total 
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE 1=1";
            $params = [];
            
            // Apply same filters as getAll
            if (!empty($filters['status'])) {
                $sql .= " AND c.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND c.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['client_id'])) {
                $sql .= " AND c.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['created_by'])) {
                $sql .= " AND c.created_by = :created_by";
                $params[':created_by'] = $filters['created_by'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.title LIKE :search OR c.description LIKE :search OR cl.name LIKE :search OR cl.company LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND c.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND c.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['value_min'])) {
                $sql .= " AND c.value >= :value_min";
                $params[':value_min'] = $filters['value_min'];
            }
            
            if (!empty($filters['value_max'])) {
                $sql .= " AND c.value <= :value_max";
                $params[':value_max'] = $filters['value_max'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting contract count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get contract by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           cl.email as client_email,
                           cl.company as client_company,
                           cl.phone as client_phone,
                           u.name as created_by_name,
                           COUNT(DISTINCT s.id) as signature_count
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN signatures s ON c.id = s.contract_id
                    WHERE c.id = :id
                    GROUP BY c.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting contract by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new contract
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO contracts (client_id, title, description, type, value, status, start_date, end_date, terms, created_by, created_at, updated_at)
                    VALUES (:client_id, :title, :description, :type, :value, :status, :start_date, :end_date, :terms, :created_by, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':value', $data['value']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':start_date', $data['start_date']);
            $stmt->bindParam(':end_date', $data['end_date']);
            $stmt->bindParam(':terms', $data['terms']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating contract: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update contract
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE contracts SET 
                           client_id = :client_id,
                           title = :title,
                           description = :description,
                           type = :type,
                           value = :value,
                           status = :status,
                           start_date = :start_date,
                           end_date = :end_date,
                           terms = :terms,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':value', $data['value']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':start_date', $data['start_date']);
            $stmt->bindParam(':end_date', $data['end_date']);
            $stmt->bindParam(':terms', $data['terms']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating contract: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete contract
     */
    public function delete($id) {
        try {
            // Check if contract has signatures
            $checkSql = "SELECT COUNT(*) as signature_count FROM signatures WHERE contract_id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($count['signature_count'] > 0) {
                // Don't delete, just mark as cancelled
                $sql = "UPDATE contracts SET status = 'cancelled', updated_at = NOW() WHERE id = :id";
            } else {
                // Safe to delete
                $sql = "DELETE FROM contracts WHERE id = :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting contract: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get contracts by status
     */
    public function getByStatus($status) {
        try {
            $sql = "SELECT c.*, cl.name as client_name, cl.company as client_company
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE c.status = :status 
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting contracts by status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get contracts by client
     */
    public function getByClient($clientId) {
        try {
            $sql = "SELECT c.*, u.name as created_by_name
                    FROM contracts c
                    LEFT JOIN users u ON c.created_by = u.id
                    WHERE c.client_id = :client_id 
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting contracts by client: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent contracts
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT c.*, cl.name as client_name, cl.company as client_company, u.name as created_by_name
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON c.created_by = u.id
                    ORDER BY c.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent contracts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get contract statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $whereClause .= " AND created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND created_at <= :date_to";
                $params[':date_to'] = $dateTo . ' 23:59:59';
            }
            
            $sql = "SELECT 
                           COUNT(*) as total_contracts,
                           COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_contracts,
                           COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_contracts,
                           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_contracts,
                           COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_contracts,
                           COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_contracts,
                           SUM(CASE WHEN status = 'active' THEN value ELSE 0 END) as active_value,
                           SUM(CASE WHEN status = 'completed' THEN value ELSE 0 END) as completed_value,
                           AVG(value) as average_value,
                           COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
                           COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week,
                           COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
                    FROM contracts {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting contract statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update contract status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE contracts SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating contract status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get expiring contracts
     */
    public function getExpiring($days = 30) {
        try {
            $sql = "SELECT c.*, cl.name as client_name, cl.email as client_email
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE c.status = 'active' 
                    AND c.end_date IS NOT NULL 
                    AND c.end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                    ORDER BY c.end_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting expiring contracts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get contracts by type
     */
    public function getByType($type) {
        try {
            $sql = "SELECT c.*, cl.name as client_name, cl.company as client_company
                    FROM contracts c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE c.type = :type 
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting contracts by type: " . $e->getMessage());
            return [];
        }
    }
}