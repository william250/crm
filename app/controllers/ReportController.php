<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReportController
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getSalesReport(Request $request, Response $response, array $args): Response
    {
        try {
            $params = $request->getQueryParams();
            $startDate = $params['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $params['endDate'] ?? date('Y-m-d');
            $groupBy = $params['groupBy'] ?? 'month';

            // Get sales data
            $salesData = $this->getSalesData($startDate, $endDate, $groupBy);
            $kpis = $this->getSalesKPIs($startDate, $endDate);
            $dealStatus = $this->getDealStatusData($startDate, $endDate);
            $pipeline = $this->getPipelineData();

            $data = [
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'revenue' => $salesData,
                    'deals' => $dealStatus,
                    'pipeline' => $pipeline,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $error = [
                'success' => false,
                'message' => 'Erro ao gerar relatório de vendas',
                'error' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getFinancialReport(Request $request, Response $response, array $args): Response
    {
        try {
            $params = $request->getQueryParams();
            $startDate = $params['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $params['endDate'] ?? date('Y-m-d');

            $financialData = $this->getFinancialData($startDate, $endDate);
            $summary = $this->getFinancialSummary($startDate, $endDate);

            $data = [
                'success' => true,
                'data' => [
                    'financial' => $financialData,
                    'summary' => $summary,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $error = [
                'success' => false,
                'message' => 'Erro ao gerar relatório financeiro',
                'error' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getPerformanceReport(Request $request, Response $response, array $args): Response
    {
        try {
            $params = $request->getQueryParams();
            $startDate = $params['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $params['endDate'] ?? date('Y-m-d');

            $performanceData = $this->getPerformanceData($startDate, $endDate);
            $clientData = $this->getClientAnalysisData($startDate, $endDate);

            $data = [
                'success' => true,
                'data' => [
                    'performance' => $performanceData,
                    'clients' => $clientData,
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $error = [
                'success' => false,
                'message' => 'Erro ao gerar relatório de performance',
                'error' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function getSalesData($startDate, $endDate, $groupBy)
    {
        // Mock data for now - replace with actual database queries
        $labels = [];
        $data = [];
        
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
            $labels[] = $date->format('Y-m-d');
            $data[] = rand(1000, 5000);
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getSalesKPIs($startDate, $endDate)
    {
        // Mock KPIs - replace with actual calculations
        return [
            'totalRevenue' => 125000 + rand(0, 50000),
            'totalDeals' => 45 + rand(0, 20),
            'conversionRate' => 15 + rand(0, 10),
            'avgDealSize' => 2500 + rand(0, 1000),
            'revenueChange' => (rand(0, 100) - 50) * 0.6,
            'dealsChange' => (rand(0, 100) - 50) * 0.8,
            'conversionChange' => (rand(0, 100) - 50) * 0.4,
            'dealSizeChange' => (rand(0, 100) - 50) * 0.5
        ];
    }

    private function getDealStatusData($startDate, $endDate)
    {
        return [
            'labels' => ['Ganhos', 'Em Andamento', 'Perdidos', 'Qualificados'],
            'data' => [35, 25, 15, 25]
        ];
    }

    private function getPipelineData()
    {
        return [
            'labels' => ['Lead', 'Qualificado', 'Proposta', 'Negociação', 'Fechado'],
            'data' => [120000, 85000, 65000, 45000, 25000]
        ];
    }

    private function getFinancialData($startDate, $endDate)
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
            $labels[] = $date->format('Y-m-d');
            $revenue[] = rand(2000, 8000);
            $expenses[] = rand(1000, 4000);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses
        ];
    }

    private function getFinancialSummary($startDate, $endDate)
    {
        $totalRevenue = rand(50000, 150000);
        $totalExpenses = rand(20000, 80000);
        
        return [
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalRevenue - $totalExpenses
        ];
    }

    private function getPerformanceData($startDate, $endDate)
    {
        return [
            'metrics' => [
                ['name' => 'Leads Gerados', 'current' => 245, 'previous' => 198, 'change' => 23.7],
                ['name' => 'Leads Qualificados', 'current' => 89, 'previous' => 76, 'change' => 17.1],
                ['name' => 'Propostas Enviadas', 'current' => 34, 'previous' => 41, 'change' => -17.1],
                ['name' => 'Negócios Fechados', 'current' => 18, 'previous' => 15, 'change' => 20.0],
                ['name' => 'Ciclo Médio de Vendas', 'current' => 28, 'previous' => 32, 'change' => -12.5]
            ]
        ];
    }

    private function getClientAnalysisData($startDate, $endDate)
    {
        return [
            'topClients' => [
                ['name' => 'Empresa ABC', 'revenue' => 45000, 'deals' => 8, 'lastActivity' => '2024-01-15', 'status' => 'Active'],
                ['name' => 'Corporação XYZ', 'revenue' => 38000, 'deals' => 6, 'lastActivity' => '2024-01-14', 'status' => 'Active'],
                ['name' => 'Indústria 123', 'revenue' => 32000, 'deals' => 5, 'lastActivity' => '2024-01-13', 'status' => 'Active'],
                ['name' => 'Comércio DEF', 'revenue' => 28000, 'deals' => 4, 'lastActivity' => '2024-01-12', 'status' => 'Inactive'],
                ['name' => 'Serviços GHI', 'revenue' => 25000, 'deals' => 3, 'lastActivity' => '2024-01-11', 'status' => 'Active']
            ],
            'acquisition' => [
                'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                'data' => [12, 15, 18, 22, 19, 25]
            ]
        ];
    }
}