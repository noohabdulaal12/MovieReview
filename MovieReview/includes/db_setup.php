<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'db_connect.php';

// drop all existing tables so we start fresh
$dropQueries = [
    "DROP TABLE IF EXISTS `$dbName`.`S2G1Comments`;",
    "DROP TABLE IF EXISTS `$dbName`.`S2G1RatingLog`;",
    "DROP TABLE IF EXISTS `$dbName`.`S2G1Ratings`;",
    "DROP TABLE IF EXISTS `$dbName`.`S2G1Movies`;",
    "DROP TABLE IF EXISTS `$dbName`.`S2G1Users`;",
    "DROP TABLE IF EXISTS `$dbName`.`S2G1Categories`;",
];

// create all tables with correct structure
$createQueries = [
    "CREATE TABLE `$dbName`.`S2G1Users` (
        `Id` INT NOT NULL AUTO_INCREMENT, 
        `Username` VARCHAR(200) NOT NULL UNIQUE, 
        `Password` VARBINARY(250) NOT NULL, 
        `UserType` ENUM('visitor', 'creator', 'admin') NOT NULL DEFAULT 'visitor',
        `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE = InnoDB;",

    "CREATE TABLE `$dbName`.`S2G1Categories` (
        `Id` INT NOT NULL AUTO_INCREMENT, 
        `Category` VARCHAR(200) NOT NULL UNIQUE, 
        PRIMARY KEY (`Id`)
    ) ENGINE = InnoDB;",
    
    "CREATE TABLE `$dbName`.`S2G1Movies` (
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

    "CREATE TABLE `$dbName`.`S2G1Ratings` (
        `UserId` INT NOT NULL, 
        `MovieId` INT NOT NULL, 
        `StarCount` INT NOT NULL, 
        PRIMARY KEY (`UserId`, `MovieId`),
        CONSTRAINT `fk_ratings_user` FOREIGN KEY (`UserId`) REFERENCES `$dbName`.`S2G1Users`(`Id`) ON DELETE CASCADE
    ) ENGINE = InnoDB;",

    "CREATE TABLE `$dbName`.`S2G1Comments` (
        `UserId` INT NOT NULL, 
        `MovieId` INT NOT NULL, 
        `CommentText` VARCHAR(1500) NOT NULL,
        `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`UserId`, `MovieId`),
        CONSTRAINT `fk_comment_user` FOREIGN KEY (`UserId`) REFERENCES `$dbName`.`S2G1Users`(`Id`) ON DELETE CASCADE
    ) ENGINE = InnoDB;",

    // ratinglog table used by the trigger to log every rating insert
    "CREATE TABLE `$dbName`.`S2G1RatingLog` (
        `Id` INT AUTO_INCREMENT PRIMARY KEY,
        `MovieId` INT NOT NULL,
        `UserId` INT NOT NULL,
        `StarCount` INT NOT NULL,
        `LoggedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;"
];

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");

foreach ($dropQueries as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "Error dropping table: " . mysqli_error($conn) . "<br>";
    }
}

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");

foreach ($createQueries as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// seed users, categories, movies, ratings and comments
$seedQueries = [
    "INSERT INTO `$dbName`.`S2G1Users` (`Id`, `Username`, `Password`, `UserType`) VALUES 
    (1, 'admin_user', AES_ENCRYPT('admin123', 'sUpErsAlty392942'), 'admin'),
    (2, 'movie_creator', AES_ENCRYPT('creator123', 'sUpErsAlty392942'), 'creator'),
    (3, 'casual_viewer', AES_ENCRYPT('viewer123', 'sUpErsAlty392942'), 'visitor');",

    "INSERT INTO `$dbName`.`S2G1Categories` (`Id`, `Category`) VALUES 
    (1, 'Action'),
    (2, 'Sci-Fi'),
    (3, 'Comedy'),
    (4, 'Drama'),
    (5, 'Thriller');",

    "INSERT INTO `$dbName`.`S2G1Movies` (`Id`, `Title`, `Description`, `ImageLink`, `VideoLink`, `AdditionDate`, `CreatorId`, `CategoryId`, `ViewCount`) VALUES 
    (1, 'Inception', 'A thief who steals corporate secrets through the use of dream-sharing technology.', 'https://upload.wikimedia.org/wikipedia/en/2/2e/Inception_%282010%29_theatrical_poster.jpg', 'https://www.youtube.com/watch?v=YoHD9XEInc0', '2026-01-15', 2, 2, 105),
    (2, 'The Matrix', 'A computer hacker learns from mysterious rebels about the true nature of his reality.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ6XPdCNSgoFQS4ytebVvCUY1TfArRYlo0t5Q&s', 'https://www.youtube.com/watch?v=vKQi3bBA1y8', '2026-02-20', 2, 2, 340),
    (3, 'Interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanitys survival.', 'https://upload.wikimedia.org/wikipedia/en/b/bc/Interstellar_film_poster.jpg', 'https://www.youtube.com/watch?v=zSWdZVtXT7E', '2026-01-10', 2, 2, 210),
    (4, 'The Dark Knight', 'Batman faces the Joker, a criminal mastermind who plunges Gotham into anarchy.', 'https://upload.wikimedia.org/wikipedia/en/1/1c/The_Dark_Knight_%282008_film%29.jpg', 'https://www.youtube.com/watch?v=EXeTwQWrcwY', '2026-01-20', 2, 1, 450),
    (5, 'Avengers Endgame', 'The Avengers assemble once more to reverse the damage caused by Thanos.', 'https://upload.wikimedia.org/wikipedia/en/0/0d/Avengers_Endgame_poster.jpg', 'https://www.youtube.com/watch?v=TcMBFSGVi1c', '2026-02-01', 2, 1, 520),
    (6, 'Parasite', 'A poor family schemes to become employed by a wealthy family by infiltrating their household.', 'https://upload.wikimedia.org/wikipedia/en/5/53/Parasite_%282019_film%29.png', 'https://www.youtube.com/watch?v=5xH0HfJHsaY', '2026-02-10', 2, 4, 180),
    (7, 'The Shawshank Redemption', 'Two imprisoned men bond over a number of years finding solace and redemption through acts of decency.', 'https://upload.wikimedia.org/wikipedia/en/8/81/ShawshankRedemptionMoviePoster.jpg', 'https://www.youtube.com/watch?v=6hB3S9bIaco', '2026-01-05', 2, 4, 390),
    (8, 'Joker', 'A mentally troubled comedian embarks on a downward spiral that leads to the creation of an iconic villain.', 'https://upload.wikimedia.org/wikipedia/en/e/e1/Joker_%282019_film%29_poster.jpg', 'https://www.youtube.com/watch?v=zAGVQLHvwOY', '2026-02-15', 2, 5, 300),
    (9, 'Pulp Fiction', 'The lives of two mob hitmen a boxer a gangster and his wife intertwine in four tales of violence and redemption.', 'https://upload.wikimedia.org/wikipedia/en/3/3b/Pulp_Fiction_%281994%29_poster.jpg', 'https://www.youtube.com/watch?v=s7EdQ4FqbhY', '2026-01-25', 2, 5, 270),
    (10, 'The Hangover', 'Three friends must find the groom they lost the night before his wedding.', 'https://resizing.flixster.com/-XZAfHZM39UwaGJIFWKAE8fS0ak=/v3/t/assets/p192248_p_v13_ad.jpg', 'https://www.youtube.com/watch?v=tcdUhdOlz9M', '2026-03-01', 2, 3, 160),
    (11, 'Superbad', 'Two co-dependent high school seniors try to have fun before graduation.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSCNfxuWPQe1WJ_LwGdybWleTTdyPBlWY4mRw&s', 'https://www.youtube.com/watch?v=4eaA8aBnMRQ', '2026-03-05', 2, 3, 140),
    (12, 'Get Out', 'A young African American man visits his white girlfriends parents for the weekend where their eerie behaviour reveals a dark secret.', 'https://m.media-amazon.com/images/I/91yhYRckRvL._AC_UF1000,1000_QL80_.jpg', 'https://www.youtube.com/watch?v=DzfpyUB60YY', '2026-03-10', 2, 5, 220),
    (13, 'Dune', 'A noble family becomes embroiled in a war for control over the galaxys most valuable asset.', 'https://upload.wikimedia.org/wikipedia/en/8/8e/Dune_%282021_film%29.jpg', 'https://www.youtube.com/watch?v=8g18jFHCLXk', '2026-03-15', 2, 2, 195),
    (14, 'Forrest Gump', 'The presidencies of Kennedy and Johnson the events of Vietnam Watergate and other history unfold through the perspective of an Alabama man.', 'https://upload.wikimedia.org/wikipedia/en/6/67/Forrest_Gump_poster.jpg', 'https://www.youtube.com/watch?v=bLvqoHBptjg', '2026-03-20', 2, 4, 410),
    (15, 'John Wick', 'An ex-hitman comes out of retirement to track down the gangsters who killed his dog.', 'https://upload.wikimedia.org/wikipedia/en/9/98/John_Wick_TeaserPoster.jpg', 'https://www.youtube.com/watch?v=2AUmvWm5ZDQ', '2026-03-25', 2, 1, 330);",

    "INSERT INTO `$dbName`.`S2G1Ratings` (`UserId`, `MovieId`, `StarCount`) VALUES 
    (3, 1, 5),
    (3, 2, 4),
    (3, 3, 5),
    (3, 4, 5),
    (3, 5, 4);",

    "INSERT INTO `$dbName`.`S2G1Comments` (`UserId`, `MovieId`, `CommentText`) VALUES 
    (3, 1, 'Mind-bending masterpiece! Absolutely loved it.'),
    (3, 2, 'Classic sci-fi, the special effects still hold up.'),
    (3, 3, 'One of the best space films ever made.'),
    (3, 4, 'Heath Ledger as the Joker is unforgettable.'),
    (3, 5, 'The perfect ending to the Infinity Saga.');"
];

foreach ($seedQueries as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "Error seeding data: " . mysqli_error($conn) . "<br>";
    }
}

// add fulltext index for keyword search on title and description
mysqli_query($conn, 'ALTER TABLE S2G1Movies ADD FULLTEXT INDEX ft_movies (Title, Description)');

// drop and recreate the trigger for logging rating inserts
mysqli_query($conn, 'DROP TRIGGER IF EXISTS after_rating_insert');
if (mysqli_query($conn, 'CREATE TRIGGER after_rating_insert AFTER INSERT ON S2G1Ratings FOR EACH ROW INSERT INTO S2G1RatingLog (MovieId, UserId, StarCount, LoggedAt) VALUES (NEW.MovieId, NEW.UserId, NEW.StarCount, NOW())')) {
    echo "Trigger created successfully.<br>";
} else {
    echo "Error creating trigger: " . mysqli_error($conn) . "<br>";
}

// drop and recreate the stored procedure for getting movie details
mysqli_query($conn, 'DROP PROCEDURE IF EXISTS GetMovieDetails');
$procedureSql = 'CREATE PROCEDURE GetMovieDetails(IN movieId INT) BEGIN UPDATE S2G1Movies SET ViewCount = ViewCount + 1 WHERE Id = movieId; SELECT m.Id, m.Title, m.Description, m.ImageLink, m.VideoLink, m.ViewCount, c.Category, u.Username, COALESCE(AVG(r.StarCount), 0) AS AvgRating, COUNT(r.StarCount) AS RatingCount FROM S2G1Movies m INNER JOIN S2G1Categories c ON m.CategoryId = c.Id INNER JOIN S2G1Users u ON m.CreatorId = u.Id LEFT JOIN S2G1Ratings r ON r.MovieId = m.Id WHERE m.Id = movieId GROUP BY m.Id; END;';
if (mysqli_query($conn, $procedureSql)) {
    echo "Stored procedure created successfully.<br>";
} else {
    echo "Error creating procedure: " . mysqli_error($conn) . "<br>";
}

echo "Database successfully wiped, recreated, and seeded for: " . htmlspecialchars($dbName);
?>