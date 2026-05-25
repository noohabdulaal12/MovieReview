<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        include_once 'Database.php';
        $dbc = getConnection();
        if ($dbc) {
          $q = "SELECT * from Movies";
          $r = mysqli_query($dbc, $q);
          if (!$r) {
                echo '<div class = "alert alert-danger">There were errors:<br>'
                . mysqli_error($dbc) . '</div>';
            }
            echo "<h1>";
        print_r(mysqli_fetch_all($r, MYSQLI_ASSOC));
        echo "</h1>";
        }
        ?>
    </body>
</html>
