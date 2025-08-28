<?php

class AppointmentController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all appointments with filters and pagination
    public function getAppointments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause based on filters
            $whereConditions = [];
            $bindParams = [];
            
            if (!empty($params['status'])) {
                $whereConditions[] = "a.status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            if (!empty($params['type'])) {
                $whereConditions[] = "a.type = :type";
                $bindParams[':type'] = $params['type'];
            }
            
            if (!empty($params['assigned_to'])) {
                $whereConditions[] = "a.assigned_to = :assigned_to";
                $bindParams[':assigned_to'] = $params['assigned_to'];
            }
            
            if (!empty($params['client_id'])) {
                $whereConditions[] = "a.client_id = :client_id";
                $bindParams[':client_id'] = $params['client_id'];
            }
            
            if (!empty($params['date_from'])) {
                $whereConditions[] = "DATE(a.appointment_date) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $whereConditions[] = "DATE(a.appointment_date) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            if (!empty($params['search'])) {
                $whereConditions[] = "(a.title LIKE :search OR a.description LIKE :search OR c.name LIKE :search)";
                $bindParams[':search'] = '%' . $params['search'] . '%';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM appointments a 
                         LEFT JOIN clients c ON a.client_id = c.id 
                         $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($bindParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get appointments with pagination
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    $whereClause 
                    ORDER BY a.appointment_date ASC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $appointments,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching appointments: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single appointment by ID
    public function getAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    WHERE a.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $appointmentId);
            $stmt->execute();
            
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $appointment
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching appointment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Create new appointment
    public function createAppointment($request, $response, $args) {
        try {
            $data = json_decode($request->getBody(), true);
            
            // Validate required fields
            $required = ['title', 'appointment_date', 'client_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Validate date format
            if (!$this->isValidDateTime($data['appointment_date'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid appointment date format. Use YYYY-MM-DD HH:MM:SS'
                ], 400);
            }
            
            // Check if client exists
            $clientCheckSql = "SELECT id FROM clients WHERE id = :client_id";
            $clientCheckStmt = $this->db->prepare($clientCheckSql);
            $clientCheckStmt->bindParam(':client_id', $data['client_id']);
            $clientCheckStmt->execute();
            
            if (!$clientCheckStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Client not found'
                ], 400);
            }
            
            // Check for scheduling conflicts
            if (!empty($data['assigned_to'])) {
                $conflictSql = "SELECT id FROM appointments 
                                WHERE assigned_to = :assigned_to 
                                AND status NOT IN ('cancelled', 'completed') 
                                AND (
                                    (appointment_date <= :start_time AND DATE_ADD(appointment_date, INTERVAL duration MINUTE) > :start_time) OR
                                    (appointment_date < :end_time AND DATE_ADD(appointment_date, INTERVAL duration MINUTE) >= :end_time) OR
                                    (appointment_date >= :start_time AND appointment_date < :end_time)
                                )";
                
                $duration = isset($data['duration']) ? (int)$data['duration'] : 60;
                $endTime = date('Y-m-d H:i:s', strtotime($data['appointment_date'] . ' +' . $duration . ' minutes'));
                
                $conflictStmt = $this->db->prepare($conflictSql);
                $conflictStmt->bindParam(':assigned_to', $data['assigned_to']);
                $conflictStmt->bindParam(':start_time', $data['appointment_date']);
                $conflictStmt->bindParam(':end_time', $endTime);
                $conflictStmt->execute();
                
                if ($conflictStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Scheduling conflict detected for the assigned user'
                    ], 400);
                }
            }
            
            $sql = "INSERT INTO appointments (title, description, appointment_date, duration, type, status, client_id, assigned_to, location, notes, created_at, updated_at) 
                    VALUES (:title, :description, :appointment_date, :duration, :type, :status, :client_id, :assigned_to, :location, :notes, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description'] ?? null);
            $stmt->bindParam(':appointment_date', $data['appointment_date']);
            $stmt->bindParam(':duration', $data['duration'] ?? 60);
            $stmt->bindParam(':type', $data['type'] ?? 'meeting');
            $stmt->bindParam(':status', $data['status'] ?? 'scheduled');
            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':assigned_to', $data['assigned_to'] ?? null);
            $stmt->bindParam(':location', $data['location'] ?? null);
            $stmt->bindParam(':notes', $data['notes'] ?? null);
            
            $stmt->execute();
            $appointmentId = $this->db->lastInsertId();
            
            // Get the created appointment
            $getSql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                       FROM appointments a 
                       LEFT JOIN clients c ON a.client_id = c.id 
                       LEFT JOIN users u ON a.assigned_to = u.id 
                       WHERE a.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $appointmentId);
            $getStmt->execute();
            $appointment = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => $appointment
            ], 201);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error creating appointment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update appointment
    public function updateAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            $data = json_decode($request->getBody(), true);
            
            // Check if appointment exists
            $checkSql = "SELECT * FROM appointments WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $appointmentId);
            $checkStmt->execute();
            $existingAppointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingAppointment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            // Validate date format if provided
            if (!empty($data['appointment_date']) && !$this->isValidDateTime($data['appointment_date'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid appointment date format. Use YYYY-MM-DD HH:MM:SS'
                ], 400);
            }
            
            // Check if client exists if provided
            if (!empty($data['client_id'])) {
                $clientCheckSql = "SELECT id FROM clients WHERE id = :client_id";
                $clientCheckStmt = $this->db->prepare($clientCheckSql);
                $clientCheckStmt->bindParam(':client_id', $data['client_id']);
                $clientCheckStmt->execute();
                
                if (!$clientCheckStmt->fetch()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Client not found'
                    ], 400);
                }
            }
            
            // Check for scheduling conflicts if date or assigned user is being changed
            if ((!empty($data['appointment_date']) && $data['appointment_date'] !== $existingAppointment['appointment_date']) ||
                (!empty($data['assigned_to']) && $data['assigned_to'] !== $existingAppointment['assigned_to']) ||
                (!empty($data['duration']) && $data['duration'] !== $existingAppointment['duration'])) {
                
                $assignedTo = $data['assigned_to'] ?? $existingAppointment['assigned_to'];
                $appointmentDate = $data['appointment_date'] ?? $existingAppointment['appointment_date'];
                $duration = $data['duration'] ?? $existingAppointment['duration'];
                
                if ($assignedTo) {
                    $conflictSql = "SELECT id FROM appointments 
                                    WHERE assigned_to = :assigned_to 
                                    AND id != :appointment_id 
                                    AND status NOT IN ('cancelled', 'completed') 
                                    AND (
                                        (appointment_date <= :start_time AND DATE_ADD(appointment_date, INTERVAL duration MINUTE) > :start_time) OR
                                        (appointment_date < :end_time AND DATE_ADD(appointment_date, INTERVAL duration MINUTE) >= :end_time) OR
                                        (appointment_date >= :start_time AND appointment_date < :end_time)
                                    )";
                    
                    $endTime = date('Y-m-d H:i:s', strtotime($appointmentDate . ' +' . $duration . ' minutes'));
                    
                    $conflictStmt = $this->db->prepare($conflictSql);
                    $conflictStmt->bindParam(':assigned_to', $assignedTo);
                    $conflictStmt->bindParam(':appointment_id', $appointmentId);
                    $conflictStmt->bindParam(':start_time', $appointmentDate);
                    $conflictStmt->bindParam(':end_time', $endTime);
                    $conflictStmt->execute();
                    
                    if ($conflictStmt->fetch()) {
                        return $this->jsonResponse($response, [
                            'success' => false,
                            'message' => 'Scheduling conflict detected for the assigned user'
                        ], 400);
                    }
                }
            }
            
            // Build update query dynamically
            $updateFields = [];
            $bindParams = [':id' => $appointmentId];
            
            $allowedFields = ['title', 'description', 'appointment_date', 'duration', 'type', 'status', 'client_id', 'assigned_to', 'location', 'notes'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateFields[] = "$field = :$field";
                    $bindParams[":$field"] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ], 400);
            }
            
            $updateFields[] = "updated_at = NOW()";
            
            $sql = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated appointment
            $getSql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                       FROM appointments a 
                       LEFT JOIN clients c ON a.client_id = c.id 
                       LEFT JOIN users u ON a.assigned_to = u.id 
                       WHERE a.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $appointmentId);
            $getStmt->execute();
            $appointment = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data' => $appointment
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error updating appointment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete appointment
    public function deleteAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            
            // Check if appointment exists
            $checkSql = "SELECT id FROM appointments WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $appointmentId);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            // Delete appointment
            $sql = "DELETE FROM appointments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $appointmentId);
            $stmt->execute();
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error deleting appointment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get calendar view
    public function getCalendar($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $month = isset($params['month']) ? (int)$params['month'] : date('n');
            $year = isset($params['year']) ? (int)$params['year'] : date('Y');
            
            // Validate month and year
            if ($month < 1 || $month > 12 || $year < 1900 || $year > 2100) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid month or year'
                ], 400);
            }
            
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $whereConditions = ["DATE(a.appointment_date) >= :start_date", "DATE(a.appointment_date) <= :end_date"];
            $bindParams = [':start_date' => $startDate, ':end_date' => $endDate];
            
            if (!empty($params['assigned_to'])) {
                $whereConditions[] = "a.assigned_to = :assigned_to";
                $bindParams[':assigned_to'] = $params['assigned_to'];
            }
            
            if (!empty($params['status'])) {
                $whereConditions[] = "a.status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT a.*, c.name as client_name, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    $whereClause 
                    ORDER BY a.appointment_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group appointments by date
            $calendar = [];
            foreach ($appointments as $appointment) {
                $date = date('Y-m-d', strtotime($appointment['appointment_date']));
                if (!isset($calendar[$date])) {
                    $calendar[$date] = [];
                }
                $calendar[$date][] = $appointment;
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'appointments' => $calendar
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching calendar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get today's appointments
    public function getTodayAppointments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $today = date('Y-m-d');
            
            $whereConditions = ["DATE(a.appointment_date) = :today"];
            $bindParams = [':today' => $today];
            
            if (!empty($params['assigned_to'])) {
                $whereConditions[] = "a.assigned_to = :assigned_to";
                $bindParams[':assigned_to'] = $params['assigned_to'];
            }
            
            if (!empty($params['status'])) {
                $whereConditions[] = "a.status = :status";
                $bindParams[':status'] = $params['status'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    $whereClause 
                    ORDER BY a.appointment_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $appointments
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching today\'s appointments: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get upcoming appointments
    public function getUpcomingAppointments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            
            $whereConditions = ["a.appointment_date >= NOW()", "a.status NOT IN ('cancelled', 'completed')"];
            $bindParams = [];
            
            if (!empty($params['assigned_to'])) {
                $whereConditions[] = "a.assigned_to = :assigned_to";
                $bindParams[':assigned_to'] = $params['assigned_to'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    $whereClause 
                    ORDER BY a.appointment_date ASC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            foreach ($bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $appointments
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching upcoming appointments: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Mark appointment as completed
    public function completeAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            $data = json_decode($request->getBody(), true);
            
            // Check if appointment exists
            $checkSql = "SELECT id, status FROM appointments WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $appointmentId);
            $checkStmt->execute();
            $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            if ($appointment['status'] === 'completed') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Appointment is already completed'
                ], 400);
            }
            
            // Update appointment status
            $updateFields = ['status = "completed"', 'updated_at = NOW()'];
            $bindParams = [':id' => $appointmentId];
            
            if (!empty($data['notes'])) {
                $updateFields[] = 'notes = :notes';
                $bindParams[':notes'] = $data['notes'];
            }
            
            $sql = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            // Get updated appointment
            $getSql = "SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone, u.name as assigned_name 
                       FROM appointments a 
                       LEFT JOIN clients c ON a.client_id = c.id 
                       LEFT JOIN users u ON a.assigned_to = u.id 
                       WHERE a.id = :id";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->bindParam(':id', $appointmentId);
            $getStmt->execute();
            $appointment = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Appointment marked as completed',
                'data' => $appointment
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error completing appointment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get appointment statistics
    public function getAppointmentStats($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND appointment_date >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND appointment_date <= :date_to";
                $bindParams[':date_to'] = $params['date_to'] . ' 23:59:59';
            }
            
            // Get total appointments
            $totalSql = "SELECT COUNT(*) as total FROM appointments WHERE 1=1 $dateFilter";
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute($bindParams);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get appointments by status
            $statusSql = "SELECT status, COUNT(*) as count FROM appointments WHERE 1=1 $dateFilter GROUP BY status";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute($bindParams);
            $statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get appointments by type
            $typeSql = "SELECT type, COUNT(*) as count FROM appointments WHERE 1=1 $dateFilter GROUP BY type";
            $typeStmt = $this->db->prepare($typeSql);
            $typeStmt->execute($bindParams);
            $typeStats = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get today's appointments count
            $todaySql = "SELECT COUNT(*) as today FROM appointments WHERE DATE(appointment_date) = CURDATE() $dateFilter";
            $todayStmt = $this->db->prepare($todaySql);
            $todayStmt->execute($bindParams);
            $today = $todayStmt->fetch(PDO::FETCH_ASSOC)['today'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total' => (int)$total,
                    'today' => (int)$today,
                    'by_status' => $statusStats,
                    'by_type' => $typeStats
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching appointment statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function isValidDateTime($dateTime) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $d && $d->format('Y-m-d H:i:s') === $dateTime;
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>