<?php
session_start();
include("./include/connection.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_insurance_application.php");
    exit();
}

$application_id = (int)$_GET['id'];
$sql = "UPDATE farmer_applications SET status = 'Rejected' WHERE application_id = $application_id";

if (mysqli_query($conn, $sql)) {
    $_SESSION['flash'] = ['msg' => 'Application #' . $application_id . ' has been rejected.', 'type' => 'warning'];
} else {
    $_SESSION['flash'] = ['msg' => 'Error rejecting application: ' . mysqli_error($conn), 'type' => 'danger'];
}

mysqli_close($conn);
header("Location: view_insurance_application.php");
exit();
?>