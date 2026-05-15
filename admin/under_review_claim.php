<?php
include("./include/connection.php");

$id = (int)$_GET['id'];

mysqli_query($conn, "UPDATE insurance_claims SET claim_status = 'Under Review' WHERE claim_id = $id");

header("Location: view_claims.php");
exit();
?>
