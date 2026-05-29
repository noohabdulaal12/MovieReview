<?php
session_start();
include_once 'includes/db_connect.php';

$movieId = 0;
if (isset($_GET['id'])) {
    $movieId = (int) $_GET['id'];
}

$message = '';
$messageType = '';

if ($movieId > 0 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_comment'])) {
        if (!isset($_SESSION['Id'])) {
            $message = 'Please login to comment.';
            $messageType = 'warning';
        } else {
            $commentText = trim($_POST['comment_text']);

            if ($commentText == '') {
                $message = 'Comment cannot be empty.';
                $messageType = 'danger';
            } else {
                $userId = $_SESSION['Id'];
                $commentSql = 'INSERT INTO mr_Comments (UserId, MovieId, CommentText) VALUES (?, ?, ?)';
                $commentStmt = mysqli_prepare($conn, $commentSql);
                mysqli_stmt_bind_param($commentStmt, 'iis', $userId, $movieId, $commentText);

                if (mysqli_stmt_execute($commentStmt)) {
                    $message = 'Comment added successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Could not add comment.';
                    $messageType = 'danger';
                }

                mysqli_stmt_close($commentStmt);
            }
        }
    }

    if (isset($_POST['add_rating'])) {
        if (!isset($_SESSION['Id'])) {
            $message = 'Please login to rate this movie.';
            $messageType = 'warning';
        } else {
            $starCount = (int) $_POST['star_count'];

            if ($starCount < 1 || $starCount > 5) {
                $message = 'Please choose a rating from 1 to 5.';
                $messageType = 'danger';
            } else {
                $userId = $_SESSION['Id'];
                $ratingSql = 'INSERT INTO mr_Ratings (UserId, MovieId, StarCount)
                              VALUES (?, ?, ?)
                              ON DUPLICATE KEY UPDATE StarCount = VALUES(StarCount)';
                $ratingStmt = mysqli_prepare($conn, $ratingSql);
                mysqli_stmt_bind_param($ratingStmt, 'iii', $userId, $movieId, $starCount);

                if (mysqli_stmt_execute($ratingStmt)) {
                    $message = 'Rating saved successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Could not save rating.';
                    $messageType = 'danger';
                }

                mysqli_stmt_close($ratingStmt);
            }
        }
    }

    if (isset($_POST['delete_comment'])) {
        if (isset($_SESSION['UserType']) && $_SESSION['UserType'] == 'admin') {
            $commentId = (int) $_POST['comment_id'];
            $deleteSql = 'DELETE FROM mr_Comments WHERE Id = ? AND MovieId = ?';
            $deleteStmt = mysqli_prepare($conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, 'ii', $commentId, $movieId);

            if (mysqli_stmt_execute($deleteStmt)) {
                $message = 'Comment deleted.';
                $messageType = 'success';
            } else {
                $message = 'Could not delete comment.';
                $messageType = 'danger';
            }

            mysqli_stmt_close($deleteStmt);
        }
    }
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

$averageRating = 0;
$ratingCount = 0;

if ($movie != null) {
    $ratingSummarySql = 'SELECT AVG(StarCount), COUNT(*) FROM mr_Ratings WHERE MovieId = ?';
    $ratingSummaryStmt = mysqli_prepare($conn, $ratingSummarySql);
    mysqli_stmt_bind_param($ratingSummaryStmt, 'i', $movieId);
    mysqli_stmt_execute($ratingSummaryStmt);
    mysqli_stmt_bind_result($ratingSummaryStmt, $averageRating, $ratingCount);
    mysqli_stmt_fetch($ratingSummaryStmt);
    mysqli_stmt_close($ratingSummaryStmt);

    if ($averageRating == null) {
        $averageRating = 0;
    }
}

$comments = [];

