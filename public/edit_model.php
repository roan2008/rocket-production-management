<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

$modelId = (int)($_GET['id'] ?? 0);
if (!$modelId) {
    header('Location: manage_models.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelName = Database::sanitizeString($_POST['ModelName']);
    $projectId = (int)$_POST['ProjectID'];
    
    try {
        $stmt = $pdo->prepare('UPDATE Models SET ModelName = ?, ProjectID = ? WHERE ModelID = ?');
        $stmt->execute([$modelName, $projectId, $modelId]);
        
        header('Location: manage_models.php?updated=1');
        exit;
        
    } catch (Exception $e) {
        $error = "Error updating model: " . $e->getMessage();
    }
}

// Get model details
$stmt = $pdo->prepare('
    SELECT m.*, p.ProjectName 
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

// Get projects for dropdown
$projects = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);

// Get templates for this model
$stmt = $pdo->prepare('
    SELECT pt.*, 
           (SELECT COUNT(*) FROM processtemplatesteps WHERE TemplateID = pt.TemplateID) as StepCount
    FROM processtemplates pt 
    WHERE pt.ModelID = ? 
    ORDER BY pt.TemplateName
');
$stmt->execute([$modelId]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Edit Model: ' . $model['ModelName'];
$breadcrumbs = [
    ['title' => 'Manage Models', 'url' => 'manage_models.php'],
    ['title' => 'Edit Model']
];
include 'templates/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-edit me-2"></i>Edit Model</h4>
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
                                <option value="<?= $project['ProjectID'] ?>" 
                                        <?= $project['ProjectID'] == $model['ProjectID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['ProjectName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ModelName" class="form-label">Model Name *</label>
                        <input type="text" name="ModelName" id="ModelName" class="form-control" required 
                               value="<?= htmlspecialchars($model['ModelName']) ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Update Model
                        </button>
                        <a href="manage_models.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-list me-2"></i>Process Templates</h4>
                <a href="process_template_builder.php?model_id=<?= $modelId ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>New Template
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($templates)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No templates created yet.</p>
                        <a href="process_template_builder.php?model_id=<?= $modelId ?>" class="btn btn-sm btn-success">
                            Create First Template
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($templates as $template): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($template['TemplateName']) ?></h6>
                                        <p class="mb-1 text-muted small"><?= htmlspecialchars($template['Description'] ?? 'No description') ?></p>
                                        <small class="text-muted"><?= $template['StepCount'] ?> steps</small>
                                        <?php if ($template['TemplateID'] == $model['DefaultTemplateID']): ?>
                                            <span class="badge bg-success ms-2">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="btn-group-vertical" role="group">
                                        <a href="process_template_builder.php?template_id=<?= $template['TemplateID'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
