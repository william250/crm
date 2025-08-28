<?php

require_once __DIR__ . '/../config/database.php';

class Payment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all payments with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           c.description as charge_description,
                           c.amount as charge_amount,
                           cl.name as client_name,
                           cl.email as client_email,
                           u.name as processed_by_name
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON p.processed_by = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['charge_id'])) {
                $sql .= " AND p.charge_id = :charge_id";
                $params[':charge_id'] = $filters['charge_id'];
            }
            
            if (!empty($filters['client_id'])) {
                $sql .= " AND c.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND p.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['method'])) {
                $sql .= " AND p.method = :method";
                $params[':method'] = $filters['method'];
            }
            
            if (!empty($filters['processed_by'])) {
                $sql .= " AND p.processed_by = :processed_by";
                $params[':processed_by'] = $filters['processed_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND p.payment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND p.payment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (p.transaction_id LIKE :search OR p.notes LIKE :search OR c.invoice_number LIKE :search OR cl.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Add sorting
            $orderBy = $filters['order_by'] ?? 'payment_date';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY p.{$orderBy} {$orderDir}";
            
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
            error_log("Error getting payments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of payments with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE 1=1";
            $params = [];
            
            // Apply same filters as getAll
            if (!empty($filters['charge_id'])) {
                $sql .= " AND p.charge_id = :charge_id";
                $params[':charge_id'] = $filters['charge_id'];
            }
            
            if (!empty($filters['client_id'])) {
                $sql .= " AND c.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND p.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['method'])) {
                $sql .= " AND p.method = :method";
                $params[':method'] = $filters['method'];
            }
            
            if (!empty($filters['processed_by'])) {
                $sql .= " AND p.processed_by = :processed_by";
                $params[':processed_by'] = $filters['processed_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND p.payment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND p.payment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (p.transaction_id LIKE :search OR p.notes LIKE :search OR c.invoice_number LIKE :search OR cl.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting payment count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get payment by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           c.description as charge_description,
                           c.amount as charge_amount,
                           c.client_id,
                           cl.name as client_name,
                           cl.email as client_email,
                           cl.phone as client_phone,
                           u.name as processed_by_name
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON p.processed_by = u.id
                    WHERE p.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting payment by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new payment
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO payments (charge_id, amount, currency, method, status, transaction_id, payment_date, notes, processed_by, created_at, updated_at)
                    VALUES (:charge_id, :amount, :currency, :method, :status, :transaction_id, :payment_date, :notes, :processed_by, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':charge_id', $data['charge_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':currency', $data['currency']);
            $stmt->bindParam(':method', $data['method']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':transaction_id', $data['transaction_id']);
            $stmt->bindParam(':payment_date', $data['payment_date']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':processed_by', $data['processed_by']);
            
            if ($stmt->execute()) {
                $paymentId = $this->db->lastInsertId();
                
                // If payment is completed, update charge status
                if ($data['status'] === 'completed') {
                    $this->updateChargeStatus($data['charge_id']);
                }
                
                return $paymentId;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update payment
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE payments SET 
                           charge_id = :charge_id,
                           amount = :amount,
                           currency = :currency,
                           method = :method,
                           status = :status,
                           transaction_id = :transaction_id,
                           payment_date = :payment_date,
                           notes = :notes,
                           processed_by = :processed_by,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':charge_id', $data['charge_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':currency', $data['currency']);
            $stmt->bindParam(':method', $data['method']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':transaction_id', $data['transaction_id']);
            $stmt->bindParam(':payment_date', $data['payment_date']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':processed_by', $data['processed_by']);
            
            if ($stmt->execute()) {
                // Update charge status if payment status changed
                $this->updateChargeStatus($data['charge_id']);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error updating payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete payment
     */
    public function delete($id) {
        try {
            // Get payment info before deletion
            $payment = $this->getById($id);
            if (!$payment) {
                return false;
            }
            
            $sql = "DELETE FROM payments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Update charge status after payment deletion
                $this->updateChargeStatus($payment['charge_id']);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error deleting payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payments by charge
     */
    public function getByCharge($chargeId) {
        try {
            $sql = "SELECT p.*, u.name as processed_by_name
                    FROM payments p
                    LEFT JOIN users u ON p.processed_by = u.id
                    WHERE p.charge_id = :charge_id 
                    ORDER BY p.payment_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':charge_id', $chargeId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting payments by charge: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payments by status
     */
    public function getByStatus($status) {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           cl.name as client_name,
                           u.name as processed_by_name
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON p.processed_by = u.id
                    WHERE p.status = :status 
                    ORDER BY p.payment_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting payments by status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent payments
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           cl.name as client_name,
                           u.name as processed_by_name
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN users u ON p.processed_by = u.id
                    ORDER BY p.payment_date DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent payments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $whereClause .= " AND p.payment_date >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND p.payment_date <= :date_to";
                $params[':date_to'] = $dateTo . ' 23:59:59';
            }
            
            $sql = "SELECT 
                           COUNT(*) as total_payments,
                           SUM(p.amount) as total_amount,
                           COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_payments,
                           COUNT(CASE WHEN p.status = 'processing' THEN 1 END) as processing_payments,
                           COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as completed_payments,
                           COUNT(CASE WHEN p.status = 'failed' THEN 1 END) as failed_payments,
                           COUNT(CASE WHEN p.status = 'refunded' THEN 1 END) as refunded_payments,
                           SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as completed_amount,
                           SUM(CASE WHEN p.status = 'refunded' THEN p.amount ELSE 0 END) as refunded_amount,
                           COUNT(CASE WHEN p.method = 'credit_card' THEN 1 END) as credit_card_payments,
                           COUNT(CASE WHEN p.method = 'bank_transfer' THEN 1 END) as bank_transfer_payments,
                           COUNT(CASE WHEN p.method = 'cash' THEN 1 END) as cash_payments,
                           COUNT(CASE WHEN p.method = 'check' THEN 1 END) as check_payments,
                           COUNT(CASE WHEN p.method = 'paypal' THEN 1 END) as paypal_payments,
                           COUNT(CASE WHEN p.method = 'other' THEN 1 END) as other_payments,
                           COUNT(CASE WHEN DATE(p.payment_date) = CURDATE() THEN 1 END) as payments_today,
                           COUNT(CASE WHEN DATE(p.payment_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as payments_this_week,
                           COUNT(CASE WHEN DATE(p.payment_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as payments_this_month,
                           SUM(CASE WHEN DATE(p.payment_date) = CURDATE() THEN p.amount ELSE 0 END) as amount_today,
                           SUM(CASE WHEN DATE(p.payment_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN p.amount ELSE 0 END) as amount_this_week,
                           SUM(CASE WHEN DATE(p.payment_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN p.amount ELSE 0 END) as amount_this_month
                    FROM payments p {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting payment statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update payment status
     */
    public function updateStatus($id, $status) {
        try {
            $payment = $this->getById($id);
            if (!$payment) {
                return false;
            }
            
            $sql = "UPDATE payments SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                // Update charge status
                $this->updateChargeStatus($payment['charge_id']);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payments by method
     */
    public function getByMethod($method) {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           cl.name as client_name
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE p.method = :method 
                    ORDER BY p.payment_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':method', $method);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting payments by method: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payments by client
     */
    public function getByClient($clientId) {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           c.description as charge_description,
                           u.name as processed_by_name
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN users u ON p.processed_by = u.id
                    WHERE c.client_id = :client_id 
                    ORDER BY p.payment_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting payments by client: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get failed payments
     */
    public function getFailedPayments() {
        try {
            $sql = "SELECT p.*, 
                           c.invoice_number,
                           cl.name as client_name,
                           cl.email as client_email
                    FROM payments p
                    LEFT JOIN charges c ON p.charge_id = c.id
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE p.status = 'failed' 
                    ORDER BY p.payment_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting failed payments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update charge status based on payments
     */
    private function updateChargeStatus($chargeId) {
        try {
            // Get charge and payment totals
            $sql = "SELECT 
                           c.amount as charge_amount,
                           COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as paid_amount
                    FROM charges c
                    LEFT JOIN payments p ON c.id = p.charge_id
                    WHERE c.id = :charge_id
                    GROUP BY c.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':charge_id', $chargeId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $chargeAmount = (float)$result['charge_amount'];
                $paidAmount = (float)$result['paid_amount'];
                
                $newStatus = 'pending';
                
                if ($paidAmount >= $chargeAmount) {
                    $newStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $newStatus = 'partial';
                }
                
                // Update charge status
                $updateSql = "UPDATE charges SET status = :status, updated_at = NOW() WHERE id = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bindParam(':status', $newStatus);
                $updateStmt->bindParam(':id', $chargeId, PDO::PARAM_INT);
                $updateStmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Error updating charge status: " . $e->getMessage());
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($id, $refundAmount = null, $reason = null) {
        try {
            $payment = $this->getById($id);
            if (!$payment || $payment['status'] !== 'completed') {
                return false;
            }
            
            $refundAmount = $refundAmount ?? $payment['amount'];
            
            // Create refund payment record
            $refundData = [
                'charge_id' => $payment['charge_id'],
                'amount' => -$refundAmount, // Negative amount for refund
                'currency' => $payment['currency'],
                'method' => $payment['method'],
                'status' => 'completed',
                'transaction_id' => 'REFUND-' . $payment['transaction_id'],
                'payment_date' => date('Y-m-d H:i:s'),
                'notes' => 'Refund: ' . ($reason ?? 'No reason provided'),
                'processed_by' => $payment['processed_by']
            ];
            
            $refundId = $this->create($refundData);
            
            if ($refundId) {
                // Update original payment status if fully refunded
                if ($refundAmount >= $payment['amount']) {
                    $this->updateStatus($id, 'refunded');
                }
                
                return $refundId;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error processing refund: " . $e->getMessage());
            return false;
        }
    }
}