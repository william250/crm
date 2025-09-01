<?php

namespace App\Models;

use PDO;
use PDOException;

class Lead
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    public function getAll(array $filters = []): array
    {
        try {
            $sql = "SELECT l.*, u.name as assigned_user_name FROM leads l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= " AND l.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['assigned_to'])) {
                $sql .= " AND l.user_id = ?";
                $params[] = $filters['assigned_to'];
            }
            
            if (!empty($filters['source'])) {
                $sql .= " AND l.source = ?";
                $params[] = $filters['source'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (l.name LIKE ? OR l.email LIKE ? OR l.company LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY l.created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . (int)$filters['limit'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting leads: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, u.name as assigned_user_name 
                FROM leads l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $lead ?: null;
        } catch (PDOException $e) {
            error_log("Error getting lead by ID: " . $e->getMessage());
            return null;
        }
    }
    
    public function create(array $leadData): ?int
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO leads (name, email, phone, company, source, status, 
                                 assigned_to, notes, value, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $leadData['name'],
                $leadData['email'] ?? null,
                $leadData['phone'] ?? null,
                $leadData['company'] ?? null,
                $leadData['source'] ?? 'website',
                $leadData['status'] ?? 'new',
                $leadData['assigned_to'] ?? null,
                $leadData['notes'] ?? null,
                $leadData['value'] ?? 0
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating lead: " . $e->getMessage());
            return null;
        }
    }
    
    public function update(int $id, array $leadData): bool
    {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = ['name', 'email', 'phone', 'company', 'source', 
                            'status', 'assigned_to', 'notes', 'value'];
            
            foreach ($leadData as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $id;
            $sql = "UPDATE leads SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error updating lead: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting lead: " . $e->getMessage());
            return false;
        }
    }
    
    public function convertToClient(int $leadId, array $clientData): ?int
    {
        try {
            $this->db->beginTransaction();
            
            // Get lead data
            $lead = $this->getById($leadId);
            if (!$lead) {
                $this->db->rollBack();
                return null;
            }
            
            // Create client
            $stmt = $this->db->prepare("
                INSERT INTO clients (name, email, phone, company, address, 
                                   assigned_to, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $clientData['name'] ?? $lead['name'],
                $clientData['email'] ?? $lead['email'],
                $clientData['phone'] ?? $lead['phone'],
                $clientData['company'] ?? $lead['company'],
                $clientData['address'] ?? null,
                $clientData['assigned_to'] ?? $lead['assigned_to'],
                $clientData['notes'] ?? $lead['notes']
            ]);
            
            $clientId = $this->db->lastInsertId();
            
            // Update lead status to converted
            $this->update($leadId, ['status' => 'converted']);
            
            $this->db->commit();
            return $clientId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error converting lead to client: " . $e->getMessage());
            return null;
        }
    }
    
    public function getLeadsByStatus(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT status, COUNT(*) as count 
                FROM leads 
                GROUP BY status 
                ORDER BY count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting leads by status: " . $e->getMessage());
            return [];
        }
    }
    
    public function getLeadsBySource(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT source, COUNT(*) as count 
                FROM leads 
                GROUP BY source 
                ORDER BY count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting leads by source: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRecentLeads(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, u.name as assigned_user_name 
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                ORDER BY l.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent leads: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalCount()
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM leads";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting total count: " . $e->getMessage());
            return 0;
        }
    }

    public function getConvertedCount()
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM leads WHERE status = 'converted'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting converted count: " . $e->getMessage());
            return 0;
        }
    }

    public function getNewLeadsCount()
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM leads WHERE status = 'new' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting new leads count: " . $e->getMessage());
            return 0;
        }
    }

    public function getCountByStatus($status)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM leads WHERE status = :status";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting count by status: " . $e->getMessage());
            return 0;
        }
    }

    public function findByEmail($email)
    {
        try {
            $sql = "SELECT * FROM leads WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding lead by email: " . $e->getMessage());
            return false;
        }
    }
}