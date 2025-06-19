<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelName = Database::sanitizeString($_POST['ModelName']);
    $projectId = (int)$_POST['ProjectID'];
    $templateName = Database::sanitizeString($_POST['TemplateName']);
    $templateDesc = Database::sanitizeString($_POST['TemplateDescription']);
    
    try {
        $pdo->beginTransaction();
        
        // Insert model
        $stmt = $pdo->prepare('INSERT INTO Models (ModelName, ProjectID) VALUES (?, ?)');
        $stmt->execute([$modelName, $projectId]);
        $modelId = $pdo->lastInsertId();
        
        // Insert default template if provided
        if (!empty($templateName)) {
            $stmt = $pdo->prepare('INSERT INTO processtemplates (ModelID, TemplateName, Description) VALUES (?, ?, ?)');
            $stmt->execute([$modelId, $templateName, $templateDesc]);
            $templateId = $pdo->lastInsertId();
            
            // Set as default template
            $stmt = $pdo->prepare('UPDATE Models SET DefaultTemplateID = ? WHERE ModelID = ?');
            $stmt->execute([$templateId, $modelId]);
            
            // Add default template steps
            $defaultSteps = [
                ['Visual Inspection', 1, true],
                ['Weight Measurement', 2, true], 
                ['Quality Check', 3, true],
                ['Final Sign-off', 4, true]
            ];
            
            $stmt = $pdo->prepare('INSERT INTO processtemplatesteps (TemplateID, StepName, StepOrder, IsRequired) VALUES (?, ?, ?, ?)');
            foreach ($defaultSteps as $step) {
                $stmt->execute([$templateId, $step[0], $step[1], $step[2]]);
            }
        }
        
        $pdo->commit();
        header('Location: manage_models.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = "Error creating model: " . $e->getMessage();
    }
}

// Get projects for dropdown
$projects = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Create New Model';
$breadcrumbs = [
    ['title' => 'Manage Models', 'url' => 'manage_models.php'],
    ['title' => 'Create New Model']
];
include 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-plus me-2"></i>Create New Model</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="ProjectID" class="form-label">Project *</label>
                        <select name="ProjectID" id="ProjectID" class="form-select" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['ProjectID'] ?>"><?= htmlspecialchars($project['ProjectName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ModelName" class="form-label">Model Name *</label>
                        <input type="text" name="ModelName" id="ModelName" class="form-control" required 
                               placeholder="e.g., Apollo-V3, Mars-Rover-2">
                    </div>

                    <hr>
                    <h5>Default Process Template (Optional)</h5>
                    <p class="text-muted">Create a default process template for this model. You can modify it later.</p>

                    <div class="mb-3">
                        <label for="TemplateName" class="form-label">Template Name</label>
                        <input type="text" name="TemplateName" id="TemplateName" class="form-control" 
                               placeholder="e.g., Standard Process, QC Process">
                    </div>

                    <div class="mb-3">
                        <label for="TemplateDescription" class="form-label">Template Description</label>
                        <textarea name="TemplateDescription" id="TemplateDescription" class="form-control" rows="3"
                                  placeholder="Describe the purpose of this template..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        If you create a template, the following default steps will be added:
                        <ul class="mb-0 mt-2">
                            <li>Visual Inspection (Required)</li>
                            <li>Weight Measurement (Required)</li>
                            <li>Quality Check (Required)</li>
                            <li>Final Sign-off (Required)</li>
                        </ul>
                        You can modify these steps later using the Template Builder.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Create Model
                        </button>
                        <a href="manage_models.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
