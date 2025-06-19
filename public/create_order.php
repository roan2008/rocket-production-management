<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$productionNumber = Database::sanitizeString($_POST['ProductionNumber'] ?? '');
$emptyTube = Database::sanitizeString($_POST['EmptyTubeNumber'] ?? '');
$projectID = $_POST['ProjectID'] !== '' ? (int)$_POST['ProjectID'] : null;
$modelID = $_POST['ModelID'] !== '' ? (int)$_POST['ModelID'] : null;    if (!$productionNumber) {
        $error = 'กรุณาใส่ Production Number (Production Number is required)';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ProductionOrders WHERE ProductionNumber = ?');
        $stmt->execute([$productionNumber]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Production Number "' . $productionNumber . '" มีอยู่แล้ว กรุณาใช้หมายเลขอื่น (Production Number already exists, please use a different number)';
        }
    }

    if (!$error) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO ProductionOrders (ProductionNumber, EmptyTubeNumber, ProjectID, ModelID) VALUES (?, ?, ?, ?)');
        $stmt->execute([$productionNumber, $emptyTube, $projectID, $modelID]);

        if (!empty($_POST['liner'])) {
            $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
            foreach ($_POST['liner'] as $liner) {
                $linerType = Database::sanitizeString($liner['LinerType'] ?? '');
                if (!$linerType) {
                    continue;
                }
                $batch = Database::sanitizeString($liner['LinerBatchNumber'] ?? '');
                $remarks = Database::sanitizeString($liner['Remarks'] ?? '');
                $luStmt->execute([$productionNumber, $linerType, $batch, $remarks]);
            }
        }

        if (!empty($_POST['log'])) {
            $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($_POST['log'] as $log) {
                $logStmt->execute([
                    $productionNumber,
                    (int)($log['SequenceNo'] ?? 0),
                    Database::sanitizeString($log['ProcessStepName'] ?? ''),
                    $log['DatePerformed'] ?: null,
                    Database::sanitizeString($log['Result'] ?? ''),
                    $log['Operator_UserID'] !== '' ? (int)$log['Operator_UserID'] : null,
                    Database::sanitizeString($log['Remarks'] ?? ''),
                    $log['ControlValue'] !== '' ? $log['ControlValue'] : null,
                    $log['ActualMeasuredValue'] !== '' ? $log['ActualMeasuredValue'] : null,
                ]);
            }
        }

        $pdo->commit();
        header('Location: view_order.php?pn=' . urlencode($productionNumber));
        exit;
    }
}

$projects = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query('SELECT UserID, FullName FROM Users ORDER BY FullName')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Create Order';
$breadcrumbs = [
    ['title' => 'Create New Order']
];

// Phase 1 UX Improvements - Custom CSS for Template Preview
$custom_css = '
<style>
.template-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.process-step-item {
    background: white;
    transition: all 0.3s ease;
    border-left: 4px solid #007bff;
}

.process-step-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-left-color: #0056b3;
}

