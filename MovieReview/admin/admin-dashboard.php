<?php
session_start();

// only allow admin users to access this page
if (!isset($_SESSION['UserType']) || $_SESSION['UserType'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include_once '../includes/db_connect.php';

$displayUsername = $_SESSION['Username'];

$message = '';
$messageType = '';

// delete user
// admin can remove any user except their own account
if (isset($_POST['delete_user'])) {
    $deleteUserId = (int) $_POST['user_id'];
    if ($deleteUserId == $_SESSION['Id']) {
        $message     = 'You cannot delete your own account.';
        $messageType = 'danger';
    } else {
        $sql  = 'DELETE FROM S2G1Users WHERE Id = ?';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $deleteUserId);
        if (mysqli_stmt_execute($stmt)) {
            $message     = 'User deleted successfully.';
            $messageType = 'success';
        } else {
            $message     = 'Could not delete user.';
            $messageType = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// delete movie
// admin can remove any movie from the system regardless of who created it
if (isset($_POST['delete_movie'])) {
    $deleteMovieId = (int) $_POST['movie_id'];
    $sql  = 'DELETE FROM S2G1Movies WHERE Id = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $deleteMovieId);
    if (mysqli_stmt_execute($stmt)) {
        $message     = 'Movie removed successfully.';
        $messageType = 'success';
    } else {
        $message     = 'Could not remove movie.';
        $messageType = 'danger';
    }
    mysqli_stmt_close($stmt);
}

// get all users for the manage users table
$users = [];
$userResult = mysqli_query($conn, 'SELECT Id, Username, UserType, CreatedAt FROM S2G1Users ORDER BY CreatedAt DESC');
while ($row = mysqli_fetch_assoc($userResult)) {
    $users[] = $row;
}

// get all movies with creator name and category for the manage movies table
$movies = [];
$movieSql = 'SELECT m.Id, m.Title, m.AdditionDate, m.ViewCount, c.Category, u.Username
             FROM S2G1Movies m
             INNER JOIN S2G1Categories c ON m.CategoryId = c.Id
             INNER JOIN S2G1Users u ON m.CreatorId = u.Id
             ORDER BY m.AdditionDate DESC';
$movieResult = mysqli_query($conn, $movieSql);
while ($row = mysqli_fetch_assoc($movieResult)) {
    $movies[] = $row;
}

// report 1 = most popular movies filtered by date range ordered by view count
$reportMovies = [];
$reportStartDate = '';
$reportEndDate   = '';
if (isset($_POST['report_popular'])) {
    $reportStartDate = trim($_POST['report_start_date']);
    $reportEndDate   = trim($_POST['report_end_date']);

    if ($reportStartDate != '' && $reportEndDate != '') {
        $reportSql  = 'SELECT m.Title, m.ViewCount, m.AdditionDate, u.Username
                       FROM S2G1Movies m
                       INNER JOIN S2G1Users u ON m.CreatorId = u.Id
                       WHERE m.AdditionDate BETWEEN ? AND ?
                       ORDER BY m.ViewCount DESC';
        $reportStmt = mysqli_prepare($conn, $reportSql);
        mysqli_stmt_bind_param($reportStmt, 'ss', $reportStartDate, $reportEndDate);
        mysqli_stmt_execute($reportStmt);
        $reportResult = mysqli_stmt_get_result($reportStmt);
        while ($row = mysqli_fetch_assoc($reportResult)) {
            $reportMovies[] = $row;
        }
        mysqli_stmt_close($reportStmt);
    }
}

// report 2 = all movies by a specific creator username
$creatorMovies = [];
$searchCreator = '';
if (isset($_POST['report_creator'])) {
    $searchCreator = trim($_POST['creator_username']);

    if ($searchCreator != '') {
        $creatorSql  = 'SELECT m.Title, m.AdditionDate, m.ViewCount, c.Category
                        FROM S2G1Movies m
                        INNER JOIN S2G1Users u ON m.CreatorId = u.Id
                        INNER JOIN S2G1Categories c ON m.CategoryId = c.Id
                        WHERE u.Username LIKE ?
                        ORDER BY m.AdditionDate DESC';
        $creatorStmt = mysqli_prepare($conn, $creatorSql);
        $likeSearch  = '%' . $searchCreator . '%';
        mysqli_stmt_bind_param($creatorStmt, 's', $likeSearch);
        mysqli_stmt_execute($creatorStmt);
        $creatorResult = mysqli_stmt_get_result($creatorStmt);
        while ($row = mysqli_fetch_assoc($creatorResult)) {
            $creatorMovies[] = $row;
        }
        mysqli_stmt_close($creatorStmt);
    }
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
                    <p class="text-muted">Welcome, <?php echo htmlspecialchars($displayUsername); ?>. Manage users, movies and view reports.</p>
                </div>

                <?php if ($message != '') { ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <!-- manage users section -->
                <section id="manageUsers" class="dashboard-section mb-4">
                    <h2 class="h4">Manage Users</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user) { ?>
                                    <tr>
                                        <td><?php echo $user['Id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td>
                                            <!-- badge colour shows user role at a glance -->
                                            <?php if ($user['UserType'] == 'admin') { ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php } elseif ($user['UserType'] == 'creator') { ?>
                                                <span class="badge bg-primary">Creator</span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary">Visitor</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['CreatedAt']); ?></td>
                                        <td>
                                            <?php if ($user['Id'] != $_SESSION['Id']) { ?>
                                                <!-- prevent admin from deleting their own account -->
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($user['Username']); ?>?')">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['Id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            <?php } else { ?>
                                                <span class="text-muted small">Your account</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- manage movies section -->
                <section id="manageMovies" class="dashboard-section mb-4">
                    <h2 class="h4">Manage Movies</h2>
                    <?php if (count($movies) == 0) { ?>
                        <div class="alert alert-info">No movies found.</div>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Creator</th>
                                        <th>Date Added</th>
                                        <th>Views</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movies as $movie) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($movie['Title']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['Category']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['Username']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['AdditionDate']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['ViewCount']); ?></td>
                                            <td>
                                                <!-- admins can remove any movie regardless of who created it -->
                                                <form method="post" class="d-inline" onsubmit="return confirm('Remove this movie from the system?')">
                                                    <input type="hidden" name="movie_id" value="<?php echo $movie['Id']; ?>">
                                                    <button type="submit" name="delete_movie" class="btn btn-danger btn-sm">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </section>

                <!-- reports section -->
                <section id="reports" class="dashboard-section">
                    <h2 class="h4">Reports</h2>

                    <!-- report 1 most popular movies by date range -->
                    <div class="card mb-4">
                        <div class="card-header"><strong>Report 1: Most Popular Movies by Date Range</strong></div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">From Date</label>
                                        <input type="date" class="form-control" name="report_start_date" value="<?php echo htmlspecialchars($reportStartDate); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">To Date</label>
                                        <input type="date" class="form-control" name="report_end_date" value="<?php echo htmlspecialchars($reportEndDate); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="report_popular" class="btn btn-primary w-100">Generate Report</button>
                                    </div>
                                </div>
                            </form>

                            <?php if (isset($_POST['report_popular'])) { ?>
                                <hr>
                                <?php if (count($reportMovies) == 0) { ?>
                                    <div class="alert alert-info mb-0">No movies found in this date range.</div>
                                <?php } else { ?>
                                    <table class="table table-bordered mt-2">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Title</th>
                                                <th>Creator</th>
                                                <th>Date Added</th>
                                                <th>View Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportMovies as $r) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($r['Title']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['Username']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['AdditionDate']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['ViewCount']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- report 2 movies by a specific creator -->
                    <div class="card">
                        <div class="card-header"><strong>Report 2: Movies by Specific Creator</strong></div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label">Creator Username</label>
                                        <input type="text" class="form-control" name="creator_username" placeholder="Enter username..." value="<?php echo htmlspecialchars($searchCreator); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="report_creator" class="btn btn-primary w-100">Generate Report</button>
                                    </div>
                                </div>
                            </form>

                            <?php if (isset($_POST['report_creator'])) { ?>
                                <hr>
                                <?php if (count($creatorMovies) == 0) { ?>
                                    <div class="alert alert-info mb-0">No movies found for this creator.</div>
                                <?php } else { ?>
                                    <table class="table table-bordered mt-2">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Date Added</th>
                                                <th>View Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($creatorMovies as $r) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($r['Title']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['Category']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['AdditionDate']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['ViewCount']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                </section>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // highlight sidebar link when clicked
        document.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-link').forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>