<?php
include("./include/connection.php");

    $id = $_GET['pid'];

    $sql = "DELETE FROM insurance_plan WHERE plan_id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Plan Deleted Successfully'); window.location.href='view_plan.php';</script>";
    } else {
        echo "<script>alert('Error Deleting Plan'); window.location.href='view_plan.php';</script>";
    }

?>
