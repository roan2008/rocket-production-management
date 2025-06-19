<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

$templateId = (int)($_GET['template_id'] ?? 0);
$modelId = (int)($_GET['model_id'] ?? 0);
$mode = $templateId ? 'edit' : 'create';

// If editing, get template details
if ($mode === 'edit') {
    $stmt = $pdo->prepare('
        SELECT pt.*, m.ModelName, p.ProjectName
        FROM processtemplates pt
        JOIN Models m ON pt.ModelID = m.ModelID
        LEFT JOIN Projects p ON m.ProjectID = p.ProjectID
        WHERE pt.TemplateID = ?
    ');
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        header('Location: manage_models.php');
        exit;
    }
    
    $modelId = $template['ModelID'];
    
    // Get template steps
    $stmt = $pdo->prepare('
        SELECT * FROM processtemplatesteps 
        WHERE TemplateID = ? 
        ORDER BY StepOrder
    ');
    $stmt->execute([$templateId]);
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Creating new template
    if (!$modelId) {
        header('Location: manage_models.php');
        exit;
    }
    
    $stmt = $pdo->prepare('
        SELECT m.ModelName, p.ProjectName
        FROM Models m
        LEFT JOIN Projects p ON m.ProjectID = p.ProjectID
        WHERE m.ModelID = ?
    ');
    $stmt->execute([$modelId]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$model) {
        header('Location: manage_models.php');
        exit;
    }
    
    $template = ['ModelName' => $model['ModelName'], 'ProjectName' => $model['ProjectName']];
    $steps = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateName = Database::sanitizeString($_POST['templateName']);
    $templateDesc = Database::sanitizeString($_POST['templateDesc']);
    $stepsData = json_decode($_POST['stepsData'], true);
    
    try {
        $pdo->beginTransaction();
        
        if ($mode === 'create') {
            // Create new template
            $stmt = $pdo->prepare('INSERT INTO processtemplates (ModelID, TemplateName, Description) VALUES (?, ?, ?)');
            $stmt->execute([$modelId, $templateName, $templateDesc]);
            $templateId = $pdo->lastInsertId();
        } else {
            // Update existing template
            $stmt = $pdo->prepare('UPDATE processtemplates SET TemplateName = ?, Description = ? WHERE TemplateID = ?');
            $stmt->execute([$templateName, $templateDesc, $templateId]);
            
            // Delete existing steps
            $stmt = $pdo->prepare('DELETE FROM processtemplatesteps WHERE TemplateID = ?');
            $stmt->execute([$templateId]);
        }
        
        // Insert steps
        $stmt = $pdo->prepare('INSERT INTO processtemplatesteps (TemplateID, StepOrder, StepName, DefaultValue, IsRequired) VALUES (?, ?, ?, ?, ?)');
        foreach ($stepsData as $index => $step) {
            $stmt->execute([
                $templateId,
                $index + 1,
                $step['name'],
                $step['defaultValue'] ?: null,
                $step['required'] ? 1 : 0
            ]);
        }
        
        $pdo->commit();
        header('Location: edit_model.php?id=' . $modelId . '&template_saved=1');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = "Error saving template: " . $e->getMessage();
    }
}

$page_title = ($mode === 'edit' ? 'Edit' : 'Create') . ' Process Template';
$breadcrumbs = [
    ['title' => 'Manage Models', 'url' => 'manage_models.php'],
    ['title' => $template['ModelName'], 'url' => 'edit_model.php?id=' . $modelId],
    ['title' => ($mode === 'edit' ? 'Edit' : 'Create') . ' Template']
];
include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>
                    <i class="fas fa-tools me-2"></i>
                    <?= $mode === 'edit' ? 'Edit' : 'Create' ?> Process Template
                </h4>
                <p class="mb-0 text-muted">
                    Project: <strong><?= htmlspecialchars($template['ProjectName'] ?? 'Unknown') ?></strong> | 
                    Model: <strong><?= htmlspecialchars($template['ModelName']) ?></strong>
                </p>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form id="templateForm" method="post">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="templateName" class="form-label">Template Name *</label>
                            <input type="text" id="templateName" name="templateName" class="form-control" required
                                   value="<?= htmlspecialchars($template['TemplateName'] ?? '') ?>"
                                   placeholder="e.g., Standard Process, QC Process">
                        </div>
                        <div class="col-md-6">
                            <label for="templateDesc" class="form-label">Description</label>
                            <textarea id="templateDesc" name="templateDesc" class="form-control" rows="2"
                                      placeholder="Describe the purpose of this template..."><?= htmlspecialchars($template['Description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-list me-2"></i>Process Steps</h5>
                        <button type="button" id="addStep" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i>Add Step
                        </button>
                    </div>

                    <div id="stepsContainer" class="mb-4">
                        <!-- Steps will be dynamically added here -->
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tips:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Drag and drop to reorder steps</li>
                            <li>Required steps must be filled out when creating orders</li>
                            <li>Default values will be pre-populated in order forms</li>
                        </ul>
                    </div>

                    <input type="hidden" name="stepsData" id="stepsData">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Template
                        </button>
                        <a href="edit_model.php?id=<?= $modelId ?>" class="btn btn-secondary">Cancel</a>
                        <?php if ($mode === 'edit'): ?>
                            <button type="button" class="btn btn-info ms-auto" id="previewBtn">
                                <i class="fas fa-eye me-2"></i>Preview
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Step Template -->
<template id="stepTemplate">
    <div class="step-item border rounded p-3 mb-3" data-step-index="">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="d-flex align-items-center">
                <span class="drag-handle me-2" style="cursor: move;">
                    <i class="fas fa-grip-vertical text-muted"></i>
                </span>
                <span class="step-number badge bg-primary me-2">1</span>
                <h6 class="mb-0">Step Name</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-step">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Step Name *</label>
                <input type="text" class="form-control step-name" placeholder="e.g., Visual Inspection" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Default Value</label>
                <input type="text" class="form-control step-default" placeholder="Optional default value">
            </div>
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input class="form-check-input step-required" type="checkbox">
                    <label class="form-check-label">Required Step</label>
                </div>
            </div>
        </div>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stepsContainer = document.getElementById('stepsContainer');
    const addStepBtn = document.getElementById('addStep');
    const stepTemplate = document.getElementById('stepTemplate');
    const form = document.getElementById('templateForm');
    
    // Initialize with existing steps
    const existingSteps = <?= json_encode($steps) ?>;
    
    existingSteps.forEach(step => {
        addStep(step.StepName, step.DefaultValue, step.IsRequired);
    });
    
    // If no existing steps, add one empty step
    if (existingSteps.length === 0) {
        addStep();
    }
    
    // Make container sortable
    new Sortable(stepsContainer, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: updateStepNumbers
    });
    
    // Add step button
    addStepBtn.addEventListener('click', () => addStep());
    
    // Form submission
    form.addEventListener('submit', function(e) {
        const steps = collectStepsData();
        if (steps.length === 0) {
            e.preventDefault();
            alert('Please add at least one step to the template.');
            return;
        }
        
        document.getElementById('stepsData').value = JSON.stringify(steps);
    });
    
    function addStep(name = '', defaultValue = '', required = false) {
        const stepElement = stepTemplate.content.cloneNode(true);
        const stepDiv = stepElement.querySelector('.step-item');
        
        // Set values
        stepDiv.querySelector('.step-name').value = name;
        stepDiv.querySelector('.step-default').value = defaultValue;
        stepDiv.querySelector('.step-required').checked = required;
        
        // Add remove handler
        stepDiv.querySelector('.remove-step').addEventListener('click', function() {
            stepDiv.remove();
            updateStepNumbers();
        });
        
        stepsContainer.appendChild(stepElement);
        updateStepNumbers();
    }
    
    function updateStepNumbers() {
        const steps = stepsContainer.querySelectorAll('.step-item');
        steps.forEach((step, index) => {
            step.querySelector('.step-number').textContent = index + 1;
            step.dataset.stepIndex = index;
        });
    }
    
    function collectStepsData() {
        const steps = [];
        stepsContainer.querySelectorAll('.step-item').forEach(step => {
            const name = step.querySelector('.step-name').value.trim();
            if (name) {
                steps.push({
                    name: name,
                    defaultValue: step.querySelector('.step-default').value.trim(),
                    required: step.querySelector('.step-required').checked
                });
            }
        });
        return steps;
    }
});
</script>

<?php include 'templates/footer.php'; ?>
