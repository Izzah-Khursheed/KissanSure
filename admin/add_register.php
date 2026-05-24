<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

if (isset($_POST["submit"])) {

    $name = strtolower(mysqli_real_escape_string($conn, $_POST["name"]));
    $father_name = mysqli_real_escape_string($conn, $_POST["father_name"]);
    $phone = mysqli_real_escape_string($conn, $_POST["phone"]);
    $cnic = mysqli_real_escape_string($conn, $_POST["cnic"]);
    $address = mysqli_real_escape_string($conn, $_POST["address"]);
    $city = mysqli_real_escape_string($conn, $_POST["city"]);
    $field_size = (float)$_POST["field_size"];
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = mysqli_real_escape_string($conn, $_POST["password"]);

    // hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // check if user already exists (phone or CNIC)
    $checksql = "SELECT * FROM `register_farmer` WHERE `phone`='$phone' OR `cnic`='$cnic'";
    $checkrunn = mysqli_query($conn, $checksql);

    if(mysqli_num_rows($checkrunn) > 0){
        echo "<script>showFlash('Phone or CNIC is already registered.', 'warning');</script>";
    } else {

        $sql = "INSERT INTO `register_farmer`(
            `name`,
            `father_name`,
            `phone`,
            `cnic`,
            `address`,
            `city`,
            `field_size`,
            `email`,
            `password`
        ) VALUES (
            '$name',
            '$father_name',
            '$phone',
            '$cnic',
            '$address',
            '$city',
            '$field_size',
            '$email',
            '$hashed_password'
        )";

        $runn = mysqli_query($conn, $sql);

        if ($runn) {
            echo "<script>showFlash('Farmer registered successfully!', 'success');</script>";
        } else {
            echo "<script>showFlash('Error while registering. Please try again.', 'danger');</script>";
        }
    }

    mysqli_close($conn);
}
?>

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-warning text-black text-center">
            <h2>Farmer Registration</h2>
        </div>
        <div class="card-body">

            <form method="post">

                <h4 class="mt-4 mb-3 text-warning">1. Personal Details</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Muhammad Ali"
                               required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Father Name</label>
                        <input type="text" name="father_name" class="form-control" placeholder="e.g., Muhammad Akbar"
                               required pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="03001234567"
                               required maxlength="11" pattern="\d{11}" title="Enter 11 digit phone number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">CNIC</label>
                        <input type="text" name="cnic" class="form-control" placeholder="1234512345671"
                               required maxlength="13" pattern="\d{13}" title="Enter 13 digit CNIC number without dashes">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email (optional)</label>
                        <input type="email" name="email" class="form-control" placeholder="example@email.com">
                    </div>
                </div>

                <h4 class="mt-5 mb-3 text-warning">2. Address & Land Details</h4>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Street, Area, Village" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">City</label>
                        <input type="text" name="city" class="form-control" placeholder="e.g., Lahore" required pattern="[A-Za-z\s]+" title="Only letters allowed">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Total Field Size (Acres)</label>
                        <input type="number" name="field_size" class="form-control" placeholder="e.g., 12.5" required min="0.1" step="0.1">
                    </div>
                </div>

                <h4 class="mt-5 mb-3 text-warning">3. Account Security</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter Password" required minlength="6" title="Password must be at least 6 characters">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Confirm Password</label>
                        <input type="password" name="confirm" class="form-control" placeholder="Re-enter Password" required minlength="6">
                    </div>
                </div>

                <hr class="my-4">
                <button type="submit" name="submit" class="btn btn-success w-100 btn-lg">
                    Register Farmer
                </button>

            </form>

        </div>
    </div>
</div>


<?php include("./include/footer.php"); ?>
