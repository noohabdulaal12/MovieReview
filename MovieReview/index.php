<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'includes/db_connect.php';

// helper function to bind dynamic parameters to prepared statements
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

// get search inputs from the url
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

// pagination shows 10 movies per page
$moviesPerPage = 10;
$currentPage   = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset        = ($currentPage - 1) * $moviesPerPage;

// get categories for the navbar
$categorySql    = 'SELECT Id, Category FROM S2G1Categories ORDER BY Category ASC';
$categoryResult = mysqli_query($conn, $categorySql);

// build where clause based on what filters the user selected
$where  = ' WHERE 1=1';
$types  = '';
$params = [];

if ($categoryId > 0) {
    $where  .= ' AND m.CategoryId = ?';
    $types  .= 'i';
    $params[] = $categoryId;
}

if ($keyword != '') {
    $where  .= ' AND MATCH(m.Title, m.Description) AGAINST(?)';
    $types  .= 's';
    $params[] = $keyword;
}

if ($startDate != '') {
    $where  .= ' AND m.AdditionDate >= ?';
    $types  .= 's';
    $params[] = $startDate;
}

if ($endDate != '') {
    $where  .= ' AND m.AdditionDate <= ?';
    $types  .= 's';
    $params[] = $endDate;
}

if ($creator != '') {
    $where  .= ' AND u.Username LIKE ?';
    $types  .= 's';
    $params[] = '%' . $creator . '%';
}

// count total matching movies so we know how many pages to show
$countSql  = "SELECT COUNT(*) FROM S2G1Movies m INNER JOIN S2G1Users u ON m.CreatorId = u.Id" . $where;
$countStmt = mysqli_prepare($conn, $countSql);
if ($countStmt) {
    bindParams($countStmt, $types, $params);
    mysqli_stmt_execute($countStmt);
    mysqli_stmt_bind_result($countStmt, $totalMovies);
    mysqli_stmt_fetch($countStmt);
    mysqli_stmt_close($countStmt);
}

// work out total number of pages
$totalPages = ceil($totalMovies / $moviesPerPage);

// sort order based on user selection
$orderBy = ($sort == 'popular') ? ' ORDER BY m.ViewCount DESC' : ' ORDER BY m.AdditionDate DESC';

// main query with limit and offset for pagination
$movieSql  = "SELECT m.Id, m.Title, m.Description, m.ImageLink, m.VideoLink, m.AdditionDate, m.ViewCount, u.Username
              FROM S2G1Movies m
              INNER JOIN S2G1Users u ON m.CreatorId = u.Id"
             . $where . $orderBy . " LIMIT ? OFFSET ?";

// add the pagination values to the params array
$paginationTypes  = $types . 'ii';
$paginationParams = array_merge($params, [$moviesPerPage, $offset]);

$movieStmt = mysqli_prepare($conn, $movieSql);
if ($movieStmt) {
    bindParams($movieStmt, $paginationTypes, $paginationParams);
    mysqli_stmt_execute($movieStmt);
    mysqli_stmt_bind_result($movieStmt, $movieId, $title, $description, $imageLink, $videoLink, $additionDate, $viewCount, $creatorUsername);
}

// build the query string for pagination links so filters stay active when changing page
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
$queryString = $queryString ? $queryString . '&' : '';
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
            <a class="navbar-brand" href="index.php"><img src="images/logo.png" alt="Movie Review System" height="38"></a>
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
                                    <?php echo htmlspecialchars($category['Category']); ?>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>

                <div class="d-flex gap-2">
                    <?php
                    session_start();
                    if (isset($_SESSION['Id'])) { 
                        ?> <span class="text-light small mt-1">Hi, <?php echo htmlspecialchars($_SESSION['Username']); ?></span> <?php
                        if ($_SESSION['UserType'] == "admin")
                        {
                            ?> <a class="btn btn-outline-light btn-sm" href="admin/admin-dashboard.php">Admin Dashboard</a> <?php
                        }
                        else if ($_SESSION['UserType'] == "creator")
                        {
                            ?> <a class="btn btn-outline-light btn-sm" href="creator/creator-dashboard.php">Creator Dashboard</a> <?php
                        }
                        ?>
                        <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
                    <?php } else { ?>
                        <a class="btn btn-outline-light btn-sm" href="login.php">Login</a>
                        <a class="btn btn-primary btn-sm" href="signup.php">Sign Up</a>
                    <?php } ?>
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

        <?php if ($totalPages > 1) { ?>
            <nav class="mt-4" aria-label="Movie pagination">
                <ul class="pagination justify-content-center">

                    <!-- previous page button, disabled when on first page -->
                    <li class="page-item <?php if ($currentPage == 1) echo 'disabled'; ?>">
                        <a class="page-link" href="index.php?<?php echo $queryString; ?>page=<?php echo $currentPage - 1; ?>">Previous</a>
                    </li>

                    <!-- numbered page buttons -->
                    <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                        <li class="page-item <?php if ($i == $currentPage) echo 'active'; ?>">
                            <a class="page-link" href="index.php?<?php echo $queryString; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php } ?>

                    <!-- next page button, disabled when on last page -->
                    <li class="page-item <?php if ($currentPage == $totalPages) echo 'disabled'; ?>">
                        <a class="page-link" href="index.php?<?php echo $queryString; ?>page=<?php echo $currentPage + 1; ?>">Next</a>
                    </li>

                </ul>
                <p class="text-center text-muted small">
                    Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> (<?php echo $totalMovies; ?> movies total)
                </p>
            </nav>
        <?php } ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>