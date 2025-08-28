<?php

require_once __DIR__ . '/../config/database.php';

class Interaction {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all interactions with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           c.email as client_email,
                           l.title as lead_title,
                           l.email as lead_email,
                           u.name as user_name
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['client_id'])) {
                $sql .= " AND i.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['lead_id'])) {
                $sql .= " AND i.lead_id = :lead_id";
                $params[':lead_id'] = $filters['lead_id'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND i.user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND i.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['direction'])) {
                $sql .= " AND i.direction = :direction";
                $params[':direction'] = $filters['direction'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND i.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND i.interaction_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND i.interaction_date <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (i.subject LIKE :search OR i.notes LIKE :search OR c.name LIKE :search OR l.title LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Add sorting
            $orderBy = $filters['order_by'] ?? 'interaction_date';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY i.{$orderBy} {$orderDir}";
            
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
            error_log("Error getting interactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of interactions with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    WHERE 1=1";
            $params = [];
            
            // Apply same filters as getAll
            if (!empty($filters['client_id'])) {
                $sql .= " AND i.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (!empty($filters['lead_id'])) {
                $sql .= " AND i.lead_id = :lead_id";
                $params[':lead_id'] = $filters['lead_id'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND i.user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND i.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (!empty($filters['direction'])) {
                $sql .= " AND i.direction = :direction";
                $params[':direction'] = $filters['direction'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND i.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND i.interaction_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND i.interaction_date <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (i.subject LIKE :search OR i.notes LIKE :search OR c.name LIKE :search OR l.title LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting interaction count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get interaction by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           c.email as client_email,
                           l.title as lead_title,
                           l.email as lead_email,
                           u.name as user_name,
                           u.email as user_email
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interaction by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new interaction
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO interactions (client_id, lead_id, user_id, type, direction, subject, notes, interaction_date, duration, status, follow_up_date, created_at, updated_at)
                    VALUES (:client_id, :lead_id, :user_id, :type, :direction, :subject, :notes, :interaction_date, :duration, :status, :follow_up_date, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':lead_id', $data['lead_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':direction', $data['direction']);
            $stmt->bindParam(':subject', $data['subject']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':interaction_date', $data['interaction_date']);
            $stmt->bindParam(':duration', $data['duration']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':follow_up_date', $data['follow_up_date']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating interaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update interaction
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE interactions SET 
                           client_id = :client_id,
                           lead_id = :lead_id,
                           user_id = :user_id,
                           type = :type,
                           direction = :direction,
                           subject = :subject,
                           notes = :notes,
                           interaction_date = :interaction_date,
                           duration = :duration,
                           status = :status,
                           follow_up_date = :follow_up_date,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':lead_id', $data['lead_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':direction', $data['direction']);
            $stmt->bindParam(':subject', $data['subject']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->bindParam(':interaction_date', $data['interaction_date']);
            $stmt->bindParam(':duration', $data['duration']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':follow_up_date', $data['follow_up_date']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating interaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete interaction
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM interactions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting interaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get interactions by client
     */
    public function getByClient($clientId) {
        try {
            $sql = "SELECT i.*, u.name as user_name
                    FROM interactions i
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.client_id = :client_id 
                    ORDER BY i.interaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interactions by client: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get interactions by lead
     */
    public function getByLead($leadId) {
        try {
            $sql = "SELECT i.*, u.name as user_name
                    FROM interactions i
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.lead_id = :lead_id 
                    ORDER BY i.interaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':lead_id', $leadId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interactions by lead: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get interactions by user
     */
    public function getByUser($userId) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           l.title as lead_title
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    WHERE i.user_id = :user_id 
                    ORDER BY i.interaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interactions by user: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get interactions by type
     */
    public function getByType($type) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           l.title as lead_title,
                           u.name as user_name
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.type = :type 
                    ORDER BY i.interaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interactions by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent interactions
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           l.title as lead_title,
                           u.name as user_name
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    ORDER BY i.interaction_date DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent interactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get interactions requiring follow-up
     */
    public function getFollowUpRequired($userId = null) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           l.title as lead_title,
                           u.name as user_name
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.follow_up_date IS NOT NULL 
                    AND i.follow_up_date <= CURDATE()
                    AND i.status != 'completed'";
            
            $params = [];
            
            if ($userId) {
                $sql .= " AND i.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY i.follow_up_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting follow-up interactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get interaction statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null, $userId = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $whereClause .= " AND i.interaction_date >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND i.interaction_date <= :date_to";
                $params[':date_to'] = $dateTo . ' 23:59:59';
            }
            
            if ($userId) {
                $whereClause .= " AND i.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql = "SELECT 
                           COUNT(*) as total_interactions,
                           COUNT(CASE WHEN i.type = 'call' THEN 1 END) as call_interactions,
                           COUNT(CASE WHEN i.type = 'email' THEN 1 END) as email_interactions,
                           COUNT(CASE WHEN i.type = 'meeting' THEN 1 END) as meeting_interactions,
                           COUNT(CASE WHEN i.type = 'sms' THEN 1 END) as sms_interactions,
                           COUNT(CASE WHEN i.type = 'note' THEN 1 END) as note_interactions,
                           COUNT(CASE WHEN i.type = 'other' THEN 1 END) as other_interactions,
                           COUNT(CASE WHEN i.direction = 'inbound' THEN 1 END) as inbound_interactions,
                           COUNT(CASE WHEN i.direction = 'outbound' THEN 1 END) as outbound_interactions,
                           COUNT(CASE WHEN i.status = 'completed' THEN 1 END) as completed_interactions,
                           COUNT(CASE WHEN i.status = 'pending' THEN 1 END) as pending_interactions,
                           COUNT(CASE WHEN i.status = 'scheduled' THEN 1 END) as scheduled_interactions,
                           COUNT(CASE WHEN i.follow_up_date IS NOT NULL AND i.follow_up_date <= CURDATE() AND i.status != 'completed' THEN 1 END) as follow_up_required,
                           AVG(i.duration) as average_duration,
                           SUM(i.duration) as total_duration,
                           COUNT(CASE WHEN DATE(i.interaction_date) = CURDATE() THEN 1 END) as interactions_today,
                           COUNT(CASE WHEN DATE(i.interaction_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as interactions_this_week,
                           COUNT(CASE WHEN DATE(i.interaction_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as interactions_this_month
                    FROM interactions i {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interaction statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update interaction status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE interactions SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating interaction status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get interactions by date range
     */
    public function getByDateRange($dateFrom, $dateTo, $userId = null) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           l.title as lead_title,
                           u.name as user_name
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.interaction_date >= :date_from 
                    AND i.interaction_date <= :date_to";
            
            $params = [
                ':date_from' => $dateFrom,
                ':date_to' => $dateTo . ' 23:59:59'
            ];
            
            if ($userId) {
                $sql .= " AND i.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY i.interaction_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting interactions by date range: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get upcoming scheduled interactions
     */
    public function getUpcoming($userId = null, $days = 7) {
        try {
            $sql = "SELECT i.*, 
                           c.name as client_name,
                           l.title as lead_title,
                           u.name as user_name
                    FROM interactions i
                    LEFT JOIN clients c ON i.client_id = c.id
                    LEFT JOIN leads l ON i.lead_id = l.id
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.status = 'scheduled'
                    AND i.interaction_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)";
            
            $params = [':days' => $days];
            
            if ($userId) {
                $sql .= " AND i.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY i.interaction_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting upcoming interactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark interaction as completed
     */
    public function markCompleted($id, $notes = null) {
        try {
            $sql = "UPDATE interactions SET 
                           status = 'completed',
                           notes = CASE WHEN :notes IS NOT NULL THEN CONCAT(COALESCE(notes, ''), '\n\n', :notes) ELSE notes END,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':notes', $notes);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking interaction as completed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Schedule follow-up interaction
     */
    public function scheduleFollowUp($id, $followUpDate, $notes = null) {
        try {
            $sql = "UPDATE interactions SET 
                           follow_up_date = :follow_up_date,
                           notes = CASE WHEN :notes IS NOT NULL THEN CONCAT(COALESCE(notes, ''), '\n\n', 'Follow-up scheduled: ', :notes) ELSE notes END,
                           updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':follow_up_date', $followUpDate);
            $stmt->bindParam(':notes', $notes);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error scheduling follow-up: " . $e->getMessage());
            return false;
        }
    }
}