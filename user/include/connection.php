<?php
$conn = mysqli_connect("localhost", "root", "", "crop_insurance_one");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>