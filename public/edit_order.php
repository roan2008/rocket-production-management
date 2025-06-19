<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();
$pn = Database::sanitizeString($_GET['pn'] ?? '');

// Helper functions for Phase 1 UX improvements
function getProcessStepStatus($result) {
    if (empty($result)) return 'pending';
    $result = strtolower(trim($result));
    if (in_array($result, ['complete', 'completed', 'pass', 'ok', 'good'])) return 'completed';
    if (in_array($result, ['in progress', 'working', 'started'])) return 'in-progress';
    return 'pending';
}

function getStatusBadgeClass($result) {
    if (empty($result)) return 'bg-secondary';
    $result = strtolower(trim($result));
    if (in_array($result, ['complete', 'completed', 'pass', 'ok', 'good'])) return 'bg-success';
    if (in_array($result, ['in progress', 'working', 'started'])) return 'bg-warning text-dark';
    if (in_array($result, ['fail', 'failed', 'error', 'reject'])) return 'bg-danger';
    return 'bg-secondary';
}

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

// Phase 1 UX Improvements - Custom CSS for Edit Order
$custom_css = '
<style>
.process-log-readonly {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #dee2e6;
}

.process-step-row {
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 5px;
    padding: 12px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.process-step-row:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.process-step-row.completed {
    border-left: 4px solid #28a745;
    background: linear-gradient(90deg, #d4edda 0%, white 20%);
}

.process-step-row.in-progress {
    border-left: 4px solid #ffc107;
    background: linear-gradient(90deg, #fff3cd 0%, white 20%);
}

.process-step-row.pending {
    border-left: 4px solid #6c757d;
    background: linear-gradient(90deg, #f8f9fa 0%, white 20%);
}

.status-badge {
    font-size: 0.8rem;
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 12px;
}

.edit-mode-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

#insert-process-btn {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}
</style>
';

include __DIR__ . '/templates/header.php';
echo $custom_css;
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
                </div>            </div>            <!-- Process Log Section - Phase 1 UX Enhancement -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0"><i class="fas fa-clipboard-list me-2"></i>Process Log Management</h5>
                        <small class="text-muted">Current order process steps and status</small>
                    </div>
                    <div class="btn-group">
                        <button type="button" id="insert-process-btn" class="btn btn-warning btn-sm" onclick="showInsertProcessModal()">
                            <i class="fas fa-plus-circle me-1"></i>Insert Missing Process
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="editProcessTemplate()">
                            <i class="fas fa-edit me-1"></i>Edit Template
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="refreshProcessSteps()">
                            <i class="fas fa-sync me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="edit-mode-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1"><i class="fas fa-tools me-2"></i>Edit Mode: Production Order Process</h6>
                                <p class="mb-0">ในหน้านี้คุณสามารถแก้ไขสถานะและรายละเอียดของแต่ละ Process Step ได้ หากต้องการเพิ่ม/ลบ Process ให้ใช้ปุ่ม "Insert Missing Process" หรือ "Edit Template"</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="badge bg-light text-dark fs-6">Order: <?php echo htmlspecialchars($order['ProductionNumber']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="process-log-container">
                        <?php if (empty($logs)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-clipboard fa-3x mb-3"></i>
                                <h5>No Process Steps Found</h5>
                                <p>This order doesn't have any process steps yet.</p>
                                <button type="button" class="btn btn-primary" onclick="loadProcessFromTemplate()">
                                    <i class="fas fa-download me-2"></i>Load from Template
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $index => $log): ?>
                                <div class="process-step-row <?php echo getProcessStepStatus($log['Result']); ?>" data-step-id="<?php echo $log['SequenceNo']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-weight: bold;">
                                                <?php echo $log['SequenceNo']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <strong><?php echo htmlspecialchars($log['ProcessStepName']); ?></strong>
                                            <?php if ($log['DatePerformed']): ?>
                                                <br><small class="text-muted"><?php echo date('M j, Y', strtotime($log['DatePerformed'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="status-badge <?php echo getStatusBadgeClass($log['Result']); ?>">
                                                <?php echo $log['Result'] ?: 'Pending'; ?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if ($log['Operator_UserID']): ?>
                                                <small>Operator: <?php echo $log['Operator_UserID']; ?></small>
                                            <?php endif; ?>
                                            <?php if ($log['ControlValue'] || $log['ActualMeasuredValue']): ?>
                                                <br><small>Control: <?php echo $log['ControlValue']; ?> | Actual: <?php echo $log['ActualMeasuredValue']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?php if ($log['Remarks']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['Remarks']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editProcessStep(<?php echo $log['SequenceNo']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden inputs for form submission -->
                    <div id="hidden-process-data" style="display: none;">
                        <?php foreach ($logs as $index => $log): ?>
                            <input type="hidden" name="log[<?php echo $index; ?>][SequenceNo]" value="<?php echo $log['SequenceNo']; ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][ProcessStepName]" value="<?php echo htmlspecialchars($log['ProcessStepName']); ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][DatePerformed]" value="<?php echo $log['DatePerformed']; ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][Result]" value="<?php echo htmlspecialchars($log['Result']); ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][Operator_UserID]" value="<?php echo $log['Operator_UserID']; ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][Remarks]" value="<?php echo htmlspecialchars($log['Remarks']); ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][ControlValue]" value="<?php echo $log['ControlValue']; ?>">
                            <input type="hidden" name="log[<?php echo $index; ?>][ActualMeasuredValue]" value="<?php echo $log['ActualMeasuredValue']; ?>">
                        <?php endforeach; ?>
                    </div>                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-success">Update Order</button>
                <a href="view_order.php?pn=<?php echo urlencode($pn); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>    <script>
        let linerRowCount = <?php echo max(1, count($liners)); ?>;

        function addLinerRow() {
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

        function removeRow(button) {
            const row = button.closest('tr');
            row.remove();
        }

        // Phase 1 UX Improvement Functions
        function showInsertProcessModal() {
            showToast('Feature coming in Phase 2: Drag & Drop Process Insertion', 'info');
        }

        function editProcessTemplate() {
            const modelId = document.querySelector('[name="ModelID"]').value;
            if (!modelId) {
                showToast('Please select a Model first to edit its process template', 'warning');
                return;
            }
            window.open(`process_template_builder.php?model_id=${modelId}`, '_blank');
        }

        function refreshProcessSteps() {
            showLoading();
            fetch(`api/get_process_steps.php?pn=<?php echo urlencode($pn); ?>`)
                .then(r => r.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        location.reload(); // Refresh page to show updated steps
                    } else {
                        showToast(data.error || 'Failed to refresh process steps', 'error');
                    }
                })
                .catch(() => {
                    hideLoading();
                    showToast('Network error while refreshing', 'error');
                });
        }

        function editProcessStep(stepId) {
            showToast('Feature coming in Phase 2: Individual Process Step Editing', 'info');
        }

        function loadProcessFromTemplate() {
            const modelId = document.querySelector('[name="ModelID"]').value;
            if (!modelId) {
                showToast('Please select a Model first', 'warning');
                return;
            }
            
            showLoading();
            fetch(`api/load_process_template.php?model_id=${modelId}&pn=<?php echo urlencode($pn); ?>`)
                .then(r => r.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showToast('Process steps loaded from template');
                        location.reload();
                    } else {
                        showToast(data.error || 'Failed to load template', 'error');
                    }
                })
                .catch(() => {
                    hideLoading();
                    showToast('Network error', 'error');
                });
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
                }

                showLoading();
                fetch('api/edit_order.php?pn=<?php echo urlencode($pn); ?>', {
                    method: 'POST',
                    body: new FormData(form)
                })
                    .then(r => r.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showToast('Order updated successfully');
                            setTimeout(() => location.reload(), 1000);
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
    </script>
    </script>

<?php include __DIR__ . '/templates/footer.php'; ?>
