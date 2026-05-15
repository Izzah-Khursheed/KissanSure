<?php
session_start();
include("./include/connection.php");
$just_registered = isset($_GET['registered']);

if (isset($_POST["submit"])) {

    $phone = mysqli_real_escape_string($conn, $_POST["phone"]);
    $password = mysqli_real_escape_string($conn, $_POST["password"]);

    $sql = "SELECT * FROM register_farmer WHERE phone='$phone'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $farmer = mysqli_fetch_assoc($result);
        if (password_verify($password, $farmer['password'])) {
            $_SESSION['farmer_id']   = $farmer['farmerid'];
            $_SESSION['farmer_name'] = $farmer['name'];
            header("Location: index.php");
            exit();

        } else {
            echo '<script>alert("Incorrect Password!");</script>';
        }

    } else {
        echo '<script>alert("Farmer not found!");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmer Login</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

body {
    background: linear-gradient(135deg, #28a745, #198754);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-card {
    width: 100%;
    max-width: 420px;
    border: none;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}

.login-card .card-body {
    padding: 40px;
}

.form-control {
    height: 45px;
    border-radius: 8px;
}

.btn-login {
    height: 45px;
    border-radius: 8px;
    font-weight: 600;
}

.icon-input {
    position: relative;
}

.icon-input i {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #198754;
}

.icon-input input {
    padding-left: 38px;
}

</style>

</head>
<body>

<div class="card login-card">
    <div class="card-body">

        <div class="text-center mb-4">
            <i class="fas fa-seedling fa-3x text-success mb-3"></i>
            <h3 class="fw-bold">Farmer Login</h3>
            <p class="text-muted">Access your insurance dashboard</p>
        </div>

        <?php if ($just_registered): ?>
        <div class="alert alert-success text-center py-2 mb-3">
            Registration successful! Please login.
        </div>
        <?php endif; ?>

        <form method="post">

            <div class="mb-3 icon-input">
                <label class="form-label fw-semibold">Phone Number</label>
               
                <input type="text" name="phone" class="form-control"
                       placeholder="03001234567"
                       required maxlength="11" pattern="\d{11}">
            </div>

            <div class="mb-3 icon-input">
                <label class="form-label fw-semibold">Password</label>
                
                <input type="password" name="password"
                       class="form-control"
                       placeholder="Enter Password"
                       required>
            </div>

            <button type="submit" name="submit"
                    class="btn btn-success w-100 btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>

            <div class="text-center mt-3">
                <small>
                    Don’t have an account?
                    <a href="farmer_register.php" class="fw-semibold text-success">
                        Register Here
                    </a>
                </small>
            </div>

        </form>

    </div>
</div>

</body>
</html>