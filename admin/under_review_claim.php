<?php
include("./include/connection.php");

$id = (int)$_GET['id'];

$sql = "
    SELECT ic.*, fa.farmer_id, fa.crop_insured
    FROM insurance_claims ic
    JOIN farmer_applications fa ON ic.application_id = fa.application_id
    WHERE ic.claim_id = $id
";
$row = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if ($row && $row['claim_status'] !== 'Under Review') {
    mysqli_query($conn, "UPDATE insurance_claims SET claim_status = 'Under Review' WHERE claim_id = $id");

    include_once __DIR__ . '/../user/include/notifications_helper.php';
    add_notification($conn, $row['farmer_id'], 'claim_under_review',
        'Claim Under Review',
        'Your damage claim for ' . $row['crop_insured'] . ' under ' . $row['plan_name'] . ' is currently under review. You will be notified once a decision is made.',
        BASE_URL . '/user/view_application_claim.php'
    );
} else {
    mysqli_query($conn, "UPDATE insurance_claims SET claim_status = 'Under Review' WHERE claim_id = $id");
}

header("Location: view_claims.php");
exit();
?>
