<?php

namespace App\Controllers;

use App\Models\Lead;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CrmController
{
    private $leadModel;
    private $userModel;

    public function __construct()
    {
        require_once __DIR__ . '/../../config/database.php';
        $db = \Database::getInstance()->getConnection();
        $this->leadModel = new Lead($db);
        $this->userModel = new User($db);
    }

    // Dashboard Analytics
    public function getDashboard(Request $request, Response $response): Response
    {
        try {
            $totalLeads = $this->leadModel->getTotalCount();
            $convertedLeads = $this->leadModel->getConvertedCount();
            $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0;
            $newLeads = $this->leadModel->getNewLeadsCount();

            $data = [
                'totalLeads' => $totalLeads,
                'convertedLeads' => $convertedLeads,
                'conversionRate' => $conversionRate,
                'newLeads' => $newLeads
            ];

            return $this->jsonResponse($response, $data);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load dashboard data'], 500);
        }
    }

    // Get all leads with filters
    public function getLeads(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $filters = [
                'status' => $params['status'] ?? null,
                'source' => $params['source'] ?? null,
                'assigned_to' => $params['assigned_to'] ?? null,
                'search' => $params['search'] ?? null,
                'page' => (int)($params['page'] ?? 1),
                'limit' => (int)($params['limit'] ?? 20)
            ];

            $leads = $this->leadModel->getAll($filters);
            return $this->jsonResponse($response, $leads);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load leads'], 500);
        }
    }

    // Get single lead
    public function getLead(Request $request, Response $response, array $args): Response
    {
        try {
            $leadId = (int)$args['id'];
            $lead = $this->leadModel->getById($leadId);

            if (!$lead) {
                return $this->jsonResponse($response, ['error' => 'Lead not found'], 404);
            }

            return $this->jsonResponse($response, $lead);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load lead'], 500);
        }
    }

    // Create new lead
    public function createLead(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            // Validate required fields
            $required = ['name', 'email', 'phone'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, ['error' => "Field '{$field}' is required"], 400);
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, ['error' => 'Invalid email format'], 400);
            }

            // Check if lead with same email exists
            $existingLead = $this->leadModel->findByEmail($data['email']);
            if ($existingLead) {
                return $this->jsonResponse($response, ['error' => 'Lead with this email already exists'], 409);
            }

            $leadData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'company' => $data['company'] ?? null,
                'position' => $data['position'] ?? null,
                'source' => $data['source'] ?? 'website',
                'status' => $data['status'] ?? 'new',
                'value' => $data['value'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? $user['id'],
                'created_by' => $user['id']
            ];

            $leadId = $this->leadModel->create($leadData);
            $lead = $this->leadModel->getById($leadId);

            return $this->jsonResponse($response, $lead, 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to create lead'], 500);
        }
    }

    // Update lead
    public function updateLead(Request $request, Response $response, array $args): Response
    {
        try {
            $leadId = (int)$args['id'];
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user');

            // Check if lead exists
            $existingLead = $this->leadModel->getById($leadId);
            if (!$existingLead) {
                return $this->jsonResponse($response, ['error' => 'Lead not found'], 404);
            }

            // Validate email if provided
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse($response, ['error' => 'Invalid email format'], 400);
            }

            // Check for duplicate email (excluding current lead)
            if (!empty($data['email']) && $data['email'] !== $existingLead['email']) {
                $duplicateLead = $this->leadModel->findByEmail($data['email']);
                if ($duplicateLead && $duplicateLead['id'] !== $leadId) {
                    return $this->jsonResponse($response, ['error' => 'Lead with this email already exists'], 409);
                }
            }

            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'position' => $data['position'] ?? null,
                'source' => $data['source'] ?? null,
                'status' => $data['status'] ?? null,
                'value' => $data['value'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'updated_by' => $user['id']
            ], function($value) {
                return $value !== null;
            });

            $this->leadModel->update($leadId, $updateData);
            $lead = $this->leadModel->getById($leadId);

            return $this->jsonResponse($response, $lead);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to update lead'], 500);
        }
    }

    // Delete lead
    public function deleteLead(Request $request, Response $response, array $args): Response
    {
        try {
            $leadId = (int)$args['id'];

            // Check if lead exists
            $lead = $this->leadModel->getById($leadId);
            if (!$lead) {
                return $this->jsonResponse($response, ['error' => 'Lead not found'], 404);
            }

            $this->leadModel->delete($leadId);
            return $this->jsonResponse($response, ['message' => 'Lead deleted successfully']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to delete lead'], 500);
        }
    }

    // Convert lead to client
    public function convertLead(Request $request, Response $response, array $args): Response
    {
        try {
            $leadId = (int)$args['id'];
            $user = $request->getAttribute('user');

            // Check if lead exists
            $lead = $this->leadModel->getById($leadId);
            if (!$lead) {
                return $this->jsonResponse($response, ['error' => 'Lead not found'], 404);
            }

            // Check if already converted
            if ($lead['status'] === 'converted') {
                return $this->jsonResponse($response, ['error' => 'Lead already converted'], 400);
            }

            $clientId = $this->leadModel->convertToClient($leadId, $user['id']);
            return $this->jsonResponse($response, ['message' => 'Lead converted successfully', 'client_id' => $clientId]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to convert lead'], 500);
        }
    }

    // Get leads by status
    public function getLeadsByStatus(Request $request, Response $response): Response
    {
        try {
            $data = $this->leadModel->getLeadsByStatus();
            return $this->jsonResponse($response, $data);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load leads by status'], 500);
        }
    }

    // Get leads by source
    public function getLeadsBySource(Request $request, Response $response): Response
    {
        try {
            $data = $this->leadModel->getLeadsBySource();
            return $this->jsonResponse($response, $data);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load leads by source'], 500);
        }
    }

    // Get recent leads
    public function getRecentLeads(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = (int)($params['limit'] ?? 10);
            
            $leads = $this->leadModel->getRecentLeads($limit);
            return $this->jsonResponse($response, $leads);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load recent leads'], 500);
        }
    }

    // Get pipeline overview
    public function getPipeline(Request $request, Response $response): Response
    {
        try {
            // Get all deals
            $deals = $this->leadModel->getAll();
            
            // Get pipeline statistics
            $pipeline = [
                'new' => $this->leadModel->getCountByStatus('new'),
                'contacted' => $this->leadModel->getCountByStatus('contacted'),
                'qualified' => $this->leadModel->getCountByStatus('qualified'),
                'proposal' => $this->leadModel->getCountByStatus('proposal'),
                'won' => $this->leadModel->getCountByStatus('won'),
                'lost' => $this->leadModel->getCountByStatus('lost')
            ];
            
            // Return both deals and statistics
            $response_data = [
                'deals' => $deals,
                'statistics' => $pipeline
            ];

            return $this->jsonResponse($response, $response_data);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load pipeline'], 500);
        }
    }

    // Get salespeople
    public function getSalespeople(Request $request, Response $response): Response
    {
        try {
            $salespeople = $this->userModel->getUsersByRole('seller');
            return $this->jsonResponse($response, $salespeople);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to load salespeople'], 500);
        }
    }

    // Helper method for JSON responses
    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $payload = [
            'success' => $status < 400,
            'data' => $data,
            'timestamp' => date('c')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    // Deal Management Methods
    public function getDeals($request, $response, $args) {
        try {
            $deals = $this->leadModel->getAllLeads();
            return $this->jsonResponse($response, $deals);
        } catch (Exception $e) {
            error_log('Error getting deals: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get deals'], 500);
        }
    }

    public function getDeal($request, $response, $args) {
        try {
            $dealId = $args['id'];
            $deal = $this->leadModel->getLeadById($dealId);
            
            if (!$deal) {
                return $this->jsonResponse($response, ['error' => 'Deal not found'], 404);
            }
            
            return $this->jsonResponse($response, $deal);
        } catch (Exception $e) {
            error_log('Error getting deal: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to get deal'], 500);
        }
    }

    public function createDeal($request, $response, $args) {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate required fields
            if (empty($data['title']) || empty($data['client_id'])) {
                return $this->jsonResponse($response, ['error' => 'Title and client are required'], 400);
            }
            
            // Prepare deal data
            $dealData = [
                'title' => $data['title'],
                'client_id' => $data['client_id'],
                'value' => $data['value'] ?? 0,
                'status' => $data['status'] ?? 'new',
                'probability' => $data['probability'] ?? 0,
                'close_date' => $data['close_date'] ?? null,
                'notes' => $data['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $dealId = $this->leadModel->createLead($dealData);
            
            if ($dealId) {
                $deal = $this->leadModel->getLeadById($dealId);
                return $this->jsonResponse($response, $deal, 201);
            } else {
                return $this->jsonResponse($response, ['error' => 'Failed to create deal'], 500);
            }
        } catch (Exception $e) {
            error_log('Error creating deal: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to create deal'], 500);
        }
    }

    public function updateDeal($request, $response, $args) {
        try {
            $dealId = $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Check if deal exists
            $existingDeal = $this->leadModel->getLeadById($dealId);
            if (!$existingDeal) {
                return $this->jsonResponse($response, ['error' => 'Deal not found'], 404);
            }
            
            // Prepare update data
            $updateData = [
                'title' => $data['title'] ?? $existingDeal['title'],
                'client_id' => $data['client_id'] ?? $existingDeal['client_id'],
                'value' => $data['value'] ?? $existingDeal['value'],
                'status' => $data['status'] ?? $existingDeal['status'],
                'probability' => $data['probability'] ?? $existingDeal['probability'],
                'close_date' => $data['close_date'] ?? $existingDeal['close_date'],
                'notes' => $data['notes'] ?? $existingDeal['notes'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $success = $this->leadModel->updateLead($dealId, $updateData);
            
            if ($success) {
                $deal = $this->leadModel->getLeadById($dealId);
                return $this->jsonResponse($response, $deal);
            } else {
                return $this->jsonResponse($response, ['error' => 'Failed to update deal'], 500);
            }
        } catch (Exception $e) {
            error_log('Error updating deal: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to update deal'], 500);
        }
    }

    public function deleteDeal($request, $response, $args) {
        try {
            $dealId = $args['id'];
            
            // Check if deal exists
            $existingDeal = $this->leadModel->getLeadById($dealId);
            if (!$existingDeal) {
                return $this->jsonResponse($response, ['error' => 'Deal not found'], 404);
            }
            
            $success = $this->leadModel->deleteLead($dealId);
            
            if ($success) {
                return $this->jsonResponse($response, ['message' => 'Deal deleted successfully']);
            } else {
                return $this->jsonResponse($response, ['error' => 'Failed to delete deal'], 500);
            }
        } catch (Exception $e) {
            error_log('Error deleting deal: ' . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Failed to delete deal'], 500);
        }
    }
}