<?php
include_once 'includes/db_connect.php';

$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$categoryId = 0;
if (isset($_GET['category'])) {
    $categoryId = (int) $_GET['category'];
}

$categorySql = 'SELECT Id, CategoryName FROM mr_Categories ORDER BY CategoryName ASC';
$categoryResult = mysqli_query($conn, $categorySql);

$movieSql = "SELECT Id, Title, Description, ImageLink, VideoLink, AdditionDate
             FROM mr_Movies
             WHERE Status = 'published'";

if ($categoryId > 0) {
    $movieSql .= " AND CategoryId = " . $categoryId;
}

if ($search != '') {
    $movieSql .= " AND (Title LIKE ? OR Description LIKE ?)";
}

$movieSql .= " ORDER BY AdditionDate DESC";

if ($search != '') {
    $movieStmt = mysqli_prepare($conn, $movieSql);
    $searchText = '%' . $search . '%';
    mysqli_stmt_bind_param($movieStmt, 'ss', $searchText, $searchText);
    mysqli_stmt_execute($movieStmt);
    mysqli_stmt_bind_result($movieStmt, $movieId, $title, $description, $imageLink, $videoLink, $additionDate);
} else {
    $movieResult = mysqli_query($conn, $movieSql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Review System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Movie Review System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php if ($categoryId == 0) { echo 'active'; } ?>" href="index.php">Home</a>
                    </li>

                    <?php if ($categoryResult && mysqli_num_rows($categoryResult) > 0) { ?>
                        <?php while ($category = mysqli_fetch_assoc($categoryResult)) { ?>
                            <li class="nav-item">
                                <a class="nav-link <?php if ($categoryId == $category['Id']) { echo 'active'; } ?>" href="index.php?category=<?php echo $category['Id']; ?>">
                                    <?php echo htmlspecialchars($category['CategoryName']); ?>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>

                <div class="d-flex gap-2">
                    <a class="btn btn-outline-light btn-sm" href="login.php">Login</a>
                    <a class="btn btn-primary btn-sm" href="signup.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row mb-4">
            <div class="col-lg-8">
                <h1 class="h2">Published Movies</h1>
                <p class="text-muted">Browse the latest movies.</p>
            </div>
            <div class="col-lg-4">
                <form action="index.php" method="get" class="d-flex gap-2">
                    <?php if ($categoryId > 0) { ?>
                        <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                    <?php } ?>
                    <input type="text" class="form-control" name="search" placeholder="Search movies" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <?php if ($search == '' && !$movieResult) { ?>
            <div class="alert alert-danger">
                There was an error loading movies: <?php echo htmlspecialchars(mysqli_error($conn)); ?>
            </div>
        <?php } ?>

        <div class="row g-4">
            <?php
            $hasMovies = false;

            if ($search != '') {
                while (mysqli_stmt_fetch($movieStmt)) {
                    $hasMovies = true;
                    $shortDescription = substr($description, 0, 120);
                    if (strlen($description) > 120) {
                        $shortDescription .= '...';
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card movie-card h-100 shadow-sm">
                            <img src="<?php echo htmlspecialchars($imageLink); ?>" class="card-img-top movie-image" alt="<?php echo htmlspecialchars($title); ?>">
                            <div class="card-body d-flex flex-column">
                                <h2 class="h5 card-title"><?php echo htmlspecialchars($title); ?></h2>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($shortDescription); ?></p>
                                <div class="mt-auto d-flex gap-2">
                                    <a href="<?php echo htmlspecialchars($videoLink); ?>" class="btn btn-outline-secondary btn-sm" target="_blank">Video Link</a>
                                    <a href="movie-details.php?id=<?php echo $movieId; ?>" class="btn btn-primary btn-sm">View More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                mysqli_stmt_close($movieStmt);
            } elseif ($movieResult && mysqli_num_rows($movieResult) > 0) {
                while ($movie = mysqli_fetch_assoc($movieResult)) {
                    $hasMovies = true;
                    $shortDescription = substr($movie['Description'], 0, 120);
                    if (strlen($movie['Description']) > 120) {
                        $shortDescription .= '...';
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card movie-card h-100 shadow-sm">
                            <img src="<?php echo htmlspecialchars($movie['ImageLink']); ?>" class="card-img-top movie-image" alt="<?php echo htmlspecialchars($movie['Title']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h2 class="h5 card-title"><?php echo htmlspecialchars($movie['Title']); ?></h2>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($shortDescription); ?></p>
                                <div class="mt-auto d-flex gap-2">
                                    <a href="<?php echo htmlspecialchars($movie['VideoLink']); ?>" class="btn btn-outline-secondary btn-sm" target="_blank">Video Link</a>
                                    <a href="movie-details.php?id=<?php echo $movie['Id']; ?>" class="btn btn-primary btn-sm">View More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

            <?php if (!$hasMovies) { ?>
                <div class="col-12">
                    <div class="alert alert-info">No published movies found.</div>
                </div>
            <?php } ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
