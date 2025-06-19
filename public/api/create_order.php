<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['UserID'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/Database.php';
$pdo = Database::connect();

$productionNumber = $_POST['ProductionNumber'] ?? '';
$emptyTube = $_POST['EmptyTubeNumber'] ?? '';
$projectID = $_POST['ProjectID'] ?? null;
$modelID = $_POST['ModelID'] ?? null;

$error = '';

if (!$productionNumber) {
    $error = 'กรุณาใส่ Production Number (Production Number is required)';
} else {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ProductionOrders WHERE ProductionNumber = ?');
    $stmt->execute([$productionNumber]);
    if ($stmt->fetchColumn() > 0) {
        $error = 'Production Number "' . $productionNumber . '" มีอยู่แล้ว กรุณาใช้หมายเลขอื่น (Production Number already exists, please use a different number)';
    }
}

if ($error) {
    http_response_code(422);
    echo json_encode(['error' => $error]);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO ProductionOrders (ProductionNumber, EmptyTubeNumber, ProjectID, ModelID) VALUES (?, ?, ?, ?)');
    $stmt->execute([$productionNumber, $emptyTube, $projectID, $modelID]);

    if (!empty($_POST['liner'])) {
        $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
        foreach ($_POST['liner'] as $liner) {
            if (!$liner['LinerType']) continue;
            $luStmt->execute([$productionNumber, $liner['LinerType'], $liner['LinerBatchNumber'], $liner['Remarks']]);
        }
    }

    if (!empty($_POST['log'])) {
        $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($_POST['log'] as $log) {
            $logStmt->execute([
                $productionNumber,
                $log['SequenceNo'],
                $log['ProcessStepName'],
                $log['DatePerformed'] ?: null,
                $log['Result'] ?: null,
                $log['Operator_UserID'] ?: null,
                $log['Remarks'] ?: null,
                $log['ControlValue'] ?: null,
                $log['ActualMeasuredValue'] ?: null,
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'production_number' => $productionNumber]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error creating order']);
}
?>
