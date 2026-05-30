<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'db_connect.php';

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");

$dropQueries = [
    "DROP TABLE IF EXISTS `$dbName`.`Comments`;",
    "DROP TABLE IF EXISTS `$dbName`.`Ratings`;",
    "DROP TABLE IF EXISTS `$dbName`.`Movies`;",
    "DROP TABLE IF EXISTS `$dbName`.`Users`;",
    "DROP TABLE IF EXISTS `$dbName`.`Categories`;"
];

$createQueries = [
    "CREATE TABLE `$dbName`.`Users` (
        `Id` INT NOT NULL AUTO_INCREMENT, 
        `Username` VARCHAR(200) NOT NULL UNIQUE, 
        `Password` VARBINARY(250) NOT NULL, 
        `UserType` ENUM('visitor', 'creator', 'admin') NOT NULL DEFAULT 'visitor',
        `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE = InnoDB;",

    "CREATE TABLE `$dbName`.`Categories` (
        `Id` INT NOT NULL AUTO_INCREMENT, 
        `Category` VARCHAR(200) NOT NULL UNIQUE, 
        PRIMARY KEY (`Id`)
    ) ENGINE = InnoDB;",
    
    "CREATE TABLE `$dbName`.`Movies` (
        `Id` INT NOT NULL AUTO_INCREMENT, 
        `Title` VARCHAR(200) NOT NULL, 
        `Description` VARCHAR(2000) NOT NULL, 
        `ImageLink` VARCHAR(500) NOT NULL, 
        `VideoLink` VARCHAR(500) NOT NULL, 
        `AdditionDate` DATE NOT NULL, 
        `CreatorId` INT NOT NULL,
        `CategoryId` INT NOT NULL,
        `ViewCount` INT NOT NULL DEFAULT '0', 
        PRIMARY KEY (`Id`)
    ) ENGINE = MyISAM;", 

    "CREATE TABLE `$dbName`.`Ratings` (
        `UserId` INT NOT NULL, 
        `MovieId` INT NOT NULL, 
        `StarCount` INT NOT NULL, 
        PRIMARY KEY (`UserId`, `MovieId`),
        CONSTRAINT `fk_ratings_user` FOREIGN KEY (`UserId`) REFERENCES `$dbName`.`Users`(`Id`) ON DELETE CASCADE
    ) ENGINE = InnoDB;",

    "CREATE TABLE `$dbName`.`Comments` (
        `UserId` INT NOT NULL, 
        `MovieId` INT NOT NULL, 
        `CommentText` VARCHAR(1500) NOT NULL,
        `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`UserId`, `MovieId`),
        CONSTRAINT `fk_comment_user` FOREIGN KEY (`UserId`) REFERENCES `$dbName`.`Users`(`Id`) ON DELETE CASCADE
    ) ENGINE = InnoDB;"
];

foreach ($dropQueries as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "Error dropping table: " . mysqli_error($conn) . "<br>";
    }
}

foreach ($createQueries as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

$seedQueries = [
     "INSERT INTO `$dbName`.`Users` (`Id`, `Username`, `Password`, `UserType`) VALUES 
    (1, 'admin_user', AES_ENCRYPT('admin123', 'sUpErsAlty392942'), 'admin'),
    (2, 'movie_creator', AES_ENCRYPT('creator123', 'sUpErsAlty392942'), 'creator'),
    (3, 'casual_viewer', AES_ENCRYPT('viewer123', 'sUpErsAlty392942'), 'visitor');",

    "INSERT INTO `$dbName`.`Categories` (`Id`, `Category`) VALUES 
    (1, 'Action'),
    (2, 'Sci-Fi'),
    (3, 'Comedy');",

    "INSERT INTO `$dbName`.`Movies` (`Id`, `Title`, `Description`, `ImageLink`, `VideoLink`, `AdditionDate`, `CreatorId`, `CategoryId`, `ViewCount`) VALUES 
    (1, 'Inception', 'A thief who steals corporate secrets through the use of dream-sharing technology.', 'images/inception.jpg', 'videos/inception.mp4', '2026-01-15', 2, 2, 105),
    (2, 'The Matrix', 'A computer hacker learns from mysterious rebels about the true nature of his reality.', 'images/matrix.jpg', 'videos/matrix.mp4', '2026-02-20', 2, 2, 340);",

    "INSERT INTO `$dbName`.`Ratings` (`UserId`, `MovieId`, `StarCount`) VALUES 
    (3, 1, 5),
    (3, 2, 4);",

    "INSERT INTO `$dbName`.`Comments` (`UserId`, `MovieId`, `CommentText`) VALUES 
    (3, 1, 'Mind-bending masterpiece! Absolutely loved it.'),
    (3, 2, 'Classic sci-fi, the special effects still hold up.');"
];

foreach ($seedQueries as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "Error seeding data: " . mysqli_error($conn) . "<br>";
    }
}

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");

echo "Database successfully wiped, recreated, and seeded for: " . htmlspecialchars($dbName);
