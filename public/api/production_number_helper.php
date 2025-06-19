<?php
// Helper API to check production number availability and suggest format
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/Database.php';
$pdo = Database::connect();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check':
            $productionNumber = $_GET['production_number'] ?? '';
            
            if (!$productionNumber) {
                echo json_encode([
                    'available' => false,
                    'message' => 'Production number is required',
                    'suggestions' => getProductionNumberSuggestions($pdo)
                ]);
                exit;
            }
            
            // Check if production number exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM ProductionOrders WHERE ProductionNumber = ?');
            $stmt->execute([$productionNumber]);
            $exists = $stmt->fetchColumn() > 0;
            
            if ($exists) {
                echo json_encode([
                    'available' => false,
                    'message' => 'Production number already exists',
                    'suggestions' => getProductionNumberSuggestions($pdo, $productionNumber)
                ]);
            } else {
                echo json_encode([
                    'available' => true,
                    'message' => 'Production number is available'
                ]);
            }
            break;
            
        case 'suggest':
            echo json_encode([
                'suggestions' => getProductionNumberSuggestions($pdo)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function getProductionNumberSuggestions($pdo, $baseName = null) {
    $suggestions = [];
    
    // Get current year for suggestions
    $currentYear = date('Y');
    $currentMonth = date('m');
    
    // Get the highest number for each common prefix
    $prefixes = ['PO-' . $currentYear . '-', 'TOP-', 'M2C-', 'M3C-'];
    
    foreach ($prefixes as $prefix) {
        $stmt = $pdo->prepare("
            SELECT ProductionNumber 
            FROM ProductionOrders 
            WHERE ProductionNumber LIKE ? 
            ORDER BY ProductionNumber DESC 
            LIMIT 1
        ");
        $stmt->execute([$prefix . '%']);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            // Extract number from the end and increment
            preg_match('/(\d+)$/', $lastNumber, $matches);
            if ($matches) {
                $nextNumber = (int)$matches[1] + 1;
                $nextFormatted = str_pad($nextNumber, strlen($matches[1]), '0', STR_PAD_LEFT);
                $suggestion = preg_replace('/\d+$/', $nextFormatted, $lastNumber);
                $suggestions[] = $suggestion;
            }
        } else {
            // No existing numbers with this prefix
            if ($prefix === 'PO-' . $currentYear . '-') {
                $suggestions[] = $prefix . '001';
            } elseif ($prefix === 'TOP-') {
                $suggestions[] = $prefix . '001';
            } else {
                $suggestions[] = $prefix . '001';
            }
        }
    }
    
    // Add some custom suggestions based on current date
    $suggestions[] = 'PO-' . $currentYear . '-' . $currentMonth . date('d') . '-001';
    $suggestions[] = 'BATCH-' . $currentYear . '-001';
    
    return array_unique($suggestions);
}
?>
