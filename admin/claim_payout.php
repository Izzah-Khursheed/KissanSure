<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

if (!isset($_GET['claim_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Claim ID missing.</div></div>";
    include("./include/footer.php");
    exit();
}

$claim_id = (int)$_GET['claim_id'];

$sql = "
SELECT ic.*,
       fa.farmer_id, fa.full_name, fa.cnic_number, fa.mobile_number, fa.bank_account,
       fa.coverage_amount, fa.crop_insured,
       ip.plan_name, ip.deductible_rate, ip.coverage_level, ip.base_premium_rate
FROM insurance_claims ic
JOIN farmer_applications fa ON ic.application_id = fa.application_id
JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
WHERE ic.claim_id = $claim_id
";
$result = mysqli_query($conn, $sql);
$claim  = mysqli_fetch_assoc($result);

if (!$claim) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Claim not found.</div></div>";
    include("./include/footer.php");
    exit();
}

// Use stored values calculated at submission time
$damage_pct      = (float)($claim['damage_percentage'] ?? 0);
$coverage_amount = (float)($claim['coverage_amount'] ?? 0);
$deductible      = (float)($claim['deductible_rate'] ?? 0);
$damage_loss     = (float)($claim['damage_loss']  ?? ($coverage_amount * $damage_pct / 100));
$final_payout    = (float)($claim['final_payout'] ?? ($damage_loss * (1 - $deductible / 100)));

$msg   = '';
$error = '';

// Handle payout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_payout'])) {
    if (empty($_FILES['payout_receipt']['name'])) {
        $error = 'Please upload the payout receipt.';
    } else {
        $file     = $_FILES['payout_receipt'];
        $allowed  = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($file['type'], $allowed)) {
            $error = 'Only JPG, PNG, WEBP, or PDF files are accepted.';
        } elseif ($file['size'] > $max_size) {
            $error = 'File size must be under 5 MB.';
        } else {
            $upload_dir = __DIR__ . '/uploads/payouts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'payout_' . $claim_id . '_' . time() . '.' . $ext;
            $dest     = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $fname = mysqli_real_escape_string($conn, $filename);
                mysqli_query($conn, "
                    UPDATE insurance_claims SET
                        damage_percentage = $damage_pct,
                        damage_loss       = $damage_loss,
                        final_payout      = $final_payout,
                        payout_receipt    = '$fname',
                        payout_date       = CURDATE(),
                        payout_status     = 'Sent',
                        claim_status      = 'Payout Sent'
                    WHERE claim_id = $claim_id
                ");
                include_once __DIR__ . '/../user/include/notifications_helper.php';
                add_notification($conn, $claim['farmer_id'], 'payout_sent',
                    'Claim Payout Sent!',
                    'Your payout of PKR ' . number_format($final_payout, 2) . ' for your ' . $claim['plan_name'] . ' (' . $claim['crop_insured'] . ') claim has been processed. View your receipt now.',
                    '/KissanSure/user/view_claim_payout.php?claim_id=' . $claim_id
                );
                $msg = '<div class="alert alert-success">Payout issued successfully. Farmer can now view the receipt.</div>';
                $claim['payout_status'] = 'Sent';
                $claim['claim_status']  = 'Payout Sent';
                $claim['payout_receipt'] = $filename;
                $claim['final_payout']  = $final_payout;
            } else {
                $error = 'Failed to upload receipt file. Please try again.';
            }
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-1">Claim Payout Management</h2>
    <p class="text-muted mb-4">Review the approved claim, confirm the calculated payout, upload payment receipt.</p>

    <?= $msg; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Claim & Farmer Info -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary text-white">Claim Information</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Claim ID</td><td><strong>#<?= $claim_id; ?></strong></td></tr>
                        <tr><td class="text-muted">Farmer</td><td><strong><?= htmlspecialchars($claim['full_name']); ?></strong></td></tr>
                        <tr><td class="text-muted">CNIC</td><td><?= htmlspecialchars($claim['cnic_number']); ?></td></tr>
                        <tr><td class="text-muted">Bank Account</td><td><?= htmlspecialchars($claim['bank_account'] ?? '—'); ?></td></tr>
                        <tr><td class="text-muted">Plan</td><td><?= htmlspecialchars($claim['plan_name']); ?></td></tr>
                        <tr><td class="text-muted">Loss Reason</td><td><?= htmlspecialchars($claim['reason']); ?></td></tr>
                        <tr><td class="text-muted">Loss Date</td><td><?= $claim['loss_date']; ?></td></tr>
                        <tr><td class="text-muted">Damaged Area</td><td><?= $claim['damaged_area']; ?> acres</td></tr>
                        <tr><td class="text-muted">AI Result</td><td>
                            <span class="badge <?= $claim['ai_result'] === 'damaged' ? 'bg-danger' : 'bg-success'; ?>">
                                <?= $claim['ai_result']; ?>
                            </span>
                            <?php
                            $dc = (int)($claim['damaged_count'] ?? 0);
                            if ($dc > 0) echo "({$dc}/6 images damaged)";
                            ?>
                        </td></tr>
                    </table>
                </div>
            </div>

            <!-- Payout Calculation -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">Payout Calculation</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Coverage Amount</td>
                            <td class="fw-bold">PKR <?= number_format($coverage_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Damage % <small class="d-block text-muted">(area × AI)</small></td>
                            <td>
                                <span class="badge bg-warning text-dark fs-6"><?= round($damage_pct, 1); ?>%</span>
                                <?php if ($dc > 0): ?>
                                <small class="text-muted ms-1 d-block"><?= $dc; ?>/6 images damaged</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Damage Loss</td>
                            <td>PKR <?= number_format($damage_loss, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Deductible (<?= $deductible; ?>%)</td>
                            <td class="text-danger">- PKR <?= number_format($damage_loss * $deductible / 100, 2); ?></td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold text-dark fs-6">Final Payout</td>
                            <td class="fw-bold text-success fs-5">PKR <?= number_format($final_payout, 2); ?></td>
                        </tr>
                    </table>

                    <div class="mt-2 small text-muted">
                        Formula: Coverage Amount &times; Damage% &times; (1 &minus; Deductible%)
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Upload & Action -->
        <div class="col-md-7">
            <?php if ($claim['payout_status'] === 'Sent' || $claim['payout_status'] === 'Completed'): ?>
                <div class="alert alert-success">
                    <strong>Payout already issued.</strong>
                    Amount: PKR <?= number_format($claim['final_payout'], 2); ?>
                </div>
                <?php if (!empty($claim['payout_receipt'])): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white">Payout Receipt</div>
                        <div class="card-body text-center">
                            <?php
                            $rpath = 'uploads/payouts/' . $claim['payout_receipt'];
                            $rext  = strtolower(pathinfo($claim['payout_receipt'], PATHINFO_EXTENSION));
                            ?>
                            <?php if ($rext === 'pdf'): ?>
                                <a href="<?= $rpath; ?>" target="_blank" class="btn btn-outline-primary">
                                    View PDF Receipt
                                </a>
                            <?php else: ?>
                                <img src="<?= $rpath; ?>" class="img-fluid rounded shadow" style="max-height:350px;" alt="Payout Receipt">
                                <br>
                                <a href="<?= $rpath; ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">Open Full Size</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark">Upload Payout Receipt</div>
                    <div class="card-body">
                        <p class="text-muted small">After transferring PKR <?= number_format($final_payout, 2); ?> to the farmer's account, upload the payment confirmation here.</p>
                        <form method="POST" enctype="multipart/form-data" id="payoutForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Payout Receipt / Transfer Screenshot</label>
                                <input type="file" name="payout_receipt" class="form-control"
                                       accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                <div class="form-text">Accepted: JPG, PNG, WEBP, PDF — Max 5 MB</div>
                            </div>
                            <button type="button" class="btn btn-success btn-lg w-100"
                                    data-bs-toggle="modal" data-bs-target="#confirmPayoutModal">
                                <i class="fa fa-paper-plane me-2"></i>
                                Issue Payout — PKR <?= number_format($final_payout, 2); ?>
                            </button>
                        </form>

                        <!-- Confirmation Modal -->
                        <div class="modal fade" id="confirmPayoutModal" tabindex="-1" aria-labelledby="confirmPayoutLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title" id="confirmPayoutLabel">
                                            <i class="fa fa-paper-plane me-2"></i>Confirm Payout
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center py-4">
                                        <p class="mb-1 text-muted">You are about to issue a payout of</p>
                                        <h3 class="text-success fw-bold mb-2">PKR <?= number_format($final_payout, 2); ?></h3>
                                        <p class="mb-0">to <strong><?= htmlspecialchars($claim['full_name']); ?></strong></p>
                                        <p class="text-muted small mb-0">Bank: <?= htmlspecialchars($claim['bank_account'] ?? '—'); ?></p>
                                        <hr>
                                        <p class="text-muted small mb-0">This action cannot be undone. Make sure the transfer has been completed before confirming.</p>
                                    </div>
                                    <div class="modal-footer justify-content-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-success px-4"
                                                onclick="document.getElementById('payoutForm').submit()">
                                            <i class="fa fa-check me-1"></i>Yes, Issue Payout
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="issue_payout" value="1" form="payoutForm">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="view_claims.php" class="btn btn-outline-secondary">&larr; Back to Claims</a>
    </div>
</div>

<?php include("./include/footer.php"); ?>
