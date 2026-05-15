<?php
include("./include/connection.php");
include("./include/navbar.php");

if (!isset($_SESSION['farmer_id'])) {
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

$result1 = mysqli_query($conn, "SELECT * FROM register_farmer WHERE farmerid = '$farmer_id'");
$farmer  = mysqli_fetch_assoc($result1);

$result2 = mysqli_query($conn,
    "SELECT fa.*, ip.plan_name, ip.deductible_rate, ip.base_premium_rate, ip.coverage_level
     FROM farmer_applications fa
     LEFT JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
     WHERE fa.farmer_id = '$farmer_id'
     ORDER BY fa.application_id DESC LIMIT 1");
$app = mysqli_fetch_assoc($result2);

$app_count   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM farmer_applications WHERE farmer_id='$farmer_id'"))['c'];
$claim_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM insurance_claims
     WHERE application_id IN (SELECT application_id FROM farmer_applications WHERE farmer_id='$farmer_id')"))['c'];

$farmerName = ucwords(strtolower($farmer['name']));
?>

<div class="container py-5">

    <!-- Profile Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <img src="/FYP_2026/profile_user.jpg"
                     class="rounded-circle border"
                     style="width:100px;height:100px;object-fit:cover;">
                <div>
                    <h3 class="mb-1 fw-bold"><?= htmlspecialchars($farmerName) ?></h3>
                    <p class="mb-1 text-muted"><i class="fa fa-id-card me-1"></i> CNIC: <?= htmlspecialchars($farmer['cnic']) ?></p>
                    <p class="mb-1 text-muted"><i class="fa fa-phone me-1"></i> <?= htmlspecialchars($farmer['phone']) ?></p>
                    <p class="mb-0 text-muted"><i class="fa fa-envelope me-1"></i> <?= htmlspecialchars($farmer['email'] ?: 'Not provided') ?></p>
                </div>
                <div class="ms-auto text-end">
                    <span class="badge bg-success fs-6 px-3 py-2">Farmer</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3 border-start border-primary border-4">
                <div class="text-primary fs-1 fw-bold"><?= $app_count ?></div>
                <div class="text-muted">Total Applications</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3 border-start border-warning border-4">
                <div class="text-warning fs-1 fw-bold"><?= $claim_count ?></div>
                <div class="text-muted">Total Claims</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3 border-start border-success border-4">
                <?php
                $policy_active = $app && $app['status'] === 'Active';
                ?>
                <div class="fs-1 fw-bold <?= $policy_active ? 'text-success' : 'text-secondary' ?>">
                    <?= $policy_active ? '✓' : '—' ?>
                </div>
                <div class="text-muted">Policy Status:
                    <span class="badge <?= $policy_active ? 'bg-success' : 'bg-secondary' ?> ms-1">
                        <?= $app ? htmlspecialchars($app['status']) : 'No Policy' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:45%">Full Name</th>
                            <td><?= htmlspecialchars($farmerName) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Father's Name</th>
                            <td><?= htmlspecialchars(ucwords(strtolower($farmer['father_name'] ?? ''))) ?: '<span class="text-muted">Not provided</span>' ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">CNIC</th>
                            <td><?= htmlspecialchars($farmer['cnic']) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Phone</th>
                            <td><?= htmlspecialchars($farmer['phone']) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Email</th>
                            <td><?= htmlspecialchars($farmer['email'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">City</th>
                            <td><?= htmlspecialchars(ucwords(strtolower($farmer['city'] ?? ''))) ?: '—' ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Address</th>
                            <td><?= htmlspecialchars($farmer['address'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Total Field Size</th>
                            <td><?= htmlspecialchars($farmer['field_size'] ?? '—') ?> Acres</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Latest Insurance Application -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Latest Insurance Application</h5>
                </div>
                <div class="card-body">
                    <?php if ($app): ?>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width:50%">Insurance Plan</th>
                            <td><?= htmlspecialchars($app['plan_name'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Crop Insured</th>
                            <td><?= htmlspecialchars($app['crop_insured'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Insured Area</th>
                            <td><?= htmlspecialchars($app['insured_area'] ?? '—') ?> Acres</td>
                        </tr>
                        <tr>
                            <th class="text-muted">District</th>
                            <td><?= htmlspecialchars($app['district'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Irrigation Type</th>
                            <td><?= htmlspecialchars($app['irrigation_type'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Sowing Date</th>
                            <td><?= htmlspecialchars($app['sowing_date'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Coverage Amount</th>
                            <td><?= $app['coverage_amount'] ? 'PKR ' . number_format((float)$app['coverage_amount'], 2) : '—' ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Premium Amount</th>
                            <td><?= $app['final_premium'] ? 'PKR ' . number_format((float)$app['final_premium'], 2) : '—' ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Bank Account</th>
                            <td><?= htmlspecialchars($app['bank_account'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Policy Status</th>
                            <td>
                                <?php
                                $st = $app['status'] ?? '';
                                $badge = match($st) {
                                    'Active'             => 'bg-success',
                                    'Pending Payment'    => 'bg-secondary',
                                    'Payment Submitted'  => 'bg-info text-dark',
                                    'Premium Calculated' => 'bg-warning text-dark',
                                    'Rejected'           => 'bg-danger',
                                    default              => 'bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($st) ?></span>
                            </td>
                        </tr>
                        <?php if (!empty($app['activation_date'])): ?>
                        <tr>
                            <th class="text-muted">Activated On</th>
                            <td><?= htmlspecialchars($app['activation_date']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <?php else: ?>
                        <p class="text-muted mt-3">No insurance application found.</p>
                        <a href="apply_plan.php" class="btn btn-primary btn-sm">Apply Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->

</div>

<?php include("./include/footer.php"); ?>
