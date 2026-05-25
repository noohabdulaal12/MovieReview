<?php
session_start();

$allowTesting = true;

if (!$allowTesting && (!isset($_SESSION['UserType']) || $_SESSION['UserType'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$displayUsername = 'Test Admin';
if (isset($_SESSION['Username'])) {
    $displayUsername = $_SESSION['Username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Movie Review System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">Movie Review System</a>
            <div class="ms-auto">
                <a class="btn btn-outline-light btn-sm" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <aside class="col-md-3 col-lg-2 sidebar p-3">
                <h2 class="h5 mb-3">Admin Menu</h2>
                <ul class="nav nav-pills flex-column gap-2">
                    <li class="nav-item"><a class="nav-link active" href="#manageUsers">Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="#manageMovies">Manage Movies</a></li>
                    <li class="nav-item"><a class="nav-link" href="#reports">Reports</a></li>
                </ul>
            </aside>

            <main class="col-md-9 col-lg-10 p-4">
                <div class="mb-4">
                    <h1 class="h3">Admin Dashboard</h1>
                    <p class="text-muted">Welcome, <?php echo htmlspecialchars($displayUsername); ?>. Use this page later to manage users, movies, and reports.</p>
                </div>

                <section id="manageUsers" class="dashboard-section mb-4">
                    <h2 class="h4">Manage Users</h2>
                    <p class="mb-0">Placeholder area for viewing, adding, editing, or removing users.</p>
                </section>

                <section id="manageMovies" class="dashboard-section mb-4">
                    <h2 class="h4">Manage Movies</h2>
                    <p class="mb-0">Placeholder area for approving, editing, or deleting movie records.</p>
                </section>

                <section id="reports" class="dashboard-section">
                    <h2 class="h4">Reports</h2>
                    <p class="mb-0">Placeholder area for system reports, review activity, and user activity.</p>
                </section>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
