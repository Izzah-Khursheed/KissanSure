<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

if (!isset($_GET['app_id'])) {
    echo "<div class='main-content container mt-5'><div class='alert alert-danger'>Application ID missing.</div></div>";
    include("./include/footer.php");
    exit();
}

$app_id = (int)$_GET['app_id'];

$sql = "
SELECT fa.*, ip.plan_name, ip.base_premium_rate, ip.coverage_level, ip.deductible_rate
FROM farmer_applications fa
JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
WHERE fa.application_id = $app_id
";
$result = mysqli_query($conn, $sql);
$app    = mysqli_fetch_assoc($result);

if (!$app) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Application not found.</div></div>";
    include("./include/footer.php");
    exit();
}

$msg = '';

// Handle Verify
if (isset($_GET['action']) && $_GET['action'] === 'verify') {
    // Only notify if not already active (prevents duplicate on refresh)
    $should_notify_verify = ($app['status'] !== 'Active');
    mysqli_query($conn, "
        UPDATE farmer_applications SET
            payment_status  = 'Verified',
            status          = 'Active',
            activation_date = CURDATE()
        WHERE application_id = $app_id
    ");
    if ($should_notify_verify) {
        include_once __DIR__ . '/../user/include/notifications_helper.php';
        add_notification($conn, $app['farmer_id'], 'plan_active',
            'Insurance Plan Activated!',
            'Congratulations! Your premium payment has been verified and your insurance plan is now Active.',
            '/KissanSure/user/view_application_plan.php'
        );
    }
    $msg = '<div class="alert alert-success">Payment verified. Policy is now <strong>Active</strong>.</div>';
    $app['status']          = 'Active';
    $app['payment_status']  = 'Verified';
    $app['activation_date'] = date('Y-m-d');
}

// Handle Reject
if (isset($_POST['action']) && $_POST['action'] === 'reject') {
    $notes = mysqli_real_escape_string($conn, trim($_POST['reject_reason'] ?? ''));
    // Only notify if payment was submitted (not already rejected)
    $should_notify_reject = ($app['payment_status'] === 'Submitted');
    mysqli_query($conn, "
        UPDATE farmer_applications SET
            payment_status = 'Rejected',
            status         = 'Pending Payment',
            payment_notes  = '$notes'
        WHERE application_id = $app_id
    ");
    if ($should_notify_reject) {
        include_once __DIR__ . '/../user/include/notifications_helper.php';
        $reason_text = $notes ? ' Reason: ' . $notes . '.' : '';
        add_notification($conn, $app['farmer_id'], 'payment_rejected',
            'Payment Rejected',
            'Your premium payment was rejected.' . $reason_text . ' Please resubmit your payment proof.',
            '/KissanSure/user/view_application_plan.php'
        );
    }
    $msg = '<div class="alert alert-warning">Payment rejected. Farmer will be asked to resubmit.</div>';
    $app['status']         = 'Pending Payment';
    $app['payment_status'] = 'Rejected';
}
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-1">Verify Farmer Payment</h2>
    <p class="text-muted mb-4">Review the payment proof submitted by the farmer and approve or reject it.</p>

    <?= $msg; ?>

    <div class="row g-4">
        <!-- Application Details -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white">Application & Invoice Details</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Application ID</td><td><strong>#CROP-<?= str_pad($app['application_id'], 5, '0', STR_PAD_LEFT); ?></strong></td></tr>
                        <tr><td class="text-muted">Farmer</td><td><strong><?= htmlspecialchars($app['full_name']); ?></strong></td></tr>
                        <tr><td class="text-muted">CNIC</td><td><?= htmlspecialchars($app['cnic_number']); ?></td></tr>
                        <tr><td class="text-muted">Mobile</td><td><?= htmlspecialchars($app['mobile_number']); ?></td></tr>
                        <tr><td class="text-muted">Plan</td><td><?= htmlspecialchars($app['plan_name']); ?></td></tr>
                        <tr><td class="text-muted">Crop</td><td><?= htmlspecialchars($app['crop_insured']); ?></td></tr>
                        <tr><td class="text-muted">Sum Insured</td><td>PKR <?= number_format($app['sum_insured'], 2); ?></td></tr>
                        <tr><td class="text-muted">Coverage Amount</td><td class="text-primary fw-bold">PKR <?= number_format($app['coverage_amount'], 2); ?></td></tr>
                        <tr><td class="text-muted fw-bold">Premium Due</td><td class="text-success fw-bold fs-5">PKR <?= number_format($app['final_premium'], 2); ?></td></tr>
                        <tr><td class="text-muted">Transaction ID</td><td><?= htmlspecialchars($app['transaction_id'] ?? '—'); ?></td></tr>
                        <tr><td class="text-muted">Payment Date</td><td><?= $app['payment_date'] ? date('d-M-Y', strtotime($app['payment_date'])) : '—'; ?></td></tr>
                        <tr>
                            <td class="text-muted">Payment Status</td>
                            <td>
                                <?php
                                $ps = $app['payment_status'];
                                $badge = match($ps) {
                                    'Submitted' => 'bg-info',
                                    'Verified'  => 'bg-success',
                                    'Rejected'  => 'bg-danger',
                                    default     => 'bg-warning text-dark',
                                };
                                echo "<span class='badge $badge'>$ps</span>";
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Proof -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">Payment Proof / Screenshot</div>
                <div class="card-body text-center">
                    <?php if (!empty($app['payment_proof'])): ?>
                        <?php
                        $proof_path = '../user/uploads/payments/' . $app['payment_proof'];
                        $ext = strtolower(pathinfo($app['payment_proof'], PATHINFO_EXTENSION));
                        ?>
                        <?php if ($ext === 'pdf'): ?>
                            <a href="<?= $proof_path; ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fa fa-file-pdf me-2"></i>View PDF Receipt
                            </a>
                        <?php else: ?>
                            <img src="<?= $proof_path; ?>" class="img-fluid rounded shadow" style="max-height:400px;" alt="Payment Proof">
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="<?= $proof_path; ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                                Open Full Size
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No payment proof uploaded yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <?php if ($app['payment_status'] === 'Submitted'): ?>
            <div class="card mt-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="verify_payment.php?app_id=<?= $app_id; ?>&action=verify"
                           class="btn btn-success btn-lg"
                           onclick="return confirm('Verify this payment and activate the policy?')">
                            <i class="fa fa-check-circle me-2"></i> Verify Payment & Activate Policy
                        </a>
                    </div>
                    <hr>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="reject">
                        <div class="mb-2">
                            <label class="form-label text-muted small">Rejection Reason (shown to farmer)</label>
                            <input type="text" name="reject_reason" class="form-control"
                                   placeholder="e.g. Screenshot unclear, wrong amount" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100"
                                onclick="return confirm('Reject this payment?')">
                            <i class="fa fa-times-circle me-2"></i> Reject Payment
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif ($app['status'] === 'Active'): ?>
            <div class="alert alert-success mt-3">
                <i class="fa fa-check-circle me-2"></i>
                Policy is <strong>Active</strong>. Activated on <?= $app['activation_date'] ? date('d-M-Y', strtotime($app['activation_date'])) : 'N/A'; ?>.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3">
        <a href="view_insurance_application.php" class="btn btn-outline-secondary">
            &larr; Back to Applications
        </a>
    </div>
</div>

<?php include("./include/footer.php"); ?>
