<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">RPS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="create_order.php">Create Order</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="manage_projects.php">Projects</a>
        </li>
      </ul>
      <div class="d-flex">
        <?php if (isset($_SESSION['UserID'])): ?>
          <span class="navbar-text me-3">Logged in as <?= htmlspecialchars($_SESSION['Role']) ?></span>
          <a class="btn btn-outline-danger" href="logout.php">Logout</a>
        <?php else: ?>
          <a class="btn btn-outline-primary" href="login.php">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
