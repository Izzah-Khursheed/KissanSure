<?php
include("./include/connection.php");
include("./include/header.php");

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
    .card-footer { display: none !important; }
    body { margin: 0; }
    .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    .row { margin: 0 !important; }
    .col-md-8 { max-width: 100% !important; flex: 0 0 100% !important; padding: 0 !important; }
    #printInvoice { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
}
</style>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0" id="printInvoice">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-middle p-3">
                    <h4 class="mb-0">Insurance Quote / Invoice</h4>
                    <span class="badge bg-primary"><?= $data['status']; ?></span>
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
                            <td><strong>Farmer's Total Expected Yield:</strong></td>
                            <td class="text-end"><?= number_format($data['expected_yield'], 0); ?> Maunds</td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Guaranteed Production:</strong>
                                <br><small class="text-muted"><?= $data['coverage_level']; ?>% of total expected yield</small>
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
                            <span>Coverage Amount <small class="text-muted">(<?= $data['coverage_level']; ?>% of SI)</small></span>
                            <span class="fw-bold text-primary">PKR <?= number_format($data['coverage_amount'] ?? 0, 2); ?></span>
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

                    <div class="mt-3">
                        <?php if ($data['status'] === 'Active'): ?>
                            <div class="alert alert-success text-center">
                                Policy is <strong>Active</strong>. Payment verified.
                            </div>
                        <?php elseif ($data['status'] === 'Payment Submitted'): ?>
                            <div class="alert alert-info text-center">
                                Payment submitted by farmer — pending verification.
                            </div>
                            <a href="verify_payment.php?app_id=<?= $data['application_id']; ?>"
                               class="btn btn-warning w-100">Verify Payment</a>
                        <?php endif; ?>
                    </div>

                </div>
                <div class="card-footer text-center py-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                         Download/Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("./include/footer.php"); ?>