.step-number {
    min-width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.process-steps-preview {
    max-height: 400px;
    overflow-y: auto;
}

#edit-template-btn {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}

.template-preview .bg-light {
    background-color: #e9ecef !important;
    border: 1px dashed #dee2e6;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
</style>
';

include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-plus-circle me-2"></i>Create Production Order</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="create-order-form">
            <!-- Basic Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">                        <div class="col-md-6">                            <div class="mb-3">
                                <label class="form-label">
                                    <strong>Production Number</strong> <span class="text-danger">*</span>
                                    <a href="production_number_help.php" target="_blank" class="text-info ms-2" title="ดูคู่มือการใช้ Production Number">
                                        <i class="fas fa-question-circle"></i>
                                    </a>
                                </label>
                                <input type="text" name="ProductionNumber" id="productionNumber" class="form-control" required 
                                       placeholder="e.g., PO-2025-001, TOP-001, M2C-001">
                                <div id="productionNumberFeedback" class="mt-1"></div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Production Number ต้องไม่ซ้ำกับที่เคยสร้างแล้ว คุณสามารถใช้รูปแบบใดก็ได้ เช่น PO-2025-001, TOP-001, M2C-001 เป็นต้น
                                </small>
                                <div id="productionNumberSuggestions" class="mt-2" style="display: none;">
                                    <small class="text-muted">คำแนะนำ Production Number:</small>
                                    <div id="suggestionList" class="mt-1"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Empty Tube Number</label>
                                <input type="text" name="EmptyTubeNumber" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Project</strong> <span class="text-danger">*</span></label>
                                <select name="ProjectID" class="form-select" required onchange="loadModels(this.value)">
                                    <option value="">--Select Project--</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?php echo $p['ProjectID']; ?>"><?php echo htmlspecialchars($p['ProjectName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">                            <div class="mb-3">
                                <label class="form-label"><strong>Model</strong> <span class="text-danger">*</span></label>
                                <select name="ModelID" id="model" class="form-select" required onchange="loadProcessTemplate(this.value)">
                                    <option value="">--Select Project First--</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liner Usage Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-layer-group me-2"></i>Liner Usage</h5>
                    <button type="button" class="btn btn-sm btn-success" onclick="addLinerRow()">
                        <i class="fas fa-plus me-1"></i>Add Liner
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="liner-table" class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Type</th>
                                    <th>Batch Number</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="liner[0][LinerType]" class="form-control"></td>
                                    <td><input type="text" name="liner[0][LinerBatchNumber]" class="form-control"></td>
                                    <td><input type="text" name="liner[0][Remarks]" class="form-control"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLinerRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>            <!-- Process Log Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0"><i class="fas fa-clipboard-list me-2"></i>Process Log Template Preview</h5>
                        <small class="text-muted" id="template-info" style="display: none;">
                            <i class="fas fa-info-circle me-1"></i>
                            From template: <span id="template-name"></span>
                        </small>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="edit-template-btn" onclick="editCurrentTemplate()" style="display: none;">
                            <i class="fas fa-edit me-1"></i>Edit Template
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="refreshProcessTemplate()">
                            <i class="fas fa-sync me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="template-preview-content">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <p>กรุณาเลือก Project และ Model เพื่อแสดง Process Log Template</p>
                        </div>
                    </div>
                    
                    <!-- Hidden actual process log data for form submission -->
                    <div id="hidden-process-data" style="display: none;">
                        <!-- This will contain the actual form inputs -->
                    </div>
                </div>
            </div>
                                        <input type="text" name="log[0][ProcessStepName]" 
                                               class="form-control form-control-sm" placeholder="Enter process step name">
                                    </td>
                                    <td>
                                        <input type="date" name="log[0][DatePerformed]" 
                                               class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <select name="log[0][Result]" class="form-select form-select-sm">
                                            <option value="">--</option>
                                            <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                                            <option value="✗ แก้ไข">✗ แก้ไข</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="log[0][Operator_UserID]" class="form-select form-select-sm">
                                            <option value="">--</option>
                                            <?php foreach ($users as $u): ?>
                                                <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" name="log[0][ControlValue]" 
                                               class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" name="log[0][ActualMeasuredValue]" 
                                               class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="text" name="log[0][Remarks]" 
                                               class="form-control form-control-sm">                    
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Process Log จะถูกสร้างตาม Template ของ Model ที่เลือก
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                ต้องการแก้ไข Process? คลิก "Edit Template" 
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Create Order
                            </button>
                            <a href="index.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                        <small class="text-muted">
                            <span class="text-danger">*</span> Required fields
                        </small>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function loadModels(projectId) {
    const modelSelect = document.getElementById('model');
    modelSelect.innerHTML = '<option>Loading...</option>';
    fetch('models.php?project_id=' + projectId)
        .then(r => r.json())
        .then(data => {
            modelSelect.innerHTML = '<option value="">--Select Model--</option>';
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.ModelID;
                opt.textContent = m.ModelName;
                modelSelect.appendChild(opt);
            });
        })
        .catch(error => {
            console.error('Error loading models:', error);
            modelSelect.innerHTML = '<option value="">Error loading models</option>';
        });
}

