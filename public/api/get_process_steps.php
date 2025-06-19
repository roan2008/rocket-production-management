<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/Database.php';

try {
    $pdo = Database::connect();
    $pn = Database::sanitizeString($_GET['pn'] ?? '');
    
    if (empty($pn)) {
        throw new Exception('Production number is required');
    }
    
    // Fetch current process steps for the order
    $stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
    $stmt->execute([$pn]);
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'steps' => $steps
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
