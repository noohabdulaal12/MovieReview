<?php
include_once 'includes/db_connect.php';

// get the movie id from the url and cast to int for safety
$movieId = (int) $_GET['movie_id'];

// fetch all comments for this movie joined with the username
$sql  = "SELECT c.UserId, c.CommentText, c.CreatedAt, u.Username
         FROM S2G1Comments c
         INNER JOIN S2G1Users u ON c.UserId = u.Id
         WHERE c.MovieId = ?
         ORDER BY c.CreatedAt DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $movieId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $commentUserId, $commentText, $commentDate, $commentUsername);

// loop through results and output each comment as html
$found = false;
while (mysqli_stmt_fetch($stmt)) {
    $found = true;
    echo '<div class="comment-item">';
    echo '<div class="d-flex justify-content-between gap-3">';
    echo '<div>';
    echo '<strong>' . htmlspecialchars($commentUsername) . '</strong>';
    echo '<div class="small text-muted">' . htmlspecialchars($commentDate) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '<p class="mb-0 mt-2">' . nl2br(htmlspecialchars($commentText)) . '</p>';
    echo '</div>';
}

mysqli_stmt_close($stmt);

// show a message if there are no comments yet
if (!$found) {
    echo '<div class="alert alert-info mb-0">No comments yet.</div>';
}
?>