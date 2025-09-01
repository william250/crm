<?php

class BillingController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all invoices with filtering and pagination
    public function getInvoices($request, $response, $args) {
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
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(cl.name LIKE :search OR ch.type LIKE :search)";
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
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) as total
                FROM charges ch
                LEFT JOIN clients cl ON ch.client_id = cl.id
                $whereClause
            ";
            
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get invoices
            $sql = "
                SELECT 
                    ch.id,
                    CONCAT('INV-', YEAR(ch.created_at), '-', LPAD(MONTH(ch.created_at), 2, '0'), '-', LPAD(ch.id, 3, '0')) as invoice_number,
                    ch.client_id,
                    cl.name as client_name,
                    ch.amount,
                    ch.status,
                    ch.due_date,
                    ch.created_at,
                    ch.created_at as updated_at,
                    CONCAT('Cobrança ', ch.type, ' - ', ch.payment_method) as description,
                    ch.type
                FROM charges ch
                LEFT JOIN clients cl ON ch.client_id = cl.id
                $whereClause
                ORDER BY ch.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            
            // Remove limit and offset from bindParams since we're using direct values
            // $bindParams[':limit'] = $limit;
            // $bindParams[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the response
            $formattedInvoices = array_map(function($invoice) {
                return [
                    'id' => (int)$invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'client_id' => (int)$invoice['client_id'],
                    'client_name' => $invoice['client_name'],
                    'amount' => (float)$invoice['amount'],
                    'status' => $invoice['status'],
                    'due_date' => $invoice['due_date'],
                    'created_at' => $invoice['created_at'],
                    'updated_at' => $invoice['updated_at'],
                    'description' => $invoice['description'],
                    'type' => $invoice['type']
                ];
            }, $invoices);
            
            $responseData = [
                'success' => true,
                'data' => $formattedInvoices,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$totalCount,
                    'total_pages' => ceil($totalCount / $limit)
                ]
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Erro ao buscar faturas: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // Create a new invoice
    public function createInvoice($request, $response, $args) {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate required fields
            $requiredFields = ['client_id', 'amount', 'due_date'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $errorResponse = [
                        'success' => false,
                        'message' => "Campo obrigatório ausente: $field"
                    ];
                    $response->getBody()->write(json_encode($errorResponse));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }
            
            $sql = "
                INSERT INTO charges (
                    client_id, amount, status, due_date, 
                    type, payment_method, created_at
                ) VALUES (
                    :client_id, :amount, :status, :due_date,
                    :type, :payment_method, NOW()
                )
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':client_id' => $data['client_id'],
                ':amount' => $data['amount'],
                ':status' => $data['status'] ?? 'pending',
                ':due_date' => $data['due_date'],
                ':type' => $data['type'] ?? 'one_time',
                ':payment_method' => $data['payment_method'] ?? 'pix'
            ]);
            
            $invoiceId = $this->db->lastInsertId();
            
            // Generate invoice number based on created ID
            $invoiceNumber = sprintf('INV-%s-%s-%03d', 
                date('Y'), 
                date('m'), 
                $invoiceId
            );
            
            $responseData = [
                'success' => true,
                'message' => 'Fatura criada com sucesso',
                'data' => [
                    'id' => (int)$invoiceId,
                    'invoice_number' => $invoiceNumber
                ]
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Erro ao criar fatura: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // Get a specific invoice
    public function getInvoice($request, $response, $args) {
        try {
            $invoiceId = $args['id'];
            
            $sql = "
                SELECT 
                    ch.id,
                    CONCAT('INV-', YEAR(ch.created_at), '-', LPAD(MONTH(ch.created_at), 2, '0'), '-', LPAD(ch.id, 3, '0')) as invoice_number,
                    ch.client_id,
                    cl.name as client_name,
                    ch.amount,
                    ch.status,
                    ch.due_date,
                    ch.created_at,
                    ch.created_at as updated_at,
                    CONCAT('Cobrança ', ch.type, ' - ', ch.payment_method) as description,
                    ch.type
                FROM charges ch
                LEFT JOIN clients cl ON ch.client_id = cl.id
                WHERE ch.id = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $invoiceId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invoice) {
                $errorResponse = [
                    'success' => false,
                    'message' => 'Fatura não encontrada'
                ];
                $response->getBody()->write(json_encode($errorResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $formattedInvoice = [
                'id' => (int)$invoice['id'],
                'invoice_number' => $invoice['invoice_number'],
                'client_id' => (int)$invoice['client_id'],
                'client_name' => $invoice['client_name'],
                'amount' => (float)$invoice['amount'],
                'status' => $invoice['status'],
                'due_date' => $invoice['due_date'],
                'created_at' => $invoice['created_at'],
                'updated_at' => $invoice['updated_at'],
                'description' => $invoice['description'],
                'type' => $invoice['type']
            ];
            
            $responseData = [
                'success' => true,
                'data' => $formattedInvoice
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Erro ao buscar fatura: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // Update an invoice
    public function updateInvoice($request, $response, $args) {
        try {
            $invoiceId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if invoice exists
            $checkSql = "SELECT id FROM charges WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $invoiceId]);
            
            if (!$checkStmt->fetch()) {
                $errorResponse = [
                    'success' => false,
                    'message' => 'Fatura não encontrada'
                ];
                $response->getBody()->write(json_encode($errorResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $updateFields = [];
            $bindParams = [':id' => $invoiceId];
            
            $allowedFields = ['client_id', 'amount', 'status', 'due_date', 'type', 'payment_method'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    $bindParams[":$field"] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                $errorResponse = [
                    'success' => false,
                    'message' => 'Nenhum campo para atualizar'
                ];
                $response->getBody()->write(json_encode($errorResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $sql = "UPDATE charges SET " . implode(', ', $updateFields) . " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            $responseData = [
                'success' => true,
                'message' => 'Fatura atualizada com sucesso'
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Erro ao atualizar fatura: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // Delete an invoice
    public function deleteInvoice($request, $response, $args) {
        try {
            $invoiceId = $args['id'];
            
            // Check if invoice exists
            $checkSql = "SELECT id FROM charges WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $invoiceId]);
            
            if (!$checkStmt->fetch()) {
                $errorResponse = [
                    'success' => false,
                    'message' => 'Fatura não encontrada'
                ];
                $response->getBody()->write(json_encode($errorResponse));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $sql = "DELETE FROM charges WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $invoiceId]);
            
            $responseData = [
                'success' => true,
                'message' => 'Fatura excluída com sucesso'
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Erro ao excluir fatura: ' . $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    // Generate invoice number
    private function generateInvoiceNumber() {
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $sql = "SELECT invoice_number FROM charges WHERE invoice_number LIKE :pattern ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pattern' => "INV-$year-$month-%"]);
        $lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastInvoice) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastInvoice['invoice_number']);
            $sequence = (int)end($parts) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('INV-%s-%s-%03d', $year, $month, $sequence);
    }
    
    // Get charges (for backward compatibility)
    public function getCharges($request, $response, $args) {
        return $this->getInvoices($request, $response, $args);
    }
    
    // Create charge (for backward compatibility)
    public function createCharge($request, $response, $args) {
        return $this->createInvoice($request, $response, $args);
    }
    
    // Get charge (for backward compatibility)
    public function getCharge($request, $response, $args) {
        return $this->getInvoice($request, $response, $args);
    }
    
    // Update charge (for backward compatibility)
    public function updateCharge($request, $response, $args) {
        return $this->updateInvoice($request, $response, $args);
    }
    
    // Delete charge (for backward compatibility)
    public function deleteCharge($request, $response, $args) {
        return $this->deleteInvoice($request, $response, $args);
    }
    
    // Generate payment link (placeholder)
    public function generatePaymentLink($request, $response, $args) {
        $responseData = [
            'success' => true,
            'message' => 'Funcionalidade de link de pagamento será implementada em breve',
            'data' => [
                'payment_link' => '#'
            ]
        ];
        
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }
}