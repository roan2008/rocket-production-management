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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'PUT':
            handlePut($pdo, $action);
            break;
        case 'DELETE':
            handleDelete($pdo, $action);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'by_model':
            $modelId = $_GET['model_id'] ?? 0;
            if (!$modelId) {
                throw new Exception('Model ID required');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    pt.*,
                    (SELECT COUNT(*) FROM processtemplatesteps WHERE TemplateID = pt.TemplateID) as StepCount
                FROM processtemplates pt
                WHERE pt.ModelID = ?
                ORDER BY pt.TemplateName
            ");
            $stmt->execute([$modelId]);
            
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'details':
            $templateId = $_GET['template_id'] ?? 0;
            if (!$templateId) {
                throw new Exception('Template ID required');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    pt.*,
                    m.ModelName,
                    p.ProjectName
                FROM processtemplates pt
                JOIN Models m ON pt.ModelID = m.ModelID
                LEFT JOIN Projects p ON m.ProjectID = p.ProjectID
                WHERE pt.TemplateID = ?
            ");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                throw new Exception('Template not found');
            }
            
            // Get template steps
            $stmt = $pdo->prepare("
                SELECT * FROM processtemplatesteps
                WHERE TemplateID = ?
                ORDER BY StepOrder
            ");
            $stmt->execute([$templateId]);
            $template['steps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($template);
            break;
            
        case 'steps':
            $templateId = $_GET['template_id'] ?? 0;
            if (!$templateId) {
                throw new Exception('Template ID required');
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM processtemplatesteps
                WHERE TemplateID = ?
                ORDER BY StepOrder
            ");
            $stmt->execute([$templateId]);
            
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            $modelId = (int)$input['ModelID'];
            $templateName = Database::sanitizeString($input['TemplateName']);
            $description = Database::sanitizeString($input['Description'] ?? '');
            $steps = $input['steps'] ?? [];
            
            $pdo->beginTransaction();
            
            try {
                // Create template                $stmt = $pdo->prepare('INSERT INTO processtemplates (ModelID, TemplateName, Description, CreatedBy) VALUES (?, ?, ?, ?)');
                $stmt->execute([$modelId, $templateName, $description, $_SESSION['UserID']]);
                $templateId = $pdo->lastInsertId();
                
                // Add steps
                $stmt = $pdo->prepare('INSERT INTO processtemplatesteps (TemplateID, StepOrder, StepName, DefaultValue, IsRequired) VALUES (?, ?, ?, ?, ?)');
                foreach ($steps as $index => $step) {
                    $stmt->execute([
                        $templateId,
                        $index + 1,
                        Database::sanitizeString($step['name']),
                        Database::sanitizeString($step['defaultValue'] ?? ''),
                        $step['required'] ? 1 : 0
                    ]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'template_id' => $templateId,
                    'message' => 'Template created successfully'
                ]);
                
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handlePut($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            $templateId = (int)$input['TemplateID'];
            $templateName = Database::sanitizeString($input['TemplateName']);
            $description = Database::sanitizeString($input['Description'] ?? '');
            $steps = $input['steps'] ?? [];
            
            $pdo->beginTransaction();
            
            try {
                // Update template
                $stmt = $pdo->prepare('UPDATE processtemplates SET TemplateName = ?, Description = ? WHERE TemplateID = ?');
                $stmt->execute([$templateName, $description, $templateId]);
                
                // Delete existing steps
                $stmt = $pdo->prepare('DELETE FROM processtemplatesteps WHERE TemplateID = ?');
                $stmt->execute([$templateId]);
                
                // Add new steps
                $stmt = $pdo->prepare('INSERT INTO processtemplatesteps (TemplateID, StepOrder, StepName, DefaultValue, IsRequired) VALUES (?, ?, ?, ?, ?)');
                foreach ($steps as $index => $step) {
                    $stmt->execute([
                        $templateId,
                        $index + 1,
                        Database::sanitizeString($step['name']),
                        Database::sanitizeString($step['defaultValue'] ?? ''),
                        $step['required'] ? 1 : 0
                    ]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Template updated successfully'
                ]);
                
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;
            
        case 'set_default':
            $templateId = (int)$input['TemplateID'];
            
            // Get ModelID for this template
            $stmt = $pdo->prepare('SELECT ModelID FROM processtemplates WHERE TemplateID = ?');
            $stmt->execute([$templateId]);
            $modelId = $stmt->fetchColumn();
            
            if (!$modelId) {
                throw new Exception('Template not found');
            }
            
            // Set as default template
            $stmt = $pdo->prepare('UPDATE Models SET DefaultTemplateID = ? WHERE ModelID = ?');
            $stmt->execute([$templateId, $modelId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Default template updated'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handleDelete($pdo, $action) {
    switch ($action) {
        case 'delete':
            $templateId = (int)$_GET['template_id'];
            
            // Check if template is being used as default
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM Models WHERE DefaultTemplateID = ?');
            $stmt->execute([$templateId]);
            if ($stmt->fetchColumn() > 0) {
                // Remove as default template
                $stmt = $pdo->prepare('UPDATE Models SET DefaultTemplateID = NULL WHERE DefaultTemplateID = ?');
                $stmt->execute([$templateId]);
            }
            
            $stmt = $pdo->prepare('DELETE FROM processtemplates WHERE TemplateID = ?');
            $stmt->execute([$templateId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}
?>
