<?php

class PaymentController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all payments with filtering and pagination
    public function getPayments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $whereConditions = [];
            $bindParams = [];
            
            // Apply filters
            if (!empty($params['status'])) {
                $whereConditions[] = "p.status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['charge_id'])) {
                $whereConditions[] = "p.charge_id = :charge_id";
                $bindParams[':charge_id'] = $params['charge_id'];
            }
            
            if (!empty($params['method'])) {
                $whereConditions[] = "p.method = :method";
                $bindParams[':method'] = $params['method'];
            }
            
            if (!empty($params['client_id'])) {
                $whereConditions[] = "ch.client_id = :client_id";
                $bindParams[':client_id'] = $params['client_id'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(p.transaction_id LIKE :search OR cl.name LIKE :search OR p.notes LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "DATE(p.created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "DATE(p.created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            if (!empty($params['amount_min'])) {
                $whereConditions[] = "p.amount >= :amount_min";
                $bindParams[':amount_min'] = $params['amount_min'];
            }
            
            if (!empty($params['amount_max'])) {
                $whereConditions[] = "p.amount <= :amount_max";
                $bindParams[':amount_max'] = $params['amount_max'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM payments p 
                         LEFT JOIN charges ch ON p.charge_id = ch.id
                         LEFT JOIN clients cl ON ch.client_id = cl.id 
                         $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $totalPayments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get payments
            $sql = "SELECT p.*, ch.invoice_number, ch.description as charge_description,
                           cl.name as client_name, cl.email as client_email,
                           u.name as processed_by_name
                    FROM payments p 
                    LEFT JOIN charges ch ON p.charge_id = ch.id
                    LEFT JOIN clients cl ON ch.client_id = cl.id 
                    LEFT JOIN users u ON p.processed_by = u.id
                    $whereClause 
                    ORDER BY p.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $payments,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$totalPayments,
                    'total_pages' => ceil($totalPayments / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching payments: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single payment by ID
    public function getPayment($request, $response, $args) {
        try {
            $paymentId = $args['id'];
            
            $sql = "SELECT p.*, ch.invoice_number, ch.description as charge_description, ch.amount as charge_amount,
                           cl.name as client_name, cl.email as client_email,
                           u.name as processed_by_name
                    FROM payments p 
                    LEFT JOIN charges ch ON p.charge_id = ch.id
                    LEFT JOIN clients cl ON ch.client_id = cl.id 
                    LEFT JOIN users u ON p.processed_by = u.id
                    WHERE p.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $paymentId]);
            
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $payment
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new payment
    public function createPayment($request, $response, $args) {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $userId = $request->getAttribute('user_id'); // From JWT middleware
            
            // Validate required fields
            $requiredFields = ['charge_id', 'amount', 'method'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Validate charge exists
            $chargeCheckSql = "SELECT id, amount, status FROM charges WHERE id = :charge_id";
            $chargeCheckStmt = $this->db->prepare($chargeCheckSql);
            $chargeCheckStmt->execute([':charge_id' => $data['charge_id']]);
            $charge = $chargeCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$charge) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Charge not found'
                ], 400);
            }
            
            if ($charge['status'] === 'cancelled') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot add payment to cancelled charge'
                ], 400);
            }
            
            // Validate amount
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Amount must be a positive number'
                ], 400);
            }
            
            // Check if payment amount doesn't exceed remaining balance
            $paidAmountSql = "SELECT COALESCE(SUM(amount), 0) as paid_amount 
                              FROM payments WHERE charge_id = :charge_id AND status = 'completed'";
            $paidAmountStmt = $this->db->prepare($paidAmountSql);
            $paidAmountStmt->execute([':charge_id' => $data['charge_id']]);
            $paidAmount = $paidAmountStmt->fetch(PDO::FETCH_ASSOC)['paid_amount'];
            
            $remainingBalance = $charge['amount'] - $paidAmount;
            if ($data['amount'] > $remainingBalance) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => "Payment amount cannot exceed remaining balance of $remainingBalance"
                ], 400);
            }
            
            // Validate payment method
            $validMethods = ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'paypal', 'other'];
            if (!in_array($data['method'], $validMethods)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid payment method. Must be one of: ' . implode(', ', $validMethods)
                ], 400);
            }
            
            $this->db->beginTransaction();
            
            try {
                // Insert payment
                $sql = "INSERT INTO payments (charge_id, amount, method, transaction_id, notes, 
                                            status, processed_by, created_at, updated_at) 
                        VALUES (:charge_id, :amount, :method, :transaction_id, :notes, 
                                :status, :processed_by, NOW(), NOW())";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':charge_id' => $data['charge_id'],
                    ':amount' => $data['amount'],
                    ':method' => $data['method'],
                    ':transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : null,
                    ':notes' => isset($data['notes']) ? $data['notes'] : null,
                    ':status' => isset($data['status']) ? $data['status'] : 'completed',
                    ':processed_by' => $userId
                ]);
                
                $paymentId = $this->db->lastInsertId();
                
                // Check if charge is now fully paid
                $newPaidAmount = $paidAmount + $data['amount'];
                if ($newPaidAmount >= $charge['amount']) {
                    // Update charge status to paid
                    $updateChargeSql = "UPDATE charges SET status = 'paid', updated_at = NOW() WHERE id = :charge_id";
                    $updateChargeStmt = $this->db->prepare($updateChargeSql);
                    $updateChargeStmt->execute([':charge_id' => $data['charge_id']]);
                }
                
                $this->db->commit();
                
                // Get created payment
                $getPaymentSql = "SELECT p.*, ch.invoice_number, ch.description as charge_description,
                                         cl.name as client_name, cl.email as client_email,
                                         u.name as processed_by_name
                                  FROM payments p 
                                  LEFT JOIN charges ch ON p.charge_id = ch.id
                                  LEFT JOIN clients cl ON ch.client_id = cl.id 
                                  LEFT JOIN users u ON p.processed_by = u.id
                                  WHERE p.id = :id";
                $getPaymentStmt = $this->db->prepare($getPaymentSql);
                $getPaymentStmt->execute([':id' => $paymentId]);
                $payment = $getPaymentStmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Payment created successfully',
                    'data' => $payment
                ], 201);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update payment
    public function updatePayment($request, $response, $args) {
        try {
            $paymentId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if payment exists
            $checkSql = "SELECT id, status, charge_id, amount FROM payments WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $paymentId]);
            $existingPayment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingPayment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            // Prevent editing completed payments (except status changes)
            if ($existingPayment['status'] === 'completed' && isset($data['amount'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot edit amount of completed payments'
                ], 400);
            }
            
            $updateFields = [];
            $bindParams = [':id' => $paymentId];
            
            // Update fields if provided
            if (isset($data['amount'])) {
                if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Amount must be a positive number'
                    ], 400);
                }
                
                // Validate amount doesn't exceed charge amount
                $chargeCheckSql = "SELECT amount FROM charges WHERE id = :charge_id";
                $chargeCheckStmt = $this->db->prepare($chargeCheckSql);
                $chargeCheckStmt->execute([':charge_id' => $existingPayment['charge_id']]);
                $charge = $chargeCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($data['amount'] > $charge['amount']) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Payment amount cannot exceed charge amount'
                    ], 400);
                }
                
                $updateFields[] = "amount = :amount";
                $bindParams[':amount'] = $data['amount'];
            }
            
            if (isset($data['method'])) {
                $validMethods = ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'paypal', 'other'];
                if (!in_array($data['method'], $validMethods)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Invalid payment method. Must be one of: ' . implode(', ', $validMethods)
                    ], 400);
                }
                
                $updateFields[] = "method = :method";
                $bindParams[':method'] = $data['method'];
            }
            
            if (isset($data['transaction_id'])) {
                $updateFields[] = "transaction_id = :transaction_id";
                $bindParams[':transaction_id'] = $data['transaction_id'];
            }
            
            if (isset($data['notes'])) {
                $updateFields[] = "notes = :notes";
                $bindParams[':notes'] = $data['notes'];
            }
            
            if (isset($data['status'])) {
                $validStatuses = ['pending', 'completed', 'failed', 'refunded'];
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
            
            // Update payment
            $sql = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated payment
            $getPaymentSql = "SELECT p.*, ch.invoice_number, ch.description as charge_description,
                                     cl.name as client_name, cl.email as client_email,
                                     u.name as processed_by_name
                              FROM payments p 
                              LEFT JOIN charges ch ON p.charge_id = ch.id
                              LEFT JOIN clients cl ON ch.client_id = cl.id 
                              LEFT JOIN users u ON p.processed_by = u.id
                              WHERE p.id = :id";
            $getPaymentStmt = $this->db->prepare($getPaymentSql);
            $getPaymentStmt->execute([':id' => $paymentId]);
            $payment = $getPaymentStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete payment
    public function deletePayment($request, $response, $args) {
        try {
            $paymentId = $args['id'];
            
            // Check if payment exists
            $checkSql = "SELECT id, status, charge_id, amount FROM payments WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $paymentId]);
            $payment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            // Prevent deleting completed payments
            if ($payment['status'] === 'completed') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot delete completed payments. Use refund instead.'
                ], 400);
            }
            
            // Delete payment
            $sql = "DELETE FROM payments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $paymentId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get payment statistics
    public function getPaymentStats($request, $response, $args) {
        try {
            // Get total payments
            $totalSql = "SELECT COUNT(*) as total FROM payments WHERE status = 'completed'";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute();
            $totalPayments = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get payments by status
            $statusSql = "SELECT status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount 
                          FROM payments GROUP BY status";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute();
            $paymentsByStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get payments by method
            $methodSql = "SELECT method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount 
                          FROM payments WHERE status = 'completed' GROUP BY method";
            $methodStmt = $this->db->prepare($methodSql);
            $methodStmt->execute();
            $paymentsByMethod = $methodStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total revenue (completed payments)
            $revenueSql = "SELECT COALESCE(SUM(amount), 0) as total_revenue FROM payments WHERE status = 'completed'";
            $revenueStmt = $this->db->prepare($revenueSql);
            $revenueStmt->execute();
            $totalRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
            
            // Get today's payments
            $todaySql = "SELECT COUNT(*) as today_count, COALESCE(SUM(amount), 0) as today_amount 
                         FROM payments WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
            $todayStmt = $this->db->prepare($todaySql);
            $todayStmt->execute();
            $todayData = $todayStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get monthly revenue trend (last 12 months)
            $monthlyRevenueSql = "SELECT 
                                    DATE_FORMAT(created_at, '%Y-%m') as month,
                                    COUNT(*) as payment_count,
                                    COALESCE(SUM(amount), 0) as revenue
                                  FROM payments 
                                  WHERE status = 'completed' 
                                    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                  ORDER BY month DESC";
            $monthlyRevenueStmt = $this->db->prepare($monthlyRevenueSql);
            $monthlyRevenueStmt->execute();
            $monthlyRevenue = $monthlyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total_payments' => (int)$totalPayments,
                    'total_revenue' => (float)$totalRevenue,
                    'today_payments' => (int)$todayData['today_count'],
                    'today_revenue' => (float)$todayData['today_amount'],
                    'payments_by_status' => $paymentsByStatus,
                    'payments_by_method' => $paymentsByMethod,
                    'monthly_revenue' => $monthlyRevenue
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching payment statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Refund payment
    public function refundPayment($request, $response, $args) {
        try {
            $paymentId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            $userId = $request->getAttribute('user_id'); // From JWT middleware
            
            // Check if payment exists and is completed
            $checkSql = "SELECT id, status, amount, charge_id FROM payments WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $paymentId]);
            $payment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            if ($payment['status'] !== 'completed') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Only completed payments can be refunded'
                ], 400);
            }
            
            $refundAmount = isset($data['amount']) ? $data['amount'] : $payment['amount'];
            
            // Validate refund amount
            if (!is_numeric($refundAmount) || $refundAmount <= 0 || $refundAmount > $payment['amount']) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid refund amount'
                ], 400);
            }
            
            $this->db->beginTransaction();
            
            try {
                // Create refund payment record
                $refundSql = "INSERT INTO payments (charge_id, amount, method, transaction_id, notes, 
                                                   status, processed_by, created_at, updated_at) 
                              VALUES (:charge_id, :amount, :method, :transaction_id, :notes, 
                                      'refunded', :processed_by, NOW(), NOW())";
                
                $refundStmt = $this->db->prepare($refundSql);
                $refundStmt->execute([
                    ':charge_id' => $payment['charge_id'],
                    ':amount' => -$refundAmount, // Negative amount for refund
                    ':method' => 'refund',
                    ':transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : null,
                    ':notes' => 'Refund for payment #' . $paymentId . (isset($data['reason']) ? ' - ' . $data['reason'] : ''),
                    ':processed_by' => $userId
                ]);
                
                // If full refund, update original payment status
                if ($refundAmount == $payment['amount']) {
                    $updatePaymentSql = "UPDATE payments SET status = 'refunded', updated_at = NOW() WHERE id = :id";
                    $updatePaymentStmt = $this->db->prepare($updatePaymentSql);
                    $updatePaymentStmt->execute([':id' => $paymentId]);
                }
                
                // Update charge status if needed
                $chargePaidSql = "SELECT COALESCE(SUM(amount), 0) as total_paid 
                                  FROM payments WHERE charge_id = :charge_id AND status = 'completed'";
                $chargePaidStmt = $this->db->prepare($chargePaidSql);
                $chargePaidStmt->execute([':charge_id' => $payment['charge_id']]);
                $totalPaid = $chargePaidStmt->fetch(PDO::FETCH_ASSOC)['total_paid'];
                
                if ($totalPaid <= 0) {
                    $updateChargeSql = "UPDATE charges SET status = 'pending', updated_at = NOW() WHERE id = :charge_id";
                    $updateChargeStmt = $this->db->prepare($updateChargeSql);
                    $updateChargeStmt->execute([':charge_id' => $payment['charge_id']]);
                }
                
                $this->db->commit();
                
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Payment refunded successfully'
                ]);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error refunding payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>