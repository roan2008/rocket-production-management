<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

// Get all models with their projects and template info
$query = "
    SELECT 
        m.ModelID, 
        m.ModelName, 
        p.ProjectName,
        pt.TemplateName,
        pt.TemplateID,
        (SELECT COUNT(*) FROM processtemplates WHERE ModelID = m.ModelID) as TemplateCount
    FROM Models m
    LEFT JOIN Projects p ON m.ProjectID = p.ProjectID
    LEFT JOIN processtemplates pt ON m.DefaultTemplateID = pt.TemplateID
    ORDER BY p.ProjectName, m.ModelName
";

$models = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Manage Models';
$breadcrumbs = [['title' => 'Manage Models']];
include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-cog me-2"></i>Model Management</h1>
            <div>
                <a href="create_model.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Create New Model
                </a>
                <a href="process_template_builder.php" class="btn btn-info">
                    <i class="fas fa-tools me-2"></i>Template Builder
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Project</th>
                        <th>Model Name</th>
                        <th>Default Template</th>
                        <th>Templates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($models as $model): ?>
                    <tr>
                        <td>
                            <span class="badge bg-primary"><?= htmlspecialchars($model['ProjectName'] ?? 'No Project') ?></span>
                        </td>
                        <td><?= htmlspecialchars($model['ModelName']) ?></td>
                        <td>
                            <?php if ($model['TemplateName']): ?>
                                <span class="badge bg-success"><?= htmlspecialchars($model['TemplateName']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning">No Default</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $model['TemplateCount'] ?> template(s)</span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="edit_model.php?id=<?= $model['ModelID'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="process_template_builder.php?model_id=<?= $model['ModelID'] ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-list"></i> Templates
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($models)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4>No Models Found</h4>
            <p class="text-muted">Create your first model to get started.</p>
            <a href="create_model.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Create New Model
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
