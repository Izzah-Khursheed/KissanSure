<?php
session_start();
include("./include/connection.php");

$signup_error   = '';
$signup_success = '';

if (isset($_POST['submit'])) {

    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if ($password !== $confirm) {
        $signup_error = "Passwords do not match. Please try again.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $check  = "SELECT * FROM users WHERE name='$name'";
        $res    = mysqli_query($conn, $check);

        if (mysqli_num_rows($res) > 0) {
            $signup_error = "Username already exists. Please choose a different one.";
        } else {
            $sql = "INSERT INTO users (name, password) VALUES ('$name', '$hashed')";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash'] = ['msg' => 'Admin account created successfully.', 'type' => 'success'];
                header("Location: dashboard.php");
                exit();
            } else {
                $signup_error = "Signup failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow p-4" style="max-width: 420px; width:100%;">

        <h3 class="text-center mb-3">Signup</h3>
        <p class="text-center text-muted">Create an account</p>

        <?php if ($signup_error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
            <i class="bi bi-x-circle-fill flex-shrink-0"></i>
            <div class="small"><?= htmlspecialchars($signup_error); ?></div>
        </div>
        <?php endif; ?>

        <form method="post">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">
                Sign Up
            </button>

            <div class="text-center mt-3">
                <small>
                    Already have an account?
                    <a href="login.php">Login</a>
                </small>
            </div>

        </form>

    </div>
</div>

</body>
</html>
