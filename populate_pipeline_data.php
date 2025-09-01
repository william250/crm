<?php

require_once 'config/database.php';

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
    echo "Connected to database successfully.\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

try {
    
    // Sample pipeline data
    $pipelineData = [
        [
            'name' => 'Tech Solutions Inc',
            'email' => 'contact@techsolutions.com',
            'phone' => '+1-555-0101',
            'source' => 'website',
            'value' => 15000.00,
            'status' => 'proposal',
            'created_at' => '2024-01-10 09:00:00',
            'updated_at' => '2024-01-15 14:30:00'
        ],
        [
            'name' => 'Fashion Boutique Ltd',
            'email' => 'info@fashionboutique.com',
            'phone' => '+1-555-0102',
            'source' => 'referral',
            'value' => 25000.00,
            'status' => 'qualified',
            'created_at' => '2024-01-08 11:15:00',
            'updated_at' => '2024-01-16 16:45:00'
        ],
        [
            'name' => 'StartUp Innovations',
            'email' => 'hello@startupinnovations.com',
            'phone' => '+1-555-0103',
            'source' => 'social_media',
            'value' => 35000.00,
            'status' => 'qualified',
            'created_at' => '2024-01-12 13:20:00',
            'updated_at' => '2024-01-17 10:15:00'
        ],
        [
            'name' => 'Global Services Corp',
            'email' => 'sales@globalservices.com',
            'phone' => '+1-555-0104',
            'source' => 'cold_call',
            'value' => 18000.00,
            'status' => 'contacted',
            'created_at' => '2024-01-14 15:30:00',
            'updated_at' => '2024-01-18 09:00:00'
        ],
        [
            'name' => 'Local Restaurant Chain',
            'email' => 'manager@localrestaurant.com',
            'phone' => '+1-555-0105',
            'source' => 'website',
            'value' => 8000.00,
            'status' => 'new',
            'created_at' => '2024-01-16 08:45:00',
            'updated_at' => '2024-01-16 08:45:00'
        ],
        [
            'name' => 'Healthcare Systems Inc',
            'email' => 'procurement@healthcaresys.com',
            'phone' => '+1-555-0106',
            'source' => 'referral',
            'value' => 22000.00,
            'status' => 'won',
            'created_at' => '2024-01-05 10:00:00',
            'updated_at' => '2024-01-19 14:20:00'
        ],
        [
            'name' => 'Financial Services Ltd',
            'email' => 'it@financialservices.com',
            'phone' => '+1-555-0107',
            'source' => 'trade_show',
            'value' => 12000.00,
            'status' => 'lost',
            'created_at' => '2024-01-07 14:15:00',
            'updated_at' => '2024-01-20 11:30:00'
        ],
        [
            'name' => 'Manufacturing Co',
            'email' => 'tech@manufacturing.com',
            'phone' => '+1-555-0108',
            'source' => 'website',
            'value' => 30000.00,
            'status' => 'proposal',
            'created_at' => '2024-01-11 16:00:00',
            'updated_at' => '2024-01-18 12:00:00'
        ]
    ];
    
    // Clear existing leads data
    $pdo->exec("DELETE FROM leads");
    echo "Cleared existing leads data.\n";
    
    // Insert sample data
     $stmt = $pdo->prepare("
         INSERT INTO leads (user_id, name, email, phone, source, value, status, created_at, updated_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
     ");
    
    $insertedCount = 0;
    foreach ($pipelineData as $lead) {
         $success = $stmt->execute([
             1, // user_id padrão
             $lead['name'],
             $lead['email'],
             $lead['phone'],
             $lead['source'],
             $lead['value'],
             $lead['status'],
             $lead['created_at'],
             $lead['updated_at']
         ]);
        
        if ($success) {
            $insertedCount++;
            echo "Inserted lead: {$lead['name']}\n";
        } else {
            echo "Failed to insert lead: {$lead['name']}\n";
        }
    }
    
    echo "\n=== Pipeline Data Population Complete ===\n";
    echo "Total leads inserted: $insertedCount\n";
    
    // Show summary by status
    $statusQuery = $pdo->query("
        SELECT status, COUNT(*) as count, SUM(value) as total_value 
        FROM leads 
        GROUP BY status 
        ORDER BY 
            CASE status 
                WHEN 'new' THEN 1
                WHEN 'contacted' THEN 2
                WHEN 'qualified' THEN 3
                WHEN 'proposal' THEN 4
                WHEN 'negotiation' THEN 5
                WHEN 'won' THEN 6
                WHEN 'lost' THEN 7
                ELSE 8
            END
    ");
    
    echo "\n=== Pipeline Summary ===\n";
    while ($row = $statusQuery->fetch(PDO::FETCH_ASSOC)) {
        $status = ucfirst($row['status']);
        $count = $row['count'];
        $value = number_format($row['total_value'], 2);
        echo "$status: $count leads ($$value)\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nPipeline data population script completed.\n";
?>