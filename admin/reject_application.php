<?php
include("./include/connection.php");

// 1. Check if the ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_insurance_application.php");
    exit();
}

$application_id = (int)$_GET['id'];

// 2. Update the status to 'Rejected'
// In a more advanced version, you could add a 'rejection_reason' column as well.
$sql = "UPDATE farmer_applications SET status = 'Rejected' WHERE application_id = $application_id";

if (mysqli_query($conn, $sql)) {
    echo "<script>
            alert('Application ID: $application_id has been Rejected.');
            window.location.href='view_insurance_application.php';
          </script>";
} else {
    echo "<script>
            alert('Error updating status: " . mysqli_error($conn) . "');
            window.location.href='view_insurance_application.php';
          </script>";
}

mysqli_close($conn);
?>