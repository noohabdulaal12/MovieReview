<?php
session_start();
include_once 'includes/db_connect.php';

// check if the user is logged in before allowing a comment
if (!isset($_SESSION['Id'])) {
    echo 'Please login to comment.';
    exit();
}

// get the user id from session and clean the inputs
$userId      = (int) $_SESSION['Id'];
$movieId     = (int) $_POST['movie_id'];
$commentText = trim($_POST['comment_text']);

// make sure the comment is not empty
if ($commentText == '') {
    echo 'Comment cannot be empty.';
    exit();
}

// prepared statement to safely insert comment into the database
// on duplicate key update allows the user to update their existing comment
$sql  = 'INSERT INTO S2G1Comments (UserId, MovieId, CommentText) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE CommentText = VALUES(CommentText)';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iis', $userId, $movieId, $commentText);

if (mysqli_stmt_execute($stmt)) {
    echo 'Comment added successfully!';
} else {
    echo 'Could not add comment.';
}

mysqli_stmt_close($stmt);
?>