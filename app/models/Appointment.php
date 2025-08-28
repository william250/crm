<?php

namespace App\Models;

use PDO;
use PDOException;

class Appointment {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all appointments with optional filters
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['date_from'])) {
                $sql .= " AND a.date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND a.date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND a.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['client_id'])) {
                $sql .= " AND a.client_id = ?";
                $params[] = $filters['client_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND a.type = ?";
                $params[] = $filters['type'];
            }
            
            // Add search functionality
            if (!empty($filters['search'])) {
                $sql .= " AND (c.name LIKE ? OR a.notes LIKE ? OR a.location LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Add ordering
            $sql .= " ORDER BY a.date ASC, a.start_time ASC";
            
            // Add pagination
            if (!empty($filters['limit'])) {
                $offset = (!empty($filters['page']) ? ($filters['page'] - 1) * $filters['limit'] : 0);
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = (int)$filters['limit'];
                $params[] = (int)$offset;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of appointments with filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Apply same filters as getAll method
            if (!empty($filters['date_from'])) {
                $sql .= " AND a.date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND a.date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND a.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['client_id'])) {
                $sql .= " AND a.client_id = ?";
                $params[] = $filters['client_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND a.type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (c.name LIKE ? OR a.notes LIKE ? OR a.location LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting appointments count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get appointment by ID
     */
    public function findById($id) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointment by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new appointment
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO appointments (client_id, type, date, start_time, end_time, status, location, notes, reminder, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['client_id'],
                $data['type'],
                $data['date'],
                $data['start_time'],
                $data['end_time'],
                $data['status'] ?? 'scheduled',
                $data['location'] ?? null,
                $data['notes'] ?? null,
                $data['reminder'] ?? false
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update appointment
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE appointments SET 
                    client_id = ?, type = ?, date = ?, start_time = ?, end_time = ?, 
                    status = ?, location = ?, notes = ?, reminder = ?, updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['client_id'],
                $data['type'],
                $data['date'],
                $data['start_time'],
                $data['end_time'],
                $data['status'],
                $data['location'],
                $data['notes'],
                $data['reminder'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating appointment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete appointment
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM appointments WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting appointment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get appointments for a specific date
     */
    public function getByDate($date) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.date = ? 
                    ORDER BY a.start_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointments by date: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get appointments for date range
     */
    public function getByDateRange($startDate, $endDate) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.date BETWEEN ? AND ? 
                    ORDER BY a.date ASC, a.start_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointments by date range: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get upcoming appointments
     */
    public function getUpcoming($limit = 10) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.date >= CURDATE() AND a.status IN ('scheduled', 'confirmed') 
                    ORDER BY a.date ASC, a.start_time ASC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting upcoming appointments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get appointments by status
     */
    public function getByStatus($status) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.status = ? 
                    ORDER BY a.date ASC, a.start_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointments by status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get appointments by client
     */
    public function getByClient($clientId) {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.client_id = ? 
                    ORDER BY a.date DESC, a.start_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointments by client: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check for appointment conflicts
     */
    public function checkConflict($date, $startTime, $endTime, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM appointments 
                    WHERE date = ? 
                    AND status NOT IN ('cancelled', 'no_show') 
                    AND (
                        (start_time <= ? AND end_time > ?) OR 
                        (start_time < ? AND end_time >= ?) OR 
                        (start_time >= ? AND end_time <= ?)
                    )";
            
            $params = [$date, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking appointment conflict: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update appointment status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Error updating appointment status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get appointment statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $whereClause .= " AND date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND date <= ?";
                $params[] = $dateTo;
            }
            
            $sql = "SELECT 
                        COUNT(*) as total_appointments,
                        COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled,
                        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show,
                        COUNT(CASE WHEN date = CURDATE() THEN 1 END) as today,
                        COUNT(CASE WHEN date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as this_week
                    FROM appointments 
                    {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointment statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get appointments that need reminders
     */
    public function getReminders($reminderTime = '1 DAY') {
        try {
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    WHERE a.reminder = 1 
                    AND a.status IN ('scheduled', 'confirmed') 
                    AND CONCAT(a.date, ' ', a.start_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL {$reminderTime}) 
                    ORDER BY a.date ASC, a.start_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting appointment reminders: " . $e->getMessage());
            return [];
        }
    }
}