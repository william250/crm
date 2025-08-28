<?php

require_once __DIR__ . '/../config/database.php';

class Signature {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all signatures with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT s.*, 
                           c.title as contract_title,
                           cl.name as client_name,
                           cl.email as client_email,
                           u.name as signed_by_name
                    FROM signatures s
                    LEFT JOIN contracts c ON s.contract_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON s.signed_by = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['contract_id'])) {
                $sql .= " AND s.contract_id = :contract_id";
                $params[':contract_id'] = $filters['contract_id'];
            }
            
            if (!empty($filters['signer_type'])) {
                $sql .= " AND s.signer_type = :signer_type";
                $params[':signer_type'] = $filters['signer_type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND s.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['signed_by'])) {
                $sql .= " AND s.signed_by = :signed_by";
                $params[':signed_by'] = $filters['signed_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND s.signed_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND s.signed_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            // Add sorting
            $orderBy = $filters['order_by'] ?? 'signed_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY s.{$orderBy} {$orderDir}";
            
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
            error_log("Error getting signatures: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of signatures with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM signatures s
                    LEFT JOIN contracts c ON s.contract_id = c.id
                    WHERE 1=1";
            $params = [];
            
            // Apply same filters as getAll
            if (!empty($filters['contract_id'])) {
                $sql .= " AND s.contract_id = :contract_id";
                $params[':contract_id'] = $filters['contract_id'];
            }
            
            if (!empty($filters['signer_type'])) {
                $sql .= " AND s.signer_type = :signer_type";
                $params[':signer_type'] = $filters['signer_type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND s.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['signed_by'])) {
                $sql .= " AND s.signed_by = :signed_by";
                $params[':signed_by'] = $filters['signed_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND s.signed_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND s.signed_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting signature count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get signature by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT s.*, 
                           c.title as contract_title,
                           c.client_id,
                           cl.name as client_name,
                           cl.email as client_email,
                           u.name as signed_by_name
                    FROM signatures s
                    LEFT JOIN contracts c ON s.contract_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON s.signed_by = u.id
                    WHERE s.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting signature by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new signature
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO signatures (contract_id, signer_name, signer_email, signer_type, signature_data, ip_address, user_agent, status, signed_by, signed_at, created_at, updated_at)
                    VALUES (:contract_id, :signer_name, :signer_email, :signer_type, :signature_data, :ip_address, :user_agent, :status, :signed_by, :signed_at, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $data['contract_id']);
            $stmt->bindParam(':signer_name', $data['signer_name']);
            $stmt->bindParam(':signer_email', $data['signer_email']);
            $stmt->bindParam(':signer_type', $data['signer_type']);
            $stmt->bindParam(':signature_data', $data['signature_data']);
            $stmt->bindParam(':ip_address', $data['ip_address']);
            $stmt->bindParam(':user_agent', $data['user_agent']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':signed_by', $data['signed_by']);
            $stmt->bindParam(':signed_at', $data['signed_at']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating signature: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update signature
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE signatures SET 
                           signer_name = :signer_name,
                           signer_email = :signer_email,
                           signer_type = :signer_type,
                           signature_data = :signature_data,
                           status = :status,
                           signed_by = :signed_by,
                           signed_at = :signed_at,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':signer_name', $data['signer_name']);
            $stmt->bindParam(':signer_email', $data['signer_email']);
            $stmt->bindParam(':signer_type', $data['signer_type']);
            $stmt->bindParam(':signature_data', $data['signature_data']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':signed_by', $data['signed_by']);
            $stmt->bindParam(':signed_at', $data['signed_at']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating signature: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete signature
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM signatures WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting signature: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get signatures by contract
     */
    public function getByContract($contractId) {
        try {
            $sql = "SELECT s.*, u.name as signed_by_name
                    FROM signatures s
                    LEFT JOIN users u ON s.signed_by = u.id
                    WHERE s.contract_id = :contract_id 
                    ORDER BY s.signed_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting signatures by contract: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get signatures by status
     */
    public function getByStatus($status) {
        try {
            $sql = "SELECT s.*, 
                           c.title as contract_title,
                           cl.name as client_name
                    FROM signatures s
                    LEFT JOIN contracts c ON s.contract_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE s.status = :status 
                    ORDER BY s.signed_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting signatures by status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent signatures
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT s.*, 
                           c.title as contract_title,
                           cl.name as client_name,
                           u.name as signed_by_name
                    FROM signatures s
                    LEFT JOIN contracts c ON s.contract_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON s.signed_by = u.id
                    ORDER BY s.signed_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent signatures: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get signature statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $whereClause .= " AND signed_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND signed_at <= :date_to";
                $params[':date_to'] = $dateTo . ' 23:59:59';
            }
            
            $sql = "SELECT 
                           COUNT(*) as total_signatures,
                           COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_signatures,
                           COUNT(CASE WHEN status = 'signed' THEN 1 END) as signed_signatures,
                           COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_signatures,
                           COUNT(CASE WHEN signer_type = 'client' THEN 1 END) as client_signatures,
                           COUNT(CASE WHEN signer_type = 'company' THEN 1 END) as company_signatures,
                           COUNT(CASE WHEN signer_type = 'witness' THEN 1 END) as witness_signatures,
                           COUNT(CASE WHEN DATE(signed_at) = CURDATE() THEN 1 END) as signed_today,
                           COUNT(CASE WHEN DATE(signed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as signed_this_week,
                           COUNT(CASE WHEN DATE(signed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as signed_this_month
                    FROM signatures {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting signature statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update signature status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE signatures SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating signature status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if contract is fully signed
     */
    public function isContractFullySigned($contractId) {
        try {
            $sql = "SELECT 
                           COUNT(*) as total_signatures,
                           COUNT(CASE WHEN status = 'signed' THEN 1 END) as signed_count
                    FROM signatures 
                    WHERE contract_id = :contract_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total_signatures'] > 0 && $result['total_signatures'] == $result['signed_count'];
        } catch (PDOException $e) {
            error_log("Error checking if contract is fully signed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending signatures for a contract
     */
    public function getPendingByContract($contractId) {
        try {
            $sql = "SELECT * FROM signatures 
                    WHERE contract_id = :contract_id AND status = 'pending'
                    ORDER BY created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pending signatures by contract: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sign a signature (update with signature data)
     */
    public function sign($id, $signatureData, $ipAddress, $userAgent, $signedBy = null) {
        try {
            $sql = "UPDATE signatures SET 
                           signature_data = :signature_data,
                           ip_address = :ip_address,
                           user_agent = :user_agent,
                           signed_by = :signed_by,
                           signed_at = NOW(),
                           status = 'signed',
                           updated_at = NOW()
                    WHERE id = :id AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':signature_data', $signatureData);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->bindParam(':signed_by', $signedBy);
            
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error signing signature: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reject a signature
     */
    public function reject($id, $reason = null) {
        try {
            $sql = "UPDATE signatures SET 
                           status = 'rejected',
                           signature_data = :reason,
                           updated_at = NOW()
                    WHERE id = :id AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':reason', $reason);
            
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error rejecting signature: " . $e->getMessage());
            return false;
        }
    }
}