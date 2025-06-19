<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/Database.php';
$pdo = Database::connect();

$modelId = (int)($_GET['model_id'] ?? 0);

if (!$modelId) {
    http_response_code(400);
    echo json_encode(['error' => 'Model ID required']);
    exit;
}

try {
    // Get default template for the model
    $stmt = $pdo->prepare("
        SELECT 
            pt.TemplateID,
            pt.TemplateName,
            pt.Description,
            m.ModelName
        FROM Models m
        LEFT JOIN processtemplates pt ON m.DefaultTemplateID = pt.TemplateID
        WHERE m.ModelID = ?
    ");
    $stmt->execute([$modelId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo json_encode(['error' => 'Model not found']);
        exit;
    }
    
    // If no default template, get the first available template
    if (!$template['TemplateID']) {
        $stmt = $pdo->prepare("
            SELECT 
                pt.TemplateID,
                pt.TemplateName,
                pt.Description,
                m.ModelName
            FROM processtemplates pt
            JOIN Models m ON pt.ModelID = m.ModelID
            WHERE pt.ModelID = ?
            ORDER BY pt.CreatedDate ASC
            LIMIT 1
        ");
        $stmt->execute([$modelId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $response = [
        'model_id' => $modelId,
        'model_name' => $template['ModelName'] ?? 'Unknown Model',
        'template' => null,
        'steps' => []
    ];
    
    if ($template && $template['TemplateID']) {
        $response['template'] = [
            'template_id' => $template['TemplateID'],
            'template_name' => $template['TemplateName'],
            'description' => $template['Description']
        ];
          // Get template steps
        $stmt = $pdo->prepare("
            SELECT 
                TemplateStepID as StepID,
                StepOrder,
                StepName,
                DefaultValue,
                IsRequired
            FROM processtemplatesteps
            WHERE TemplateID = ?
            ORDER BY StepOrder
        ");
        $stmt->execute([$template['TemplateID']]);
        $response['steps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
