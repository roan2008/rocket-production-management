<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
$project = $pdo->prepare('SELECT ProjectName FROM Projects WHERE ProjectID = ?');
$project->execute([$id]);
$project = $project->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    header('Location: manage_projects.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = Database::sanitizeString($_POST['ProjectName'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare('UPDATE Projects SET ProjectName = ? WHERE ProjectID = ?');
        $stmt->execute([$name, $id]);
        header('Location: manage_projects.php');
        exit;
    } else {
        $error = 'Project name is required';
    }
}

$page_title = 'Edit Project';
$breadcrumbs = [
    ['title' => 'Manage Projects', 'url' => 'manage_projects.php'],
    ['title' => 'Edit Project']
];
include 'templates/header.php';
?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Project</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Project Name</label>
                        <input type="text" name="ProjectName" value="<?php echo htmlspecialchars($project['ProjectName']); ?>" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Save</button>
                    <a href="manage_projects.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
