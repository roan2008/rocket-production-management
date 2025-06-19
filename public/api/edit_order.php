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

$productionNumber = Database::sanitizeString($_POST['ProductionNumber'] ?? $_GET['pn'] ?? '');
$emptyTube = Database::sanitizeString($_POST['EmptyTubeNumber'] ?? '');
$projectID = $_POST['ProjectID'] !== '' ? (int)$_POST['ProjectID'] : null;
$modelID = $_POST['ModelID'] !== '' ? (int)$_POST['ModelID'] : null;
$status = Database::sanitizeString($_POST['MC02_Status'] ?? '');

// Debug: Log received parameters (for development only)
error_log("Edit Order API - ProductionNumber: " . $productionNumber . 
          ", POST: " . ($_POST['ProductionNumber'] ?? 'not set') . 
          ", GET: " . ($_GET['pn'] ?? 'not set'));

if (!$productionNumber) {
    http_response_code(422);
    echo json_encode([
        'error' => 'กรุณาใส่ Production Number (Invalid production number)',
        'debug' => [
            'post_pn' => $_POST['ProductionNumber'] ?? null,
            'get_pn' => $_GET['pn'] ?? null,
            'received_pn' => $productionNumber
        ]
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE ProductionOrders SET EmptyTubeNumber = ?, ProjectID = ?, ModelID = ?, MC02_Status = ? WHERE ProductionNumber = ?');
    $stmt->execute([$emptyTube, $projectID, $modelID, $status, $productionNumber]);

    $pdo->prepare('DELETE FROM MC02_LinerUsage WHERE ProductionNumber = ?')->execute([$productionNumber]);
    if (!empty($_POST['liner'])) {
        $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
        foreach ($_POST['liner'] as $liner) {
            $linerType = Database::sanitizeString($liner['LinerType'] ?? '');
            if ($linerType === '') {
                continue;
            }
            $batch = Database::sanitizeString($liner['LinerBatchNumber'] ?? '');
            $remarks = Database::sanitizeString($liner['Remarks'] ?? '');
            $luStmt->execute([$productionNumber, $linerType, $batch, $remarks]);
        }
    }

    $pdo->prepare('DELETE FROM MC02_ProcessLog WHERE ProductionNumber = ?')->execute([$productionNumber]);
    if (!empty($_POST['log'])) {
        $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($_POST['log'] as $log) {
            $step = Database::sanitizeString($log['ProcessStepName'] ?? '');
            if ($step !== '') {
                $logStmt->execute([
                    $productionNumber,
                    (int)($log['SequenceNo'] ?? 0),
                    $step,
                    $log['DatePerformed'] ?: null,
                    Database::sanitizeString($log['Result'] ?? ''),
                    $log['Operator_UserID'] !== '' ? (int)$log['Operator_UserID'] : null,
                    Database::sanitizeString($log['Remarks'] ?? ''),
                    $log['ControlValue'] !== '' ? $log['ControlValue'] : null,
                    $log['ActualMeasuredValue'] !== '' ? $log['ActualMeasuredValue'] : null,
                ]);
            }
        }
    }

    $pdo->commit();

    // Return updated logs for immediate UI update
    $logs = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
    $logs->execute([$productionNumber]);

    echo json_encode([
        'success' => true,
        'logs' => $logs->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error updating order']);
}
