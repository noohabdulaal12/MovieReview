<?php
session_start();

// only allow creator users to access this page
if (!isset($_SESSION['UserType']) || $_SESSION['UserType'] != 'creator') {
    header('Location: ../login.php');
    exit();
}

include_once '../includes/db_connect.php';

$displayUsername = $_SESSION['Username'];
$creatorId = $_SESSION['Id'];

$message = '';
$messageType = '';

// add movie
if (isset($_POST['add_movie'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $imageLink   = trim($_POST['image_link']);
    $videoLink   = trim($_POST['video_link']);
    $categoryId  = (int) $_POST['category_id'];
    $addDate     = date('Y-m-d');

    if ($title == '' || $description == '' || $imageLink == '' || $videoLink == '' || $categoryId == 0) {
        $message     = 'Please fill in all fields.';
        $messageType = 'danger';
    } else {
        // prepared statement to safely insert movie into database
        $sql  = 'INSERT INTO Movies (Title, Description, ImageLink, VideoLink, AdditionDate, CreatorId, CategoryId, ViewCount) VALUES (?, ?, ?, ?, ?, ?, ?, 0)';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssii', $title, $description, $imageLink, $videoLink, $addDate, $creatorId, $categoryId);

        if (mysqli_stmt_execute($stmt)) {
            $message     = 'Movie added successfully!';
            $messageType = 'success';
        } else {
            $message     = 'Could not add movie. Please try again.';
            $messageType = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// edit movie
if (isset($_POST['edit_movie'])) {
    $editId      = (int) $_POST['edit_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $imageLink   = trim($_POST['image_link']);
    $videoLink   = trim($_POST['video_link']);
    $categoryId  = (int) $_POST['category_id'];

    if ($title == '' || $description == '' || $imageLink == '' || $videoLink == '' || $categoryId == 0) {
        $message     = 'Please fill in all fields.';
        $messageType = 'danger';
    } else {
        // prepared statement to safely update movie and only allows creator to edit their own movies
        $sql  = 'UPDATE Movies SET Title=?, Description=?, ImageLink=?, VideoLink=?, CategoryId=? WHERE Id=? AND CreatorId=?';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssiii', $title, $description, $imageLink, $videoLink, $categoryId, $editId, $creatorId);

        if (mysqli_stmt_execute($stmt)) {
            $message     = 'Movie updated successfully!';
            $messageType = 'success';
        } else {
            $message     = 'Could not update movie. Please try again.';
            $messageType = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// delete movie
if (isset($_POST['delete_movie'])) {
    $deleteId = (int) $_POST['delete_id'];
    // prepared statement ensures creator can only delete their own movies
    $sql  = 'DELETE FROM Movies WHERE Id=? AND CreatorId=?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $deleteId, $creatorId);

    if (mysqli_stmt_execute($stmt)) {
        $message     = 'Movie deleted successfully.';
        $messageType = 'success';
    } else {
        $message     = 'Could not delete movie. Please try again.';
        $messageType = 'danger';
    }
    mysqli_stmt_close($stmt);
}

// fetch categories
// used to populate the category dropdown in add/edit forms
$categories = [];
$catResult  = mysqli_query($conn, 'SELECT Id, Category FROM Categories ORDER BY Category ASC');
while ($row = mysqli_fetch_assoc($catResult)) {
    $categories[] = $row;
}

// get creators own movies
// only gets movies belonging to the logged in creator using session id
$myMovies  = [];
$movieSql  = 'SELECT m.Id, m.Title, m.Description, m.ImageLink, m.VideoLink, m.AdditionDate, m.ViewCount, c.Category
              FROM Movies m
              INNER JOIN Categories c ON m.CategoryId = c.Id
              WHERE m.CreatorId = ?
              ORDER BY m.AdditionDate DESC';
$movieStmt = mysqli_prepare($conn, $movieSql);
mysqli_stmt_bind_param($movieStmt, 'i', $creatorId);
mysqli_stmt_execute($movieStmt);
$movieResult = mysqli_stmt_get_result($movieStmt);
while ($row = mysqli_fetch_assoc($movieResult)) {
    $myMovies[] = $row;
}
mysqli_stmt_close($movieStmt);

// get movie to edit
// loads existing movie data into the edit form when edit button is clicked
$editMovie = null;
if (isset($_GET['edit'])) {
    $editId     = (int) $_GET['edit'];
    $editSql    = 'SELECT * FROM Movies WHERE Id=? AND CreatorId=?';
    $editStmt   = mysqli_prepare($conn, $editSql);
    mysqli_stmt_bind_param($editStmt, 'ii', $editId, $creatorId);
    mysqli_stmt_execute($editStmt);
    $editResult = mysqli_stmt_get_result($editStmt);
    $editMovie  = mysqli_fetch_assoc($editResult);
    mysqli_stmt_close($editStmt);
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
            <a class="navbar-brand" href="../index.php"><img src="../images/logo.png" alt="Movie Review System" height="38"></a>
            <div class="ms-auto d-flex gap-2">
                <span class="text-light small mt-2">Hi, <?php echo htmlspecialchars($displayUsername); ?></span>
                <a class="btn btn-outline-light btn-sm" href="../index.php">Home</a>
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
                    <p class="text-muted">Welcome, <?php echo htmlspecialchars($displayUsername); ?>. Use this page to add and manage your movie content.</p>
                </div>

                <?php if ($message != '') { ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <!-- add movie section -->
                <section id="addMovie" class="dashboard-section mb-4">
                    <h2 class="h4">Add Movie</h2>
                    <form action="creator-dashboard.php" method="post">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Choose category</option>
                                <?php foreach ($categories as $cat) { ?>
                                    <option value="<?php echo $cat['Id']; ?>"><?php echo htmlspecialchars($cat['Category']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" class="form-control" name="image_link" placeholder="https://..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Video URL</label>
                            <input type="text" class="form-control" name="video_link" placeholder="https://youtube.com/..." required>
                        </div>
                        <button type="submit" name="add_movie" class="btn btn-primary">Add Movie</button>
                    </form>
                </section>

                <!-- edit movie section -->
                <section id="editMovie" class="dashboard-section mb-4">
                    <h2 class="h4">Edit Movie</h2>
                    <?php if ($editMovie != null) { ?>
                        <!-- edit form shown when a movie's edit button is clicked -->
                        <form action="creator-dashboard.php" method="post">
                            <input type="hidden" name="edit_id" value="<?php echo $editMovie['Id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editMovie['Title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($editMovie['Description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Choose category</option>
                                    <?php foreach ($categories as $cat) { ?>
                                        <option value="<?php echo $cat['Id']; ?>" <?php if ($cat['Id'] == $editMovie['CategoryId']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($cat['Category']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image URL</label>
                                <input type="text" class="form-control" name="image_link" value="<?php echo htmlspecialchars($editMovie['ImageLink']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Video URL</label>
                                <input type="text" class="form-control" name="video_link" value="<?php echo htmlspecialchars($editMovie['VideoLink']); ?>" required>
                            </div>
                            <button type="submit" name="edit_movie" class="btn btn-warning">Save Changes</button>
                            <a href="creator-dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </form>
                    <?php } else { ?>
                        <p class="text-muted">Click the <strong>Edit</strong> button next to a movie in "View Own Content" to edit it here.</p>
                    <?php } ?>
                </section>

                <!-- upload media section -->
                <section id="uploadMedia" class="dashboard-section mb-4">
                    <h2 class="h4">Upload Media</h2>
                    <p class="text-muted">To add media to your movies, paste an image or video URL in the Add Movie or Edit Movie form above. Supported formats: direct image URLs (jpg, png) and video links (YouTube, etc).</p>
                </section>

                <!-- view own content section -->
                <section id="ownContent" class="dashboard-section">
                    <h2 class="h4">View Own Content</h2>
                    <?php if (count($myMovies) == 0) { ?>
                        <div class="alert alert-info">You have not added any movies yet.</div>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Date Added</th>
                                        <th>Views</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myMovies as $movie) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($movie['Title']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['Category']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['AdditionDate']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['ViewCount']); ?></td>
                                            <td>
                                                <!-- edit button loads movie data into the edit movie section above -->
                                                <a href="creator-dashboard.php?edit=<?php echo $movie['Id']; ?>#editMovie" class="btn btn-warning btn-sm">Edit</a>
                                                <!-- delete button removes movie from database with confirmation -->
                                                <form action="creator-dashboard.php" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this movie?')">
                                                    <input type="hidden" name="delete_id" value="<?php echo $movie['Id']; ?>">
                                                    <button type="submit" name="delete_movie" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
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