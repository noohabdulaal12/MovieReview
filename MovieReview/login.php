<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once 'includes/db_connect.php';

$message = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username == '' || $password == '') {
        $message = 'Please enter both username and password.';
    } else {
        $encryptionKey = 'sUpErsAlty392942';
        $sql = 'SELECT Id, Username, CAST(AES_DECRYPT(Password, ?) AS CHAR) as DecryptedPassword, UserType FROM Users WHERE Username = ?';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $encryptionKey, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userId, $dbUsername, $decryptedPassword, $userType);

        if (mysqli_stmt_fetch($stmt)) {
            if ($password === $decryptedPassword) {
                $_SESSION['Id'] = $userId;
                $_SESSION['Username'] = $dbUsername;
                $_SESSION['UserType'] = $userType;

                if ($userType == 'admin') {
                    header('Location: admin/admin-dashboard.php');
                    exit();
                } elseif ($userType == 'creator') {
                    header('Location: creator/creator-dashboard.php');
                    exit();
                } else {
                    header('Location: index.php');
                    exit();
                }
            } else {
                $message = 'Invalid username or password.';
            }
        } else {
            $message = 'Invalid username or password.';
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Movie Review System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Movie Review System</a>
            <div class="ms-auto">
                <a class="btn btn-outline-light btn-sm" href="signup.php">Sign Up</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3 text-center">Login</h1>
                        <p class="text-muted text-center mb-4">Enter your details to continue.</p>

                        <?php if ($message != '') { ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php } ?>

                        <div id="loginMessage" class="alert alert-danger d-none"></div>

                        <form id="loginForm" action="login.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>

                        <p class="text-center mt-3 mb-0">
                            Need an account?
                            <a href="signup.php">Sign up</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function (event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const message = document.getElementById('loginMessage');

            message.classList.add('d-none');
            message.textContent = '';

            if (username === '' || password === '') {
                event.preventDefault();
                message.textContent = 'Please enter both username and password.';
                message.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
