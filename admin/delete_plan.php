<?php
session_start();
include("./include/connection.php");

$id  = (int)$_GET['pid'];
$sql = "DELETE FROM insurance_plan WHERE plan_id = $id";

if (mysqli_query($conn, $sql)) {
    $_SESSION['flash'] = ['msg' => 'Insurance plan deleted successfully.', 'type' => 'success'];
} else {
    $_SESSION['flash'] = ['msg' => 'Error deleting plan. Please try again.', 'type' => 'danger'];
}

header("Location: view_plan.php");
exit();
?>
