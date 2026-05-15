<?php
session_start();
include('./include/connection.php');
include('./include/navbar.php');

if (!isset($_SESSION['farmer_id'])) {
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

if (!isset($_GET['claim_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Claim ID missing.</div></div>";
    include('./include/footer.php');
    exit();
}

$claim_id  = (int)$_GET['claim_id'];
$farmer_id = $_SESSION['farmer_id'];

$sql = "
SELECT ic.*,
       fa.full_name, fa.cnic_number, fa.coverage_amount, fa.farmer_id,
       ip.plan_name, ip.deductible_rate, ip.coverage_level
FROM insurance_claims ic
JOIN farmer_applications fa ON ic.application_id = fa.application_id
JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
WHERE ic.claim_id = $claim_id
AND fa.farmer_id = '$farmer_id'
";

$result = mysqli_query($conn, $sql);
$claim  = mysqli_fetch_assoc($result);

if (!$claim) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Payout details not found.</div></div>";
    include('./include/footer.php');
    exit();
}

$payout_available = in_array($claim['payout_status'], ['Sent', 'Completed']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="text-center mb-4">
                <p class="section-title bg-white text-center text-primary px-3">My Claims</p>
                <h1 class="display-6">Claim Payout Details</h1>
            </div>

            <?php if (!$payout_available): ?>
                <div class="alert alert-warning text-center">
                    Your payout has not been issued yet. Please check back after admin processes your claim.
                </div>
                <div class="text-center">
                    <a href="view_application_claim.php" class="btn btn-secondary">Back to My Claims</a>
                </div>
            <?php else: ?>

            <!-- Payout Summary Card -->
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payout Summary — Claim #<?= $claim_id; ?></h5>
                    <span class="badge bg-white text-success"><?= $claim['payout_status']; ?></span>
                </div>
                <div class="card-body p-4">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted">Farmer</h6>
                            <h5><?= htmlspecialchars($claim['full_name']); ?></h5>
                            <p class="mb-0">CNIC: <?= htmlspecialchars($claim['cnic_number']); ?></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="text-muted">Plan</h6>
                            <p class="fw-bold"><?= htmlspecialchars($claim['plan_name']); ?></p>
                            <p class="mb-0 text-muted small">
                                Payout Date: <?= $claim['payout_date'] ? date('d M Y', strtotime($claim['payout_date'])) : '—'; ?>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-muted mb-3">Loss & Damage</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Loss Reason</td>
                            <td class="text-end"><?= htmlspecialchars($claim['reason']); ?></td>
                        </tr>
                        <tr>
                            <td>Loss Date</td>
                            <td class="text-end"><?= $claim['loss_date']; ?></td>
                        </tr>
                        <tr>
                            <td>AI Result</td>
                            <td class="text-end">
                                <span class="badge <?= $claim['ai_result'] === 'damaged' ? 'bg-danger' : 'bg-success'; ?>">
                                    <?= $claim['ai_result']; ?>
                                </span>
                                (<?= round($claim['ai_confidence']); ?>% severity)
                            </td>
                        </tr>
                    </table>

                    <hr>

                    <h6 class="text-muted mb-3">Payout Calculation</h6>
                    <div class="bg-light p-3 rounded">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Coverage Amount</span>
                            <span class="fw-bold">PKR <?= number_format($claim['coverage_amount'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>AI Damage Percentage</span>
                            <span class="text-warning fw-bold"><?= $claim['damage_percentage']; ?>%</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Damage Loss <small class="text-muted">(Coverage &times; Damage%)</small></span>
                            <span>PKR <?= number_format($claim['damage_loss'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Deductible (<?= $claim['deductible_rate']; ?>% your share)</span>
                            <span>- PKR <?= number_format($claim['damage_loss'] * $claim['deductible_rate'] / 100, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Final Payout Amount</h5>
                            <h3 class="text-success mb-0">PKR <?= number_format($claim['final_payout'], 2); ?></h3>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Payout Receipt -->
            <?php if (!empty($claim['payout_receipt'])): ?>
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-secondary text-white">Payment Receipt</div>
                <div class="card-body text-center">
                    <?php
                    $receipt_path = '../admin/uploads/payouts/' . $claim['payout_receipt'];
                    $ext = strtolower(pathinfo($claim['payout_receipt'], PATHINFO_EXTENSION));
                    ?>
                    <?php if ($ext === 'pdf'): ?>
                        <a href="<?= $receipt_path; ?>" target="_blank" class="btn btn-outline-primary btn-lg">
                            <i class="fa fa-file-pdf me-2"></i>View PDF Receipt
                        </a>
                    <?php else: ?>
                        <img src="<?= $receipt_path; ?>" class="img-fluid rounded shadow mb-3"
                             style="max-height:400px;" alt="Payout Receipt">
                        <br>
                        <a href="<?= $receipt_path; ?>" download class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-download me-1"></i>Download Receipt
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center">
                <a href="view_application_claim.php" class="btn btn-outline-secondary">
                    &larr; Back to My Claims
                </a>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('./include/footer.php'); ?>
