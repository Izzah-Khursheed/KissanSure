<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

include("./include/navbar.php");

// 1. Get the Application ID (usually from session or GET)
if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Application ID not found.</div></div>";
    include("./include/footer.php");
    exit();
}

$app_id = (int)$_GET['id'];

// 2. Fetch the detailed quote data
$sql = "
SELECT 
    fa.*, 
    ip.plan_name, 
    ip.coverage_level, 
    ip.base_premium_rate,
    ip.description as plan_desc
FROM farmer_applications fa
JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
WHERE fa.application_id = $app_id
";

$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No data found for this application.</div></div>";
    exit();
}
?>
<style>
@media print {
    @page { margin: 1cm; size: A4 portrait; }

    /* Remove large sections from layout entirely so they take no space */
    #spinner,
    .top-bar,
    .nav-bar,
    .container-fluid.footer,
    .back-to-top,
    .card-footer,
    .text-center.mx-auto { display: none !important; }

    /* Collapse wrapper padding */
    body { margin: 0; }
    .container-fluid.py-5 { padding: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; }
    .row { margin: 0 !important; }

    /* Expand card to full width */
    .col-md-8 { max-width: 100% !important; flex: 0 0 100% !important; padding: 0 !important; }
    #printInvoice { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
}
</style>
<div class="container-fluid py-5">
  <div class="container">
    <div class="text-center mx-auto mb-5" style="max-width:600px;">
        <p class="section-title bg-white text-center text-primary px-3">My Applications</p>
        <h1 class="display-6">Your Application Invoice</h1>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="row justify-content-center mb-3">
        <div class="col-md-8">
            <div class="alert alert-success text-center py-3">
                <strong>Application submitted successfully!</strong>
                Your premium has been calculated. Please review the invoice below and upload your payment proof to activate your policy.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0" id="printInvoice">
                    <div class="card-header d-flex justify-content-between align-middle p-3">
                        <h4 class="mb-0">Insurance Quote / Invoice</h4>
                        <?php
                        $st = $data['status'];
                        $badgeColor = match($st) {
                            'Active'           => 'bg-success',
                            'Payment Submitted' => 'bg-info',
                            'Pending Payment'  => 'bg-warning text-dark',
                            'Rejected'         => 'bg-danger',
                            default            => 'bg-secondary',
                        };
                        echo "<span class='badge $badgeColor'>$st</span>";
                        ?>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h6 class="text-muted">Farmer Details:</h6>
                                <h5><?= $data['full_name']; ?></h5>
                                <p class="mb-0">CNIC: <?= $data['cnic_number']; ?></p>
                                <p class="mb-0">Contact: <?= $data['mobile_number']; ?></p>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <h6 class="text-muted">Application ID:</h6>
                                <p class="fw-bold">#CROP-<?= str_pad($data['application_id'], 5, '0', STR_PAD_LEFT); ?></p>
                                <p class="text-muted">Date: <?= date("d-M-Y", strtotime($data['application_date'])); ?></p>
                            </div>
                        </div>

                        <hr>

                        <h5 class="text-success mt-4 mb-3">Coverage Summary</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Selected Plan:</strong></td>
                                <td class="text-end"><?= $data['plan_name']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Crop & Season:</strong></td>
                                <td class="text-end"><?= $data['crop_insured']; ?> (<?= $data['policy_duration']; ?>)</td>
                            </tr>
                            <tr>
                                <td><strong>Insured Area:</strong></td>
                                <td class="text-end"><?= $data['insured_area']; ?> Acres</td>
                            </tr>
                            <tr>
                                <td><strong>Total Expected Yield:</strong></td>
                                <td class="text-end"><?= number_format($data['expected_yield'], 0); ?> Maunds</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Guaranteed Production:</strong>
                                    <br><small class="text-muted"><?= $data['coverage_level']; ?>% of your total yield</small>
                                </td>
                                <td class="text-end text-primary fw-bold align-middle">
                                    <?= number_format($data['guaranteed_production'], 2); ?> Maunds
                                </td>
                            </tr>
                        </table>

                        <div class="bg-light p-3 rounded mt-4">
                            <h5 class="mb-3">Financial Breakdown</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sum Insured <small class="text-muted">(Total Yield × Market Rate)</small></span>
                                <span class="fw-bold">PKR <?= number_format($data['sum_insured'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Coverage Amount <small class="text-muted">(<?= $data['coverage_level']; ?>% of Sum Insured)</small></span>
                                <span class="fw-bold text-primary">PKR <?= number_format($data['coverage_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Premium Rate</span>
                                <span><?= $data['base_premium_rate']; ?>% of Coverage Amount</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="text-dark">Net Premium Payable</h4>
                                <h3 class="text-success">PKR <?= number_format($data['final_premium'], 2); ?></h3>
                            </div>
                        </div>

                                        <!-- Payment Section -->
                        <div class="mt-4">
                        <?php if ($data['status'] === 'Pending Payment' && $data['payment_status'] === 'Pending'): ?>
                            <div class="alert alert-info small">
                                <strong>Payment Instructions:</strong> Transfer <strong>PKR <?= number_format($data['final_premium'], 2); ?></strong>
                                to any of the accounts below, then upload your payment proof.
                                <ul class="mb-0 mt-2">
                                    <li>Bank Account: <strong>0123-4567890-001</strong> (HBL — KissanSure Insurance)</li>
                                    <li>JazzCash: <strong>0301-1234567</strong></li>
                                    <li>EasyPaisa: <strong>0315-7654321</strong></li>
                                </ul>
                            </div>
                            <a href="submit_payment.php?app_id=<?= $data['application_id']; ?>"
                               class="btn btn-success btn-lg w-100 shadow">
                                <i class="fa fa-upload me-2"></i> Upload Payment Proof
                            </a>

                        <?php elseif ($data['payment_status'] === 'Submitted'): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fa fa-clock me-1"></i>
                                Your payment proof has been submitted and is <strong>under verification</strong> by the admin.
                            </div>

                        <?php elseif ($data['payment_status'] === 'Rejected'): ?>
                            <div class="alert alert-danger">
                                <strong>Payment Rejected.</strong>
                                <?php if (!empty($data['payment_notes'])): ?>
                                    Reason: <?= htmlspecialchars($data['payment_notes']); ?>
                                <?php endif; ?>
                            </div>
                            <a href="submit_payment.php?app_id=<?= $data['application_id']; ?>"
                               class="btn btn-warning btn-lg w-100">
                                <i class="fa fa-redo me-2"></i> Resubmit Payment Proof
                            </a>

                        <?php elseif ($data['status'] === 'Active'): ?>
                            <div class="alert alert-success text-center">
                                <i class="fa fa-check-circle me-1"></i>
                                Payment verified. Your policy is <strong>Active</strong>. You can now file claims.
                            </div>

                        <?php else: ?>
                            <div class="alert alert-secondary text-center">
                                Waiting for admin to calculate your premium.
                            </div>
                        <?php endif; ?>
                        </div>

                    </div>
                    <div class="card-footer text-center py-3">
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            Download/Print Invoice
                        </button>
                        <button class="btn btn-success btn-sm ms-2" onclick="downloadInvoiceImg()">
                            <i class="fa fa-image me-1"></i> Download as Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function downloadInvoiceImg() {
    var footer = document.querySelector('#printInvoice .card-footer');
    if (footer) footer.style.display = 'none';
    html2canvas(document.getElementById('printInvoice'), { scale: 2, useCORS: true }).then(function(canvas) {
        if (footer) footer.style.display = '';
        var link = document.createElement('a');
        link.download = 'insurance-invoice.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}
</script>

<?php include("./include/footer.php"); ?>