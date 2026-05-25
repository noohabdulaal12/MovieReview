<?php
session_start();

$allowTesting = true;

if (!$allowTesting && (!isset($_SESSION['UserType']) || $_SESSION['UserType'] != 'creator')) {
    header('Location: ../login.php');
    exit();
}

$displayUsername = 'Test Creator';
if (isset($_SESSION['Username'])) {
    $displayUsername = $_SESSION['Username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard - Movie Review System</title>
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
                <h2 class="h5 mb-3">Creator Menu</h2>
                <ul class="nav nav-pills flex-column gap-2">
                    <li class="nav-item"><a class="nav-link active" href="#addMovie">Add Movie</a></li>
                    <li class="nav-item"><a class="nav-link" href="#editMovie">Edit Movie</a></li>
                    <li class="nav-item"><a class="nav-link" href="#uploadMedia">Upload Media</a></li>
                    <li class="nav-item"><a class="nav-link" href="#ownContent">View Own Content</a></li>
                </ul>
            </aside>

            <main class="col-md-9 col-lg-10 p-4">
                <div class="mb-4">
                    <h1 class="h3">Creator Dashboard</h1>
                    <p class="text-muted">Welcome, <?php echo htmlspecialchars($displayUsername); ?>. Use this page later to add and manage your movie content.</p>
                </div>

                <section id="addMovie" class="dashboard-section mb-4">
                    <h2 class="h4">Add Movie</h2>
                    <p class="mb-0">Placeholder area for a movie submission form.</p>
                </section>

                <section id="editMovie" class="dashboard-section mb-4">
                    <h2 class="h4">Edit Movie</h2>
                    <p class="mb-0">Placeholder area for editing movie details.</p>
                </section>

                <section id="uploadMedia" class="dashboard-section mb-4">
                    <h2 class="h4">Upload Media</h2>
                    <p class="mb-0">Placeholder area for uploading posters, trailers, or other media files.</p>
                </section>

                <section id="ownContent" class="dashboard-section">
                    <h2 class="h4">View Own Content</h2>
                    <p class="mb-0">Placeholder area for viewing content created by the logged-in creator.</p>
                </section>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
