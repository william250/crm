<?php

class DashboardController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get dashboard statistics
    public function getDashboardStats($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            // Apply date filters if provided
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND DATE(created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND DATE(created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            // Get total leads
            $leadsSql = "SELECT COUNT(*) as total FROM leads WHERE 1=1 $dateFilter";
            $leadsStmt = $this->db->prepare($leadsSql);
            $leadsStmt->execute($bindParams);
            $totalLeads = $leadsStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get converted leads (clients)
            $convertedSql = "SELECT COUNT(*) as converted FROM clients WHERE 1=1 $dateFilter";
            $convertedStmt = $this->db->prepare($convertedSql);
            $convertedStmt->execute($bindParams);
            $convertedLeads = $convertedStmt->fetch(PDO::FETCH_ASSOC)['converted'];
            
            // Get today's appointments
            $todayAppointmentsSql = "SELECT COUNT(*) as today_appointments FROM appointments WHERE DATE(start_time) = CURDATE()";
            $todayAppointmentsStmt = $this->db->prepare($todayAppointmentsSql);
            $todayAppointmentsStmt->execute();
            $todayAppointments = $todayAppointmentsStmt->fetch(PDO::FETCH_ASSOC)['today_appointments'];
            
            // Get pending tasks (interactions with type 'task' and no completion date)
            $pendingTasksSql = "SELECT COUNT(*) as pending_tasks FROM interactions WHERE type = 'task' AND description NOT LIKE '%completed%'";
            $pendingTasksStmt = $this->db->prepare($pendingTasksSql);
            $pendingTasksStmt->execute();
            $pendingTasks = $pendingTasksStmt->fetch(PDO::FETCH_ASSOC)['pending_tasks'];
            
            // Get total revenue (sum of charges with status 'paid')
            $revenueSql = "SELECT COALESCE(SUM(amount), 0) as total_revenue FROM charges WHERE status = 'paid'";
            $revenueStmt = $this->db->prepare($revenueSql);
            $revenueStmt->execute();
            $totalRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
            
            // Get active contracts
            $activeContractsSql = "SELECT COUNT(*) as active_contracts FROM contracts WHERE status = 'active'";
            $activeContractsStmt = $this->db->prepare($activeContractsSql);
            $activeContractsStmt->execute();
            $activeContracts = $activeContractsStmt->fetch(PDO::FETCH_ASSOC)['active_contracts'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'total_leads' => (int)$totalLeads,
                    'converted_leads' => (int)$convertedLeads,
                    'today_appointments' => (int)$todayAppointments,
                    'pending_tasks' => (int)$pendingTasks,
                    'total_revenue' => (float)$totalRevenue,
                    'active_contracts' => (int)$activeContracts,
                    'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get lead pipeline data for charts
    public function getLeadPipeline($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND DATE(created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND DATE(created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            // Get leads by status
            $pipelineSql = "SELECT status, COUNT(*) as count FROM leads WHERE 1=1 $dateFilter GROUP BY status ORDER BY 
                            CASE status 
                                WHEN 'new' THEN 1
                                WHEN 'contacted' THEN 2
                                WHEN 'qualified' THEN 3
                                WHEN 'proposal' THEN 4
                                WHEN 'negotiation' THEN 5
                                WHEN 'won' THEN 6
                                WHEN 'lost' THEN 7
                                ELSE 8
                            END";
            $pipelineStmt = $this->db->prepare($pipelineSql);
            $pipelineStmt->execute($bindParams);
            $pipeline = $pipelineStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $pipeline
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching lead pipeline: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get lead sources data for charts
    public function getLeadSources($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND DATE(created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND DATE(created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            // Get leads by source
            $sourcesSql = "SELECT source, COUNT(*) as count FROM leads WHERE 1=1 $dateFilter GROUP BY source ORDER BY count DESC";
            $sourcesStmt = $this->db->prepare($sourcesSql);
            $sourcesStmt->execute($bindParams);
            $sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $sources
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching lead sources: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get recent leads for dashboard
    public function getRecentLeads($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 5;
            
            $sql = "SELECT l.*, u.name as assigned_name 
                    FROM leads l 
                    LEFT JOIN users u ON l.assigned_to = u.id 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $leads
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching recent leads: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get upcoming appointments for dashboard
    public function getUpcomingAppointments($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 5;
            
            $sql = "SELECT a.*, c.name as client_name, c.email as client_email, u.name as assigned_name 
                    FROM appointments a 
                    LEFT JOIN clients c ON a.client_id = c.id 
                    LEFT JOIN users u ON a.assigned_to = u.id 
                    WHERE a.appointment_date >= NOW() AND a.status NOT IN ('cancelled', 'completed') 
                    ORDER BY a.appointment_date ASC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
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
    
    // Get revenue trends for charts
    public function getRevenueTrends($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $period = isset($params['period']) ? $params['period'] : 'month'; // month, week, day
            $limit = isset($params['limit']) ? (int)$params['limit'] : 12;
            
            $dateFormat = '';
            $dateGroup = '';
            
            switch ($period) {
                case 'day':
                    $dateFormat = '%Y-%m-%d';
                    $dateGroup = 'DATE(p.created_at)';
                    break;
                case 'week':
                    $dateFormat = '%Y-%u';
                    $dateGroup = 'YEARWEEK(p.created_at)';
                    break;
                case 'month':
                default:
                    $dateFormat = '%Y-%m';
                    $dateGroup = 'DATE_FORMAT(p.created_at, "%Y-%m")';
                    break;
            }
            
            $sql = "SELECT 
                        DATE_FORMAT(p.created_at, '$dateFormat') as period,
                        SUM(c.amount) as revenue
                    FROM payments p
                    JOIN charges c ON p.charge_id = c.id
                    WHERE p.status = 'completed'
                    GROUP BY $dateGroup
                    ORDER BY period DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $trends = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $trends
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching revenue trends: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get activity feed for dashboard
    public function getActivityFeed($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            
            // Get recent interactions as activity feed
            $sql = "SELECT 
                        i.id,
                        i.type,
                        i.subject,
                        i.interaction_date as activity_date,
                        i.entity_type,
                        i.entity_id,
                        u.name as user_name,
                        CASE 
                            WHEN i.entity_type = 'lead' THEN l.name
                            WHEN i.entity_type = 'client' THEN c.name
                            ELSE NULL
                        END as entity_name,
                        'interaction' as activity_type
                    FROM interactions i
                    LEFT JOIN users u ON i.user_id = u.id
                    LEFT JOIN leads l ON i.entity_type = 'lead' AND i.entity_id = l.id
                    LEFT JOIN clients c ON i.entity_type = 'client' AND i.entity_id = c.id
                    
                    UNION ALL
                    
                    SELECT 
                        a.id,
                        a.type,
                        a.title as subject,
                        a.appointment_date as activity_date,
                        'client' as entity_type,
                        a.client_id as entity_id,
                        u.name as user_name,
                        c.name as entity_name,
                        'appointment' as activity_type
                    FROM appointments a
                    LEFT JOIN users u ON a.assigned_to = u.id
                    LEFT JOIN clients c ON a.client_id = c.id
                    WHERE a.appointment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    
                    ORDER BY activity_date DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $activities
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching activity feed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get performance metrics by user
    public function getUserPerformance($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $dateFilter = '';
            $bindParams = [];
            
            if (!empty($params['date_from'])) {
                $dateFilter .= " AND DATE(l.created_at) >= :date_from";
                $bindParams[':date_from'] = $params['date_from'];
            }
            
            if (!empty($params['date_to'])) {
                $dateFilter .= " AND DATE(l.created_at) <= :date_to";
                $bindParams[':date_to'] = $params['date_to'];
            }
            
            $sql = "SELECT 
                        u.id,
                        u.name,
                        COUNT(l.id) as total_leads,
                        COUNT(CASE WHEN l.status = 'won' THEN 1 END) as won_leads,
                        COUNT(CASE WHEN l.status = 'lost' THEN 1 END) as lost_leads,
                        COALESCE(AVG(CASE WHEN l.status IN ('won', 'lost') THEN DATEDIFF(l.updated_at, l.created_at) END), 0) as avg_days_to_close,
                        COUNT(a.id) as total_appointments,
                        COUNT(i.id) as total_interactions
                    FROM users u
                    LEFT JOIN leads l ON u.id = l.assigned_to $dateFilter
                    LEFT JOIN appointments a ON u.id = a.assigned_to AND DATE(a.appointment_date) >= COALESCE(:date_from, '1900-01-01') AND DATE(a.appointment_date) <= COALESCE(:date_to, '2100-12-31')
                    LEFT JOIN interactions i ON u.id = i.user_id AND DATE(i.interaction_date) >= COALESCE(:date_from, '1900-01-01') AND DATE(i.interaction_date) <= COALESCE(:date_to, '2100-12-31')
                    WHERE u.role IN ('admin', 'manager', 'sales')
                    GROUP BY u.id, u.name
                    ORDER BY total_leads DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate conversion rates
            foreach ($performance as &$user) {
                $user['conversion_rate'] = $user['total_leads'] > 0 ? round(($user['won_leads'] / $user['total_leads']) * 100, 2) : 0;
                $user['avg_days_to_close'] = round($user['avg_days_to_close'], 1);
            }
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $performance
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching user performance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get monthly summary
    public function getMonthlySummary($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            $month = isset($params['month']) ? (int)$params['month'] : date('n');
            $year = isset($params['year']) ? (int)$params['year'] : date('Y');
            
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Get leads created this month
            $leadsSql = "SELECT COUNT(*) as count FROM leads WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
            $leadsStmt = $this->db->prepare($leadsSql);
            $leadsStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $leadsCount = $leadsStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Get clients created this month
            $clientsSql = "SELECT COUNT(*) as count FROM clients WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
            $clientsStmt = $this->db->prepare($clientsSql);
            $clientsStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $clientsCount = $clientsStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Get appointments this month
            $appointmentsSql = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) BETWEEN :start_date AND :end_date";
            $appointmentsStmt = $this->db->prepare($appointmentsSql);
            $appointmentsStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $appointmentsCount = $appointmentsStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Get revenue this month
            $revenueSql = "SELECT COALESCE(SUM(c.amount), 0) as revenue 
                           FROM payments p 
                           JOIN charges c ON p.charge_id = c.id 
                           WHERE p.status = 'completed' AND DATE(p.created_at) BETWEEN :start_date AND :end_date";
            $revenueStmt = $this->db->prepare($revenueSql);
            $revenueStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $revenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            
            // Get interactions this month
            $interactionsSql = "SELECT COUNT(*) as count FROM interactions WHERE DATE(interaction_date) BETWEEN :start_date AND :end_date";
            $interactionsStmt = $this->db->prepare($interactionsSql);
            $interactionsStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $interactionsCount = $interactionsStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'leads_created' => (int)$leadsCount,
                    'clients_created' => (int)$clientsCount,
                    'appointments_scheduled' => (int)$appointmentsCount,
                    'revenue_generated' => (float)$revenue,
                    'interactions_logged' => (int)$interactionsCount
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Error fetching monthly summary: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function jsonResponse($response, $data, $status = 200) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

?>