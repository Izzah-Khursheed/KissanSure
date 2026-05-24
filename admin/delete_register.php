<?php
include("./include/connection.php");

$farmerid = (int)$_GET["delid"];
$del = "DELETE FROM `register_farmer` WHERE `farmerid`= $farmerid";
$runn = mysqli_query($conn, $del);
if ($runn) {
    header("Location:./view_register.php");
    echo 1;
} else {
    echo 2;
}