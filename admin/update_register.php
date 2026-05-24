<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

// Getting farmer ID from URL
$farmerid = $_GET["upid"];

// Fetching existing data
$sql = "SELECT * FROM `register_farmer` WHERE `farmerid` = '$farmerid'";
$run = mysqli_query($conn, $sql);
$fetup = mysqli_fetch_assoc($run);

if (isset($_POST["submit"])) {

    // Getting values from form
    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $father_name = mysqli_real_escape_string($conn, $_POST["father_name"]);
    $phone = mysqli_real_escape_string($conn, $_POST["phone"]);
    $cnic = mysqli_real_escape_string($conn, $_POST["cnic"]);
    $address = mysqli_real_escape_string($conn, $_POST["address"]);
    $city = mysqli_real_escape_string($conn, $_POST["city"]);
    $field_size = (float)$_POST["field_size"];
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = mysqli_real_escape_string($conn, $_POST["password"]);

    // If user enters a new password → hash it
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    } else {
        // keep old password
        $hashed_password = $fetup["password"];
    }

    // Updating farmer record
    $up_sql = "UPDATE `register_farmer` SET
        `name` = '$name',
        `father_name` = '$father_name',
        `phone` = '$phone',
        `cnic` = '$cnic',
        `address` = '$address',
        `city` = '$city',
        `field_size` = '$field_size',
        `email` = '$email',
        `password` = '$hashed_password'
        WHERE `farmerid` = '$farmerid'
    ";

    $update_run = mysqli_query($conn, $up_sql);

    if ($update_run) {
        echo "<script>showFlash('Farmer record updated successfully!', 'success');</script>";
        header("Refresh:2; url=./view_register.php");
    } else {
        echo "<script>showFlash('Error updating record. Please try again.', 'danger');</script>";
    }

    mysqli_close($conn);
}
?>

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-warning text-black text-center">
            <h2>Update Farmer Details</h2>
        </div>
        <div class="card-body">

            <form method="post">

                <h4 class="mt-4 mb-3 text-warning">1. Personal Details</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Muhammad Ali"
                               value="<?php echo $fetup['name']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Father Name</label>
                        <input type="text" name="father_name" class="form-control" placeholder="e.g., Muhammad Akbar"
                               value="<?php echo htmlspecialchars($fetup['father_name']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="03001234567" maxlength="11"
                               value="<?php echo $fetup['phone']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">CNIC</label>
                        <input type="text" name="cnic" class="form-control" placeholder="1234512345671" maxlength="13"
                               value="<?php echo $fetup['cnic']; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email (optional)</label>
                        <input type="email" name="email" class="form-control" placeholder="example@email.com"
                               value="<?php echo $fetup['email']; ?>">
                    </div>
                </div>

                <h4 class="mt-5 mb-3 text-warning">2. Address & Land Details</h4>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Street, Area, Village"
                               value="<?php echo $fetup['address']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">City</label>
                        <input type="text" name="city" class="form-control" placeholder="e.g., Lahore"
                               value="<?php echo $fetup['city']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Total Field Size (Acres)</label>
                        <input type="number" name="field_size" class="form-control" placeholder="e.g., 12.5"
                               value="<?php echo $fetup['field_size']; ?>" required min="0.1" step="0.1">
                    </div>
                </div>

                <h4 class="mt-5 mb-3 text-warning">3. Account Security</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">New Password (leave blank to keep old)</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter New Password">
                    </div>
                </div>

                <hr class="my-4">
                <button type="submit" name="submit" class="btn btn-success w-100 btn-lg">
                    Update Farmer
                </button>

            </form>

        </div>
    </div>
</div>

<?php include("./include/footer.php"); ?>