async function loadProcessTemplate(modelId) {
    if (!modelId) {
        // Hide template info
        document.getElementById('template-info').style.display = 'none';
        return;
    }
    
    // Check if there are existing process log entries
    const existingRows = document.querySelectorAll('#log-table tbody tr').length;
    if (existingRows > 1) { // More than just the default empty row
        if (!confirm('Changing the model will replace current process log entries with the template. Continue?')) {
            // Reset model selection
            document.getElementById('model').value = '';
            return;
        }
    }
    
    try {
        const response = await fetch(`api/get_process_template.php?model_id=${modelId}`);
        const data = await response.json();
          if (data.error) {
            console.error('Error loading template:', data.error);
            showToast('Error loading process template', 'warning');
            return;
        }
        
        if (data.template && data.steps.length > 0) {
            // Show template info
            document.getElementById('template-name').textContent = data.template.template_name;
            document.getElementById('template-info').style.display = 'block';
            
            // Show Edit Template button
            const editBtn = document.getElementById('edit-template-btn');
            editBtn.style.display = 'block';
            editBtn.setAttribute('data-template-id', data.template.template_id);
            editBtn.setAttribute('data-model-id', modelId);
            
            // Show notification about template loading
            showToast(`Loading process template: ${data.template.template_name}`, 'info');
            
            // Display template preview
            displayTemplatePreview(data.steps, data.template);
            
            // Create hidden form inputs for submission
            createHiddenProcessInputs(data.steps);
            });
        } else {
            // No template available
            document.getElementById('template-info').style.display = 'none';
            showToast('No process template available for this model', 'warning');
        }
    } catch (error) {
        console.error('Error loading process template:', error);
        showToast('Failed to load process template', 'danger');
    }
}

function clearProcessLog() {
    const tbody = document.querySelector('#log-table tbody');
    tbody.innerHTML = '';
    processLogRowCount = 0;
}

function addProcessLogRowFromTemplate(step, index) {
    const table = document.getElementById('log-table').getElementsByTagName('tbody')[0];
    const row = table.insertRow();
    const isRequired = step.IsRequired == 1;
    
    row.innerHTML = `
        <td>
            <input type="number" name="log[${index}][SequenceNo]" 
                   value="${step.StepOrder}" readonly class="form-control form-control-sm">
        </td>
        <td>
            <input type="text" name="log[${index}][ProcessStepName]" 
                   value="${htmlspecialchars(step.StepName)}" 
                   class="form-control form-control-sm ${isRequired ? 'border-warning' : ''}" 
                   ${isRequired ? 'required' : ''}>
            ${isRequired ? '<small class="text-warning">Required</small>' : ''}
        </td>
        <td>
            <input type="date" name="log[${index}][DatePerformed]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <select name="log[${index}][Result]" class="form-select form-select-sm ${isRequired ? 'border-warning' : ''}">
                <option value="">--</option>
                <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                <option value="✗ แก้ไข">✗ แก้ไข</option>
            </select>
        </td>
        <td>
            <select name="log[${index}][Operator_UserID]" class="form-select form-select-sm">
                <option value="">--</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" step="0.001" name="log[${index}][ControlValue]" 
                   value="${step.DefaultValue || ''}"
                   class="form-control form-control-sm">
        </td>
        <td>
            <input type="number" step="0.001" name="log[${index}][ActualMeasuredValue]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <input type="text" name="log[${index}][Remarks]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProcessLogRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    processLogRowCount = Math.max(processLogRowCount, index + 1);
}

function htmlspecialchars(str) {
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

function addLinerRow() {
    const table = document.getElementById('liner-table').getElementsByTagName('tbody')[0];
    const rowCount = table.rows.length;
    const row = table.insertRow();
    row.innerHTML = `
        <td><input type="text" name="liner[${rowCount}][LinerType]" class="form-control"></td>
        <td><input type="text" name="liner[${rowCount}][LinerBatchNumber]" class="form-control"></td>
        <td><input type="text" name="liner[${rowCount}][Remarks]" class="form-control"></td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLinerRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
}

