<?php

class ChargeController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all charges with filtering and pagination
    public function getCharges($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $whereConditions = [];
            $bindParams = [];
            
            // Apply filters
            if (!empty($params['status'])) {
                $whereConditions[] = "ch.status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['client_id'])) {
                $whereConditions[] = "ch.client_id = :client_id";
                $bindParams[':client_id'] = $params['client_id'];
            }
            
            if (!empty($params['type'])) {
                $whereConditions[] = "ch.type = :type";
                $bindParams[':type'] = $params['type'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(ch.description LIKE :search OR cl.name LIKE :search OR ch.invoice_number LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "DATE(ch.created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "DATE(ch.created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            if (!empty($params['amount_min'])) {
                $whereConditions[] = "ch.amount >= :amount_min";
                $bindParams[':amount_min'] = $params['amount_min'];
            }
            
            if (!empty($params['amount_max'])) {
                $whereConditions[] = "ch.amount <= :amount_max";
                $bindParams[':amount_max'] = $params['amount_max'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM charges ch 
                         LEFT JOIN clients cl ON ch.client_id = cl.id 
                         $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $totalCharges = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get charges
            $sql = "SELECT ch.*, cl.name as client_name, cl.email as client_email,
                           u.name as created_by_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount
                    FROM charges ch 
                    LEFT JOIN clients cl ON ch.client_id = cl.id 
                    LEFT JOIN users u ON ch.created_by = u.id
                    LEFT JOIN payments p ON ch.id = p.charge_id AND p.status = 'completed'
                    $whereClause 
                    GROUP BY ch.id
                    ORDER BY ch.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $charges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate remaining balance for each charge
            foreach ($charges as &$charge) {
                $charge['remaining_balance'] = $charge['amount'] - $charge['paid_amount'];
                $charge['is_fully_paid'] = $charge['remaining_balance'] <= 0;
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $charges,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$totalCharges,
                    'total_pages' => ceil($totalCharges / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching charges: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single charge by ID
    public function getCharge($request, $response, $args) {
        try {
            $chargeId = $args['id'];
            
            $sql = "SELECT ch.*, cl.name as client_name, cl.email as client_email,
                           u.name as created_by_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount
                    FROM charges ch 
                    LEFT JOIN clients cl ON ch.client_id = cl.id 
                    LEFT JOIN users u ON ch.created_by = u.id
                    LEFT JOIN payments p ON ch.id = p.charge_id AND p.status = 'completed'
                    WHERE ch.id = :id
                    GROUP BY ch.id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $chargeId]);
            
            $charge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$charge) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Charge not found'
                ], 404);
            }
            
            // Calculate remaining balance
            $charge['remaining_balance'] = $charge['amount'] - $charge['paid_amount'];
            $charge['is_fully_paid'] = $charge['remaining_balance'] <= 0;
            
            // Get payments for this charge
            $paymentsSql = "SELECT p.*, u.name as processed_by_name 
                            FROM payments p 
                            LEFT JOIN users u ON p.processed_by = u.id 
                            WHERE p.charge_id = :charge_id 
                            ORDER BY p.created_at DESC";
            $paymentsStmt = $this->db->prepare($paymentsSql);
            $paymentsStmt->execute([':charge_id' => $chargeId]);
            $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $charge['payments'] = $payments;
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $charge
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching charge: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new charge
    public function createCharge($request, $response, $args) {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $userId = $request->getAttribute('user_id'); // From JWT middleware
            
            // Validate required fields
            $requiredFields = ['client_id', 'amount', 'description', 'type'];
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
            
            // Validate amount
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Amount must be a positive number'
                ], 400);
            }
            
            // Validate charge type
            $validTypes = ['service', 'product', 'subscription', 'late_fee', 'other'];
            if (!in_array($data['type'], $validTypes)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid charge type. Must be one of: ' . implode(', ', $validTypes)
                ], 400);
            }
            
            // Validate due date if provided
            if (!empty($data['due_date']) && !$this->isValidDate($data['due_date'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid due date format. Use YYYY-MM-DD'
                ], 400);
            }
            
            // Generate invoice number if not provided
            $invoiceNumber = isset($data['invoice_number']) ? $data['invoice_number'] : $this->generateInvoiceNumber();
            
            // Insert charge
            $sql = "INSERT INTO charges (client_id, amount, description, type, invoice_number, 
                                       due_date, status, created_by, created_at, updated_at) 
                    VALUES (:client_id, :amount, :description, :type, :invoice_number, 
                            :due_date, :status, :created_by, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':client_id' => $data['client_id'],
                ':amount' => $data['amount'],
                ':description' => $data['description'],
                ':type' => $data['type'],
                ':invoice_number' => $invoiceNumber,
                ':due_date' => isset($data['due_date']) ? $data['due_date'] : null,
                ':status' => isset($data['status']) ? $data['status'] : 'pending',
                ':created_by' => $userId
            ]);
            
            $chargeId = $this->db->lastInsertId();
            
            // Get created charge
            $getChargeSql = "SELECT ch.*, cl.name as client_name, cl.email as client_email,
                                    u.name as created_by_name
                             FROM charges ch 
                             LEFT JOIN clients cl ON ch.client_id = cl.id 
                             LEFT JOIN users u ON ch.created_by = u.id
                             WHERE ch.id = :id";
            $getChargeStmt = $this->db->prepare($getChargeSql);
            $getChargeStmt->execute([':id' => $chargeId]);
            $charge = $getChargeStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Charge created successfully',
                'data' => $charge
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating charge: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update charge
    public function updateCharge($request, $response, $args) {
        try {
            $chargeId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if charge exists
            $checkSql = "SELECT id, status FROM charges WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $chargeId]);
            $existingCharge = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingCharge) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Charge not found'
                ], 404);
            }
            
            // Prevent editing paid charges
            if ($existingCharge['status'] === 'paid') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot edit paid charges'
                ], 400);
            }
            
            $updateFields = [];
            $bindParams = [':id' => $chargeId];
            
            // Update fields if provided
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
            
            if (isset($data['amount'])) {
                if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Amount must be a positive number'
                    ], 400);
                }
                
                $updateFields[] = "amount = :amount";
                $bindParams[':amount'] = $data['amount'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = :description";
                $bindParams[':description'] = $data['description'];
            }
            
            if (isset($data['type'])) {
                $validTypes = ['service', 'product', 'subscription', 'late_fee', 'other'];
                if (!in_array($data['type'], $validTypes)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid charge type. Must be one of: ' . implode(', ', $validTypes)
                    ], 400);
                }
                
                $updateFields[] = "type = :type";
                $bindParams[':type'] = $data['type'];
            }
            
            if (isset($data['invoice_number'])) {
                $updateFields[] = "invoice_number = :invoice_number";
                $bindParams[':invoice_number'] = $data['invoice_number'];
            }
            
            if (isset($data['due_date'])) {
                if (!empty($data['due_date']) && !$this->isValidDate($data['due_date'])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid due date format. Use YYYY-MM-DD'
                    ], 400);
                }
                
                $updateFields[] = "due_date = :due_date";
                $bindParams[':due_date'] = $data['due_date'];
            }
            
            if (isset($data['status'])) {
                $validStatuses = ['pending', 'sent', 'overdue', 'paid', 'cancelled'];
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
            
            // Update charge
            $sql = "UPDATE charges SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated charge
            $getChargeSql = "SELECT ch.*, cl.name as client_name, cl.email as client_email,
                                    u.name as created_by_name
                             FROM charges ch 
                             LEFT JOIN clients cl ON ch.client_id = cl.id 
                             LEFT JOIN users u ON ch.created_by = u.id
                             WHERE ch.id = :id";
            $getChargeStmt = $this->db->prepare($getChargeSql);
            $getChargeStmt->execute([':id' => $chargeId]);
            $charge = $getChargeStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Charge updated successfully',
                'data' => $charge
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating charge: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete charge
    public function deleteCharge($request, $response, $args) {
        try {
            $chargeId = $args['id'];
            
            // Check if charge exists
            $checkSql = "SELECT id, status FROM charges WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $chargeId]);
            $charge = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$charge) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Charge not found'
                ], 404);
            }
            
            // Check if charge has payments
            $paymentsCheckSql = "SELECT COUNT(*) as payment_count FROM payments WHERE charge_id = :charge_id";
            $paymentsCheckStmt = $this->db->prepare($paymentsCheckSql);
            $paymentsCheckStmt->execute([':charge_id' => $chargeId]);
            $paymentCount = $paymentsCheckStmt->fetch(PDO::FETCH_ASSOC)['payment_count'];
            
            if ($paymentCount > 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot delete charge with existing payments'
                ], 400);
            }
            
            // Delete charge
            $sql = "DELETE FROM charges WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $chargeId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Charge deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting charge: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get charge statistics
    public function getChargeStats($request, $response, $args) {
        try {
            // Get total charges
            $totalSql = "SELECT COUNT(*) as total FROM charges";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute();
            $totalCharges = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get charges by status
            $statusSql = "SELECT status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount 
                          FROM charges GROUP BY status";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute();
            $chargesByStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total revenue (paid charges)
            $revenueSql = "SELECT COALESCE(SUM(amount), 0) as total_revenue FROM charges WHERE status = 'paid'";
            $revenueStmt = $this->db->prepare($revenueSql);
            $revenueStmt->execute();
            $totalRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
            
            // Get outstanding amount (pending + overdue)
            $outstandingSql = "SELECT COALESCE(SUM(amount), 0) as outstanding 
                               FROM charges WHERE status IN ('pending', 'sent', 'overdue')";
            $outstandingStmt = $this->db->prepare($outstandingSql);
            $outstandingStmt->execute();
            $outstandingAmount = $outstandingStmt->fetch(PDO::FETCH_ASSOC)['outstanding'];
            
            // Get overdue charges
            $overdueSql = "SELECT COUNT(*) as overdue_count, COALESCE(SUM(amount), 0) as overdue_amount 
                           FROM charges WHERE status IN ('pending', 'sent') AND due_date < CURDATE()";
            $overdueStmt = $this->db->prepare($overdueSql);
            $overdueStmt->execute();
            $overdueData = $overdueStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get monthly revenue trend (last 12 months)
            $monthlyRevenueSql = "SELECT 
                                    DATE_FORMAT(created_at, '%Y-%m') as month,
                                    COALESCE(SUM(amount), 0) as revenue
                                  FROM charges 
                                  WHERE status = 'paid' 
                                    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                  ORDER BY month DESC";
            $monthlyRevenueStmt = $this->db->prepare($monthlyRevenueSql);
            $monthlyRevenueStmt->execute();
            $monthlyRevenue = $monthlyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total_charges' => (int)$totalCharges,
                    'total_revenue' => (float)$totalRevenue,
                    'outstanding_amount' => (float)$outstandingAmount,
                    'overdue_count' => (int)$overdueData['overdue_count'],
                    'overdue_amount' => (float)$overdueData['overdue_amount'],
                    'charges_by_status' => $chargesByStatus,
                    'monthly_revenue' => $monthlyRevenue
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching charge statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Mark charge as sent
    public function markAsSent($request, $response, $args) {
        try {
            $chargeId = $args['id'];
            
            // Check if charge exists and is pending
            $checkSql = "SELECT id, status FROM charges WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $chargeId]);
            $charge = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$charge) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Charge not found'
                ], 404);
            }
            
            if ($charge['status'] !== 'pending') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Only pending charges can be marked as sent'
                ], 400);
            }
            
            // Update charge status
            $sql = "UPDATE charges SET status = 'sent', updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $chargeId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Charge marked as sent successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error marking charge as sent: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function generateInvoiceNumber() {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $sql = "SELECT invoice_number FROM charges 
                WHERE invoice_number LIKE :pattern 
                ORDER BY invoice_number DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pattern' => "$prefix-$year$month-%"]);
        
        $lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastInvoice) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastInvoice['invoice_number']);
            $sequence = (int)end($parts) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
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