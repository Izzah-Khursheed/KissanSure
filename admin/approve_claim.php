<?php
include("./include/connection.php");

$id = (int)$_GET['id'];
$sql = "
    SELECT ic.*, fa.farmer_id, fa.crop_insured
    FROM insurance_claims ic
    JOIN farmer_applications fa ON ic.application_id = fa.application_id
    WHERE ic.claim_id = $id
";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// Ensure AI already analyzed
if ($row['ai_status'] != 'Analyzed') {
    die("AI analysis required before approval.");
}

// Only update + notify if not already approved
if ($row['claim_status'] !== 'Approved') {
    mysqli_query($conn, "UPDATE insurance_claims SET claim_status = 'Approved' WHERE claim_id = $id");

    include_once __DIR__ . '/../user/include/notifications_helper.php';
    add_notification($conn, $row['farmer_id'], 'claim_approved',
        'Claim Approved!',
        'Your damage claim for ' . $row['crop_insured'] . ' under ' . $row['plan_name'] . ' has been approved. Check your claims page for next steps.',
        '/KissanSure/user/view_application_claim.php'
    );
}

header("Location: view_claims.php");
exit();
?>