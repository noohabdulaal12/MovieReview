<?php
include_once 'includes/db_connect.php';

$movieId = 0;
if (isset($_GET['id'])) {
    $movieId = (int) $_GET['id'];
}

$movie = null;

if ($movieId > 0) {
    $sql = "SELECT m.Id, m.Title, m.Description, m.ImageLink, m.VideoLink, m.ViewCount,
                   c.CategoryName, u.Username
            FROM mr_Movies m
            INNER JOIN mr_Categories c ON m.CategoryId = c.Id
            INNER JOIN mr_Users u ON m.CreatorId = u.Id
            WHERE m.Id = ? AND m.Status = 'published'";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $movieId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $title, $description, $imageLink, $videoLink, $viewCount, $categoryName, $username);

    if (mysqli_stmt_fetch($stmt)) {
        $movie = [
            'Id' => $id,
            'Title' => $title,
            'Description' => $description,
            'ImageLink' => $imageLink,
            'VideoLink' => $videoLink,
            'ViewCount' => $viewCount,
            'CategoryName' => $categoryName,
            'Username' => $username
        ];
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details - Movie Review System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Movie Review System</a>
            <div class="ms-auto">
                <a class="btn btn-outline-light btn-sm" href="index.php">Back to Home</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php if ($movie == null) { ?>
            <div class="alert alert-warning">Movie not found.</div>
        <?php } else { ?>
            <div class="row g-4">
                <div class="col-lg-5">
                    <img src="<?php echo htmlspecialchars($movie['ImageLink']); ?>" class="img-fluid rounded shadow-sm detail-image" alt="<?php echo htmlspecialchars($movie['Title']); ?>">
                </div>

                <div class="col-lg-7">
                    <h1 class="h2"><?php echo htmlspecialchars($movie['Title']); ?></h1>

                    <p class="text-muted mb-2">
                        Category:
                        <strong><?php echo htmlspecialchars($movie['CategoryName']); ?></strong>
                    </p>

                    <p class="text-muted mb-2">
                        Created by:
                        <strong><?php echo htmlspecialchars($movie['Username']); ?></strong>
                    </p>

                    <p class="text-muted mb-4">
                        View count:
                        <strong><?php echo htmlspecialchars($movie['ViewCount']); ?></strong>
                    </p>

                    <p><?php echo nl2br(htmlspecialchars($movie['Description'])); ?></p>

                    <a href="<?php echo htmlspecialchars($movie['VideoLink']); ?>" class="btn btn-primary" target="_blank">Open Video Link</a>
                    <a href="index.php" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        <?php } ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