if ($movie != null) {
    $commentsSql = "SELECT c.Id, c.CommentText, c.CreatedAt, u.Username
                    FROM mr_Comments c
                    INNER JOIN mr_Users u ON c.UserId = u.Id
                    WHERE c.MovieId = ?
                    ORDER BY c.CreatedAt DESC";
    $commentsStmt = mysqli_prepare($conn, $commentsSql);
    mysqli_stmt_bind_param($commentsStmt, 'i', $movieId);
    mysqli_stmt_execute($commentsStmt);
    mysqli_stmt_bind_result($commentsStmt, $commentId, $commentText, $commentDate, $commentUsername);

    while (mysqli_stmt_fetch($commentsStmt)) {
        $comments[] = [
            'Id' => $commentId,
            'CommentText' => $commentText,
            'CreatedAt' => $commentDate,
            'Username' => $commentUsername
        ];
    }

    mysqli_stmt_close($commentsStmt);
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
            <div class="ms-auto d-flex gap-2">
                <?php if (isset($_SESSION['Id'])) { ?>
                    <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
                <?php } else { ?>
                    <a class="btn btn-outline-light btn-sm" href="login.php">Login</a>
                <?php } ?>
                <a class="btn btn-primary btn-sm" href="index.php">Back to Home</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php if ($message != '') { ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <?php if ($movie == null) { ?>
            <div class="alert alert-warning">Movie not found.</div>
        <?php } else { ?>
            <div class="row g-4 mb-4">
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

                    <p class="text-muted mb-2">
                        View count:
                        <strong><?php echo htmlspecialchars($movie['ViewCount']); ?></strong>
                    </p>

                    <p class="text-muted mb-4">
                        Average rating:
                        <strong><?php echo number_format($averageRating, 1); ?> / 5</strong>
                        (<?php echo $ratingCount; ?> ratings)
                    </p>

                    <p><?php echo nl2br(htmlspecialchars($movie['Description'])); ?></p>

                    <a href="<?php echo htmlspecialchars($movie['VideoLink']); ?>" class="btn btn-primary" target="_blank">Open Video Link</a>
                    <a href="index.php" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="content-box mb-4">
                        <h2 class="h4">Rate This Movie</h2>

                        <?php if (!isset($_SESSION['Id'])) { ?>
                            <div class="alert alert-info mb-0">Please login to rate this movie.</div>
                        <?php } else { ?>
                            <form action="movie-details.php?id=<?php echo $movieId; ?>" method="post">
                                <div class="mb-3">
                                    <label for="starCount" class="form-label">Your Rating</label>
                                    <select class="form-select" id="starCount" name="star_count">
                                        <option value="">Choose rating</option>
                                        <option value="1">1 Star</option>
                                        <option value="2">2 Stars</option>
                                        <option value="3">3 Stars</option>
                                        <option value="4">4 Stars</option>
                                        <option value="5">5 Stars</option>
                                    </select>
                                </div>

                                <button type="submit" name="add_rating" class="btn btn-primary">Save Rating</button>
                            </form>
                        <?php } ?>
                    </div>

                    <div class="content-box">
                        <h2 class="h4">Add Comment</h2>

                        <?php if (!isset($_SESSION['Id'])) { ?>
                            <div class="alert alert-info mb-0">Please login to comment.</div>
                        <?php } else { ?>
                            <form action="movie-details.php?id=<?php echo $movieId; ?>" method="post">
                                <div class="mb-3">
                                    <label for="commentText" class="form-label">Comment</label>
                                    <textarea class="form-control" id="commentText" name="comment_text" rows="4"></textarea>
                                </div>

                                <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                            </form>
                        <?php } ?>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="content-box">
                        <h2 class="h4">Comments</h2>

                        <?php if (count($comments) == 0) { ?>
                            <div class="alert alert-info mb-0">No comments yet.</div>
                        <?php } else { ?>
                            <?php foreach ($comments as $comment) { ?>
                                <div class="comment-item">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['Username']); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($comment['CreatedAt']); ?></div>
                                        </div>

                                        <?php if (isset($_SESSION['UserType']) && $_SESSION['UserType'] == 'admin') { ?>
                                            <form action="movie-details.php?id=<?php echo $movieId; ?>" method="post">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['Id']; ?>">
                                                <button type="submit" name="delete_comment" class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($comment['CommentText'])); ?></p>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
