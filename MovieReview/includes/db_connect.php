<?php
// Shared database connection for the Movie Review System.

$dbHost = 'localhost';
$dbUsername = 'u202304164';
$dbPassword = 'asdASD123!';
$dbName = 'db202304164';

$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
?>
