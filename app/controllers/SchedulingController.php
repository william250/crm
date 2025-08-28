<?php

namespace App\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SchedulingController {
    private $appointmentModel;
    private $userModel;
    
    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        $db = \Database::getInstance()->getConnection();
        $this->appointmentModel = new Appointment($db);
        $this->userModel = new User($db);
    }
    
    /**
     * Get appointments with filtering and pagination
     */
    public function getAppointments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            
            // Build filters
            $filters = [];
            
            // View-based filtering
            if (isset($params['view']) && isset($params['date'])) {
                $date = new DateTime($params['date']);
                
                switch ($params['view']) {
                    case 'month':
                        $filters['date_from'] = $date->format('Y-m-01');
                        $filters['date_to'] = $date->format('Y-m-t');
                        break;
                    case 'week':
                        $weekStart = clone $date;
                        $weekStart->modify('monday this week');
                        $weekEnd = clone $weekStart;
                        $weekEnd->modify('+6 days');
                        $filters['date_from'] = $weekStart->format('Y-m-d');
                        $filters['date_to'] = $weekEnd->format('Y-m-d');
                        break;
                    case 'day':
                        $filters['date_from'] = $date->format('Y-m-d');
                        $filters['date_to'] = $date->format('Y-m-d');
                        break;
                    case 'list':
                        // For list view, get upcoming appointments
                        $filters['date_from'] = date('Y-m-d');
                        break;
                }
            }
            
            // Additional filters
            if (!empty($params['status'])) {
                $filters['status'] = $params['status'];
            }
            
            if (!empty($params['client_id'])) {
                $filters['client_id'] = $params['client_id'];
            }
            
            if (!empty($params['type'])) {
                $filters['type'] = $params['type'];
            }
            
            if (!empty($params['search'])) {
                $filters['search'] = $params['search'];
            }
            
            // Pagination
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            
            if (isset($params['view']) && $params['view'] === 'list') {
                $filters['page'] = $page;
                $filters['limit'] = $limit;
            }
            
            // Get appointments
            $appointments = $this->appointmentModel->getAll($filters);
            
            // Get total count for pagination
            $totalCount = $this->appointmentModel->getTotalCount($filters);
            $totalPages = ceil($totalCount / $limit);
            
            $responseData = [
                'appointments' => $appointments
            ];
            
            // Add pagination info for list view
            if (isset($params['view']) && $params['view'] === 'list') {
                $responseData['pagination'] = [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalCount' => $totalCount,
                    'limit' => $limit
                ];
            }
            
            return $this->jsonResponse($response, $responseData);
        } catch (Exception $e) {
            error_log("Error getting appointments: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get appointments'], 500);
        }
    }
    
    /**
     * Get single appointment by ID
     */
    public function getAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            $appointment = $this->appointmentModel->findById($appointmentId);
            
            if (!$appointment) {
                return $this->jsonResponse($response, ['error' => 'Appointment not found'], 404);
            }
            
            return $this->jsonResponse($response, $appointment);
        } catch (Exception $e) {
            error_log("Error getting appointment: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get appointment'], 500);
        }
    }
    
    /**
     * Create new appointment
     */
    public function createAppointment($request, $response, $args) {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $requiredFields = ['client_id', 'type', 'date', 'start_time', 'end_time'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, ['error' => "Field '{$field}' is required"], 400);
                }
            }
            
            // Validate date format
            if (!$this->isValidDate($data['date'])) {
                return $this->jsonResponse($response, ['error' => 'Invalid date format'], 400);
            }
            
            // Validate time format
            if (!$this->isValidTime($data['start_time']) || !$this->isValidTime($data['end_time'])) {
                return $this->jsonResponse($response, ['error' => 'Invalid time format'], 400);
            }
            
            // Validate that end time is after start time
            if ($data['start_time'] >= $data['end_time']) {
                return $this->jsonResponse($response, ['error' => 'End time must be after start time'], 400);
            }
            
            // Check for appointment conflicts
            if ($this->appointmentModel->checkConflict($data['date'], $data['start_time'], $data['end_time'])) {
                return $this->jsonResponse($response, ['error' => 'Appointment time conflicts with existing appointment'], 400);
            }
            
            // Validate appointment type
            $validTypes = ['consultation', 'meeting', 'follow_up', 'presentation', 'other'];
            if (!in_array($data['type'], $validTypes)) {
                return $this->jsonResponse($response, ['error' => 'Invalid appointment type'], 400);
            }
            
            // Validate status if provided
            if (!empty($data['status'])) {
                $validStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
                if (!in_array($data['status'], $validStatuses)) {
                    return $this->jsonResponse($response, ['error' => 'Invalid status'], 400);
                }
            }
            
            // Create appointment
            $appointmentId = $this->appointmentModel->create($data);
            
            if (!$appointmentId) {
                return $this->jsonResponse($response, ['error' => 'Failed to create appointment'], 500);
            }
            
            // Get the created appointment
            $appointment = $this->appointmentModel->findById($appointmentId);
            
            return $this->jsonResponse($response, $appointment, 201);
        } catch (Exception $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to create appointment'], 500);
        }
    }
    
    /**
     * Update appointment
     */
    public function updateAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            $data = $request->getParsedBody();
            
            // Check if appointment exists
            $existingAppointment = $this->appointmentModel->findById($appointmentId);
            if (!$existingAppointment) {
                return $this->jsonResponse($response, ['error' => 'Appointment not found'], 404);
            }
            
            // Validate required fields
            $requiredFields = ['client_id', 'type', 'date', 'start_time', 'end_time'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, ['error' => "Field '{$field}' is required"], 400);
                }
            }
            
            // Validate date format
            if (!$this->isValidDate($data['date'])) {
                return $this->jsonResponse($response, ['error' => 'Invalid date format'], 400);
            }
            
            // Validate time format
            if (!$this->isValidTime($data['start_time']) || !$this->isValidTime($data['end_time'])) {
                return $this->jsonResponse($response, ['error' => 'Invalid time format'], 400);
            }
            
            // Validate that end time is after start time
            if ($data['start_time'] >= $data['end_time']) {
                return $this->jsonResponse($response, ['error' => 'End time must be after start time'], 400);
            }
            
            // Check for appointment conflicts (excluding current appointment)
            if ($this->appointmentModel->checkConflict($data['date'], $data['start_time'], $data['end_time'], $appointmentId)) {
                return $this->jsonResponse($response, ['error' => 'Appointment time conflicts with existing appointment'], 400);
            }
            
            // Validate appointment type
            $validTypes = ['consultation', 'meeting', 'follow_up', 'presentation', 'other'];
            if (!in_array($data['type'], $validTypes)) {
                return $this->jsonResponse($response, ['error' => 'Invalid appointment type'], 400);
            }
            
            // Validate status
            $validStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
            if (!in_array($data['status'], $validStatuses)) {
                return $this->jsonResponse($response, ['error' => 'Invalid status'], 400);
            }
            
            // Update appointment
            $success = $this->appointmentModel->update($appointmentId, $data);
            
            if (!$success) {
                return $this->jsonResponse($response, ['error' => 'Failed to update appointment'], 500);
            }
            
            // Get the updated appointment
            $appointment = $this->appointmentModel->findById($appointmentId);
            
            return $this->jsonResponse($response, $appointment);
        } catch (Exception $e) {
            error_log("Error updating appointment: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to update appointment'], 500);
        }
    }
    
    /**
     * Delete appointment
     */
    public function deleteAppointment($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            
            // Check if appointment exists
            $appointment = $this->appointmentModel->findById($appointmentId);
            if (!$appointment) {
                return $this->jsonResponse($response, ['error' => 'Appointment not found'], 404);
            }
            
            // Delete appointment
            $success = $this->appointmentModel->delete($appointmentId);
            
            if (!$success) {
                return $this->jsonResponse($response, ['error' => 'Failed to delete appointment'], 500);
            }
            
            return $this->jsonResponse($response, ['message' => 'Appointment deleted successfully']);
        } catch (Exception $e) {
            error_log("Error deleting appointment: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to delete appointment'], 500);
        }
    }
    
    /**
     * Update appointment status
     */
    public function updateAppointmentStatus($request, $response, $args) {
        try {
            $appointmentId = $args['id'];
            $data = $request->getParsedBody();
            
            if (empty($data['status'])) {
                return $this->jsonResponse($response, ['error' => 'Status is required'], 400);
            }
            
            // Validate status
            $validStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
            if (!in_array($data['status'], $validStatuses)) {
                return $this->jsonResponse($response, ['error' => 'Invalid status'], 400);
            }
            
            // Check if appointment exists
            $appointment = $this->appointmentModel->findById($appointmentId);
            if (!$appointment) {
                return $this->jsonResponse($response, ['error' => 'Appointment not found'], 404);
            }
            
            // Update status
            $success = $this->appointmentModel->updateStatus($appointmentId, $data['status']);
            
            if (!$success) {
                return $this->jsonResponse($response, ['error' => 'Failed to update appointment status'], 500);
            }
            
            // Get updated appointment
            $updatedAppointment = $this->appointmentModel->findById($appointmentId);
            
            return $this->jsonResponse($response, $updatedAppointment);
        } catch (Exception $e) {
            error_log("Error updating appointment status: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to update appointment status'], 500);
        }
    }
    
    /**
     * Get upcoming appointments
     */
    public function getUpcomingAppointments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            
            $appointments = $this->appointmentModel->getUpcoming($limit);
            
            return $this->jsonResponse($response, $appointments);
        } catch (Exception $e) {
            error_log("Error getting upcoming appointments: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get upcoming appointments'], 500);
        }
    }
    
    /**
     * Get appointment statistics
     */
    public function getAppointmentStatistics($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFrom = $params['date_from'] ?? null;
            $dateTo = $params['date_to'] ?? null;
            
            $statistics = $this->appointmentModel->getStatistics($dateFrom, $dateTo);
            
            return $this->jsonResponse($response, $statistics);
        } catch (Exception $e) {
            error_log("Error getting appointment statistics: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get appointment statistics'], 500);
        }
    }
    
    /**
     * Get appointments by client
     */
    public function getAppointmentsByClient($request, $response, $args) {
        try {
            $clientId = $args['client_id'];
            $appointments = $this->appointmentModel->getByClient($clientId);
            
            return $this->jsonResponse($response, $appointments);
        } catch (Exception $e) {
            error_log("Error getting appointments by client: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get appointments by client'], 500);
        }
    }
    
    /**
     * Get appointment reminders
     */
    public function getAppointmentReminders($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $reminderTime = $params['reminder_time'] ?? '1 DAY';
            
            $reminders = $this->appointmentModel->getReminders($reminderTime);
            
            return $this->jsonResponse($response, $reminders);
        } catch (Exception $e) {
            error_log("Error getting appointment reminders: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get appointment reminders'], 500);
        }
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate time format (HH:MM)
     */
    private function isValidTime($time) {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }
    
    /**
     * Helper method to return JSON response
     */
    private function jsonResponse($response, $data, $status = 200) {
        $payload = json_encode([
            'success' => $status < 400,
            'data' => $data
        ]);
        
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}