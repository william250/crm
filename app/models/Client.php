<?php

require_once __DIR__ . '/../config/database.php';

class Client {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all clients with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT c.*, 
                           COUNT(DISTINCT a.id) as appointment_count,
                           COUNT(DISTINCT ct.id) as contract_count,
                           MAX(a.date) as last_appointment
                    FROM clients c
                    LEFT JOIN appointments a ON c.id = a.client_id
                    LEFT JOIN contracts ct ON c.id = ct.client_id
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
            
            if (!empty($filters['assigned_to'])) {
                $sql .= " AND c.assigned_to = :assigned_to";
                $params[':assigned_to'] = $filters['assigned_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR c.company LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['created_from'])) {
                $sql .= " AND c.created_at >= :created_from";
                $params[':created_from'] = $filters['created_from'];
            }
            
            if (!empty($filters['created_to'])) {
                $sql .= " AND c.created_at <= :created_to";
                $params[':created_to'] = $filters['created_to'] . ' 23:59:59';
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
            error_log("Error getting clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of clients with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM clients WHERE 1=1";
            $params = [];
            
            // Apply same filters as getAll
            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['assigned_to'])) {
                $sql .= " AND assigned_to = :assigned_to";
                $params[':assigned_to'] = $filters['assigned_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search OR company LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['created_from'])) {
                $sql .= " AND created_at >= :created_from";
                $params[':created_from'] = $filters['created_from'];
            }
            
            if (!empty($filters['created_to'])) {
                $sql .= " AND created_at <= :created_to";
                $params[':created_to'] = $filters['created_to'] . ' 23:59:59';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting client count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get client by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT c.*, 
                           u.name as assigned_user_name,
                           COUNT(DISTINCT a.id) as appointment_count,
                           COUNT(DISTINCT ct.id) as contract_count,
                           MAX(a.date) as last_appointment
                    FROM clients c
                    LEFT JOIN users u ON c.assigned_to = u.id
                    LEFT JOIN appointments a ON c.id = a.client_id
                    LEFT JOIN contracts ct ON c.id = ct.client_id
                    WHERE c.id = :id
                    GROUP BY c.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting client by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new client
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO clients (name, email, phone, company, address, type, status, assigned_to, notes, created_at, updated_at)
                    VALUES (:name, :email, :phone, :company, :address, :type, :status, :assigned_to, :notes, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':company', $data['company']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':assigned_to', $data['assigned_to']);
            $stmt->bindParam(':notes', $data['notes']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update client
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE clients SET 
                           name = :name,
                           email = :email,
                           phone = :phone,
                           company = :company,
                           address = :address,
                           type = :type,
                           status = :status,
                           assigned_to = :assigned_to,
                           notes = :notes,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':company', $data['company']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':assigned_to', $data['assigned_to']);
            $stmt->bindParam(':notes', $data['notes']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete client
     */
    public function delete($id) {
        try {
            // Check if client has appointments or contracts
            $checkSql = "SELECT 
                                (SELECT COUNT(*) FROM appointments WHERE client_id = :id) as appointment_count,
                                (SELECT COUNT(*) FROM contracts WHERE client_id = :id) as contract_count";
            
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            $counts = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($counts['appointment_count'] > 0 || $counts['contract_count'] > 0) {
                // Don't delete, just mark as inactive
                $sql = "UPDATE clients SET status = 'inactive', updated_at = NOW() WHERE id = :id";
            } else {
                // Safe to delete
                $sql = "DELETE FROM clients WHERE id = :id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find client by email
     */
    public function findByEmail($email) {
        try {
            $sql = "SELECT * FROM clients WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding client by email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get clients by status
     */
    public function getByStatus($status) {
        try {
            $sql = "SELECT * FROM clients WHERE status = :status ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting clients by status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get clients by type
     */
    public function getByType($type) {
        try {
            $sql = "SELECT * FROM clients WHERE type = :type ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting clients by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent clients
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT c.*, u.name as assigned_user_name
                    FROM clients c
                    LEFT JOIN users u ON c.assigned_to = u.id
                    ORDER BY c.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client statistics
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
                           COUNT(*) as total_clients,
                           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_clients,
                           COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_clients,
                           COUNT(CASE WHEN type = 'individual' THEN 1 END) as individual_clients,
                           COUNT(CASE WHEN type = 'business' THEN 1 END) as business_clients,
                           COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
                           COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week,
                           COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
                    FROM clients {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting client statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get clients for dropdown/select options
     */
    public function getForSelect() {
        try {
            $sql = "SELECT id, name, email, company FROM clients WHERE status = 'active' ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting clients for select: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update client status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE clients SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating client status: " . $e->getMessage());
            return false;
        }
    }
}