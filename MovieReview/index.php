<?php
include_once 'includes/db_connect.php';

function bindParams($stmt, $types, $params)
{
    if ($types == '') {
        return;
    }

    $bindValues = [];
    $bindValues[] = $stmt;
    $bindValues[] = $types;

    for ($i = 0; $i < count($params); $i++) {
        $bindValues[] = &$params[$i];
    }

    call_user_func_array('mysqli_stmt_bind_param', $bindValues);
}

$keyword = '';
if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
}

$startDate = '';
if (isset($_GET['start_date'])) {
    $startDate = trim($_GET['start_date']);
}

$endDate = '';
if (isset($_GET['end_date'])) {
    $endDate = trim($_GET['end_date']);
}

$creator = '';
if (isset($_GET['creator'])) {
    $creator = trim($_GET['creator']);
}

$sort = '';
if (isset($_GET['sort'])) {
    $sort = trim($_GET['sort']);
}

$categoryId = 0;
if (isset($_GET['category'])) {
    $categoryId = (int) $_GET['category'];
}

$categorySql = 'SELECT Id, CategoryName FROM mr_Categories ORDER BY CategoryName ASC';
$categoryResult = mysqli_query($conn, $categorySql);

$movieSql = "SELECT m.Id, m.Title, m.Description, m.ImageLink, m.VideoLink, m.AdditionDate, m.ViewCount, u.Username
             FROM mr_Movies m
             INNER JOIN mr_Users u ON m.CreatorId = u.Id
             WHERE m.Status = 'published'";

$types = '';
$params = [];

if ($categoryId > 0) {
    $movieSql .= ' AND m.CategoryId = ?';
    $types .= 'i';
    $params[] = $categoryId;
}

if ($keyword != '') {
    $movieSql .= ' AND MATCH(m.Title, m.Description) AGAINST(?)';
    $types .= 's';
    $params[] = $keyword;
}

if ($startDate != '') {
    $movieSql .= ' AND m.AdditionDate >= ?';
    $types .= 's';
    $params[] = $startDate;
}

if ($endDate != '') {
    $movieSql .= ' AND m.AdditionDate <= ?';
    $types .= 's';
    $params[] = $endDate;
}

if ($creator != '') {
    $movieSql .= ' AND u.Username LIKE ?';
    $types .= 's';
    $params[] = '%' . $creator . '%';
}

if ($sort == 'popular') {
    $movieSql .= ' ORDER BY m.ViewCount DESC';
} else {
    $movieSql .= ' ORDER BY m.AdditionDate DESC';
}

$movieStmt = mysqli_prepare($conn, $movieSql);

if ($movieStmt) {
    bindParams($movieStmt, $types, $params);
    mysqli_stmt_execute($movieStmt);
    mysqli_stmt_bind_result($movieStmt, $movieId, $title, $description, $imageLink, $videoLink, $additionDate, $viewCount, $creatorUsername);
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
        <div class="mb-4">
            <h1 class="h2">Published Movies</h1>
            <p class="text-muted">Browse and search the latest movies.</p>
        </div>

        <form action="index.php" method="get" class="search-box mb-4">
            <?php if ($categoryId > 0) { ?>
                <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
            <?php } ?>

            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <label for="keyword" class="form-label">Keyword or Title</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Search title or description" value="<?php echo htmlspecialchars($keyword); ?>">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="startDate" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="endDate" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="creator" class="form-label">Creator</label>
                    <input type="text" class="form-control" id="creator" name="creator" placeholder="Username" value="<?php echo htmlspecialchars($creator); ?>">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="sort" class="form-label">Sort</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="" <?php if ($sort == '') { echo 'selected'; } ?>>Newest</option>
                        <option value="popular" <?php if ($sort == 'popular') { echo 'selected'; } ?>>Most Popular</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <?php if (!$movieStmt) { ?>
            <div class="alert alert-danger">
                There was an error loading movies: <?php echo htmlspecialchars(mysqli_error($conn)); ?>
            </div>
        <?php } ?>

        <div class="row g-4">
            <?php
            $hasMovies = false;

            if ($movieStmt) {
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
                                <p class="small text-muted mb-3">
                                    Added: <?php echo htmlspecialchars($additionDate); ?><br>
                                    Creator: <?php echo htmlspecialchars($creatorUsername); ?><br>
                                    Views: <?php echo htmlspecialchars($viewCount); ?>
                                </p>
                                <div class="mt-auto">
                                    <a href="movie-details.php?id=<?php echo $movieId; ?>" class="btn btn-primary btn-sm">View More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                mysqli_stmt_close($movieStmt);
            }
            ?>

            <?php if (!$hasMovies) { ?>
                <div class="col-12">
                    <div class="alert alert-info">No movies found.</div>
                </div>
            <?php } ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
