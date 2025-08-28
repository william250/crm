<?php

require_once __DIR__ . '/../config/database.php';

class Charge {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all charges with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           cl.email as client_email,
                           u.name as created_by_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance
                    FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['client_id'])) {
                $sql .= " AND c.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND c.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND c.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['created_by'])) {
                $sql .= " AND c.created_by = :created_by";
                $params[':created_by'] = $filters['created_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND c.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND c.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['due_date_from'])) {
                $sql .= " AND c.due_date >= :due_date_from";
                $params[':due_date_from'] = $filters['due_date_from'];
            }
            
            if (!empty($filters['due_date_to'])) {
                $sql .= " AND c.due_date <= :due_date_to";
                $params[':due_date_to'] = $filters['due_date_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.description LIKE :search OR c.invoice_number LIKE :search OR cl.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Group by charge ID for aggregation
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
            error_log("Error getting charges: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of charges with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(DISTINCT c.id) as total FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE 1=1";
            $params = [];
            
            // Apply same filters as getAll
            if (!empty($filters['client_id'])) {
                $sql .= " AND c.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND c.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND c.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['created_by'])) {
                $sql .= " AND c.created_by = :created_by";
                $params[':created_by'] = $filters['created_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND c.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND c.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['due_date_from'])) {
                $sql .= " AND c.due_date >= :due_date_from";
                $params[':due_date_from'] = $filters['due_date_from'];
            }
            
            if (!empty($filters['due_date_to'])) {
                $sql .= " AND c.due_date <= :due_date_to";
                $params[':due_date_to'] = $filters['due_date_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.description LIKE :search OR c.invoice_number LIKE :search OR cl.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting charge count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get charge by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           cl.email as client_email,
                           cl.phone as client_phone,
                           cl.address as client_address,
                           u.name as created_by_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance
                    FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    WHERE c.id = :id
                    GROUP BY c.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting charge by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new charge
     */
    public function create($data) {
        try {
            // Generate invoice number if not provided
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumber();
            }
            
            $sql = "INSERT INTO charges (client_id, amount, currency, description, type, status, invoice_number, due_date, created_by, created_at, updated_at)
                    VALUES (:client_id, :amount, :currency, :description, :type, :status, :invoice_number, :due_date, :created_by, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':currency', $data['currency']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':invoice_number', $data['invoice_number']);
            $stmt->bindParam(':due_date', $data['due_date']);
            $stmt->bindParam(':created_by', $data['created_by']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating charge: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update charge
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE charges SET 
                           client_id = :client_id,
                           amount = :amount,
                           currency = :currency,
                           description = :description,
                           type = :type,
                           status = :status,
                           invoice_number = :invoice_number,
                           due_date = :due_date,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':currency', $data['currency']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':invoice_number', $data['invoice_number']);
            $stmt->bindParam(':due_date', $data['due_date']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating charge: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete charge (only if no payments exist)
     */
    public function delete($id) {
        try {
            // Check if there are any payments for this charge
            $checkSql = "SELECT COUNT(*) as payment_count FROM payments WHERE charge_id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['payment_count'] > 0) {
                return ['error' => 'Cannot delete charge with existing payments'];
            }
            
            $sql = "DELETE FROM charges WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting charge: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get charges by client
     */
    public function getByClient($clientId) {
        try {
            $sql = "SELECT c.*, 
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance
                    FROM charges c
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    WHERE c.client_id = :client_id 
                    GROUP BY c.id
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting charges by client: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get charges by status
     */
    public function getByStatus($status) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance
                    FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    WHERE c.status = :status 
                    GROUP BY c.id
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting charges by status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get overdue charges
     */
    public function getOverdue() {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           cl.email as client_email,
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance,
                           DATEDIFF(CURDATE(), c.due_date) as days_overdue
                    FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    WHERE c.due_date < CURDATE() 
                    AND c.status IN ('pending', 'sent')
                    GROUP BY c.id
                    HAVING balance > 0
                    ORDER BY c.due_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting overdue charges: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent charges
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           u.name as created_by_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance
                    FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON c.created_by = u.id
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    GROUP BY c.id
                    ORDER BY c.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent charges: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get charge statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $whereClause .= " AND c.created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND c.created_at <= :date_to";
                $params[':date_to'] = $dateTo . ' 23:59:59';
            }
            
            $sql = "SELECT 
                           COUNT(*) as total_charges,
                           SUM(c.amount) as total_amount,
                           COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_charges,
                           COUNT(CASE WHEN c.status = 'sent' THEN 1 END) as sent_charges,
                           COUNT(CASE WHEN c.status = 'paid' THEN 1 END) as paid_charges,
                           COUNT(CASE WHEN c.status = 'overdue' THEN 1 END) as overdue_charges,
                           COUNT(CASE WHEN c.status = 'cancelled' THEN 1 END) as cancelled_charges,
                           SUM(CASE WHEN c.status = 'paid' THEN c.amount ELSE 0 END) as paid_amount,
                           SUM(CASE WHEN c.status IN ('pending', 'sent') THEN c.amount ELSE 0 END) as outstanding_amount,
                           COUNT(CASE WHEN c.due_date < CURDATE() AND c.status IN ('pending', 'sent') THEN 1 END) as overdue_count,
                           SUM(CASE WHEN c.due_date < CURDATE() AND c.status IN ('pending', 'sent') THEN c.amount ELSE 0 END) as overdue_amount,
                           COUNT(CASE WHEN DATE(c.created_at) = CURDATE() THEN 1 END) as created_today,
                           COUNT(CASE WHEN DATE(c.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as created_this_week,
                           COUNT(CASE WHEN DATE(c.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as created_this_month
                    FROM charges c {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting charge statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update charge status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE charges SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating charge status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber() {
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $sql = "SELECT invoice_number FROM charges 
                WHERE invoice_number LIKE :pattern 
                ORDER BY invoice_number DESC LIMIT 1";
        
        $pattern = "INV-{$year}{$month}-%";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pattern', $pattern);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Extract the sequence number and increment
            $lastNumber = $result['invoice_number'];
            $parts = explode('-', $lastNumber);
            $sequence = (int)end($parts) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf("INV-%s%s-%04d", $year, $month, $sequence);
    }
    
    /**
     * Mark charge as paid (when payment is completed)
     */
    public function markAsPaid($id) {
        try {
            // Check if charge is fully paid
            $charge = $this->getById($id);
            if (!$charge) {
                return false;
            }
            
            if ($charge['balance'] <= 0) {
                return $this->updateStatus($id, 'paid');
            }
            
            return true; // Partially paid, keep current status
        } catch (PDOException $e) {
            error_log("Error marking charge as paid: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get charges due soon (within specified days)
     */
    public function getDueSoon($days = 7) {
        try {
            $sql = "SELECT c.*, 
                           cl.name as client_name,
                           cl.email as client_email,
                           COALESCE(SUM(p.amount), 0) as paid_amount,
                           (c.amount - COALESCE(SUM(p.amount), 0)) as balance,
                           DATEDIFF(c.due_date, CURDATE()) as days_until_due
                    FROM charges c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN payments p ON c.id = p.charge_id AND p.status = 'completed'
                    WHERE c.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                    AND c.status IN ('pending', 'sent')
                    GROUP BY c.id
                    HAVING balance > 0
                    ORDER BY c.due_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting charges due soon: " . $e->getMessage());
            return [];
        }
    }
}