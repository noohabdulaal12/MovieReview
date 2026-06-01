<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'includes/db_connect.php';

$message = '';
$messageType = '';
$username = '';
$userType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $userType = trim($_POST['user_type']);

    if ($username == '' || $password == '' || $confirmPassword == '' || $userType == '') {
        $message = 'Please fill in all fields.';
        $messageType = 'danger';
    } elseif ($password != $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'danger';
    } elseif ($userType != 'visitor' && $userType != 'creator' && $userType != 'admin') {
        $message = 'Please choose a valid user type.';
        $messageType = 'danger';
    } else {
        $checkSql = 'SELECT Id FROM Users WHERE Username = ?';
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, 's', $username);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $message = 'This username is already taken.';
            $messageType = 'danger';
        } else {
            $encryptionKey = 'sUpErsAlty392942';
            $insertSql = 'INSERT INTO Users (Username, Password, UserType, CreatedAt) VALUES (?, AES_ENCRYPT(?, ?), ?, NOW())';
            $insertStmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param($insertStmt, 'ssss', $username, $password, $encryptionKey, $userType);

            if (mysqli_stmt_execute($insertStmt)) {
                $message = 'Account created successfully. You can now login.';
                $messageType = 'success';
                $username = '';
                $userType = '';
            } else {
                $message = 'Signup failed. Please try again.';
                $messageType = 'danger';
            }

            mysqli_stmt_close($insertStmt);
        }

        mysqli_stmt_close($checkStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Movie Review System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><img src="images/logo.png" alt="Movie Review System" height="38"></a>
            <div class="ms-auto">
                <a class="btn btn-outline-light btn-sm" href="login.php">Login</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3 text-center">Create Account</h1>
                        <p class="text-muted text-center mb-4">Sign up to use the Movie Review System.</p>

                        <?php if ($message != '') { ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php } ?>

                        <div id="signupMessage" class="alert alert-danger d-none"></div>

                        <form id="signupForm" action="signup.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
                            </div>

                            <div class="mb-4">
                                <label for="userType" class="form-label">User Type</label>
                                <select class="form-select" id="userType" name="user_type">
                                    <option value="">Choose user type</option>
                                    <option value="visitor" <?php if ($userType == 'visitor') { echo 'selected'; } ?>>Visitor</option>
                                    <option value="creator" <?php if ($userType == 'creator') { echo 'selected'; } ?>>Creator</option> 
                                 <!-- <option value="admin" <?php if ($userType == 'admin') { echo 'selected'; } ?>>Admin</option> --> 
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                        </form>

                        <p class="text-center mt-3 mb-0">
                            Already have an account?
                            <a href="login.php">Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function (event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();
            const userType = document.getElementById('userType').value;
            const message = document.getElementById('signupMessage');

            message.classList.add('d-none');
            message.textContent = '';

            if (username === '' || password === '' || confirmPassword === '' || userType === '') {
                event.preventDefault();
                message.textContent = 'Please fill in all fields.';
                message.classList.remove('d-none');
                return;
            }

            if (password !== confirmPassword) {
                event.preventDefault();
                message.textContent = 'Passwords do not match.';
                message.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>