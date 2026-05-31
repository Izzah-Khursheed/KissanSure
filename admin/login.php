<?php
session_start();
include("./include/connection.php");

$login_error = '';

if (isset($_POST["submit"])) {

    $farmername    = mysqli_real_escape_string($conn, $_POST["farmername"]);
    $farmerpassword = mysqli_real_escape_string($conn, $_POST["farmerpassword"]);

    $sql    = "SELECT * FROM users WHERE name='$farmername'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($farmerpassword, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = "Incorrect password. Please try again.";
        }
    } else {
        $login_error = "No admin account found with that username.";
    }
    mysqli_close($conn);
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
        <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="./css/login.css">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 420px;">

        <div class="text-center mb-4">
            <h3 class="fw-bold">Welcome Back!</h3>
            <p class="text-muted">Sign in to access the admin panel</p>
        </div>

        <?php if ($login_error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <div class="small"><?= htmlspecialchars($login_error); ?></div>
        </div>
        <?php endif; ?>

        <form method="post">

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" name="farmername" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" name="farmerpassword" class="form-control" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">
                Login
            </button>

        </form>

    </div>
</div>

</body>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>

