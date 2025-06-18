<?php
require_once __DIR__ . '/../../src/Database.php';
$pdo = Database::connect();

$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

switch ($method) {
    case 'GET':
        $data = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        break;
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $name = Database::sanitizeString($input['ProjectName'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare('INSERT INTO Projects (ProjectName) VALUES (?)');
            $stmt->execute([$name]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid name']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
