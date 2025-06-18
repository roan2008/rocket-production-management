<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ProjectName'])) {
    $name = Database::sanitizeString($_POST['ProjectName']);
    if ($name !== '') {
        $stmt = $pdo->prepare('INSERT INTO Projects (ProjectName) VALUES (?)');
        $stmt->execute([$name]);
        header('Location: manage_projects.php');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM Projects WHERE ProjectID = ?');
    $stmt->execute([$id]);
    header('Location: manage_projects.php');
    exit;
}

$projects = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Manage Projects';
$breadcrumbs = [['title' => 'Manage Projects']];
include 'templates/header.php';
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-rocket me-2"></i>Projects</h1>
        </div>
        <form method="post" class="mb-3 d-flex">
            <input type="text" name="ProjectName" class="form-control me-2" placeholder="New Project Name" required>
            <button type="submit" class="btn btn-success">Add Project</button>
        </form>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projects as $proj): ?>
                    <tr>
                        <td><?php echo $proj['ProjectID']; ?></td>
                        <td><?php echo htmlspecialchars($proj['ProjectName']); ?></td>
                        <td>
                            <a href="?delete=<?php echo $proj['ProjectID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete project?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
