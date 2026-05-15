<?php
include("./include/connection.php");

$id = $_GET['id'];
$sql= "SELECT * FROM insurance_claims WHERE claim_id = $id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Ensure AI already analyzed
if ($row['ai_status'] != 'Analyzed') {
    die("AI analysis required before approval.");
}

mysqli_query($conn, "
UPDATE insurance_claims
SET claim_status = 'Approved'
WHERE claim_id = $id
");

header("Location: view_claims.php");
exit();
?>