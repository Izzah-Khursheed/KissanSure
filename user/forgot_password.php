<?php
session_start();
include("./include/connection.php");

$error = '';

if (isset($_POST['verify'])) {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $cnic  = mysqli_real_escape_string($conn, $_POST['cnic']);

    $result = mysqli_query($conn, "SELECT farmerid, name FROM register_farmer WHERE phone='$phone' AND cnic='$cnic'");

    if (mysqli_num_rows($result) === 1) {
        $farmer = mysqli_fetch_assoc($result);
        $_SESSION['reset_farmer_id']   = $farmer['farmerid'];
        $_SESSION['reset_farmer_name'] = $farmer['name'];
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "No account found matching this phone number and CNIC. Please check and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" href="../logo.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — KissanSure</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body {
    background: linear-gradient(135deg, #28a745, #198754);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.card {
    width: 100%;
    max-width: 430px;
    border: none;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}
.card-body { padding: 40px; }
.form-control { height: 45px; border-radius: 8px; }
.btn-verify   { height: 45px; border-radius: 8px; font-weight: 600; }
</style>
</head>
<body>

<div class="card">
    <div class="card-body">

        <div class="text-center mb-4">
            <i class="fas fa-lock-open fa-3x text-success mb-3"></i>
            <h3 class="fw-bold">Forgot Password</h3>
            <p class="text-muted small">Enter your registered phone number and CNIC to verify your identity.</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <div class="small"><?= htmlspecialchars($error) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control"
                       placeholder="03001234567"
                       required maxlength="11" pattern="\d{11}">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">CNIC Number</label>
                <input type="text" name="cnic" class="form-control"
                       placeholder="1234512345671"
                       required maxlength="13" pattern="\d{13}">
            </div>

            <button type="submit" name="verify" class="btn btn-success w-100 btn-verify">
                <i class="fas fa-check me-1"></i> Verify Identity
            </button>
        </form>

        <div class="text-center mt-3">
            <small><a href="login.php" class="text-success fw-semibold">Back to Login</a></small>
        </div>

    </div>
</div>

</body>
</html>
