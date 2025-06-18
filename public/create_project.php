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
    $name = Database::sanitizeString($_POST['ProjectName'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare('INSERT INTO Projects (ProjectName) VALUES (?)');
        $stmt->execute([$name]);
        header('Location: manage_projects.php');
        exit;
    } else {
        $error = 'Project name is required';
    }
}

$page_title = 'Create Project';
$breadcrumbs = [
    ['title' => 'Manage Projects', 'url' => 'manage_projects.php'],
    ['title' => 'Create Project']
];
include 'templates/header.php';
?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">New Project</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Project Name</label>
                        <input type="text" name="ProjectName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save</button>
                    <a href="manage_projects.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