function removeLinerRow(button) {
    const row = button.closest('tr');
    const table = row.closest('tbody');
    
    // Don't allow removing the last row
    if (table.rows.length > 1) {
        row.remove();
        
        // Update the name attributes to maintain proper indexing
        Array.from(table.rows).forEach((row, index) => {
            const inputs = row.querySelectorAll('input');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('liner[')) {
                    const newName = name.replace(/liner\[\d+\]/, `liner[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });    } else {
        alert('You must have at least one liner row.');
    }
}

// Process Log Management Functions
let processLogRowCount = 1;

function addProcessLogRow() {
    const table = document.getElementById('log-table').getElementsByTagName('tbody')[0];
    const rowCount = table.rows.length;
    const row = table.insertRow();
    
    row.innerHTML = `
        <td>
            <input type="number" name="log[${rowCount}][SequenceNo]" 
                   value="${rowCount + 1}" readonly class="form-control form-control-sm">
        </td>
        <td>
            <input type="text" name="log[${rowCount}][ProcessStepName]" 
                   class="form-control form-control-sm" placeholder="Enter process step name">
        </td>
        <td>
            <input type="date" name="log[${rowCount}][DatePerformed]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <select name="log[${rowCount}][Result]" class="form-select form-select-sm">
                <option value="">--</option>
                <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                <option value="✗ แก้ไข">✗ แก้ไข</option>
            </select>
        </td>
        <td>
            <select name="log[${rowCount}][Operator_UserID]" class="form-select form-select-sm">
                <option value="">--</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" step="0.001" name="log[${rowCount}][ControlValue]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <input type="number" step="0.001" name="log[${rowCount}][ActualMeasuredValue]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <input type="text" name="log[${rowCount}][Remarks]" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProcessLogRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    processLogRowCount++;
}

function removeProcessLogRow(button) {
    const row = button.closest('tr');
    const table = row.closest('tbody');
    
    // Don't allow removing the last row
    if (table.rows.length > 1) {
        row.remove();
        
        // Update sequence numbers and name attributes
        Array.from(table.rows).forEach((row, index) => {
            // Update sequence number
            const seqInput = row.querySelector('input[name*="[SequenceNo]"]');
            if (seqInput) {
                seqInput.value = index + 1;
            }
            
            // Update all name attributes to maintain proper indexing
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('log[')) {
                    const newName = name.replace(/log\[\d+\]/, `log[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    } else {
        alert('You must have at least one process log row.');
    }
}

// Production Number Validation
    let validationTimeout;
    const productionNumberInput = document.getElementById('productionNumber');
    const feedbackDiv = document.getElementById('productionNumberFeedback');
    const suggestionsDiv = document.getElementById('productionNumberSuggestions');
    const suggestionList = document.getElementById('suggestionList');

    // Load initial suggestions
    loadSuggestions();

    productionNumberInput.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        const value = this.value.trim();
        
        if (value.length === 0) {
            feedbackDiv.innerHTML = '';
            suggestionsDiv.style.display = 'block';
            return;
        }

        feedbackDiv.innerHTML = '<small class="text-info"><i class="fas fa-spinner fa-spin"></i> Checking availability...</small>';
        
        validationTimeout = setTimeout(() => {
            checkProductionNumber(value);
        }, 500);
    });

    async function checkProductionNumber(productionNumber) {
        try {
            const response = await fetch(`api/production_number_helper.php?action=check&production_number=${encodeURIComponent(productionNumber)}`);
            const data = await response.json();
            
            if (data.available) {
                feedbackDiv.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> Production number is available</small>';
                suggestionsDiv.style.display = 'none';
            } else {
                feedbackDiv.innerHTML = `<small class="text-danger"><i class="fas fa-times"></i> ${data.message}</small>`;
                if (data.suggestions && data.suggestions.length > 0) {
                    showSuggestions(data.suggestions);
                    suggestionsDiv.style.display = 'block';
                }
            }
        } catch (error) {
            feedbackDiv.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Unable to check availability</small>';
        }
    }

    async function loadSuggestions() {
        try {
            const response = await fetch('api/production_number_helper.php?action=suggest');
            const data = await response.json();
            
            if (data.suggestions && data.suggestions.length > 0) {
                showSuggestions(data.suggestions);
                suggestionsDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Unable to load suggestions:', error);
        }
    }

    function showSuggestions(suggestions) {
        suggestionList.innerHTML = suggestions.map(suggestion => 
            `<button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1" onclick="selectSuggestion('${suggestion}')">${suggestion}</button>`
        ).join('');
    }

    function selectSuggestion(suggestion) {
        productionNumberInput.value = suggestion;
        productionNumberInput.focus();
        checkProductionNumber(suggestion);
    }

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-order-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const productionNumber = form.querySelector('input[name="ProductionNumber"]').value.trim();
        const projectId = form.querySelector('select[name="ProjectID"]').value;
        const modelId = form.querySelector('select[name="ModelID"]').value;

        if (!productionNumber) {
            showToast('Production Number is required', 'error');
            return;
        }
        if (!projectId) {
            showToast('Please select a Project', 'error');
            return;
        }
        if (!modelId) {
            showToast('Please select a Model', 'error');
            return;
        }

        showLoading();
        fetch('api/create_order.php', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Order created successfully!', 'success');
                window.location.href = 'view_order.php?pn=' + encodeURIComponent(data.production_number);
            } else {
                showToast(data.error || 'Error creating order', 'error');
            }
        })
        .catch(() => {
            hideLoading();
            showToast('Error creating order', 'error');
        });
    });
});

// Template Preview Functions for Phase 1 UX Improvements
function displayTemplatePreview(steps, template) {
    const previewContent = document.getElementById('template-preview-content');
    
    let previewHTML = `
        <div class="template-preview">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Process Steps Preview</h6>
                    <small class="text-muted">จาก Template: ${template.template_name}</small>
                </div>
                <span class="badge bg-info">${steps.length} Steps</span>
            </div>
            <div class="process-steps-preview">
    `;
    
    steps.forEach((step, index) => {
        const isRequired = step.IsRequired ? '<span class="badge badge-sm bg-warning ms-2">Required</span>' : '';
        const defaultValue = step.DefaultValue ? `<small class="text-muted">Default: ${step.DefaultValue}</small>` : '';
        
        previewHTML += `
            <div class="process-step-item mb-2 p-3 border rounded">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <span class="step-number me-3 badge bg-primary">${step.StepOrder}</span>
                            <strong>${step.StepName}</strong>
                            ${isRequired}
                        </div>
                        ${defaultValue ? `<div class="mt-1">${defaultValue}</div>` : ''}
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Ready to use</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    previewHTML += `
            </div>
            <div class="mt-3 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-8">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Process steps นี้จะถูกสร้างขึ้นใน Production Order อัตโนมัติ
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">
                            <i class="fas fa-edit me-1"></i>
                            ต้องการแก้ไข? คลิก "Edit Template"
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    previewContent.innerHTML = previewHTML;
}

function createHiddenProcessInputs(steps) {
    const hiddenContainer = document.getElementById('hidden-process-data');
    let hiddenHTML = '';
    
    steps.forEach((step, index) => {
        hiddenHTML += `
            <input type="hidden" name="log[${index}][SequenceNo]" value="${step.StepOrder}">
            <input type="hidden" name="log[${index}][ProcessStepName]" value="${step.StepName}">
            <input type="hidden" name="log[${index}][DatePerformed]" value="">
            <input type="hidden" name="log[${index}][Result]" value="">
            <input type="hidden" name="log[${index}][Operator_UserID]" value="">
            <input type="hidden" name="log[${index}][Remarks]" value="">
            <input type="hidden" name="log[${index}][ControlValue]" value="${step.DefaultValue || ''}">
            <input type="hidden" name="log[${index}][ActualMeasuredValue]" value="">
        `;
    });
    
    hiddenContainer.innerHTML = hiddenHTML;
}

function editCurrentTemplate() {
    const editBtn = document.getElementById('edit-template-btn');
    const templateId = editBtn.getAttribute('data-template-id');
    const modelId = editBtn.getAttribute('data-model-id');
    
    if (templateId && modelId) {
        // Open template builder in new tab/window
        const url = `process_template_builder.php?template_id=${templateId}&model_id=${modelId}&return_to=create_order`;
        window.open(url, '_blank');
    } else {
        showToast('Template information not available', 'error');
    }
}

function refreshProcessTemplate() {
    const modelSelect = document.getElementById('model');
    const selectedModelId = modelSelect.value;
    
    if (selectedModelId) {
        loadProcessTemplate(selectedModelId);
        showToast('Refreshing process template...', 'info');
    } else {
        showToast('Please select a model first', 'warning');
    }
}
</script>
<?php include 'templates/footer.php'; ?>
