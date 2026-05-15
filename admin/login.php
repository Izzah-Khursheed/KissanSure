<?php
session_start();
include("./include/connection.php");

if (isset($_POST["submit"])) {

    $farmername = mysqli_real_escape_string($conn, $_POST["farmername"]);
    $farmerpassword = mysqli_real_escape_string($conn, $_POST["farmerpassword"]);

$sql = "SELECT * FROM users WHERE name='$farmername'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($farmerpassword, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Role-based redirect
        if ($user['role'] === 'admin') {
            header("Location: dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }else {
            echo '<script>alert("Password incorrect while logging in");</script>';
        }
    } else {
        echo '<script>alert("User not found");</script>';
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
            <p class="text-muted">Sign in to access your dashboard</p>
        </div>

        <form method="post">

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Farmer Name
                </label>
                <input type="text" name="farmername" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-lock"></i> Farmer Password
                </label>
                <input type="password" name="farmerpassword" class="form-control" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">
                Login
            </button>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Don’t have an account?
                    <a href="./register_user.php">Sign up</a>
                </small>
            </div>

        </form>

    </div>
</div>

</body>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>

