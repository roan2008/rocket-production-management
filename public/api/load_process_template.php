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
    $modelId = (int)($_GET['model_id'] ?? 0);
    $pn = Database::sanitizeString($_GET['pn'] ?? '');
    
    if (empty($modelId) || empty($pn)) {
        throw new Exception('Model ID and Production Number are required');
    }
    
    // First, check if the order exists and get current model
    $stmt = $pdo->prepare('SELECT ModelID FROM ProductionOrders WHERE ProductionNumber = ?');
    $stmt->execute([$pn]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Production order not found');
    }
    
    // Get process template for the model
    $stmt = $pdo->prepare('SELECT * FROM ProcessTemplates WHERE ModelID = ? ORDER BY SequenceNo');
    $stmt->execute([$modelId]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($templates)) {
        throw new Exception('No process template found for this model');
    }
    
    // Clear existing process log entries for this order
    $pdo->prepare('DELETE FROM MC02_ProcessLog WHERE ProductionNumber = ?')->execute([$pn]);
    
    // Insert process steps from template
    $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, NULL, NULL, NULL, ?, ?, NULL)');
    
    foreach ($templates as $template) {
        $logStmt->execute([
            $pn,
            $template['SequenceNo'],
            $template['StepName'],
            $template['Description'] ?? '',
            $template['DefaultValue'] ?? null
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Process steps loaded from template successfully',
        'steps_loaded' => count($templates)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
