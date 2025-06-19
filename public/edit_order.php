<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();
$pn = Database::sanitizeString($_GET['pn'] ?? '');

// Fetch existing order data
$stmt = $pdo->prepare('SELECT po.*, p.ProjectName, m.ModelName
                       FROM ProductionOrders po
                       LEFT JOIN Projects p ON po.ProjectID = p.ProjectID
                       LEFT JOIN Models m ON po.ModelID = m.ModelID
                       WHERE po.ProductionNumber = ?');
$stmt->execute([$pn]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo 'Order not found';
    exit;
}

// Fetch projects and models for dropdowns
$projects = $pdo->query('SELECT * FROM Projects')->fetchAll(PDO::FETCH_ASSOC);
$models = $pdo->query('SELECT * FROM Models')->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing liner usage
$stmt = $pdo->prepare('SELECT * FROM MC02_LinerUsage WHERE ProductionNumber = ?');
$stmt->execute([$pn]);
$liners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing process logs
$stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
$stmt->execute([$pn]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emptyTube = Database::sanitizeString($_POST['EmptyTubeNumber'] ?? '');
    $projectID = $_POST['ProjectID'] !== '' ? (int)$_POST['ProjectID'] : null;
    $modelID = $_POST['ModelID'] !== '' ? (int)$_POST['ModelID'] : null;
    $status = Database::sanitizeString($_POST['MC02_Status'] ?? '');

    if (!$error) {
        $pdo->beginTransaction();
        try {
            // Update main order
            $stmt = $pdo->prepare('UPDATE ProductionOrders SET EmptyTubeNumber = ?, ProjectID = ?, ModelID = ?, MC02_Status = ? WHERE ProductionNumber = ?');
            $stmt->execute([$emptyTube, $projectID, $modelID, $status, $pn]);

            // Delete existing liner usage and insert new ones
            $pdo->prepare('DELETE FROM MC02_LinerUsage WHERE ProductionNumber = ?')->execute([$pn]);
            if (!empty($_POST['liner'])) {
                $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
                foreach ($_POST['liner'] as $liner) {
                    $linerType = Database::sanitizeString($liner['LinerType'] ?? '');
                    if ($linerType === '') {
                        continue;
                    }
                    $batch = Database::sanitizeString($liner['LinerBatchNumber'] ?? '');
                    $remarks = Database::sanitizeString($liner['Remarks'] ?? '');
                    $luStmt->execute([$pn, $linerType, $batch, $remarks]);
                }
            }

            // Delete existing process logs and insert new ones
            $pdo->prepare('DELETE FROM MC02_ProcessLog WHERE ProductionNumber = ?')->execute([$pn]);
            if (!empty($_POST['log'])) {
                $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                foreach ($_POST['log'] as $log) {
                    $step = Database::sanitizeString($log['ProcessStepName'] ?? '');
                    if ($step !== '') {
                        $logStmt->execute([
                            $pn,
                            (int)($log['SequenceNo'] ?? 0),
                            $step,
                            $log['DatePerformed'] ?: null,
                            Database::sanitizeString($log['Result'] ?? ''),
                            $log['Operator_UserID'] !== '' ? (int)$log['Operator_UserID'] : null,
                            Database::sanitizeString($log['Remarks'] ?? ''),
                            $log['ControlValue'] !== '' ? $log['ControlValue'] : null,
                            $log['ActualMeasuredValue'] !== '' ? $log['ActualMeasuredValue'] : null
                        ]);
                    }
                }
            }

            $pdo->commit();
            $success = 'Order updated successfully!';
            
            // Refresh data
            $stmt = $pdo->prepare('SELECT * FROM ProductionOrders WHERE ProductionNumber = ?');
            $stmt->execute([$pn]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare('SELECT * FROM MC02_LinerUsage WHERE ProductionNumber = ?');
            $stmt->execute([$pn]);
            $liners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
            $stmt->execute([$pn]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error updating order: ' . $e->getMessage();
        }    }
}

$page_title = 'Edit Order - ' . htmlspecialchars($order['ProductionNumber']);
$breadcrumbs = [
    ['title' => 'Orders', 'url' => 'index.php'],
    ['title' => $order['ProductionNumber'], 'url' => 'view_order.php?pn=' . urlencode($order['ProductionNumber'])],
    ['title' => 'Edit']
];
include __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Edit Order: <?php echo htmlspecialchars($order['ProductionNumber']); ?></h1>
            <div>
                <a href="view_order.php?pn=<?php echo urlencode($pn); ?>" class="btn btn-secondary me-2">← Back to View</a>
                <a href="index.php" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>        <form method="post" id="edit-order-form">
            <!-- Hidden field สำหรับ Production Number -->
            <input type="hidden" name="ProductionNumber" value="<?php echo htmlspecialchars($order['ProductionNumber']); ?>">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Production Number:</strong></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['ProductionNumber']); ?>" readonly>
                                <div class="form-text">Cannot be changed</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="EmptyTubeNumber" class="form-label"><strong>Empty Tube Number:</strong></label>
                                <input type="text" class="form-control" id="EmptyTubeNumber" name="EmptyTubeNumber" value="<?php echo htmlspecialchars($order['EmptyTubeNumber']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ProjectID" class="form-label"><strong>Project:</strong></label>
                                <select class="form-select" id="ProjectID" name="ProjectID">
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['ProjectID']; ?>" <?php echo ($project['ProjectID'] == $order['ProjectID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['ProjectName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ModelID" class="form-label"><strong>Model:</strong></label>
                                <select class="form-select" id="ModelID" name="ModelID">
                                    <option value="">Select Model</option>
                                    <?php foreach ($models as $model): ?>
                                        <option value="<?php echo $model['ModelID']; ?>" <?php echo ($model['ModelID'] == $order['ModelID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($model['ModelName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="MC02_Status" class="form-label"><strong>Status:</strong></label>
                                <select class="form-select" id="MC02_Status" name="MC02_Status">
                                    <option value="Pending" <?php echo ($order['MC02_Status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="In Progress" <?php echo ($order['MC02_Status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo ($order['MC02_Status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="On Hold" <?php echo ($order['MC02_Status'] == 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Liner Usage</h5>
                    <button type="button" onclick="addLinerRow()" class="btn btn-primary btn-sm">Add Liner Row</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="linerTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Liner Type</th>
                                    <th>Batch Number</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($liners)): ?>
                                    <?php foreach ($liners as $index => $liner): ?>
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" name="liner[<?php echo $index; ?>][LinerType]" value="<?php echo htmlspecialchars($liner['LinerType']); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="liner[<?php echo $index; ?>][LinerBatchNumber]" value="<?php echo htmlspecialchars($liner['LinerBatchNumber']); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="liner[<?php echo $index; ?>][Remarks]" value="<?php echo htmlspecialchars($liner['Remarks']); ?>"></td>
                                            <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td><input type="text" class="form-control form-control-sm" name="liner[0][LinerType]"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="liner[0][LinerBatchNumber]"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="liner[0][Remarks]"></td>
                                        <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Process Log</h5>
                    <button type="button" onclick="addLogRow()" class="btn btn-primary btn-sm">Add Process Log Row</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm" id="logTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Seq</th>
                                    <th>Process Step</th>
                                    <th>Date</th>
                                    <th>Result</th>
                                    <th>Operator ID</th>
                                    <th>Control Value</th>
                                    <th>Actual Value</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $index => $log): ?>
                                        <tr>
                                            <td><input type="number" class="form-control form-control-sm" name="log[<?php echo $index; ?>][SequenceNo]" value="<?php echo $log['SequenceNo']; ?>" style="width: 70px;"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="log[<?php echo $index; ?>][ProcessStepName]" value="<?php echo htmlspecialchars($log['ProcessStepName']); ?>"></td>
                                            <td><input type="date" class="form-control form-control-sm" name="log[<?php echo $index; ?>][DatePerformed]" value="<?php echo $log['DatePerformed']; ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="log[<?php echo $index; ?>][Result]" value="<?php echo htmlspecialchars($log['Result']); ?>"></td>
                                            <td><input type="number" class="form-control form-control-sm" name="log[<?php echo $index; ?>][Operator_UserID]" value="<?php echo $log['Operator_UserID']; ?>" style="width: 90px;"></td>
                                            <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[<?php echo $index; ?>][ControlValue]" value="<?php echo $log['ControlValue']; ?>" style="width: 100px;"></td>
                                            <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[<?php echo $index; ?>][ActualMeasuredValue]" value="<?php echo $log['ActualMeasuredValue']; ?>" style="width: 100px;"></td>
                                            <td><textarea class="form-control form-control-sm" name="log[<?php echo $index; ?>][Remarks]" rows="2"><?php echo htmlspecialchars($log['Remarks']); ?></textarea></td>
                                            <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td><input type="number" class="form-control form-control-sm" name="log[0][SequenceNo]" value="1" style="width: 70px;"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="log[0][ProcessStepName]"></td>
                                        <td><input type="date" class="form-control form-control-sm" name="log[0][DatePerformed]"></td>
                                        <td><input type="text" class="form-control form-control-sm" name="log[0][Result]"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="log[0][Operator_UserID]" style="width: 90px;"></td>
                                        <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[0][ControlValue]" style="width: 100px;"></td>
                                        <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[0][ActualMeasuredValue]" style="width: 100px;"></td>
                                        <td><textarea class="form-control form-control-sm" name="log[0][Remarks]" rows="2"></textarea></td>
                                        <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-success">Update Order</button>
                <a href="view_order.php?pn=<?php echo urlencode($pn); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

    <script>
        let linerRowCount = <?php echo max(1, count($liners)); ?>;
        let logRowCount = <?php echo max(1, count($logs)); ?>;        function addLinerRow() {
            const table = document.getElementById('linerTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" class="form-control form-control-sm" name="liner[${linerRowCount}][LinerType]"></td>
                <td><input type="text" class="form-control form-control-sm" name="liner[${linerRowCount}][LinerBatchNumber]"></td>
                <td><input type="text" class="form-control form-control-sm" name="liner[${linerRowCount}][Remarks]"></td>
                <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
            `;
            linerRowCount++;
        }

        function addLogRow() {
            const table = document.getElementById('logTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="number" class="form-control form-control-sm" name="log[${logRowCount}][SequenceNo]" value="${logRowCount + 1}" style="width: 70px;"></td>
                <td><input type="text" class="form-control form-control-sm" name="log[${logRowCount}][ProcessStepName]"></td>
                <td><input type="date" class="form-control form-control-sm" name="log[${logRowCount}][DatePerformed]"></td>
                <td><input type="text" class="form-control form-control-sm" name="log[${logRowCount}][Result]"></td>
                <td><input type="number" class="form-control form-control-sm" name="log[${logRowCount}][Operator_UserID]" style="width: 90px;"></td>
                <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[${logRowCount}][ControlValue]" style="width: 100px;"></td>
                <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[${logRowCount}][ActualMeasuredValue]" style="width: 100px;"></td>
                <td><textarea class="form-control form-control-sm" name="log[${logRowCount}][Remarks]" rows="2"></textarea></td>
                <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
            `;
            logRowCount++;
        }

        function removeRow(button) {
            const row = button.closest('tr');
            row.remove();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('edit-order-form');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const projectId = form.querySelector('[name="ProjectID"]').value;
                const modelId = form.querySelector('[name="ModelID"]').value;
                if (!projectId) {
                    showToast('Please select a Project', 'error');
                    return;
                }
                if (!modelId) {
                    showToast('Please select a Model', 'error');
                    return;
                }                showLoading();
                fetch('api/edit_order.php?pn=<?php echo urlencode($pn); ?>', {
                    method: 'POST',
                    body: new FormData(form)
                })
                    .then(r => r.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showToast('Order updated successfully');
                            refreshLogs(data.logs);
                        } else {
                            showToast(data.error || 'Update failed', 'error');
                        }
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Network error', 'error');
                    });
            });
        });

        function refreshLogs(logs) {
            const tbody = document.getElementById('logTable').getElementsByTagName('tbody')[0];
            tbody.innerHTML = '';
            logRowCount = logs.length;
            logs.forEach((log, index) => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td><input type="number" class="form-control form-control-sm" name="log[${index}][SequenceNo]" value="${log.SequenceNo}" style="width: 70px;"></td>
                    <td><input type="text" class="form-control form-control-sm" name="log[${index}][ProcessStepName]" value="${log.ProcessStepName}"></td>
                    <td><input type="date" class="form-control form-control-sm" name="log[${index}][DatePerformed]" value="${log.DatePerformed ?? ''}"></td>
                    <td><input type="text" class="form-control form-control-sm" name="log[${index}][Result]" value="${log.Result ?? ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" name="log[${index}][Operator_UserID]" value="${log.Operator_UserID ?? ''}" style="width: 90px;"></td>
                    <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[${index}][ControlValue]" value="${log.ControlValue ?? ''}" style="width: 100px;"></td>
                    <td><input type="number" step="0.001" class="form-control form-control-sm" name="log[${index}][ActualMeasuredValue]" value="${log.ActualMeasuredValue ?? ''}" style="width: 100px;"></td>
                    <td><textarea class="form-control form-control-sm" name="log[${index}][Remarks]" rows="2">${log.Remarks ?? ''}</textarea></td>
                    <td><button type="button" onclick="removeRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
                `;
            });
        }
    </script>

<?php include __DIR__ . '/templates/footer.php'; ?>
