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
        case 'list':
            $projectId = $_GET['project_id'] ?? null;
            $query = "
                SELECT 
                    m.ModelID, 
                    m.ModelName, 
                    m.ProjectID,
                    p.ProjectName,
                    pt.TemplateName as DefaultTemplate,
                    (SELECT COUNT(*) FROM processtemplates WHERE ModelID = m.ModelID) as TemplateCount
                FROM Models m
                LEFT JOIN Projects p ON m.ProjectID = p.ProjectID
                LEFT JOIN processtemplates pt ON m.DefaultTemplateID = pt.TemplateID
            ";
            
            if ($projectId) {
                $query .= " WHERE m.ProjectID = ?";
                $stmt = $pdo->prepare($query . " ORDER BY m.ModelName");
                $stmt->execute([$projectId]);
            } else {
                $stmt = $pdo->query($query . " ORDER BY p.ProjectName, m.ModelName");
            }
            
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'details':
            $modelId = $_GET['model_id'] ?? 0;
            if (!$modelId) {
                throw new Exception('Model ID required');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    m.*, 
                    p.ProjectName,
                    pt.TemplateName as DefaultTemplate,
                    pt.TemplateID as DefaultTemplateID
                FROM Models m
                LEFT JOIN Projects p ON m.ProjectID = p.ProjectID
                LEFT JOIN processtemplates pt ON m.DefaultTemplateID = pt.TemplateID
                WHERE m.ModelID = ?
            ");
            $stmt->execute([$modelId]);
            $model = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$model) {
                throw new Exception('Model not found');
            }
            
            // Get templates for this model
            $stmt = $pdo->prepare("
                SELECT 
                    pt.*,
                    (SELECT COUNT(*) FROM processtemplatesteps WHERE TemplateID = pt.TemplateID) as StepCount
                FROM processtemplates pt
                WHERE pt.ModelID = ?
                ORDER BY pt.TemplateName
            ");
            $stmt->execute([$modelId]);
            $model['templates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($model);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            $modelName = Database::sanitizeString($input['ModelName']);
            $projectId = (int)$input['ProjectID'];
            
            $stmt = $pdo->prepare('INSERT INTO Models (ModelName, ProjectID) VALUES (?, ?)');
            $stmt->execute([$modelName, $projectId]);
            
            echo json_encode([
                'success' => true,
                'model_id' => $pdo->lastInsertId(),
                'message' => 'Model created successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handlePut($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            $modelId = (int)$input['ModelID'];
            $modelName = Database::sanitizeString($input['ModelName']);
            $projectId = (int)$input['ProjectID'];
            
            $stmt = $pdo->prepare('UPDATE Models SET ModelName = ?, ProjectID = ? WHERE ModelID = ?');
            $stmt->execute([$modelName, $projectId, $modelId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Model updated successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handleDelete($pdo, $action) {
    switch ($action) {
        case 'delete':
            $modelId = (int)$_GET['model_id'];
            
            // Check if model has production orders
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM ProductionOrders WHERE ModelID = ?');
            $stmt->execute([$modelId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete model with existing production orders');
            }
            
            $stmt = $pdo->prepare('DELETE FROM Models WHERE ModelID = ?');
            $stmt->execute([$modelId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Model deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}
?>
