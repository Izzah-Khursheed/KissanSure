<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

include("./include/navbar.php");

$farmer_id = $_SESSION['farmer_id'];
    $sql = "SELECT
            *
            FROM farmer_applications fa
            JOIN insurance_plan ip
            ON fa.plan_id = ip.plan_id
            WHERE fa.farmer_id='$farmer_id'
            ORDER BY fa.application_id DESC";
    $result = mysqli_query($conn, $sql);
?>
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show text-center py-4" role="alert">
            <h4 class="mb-1">Thank you! Application Submitted.</h4>
            <p class="mb-0">Your insurance application has been received. You will be updated about your application status soon.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    <style>
        .mild-yellow-card {
            background-color: #fff9db;
            /* soft pastel yellow */
            border-left: 5px solid #f4c430;
            /* nice highlight */
        }
    </style>
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5" style="max-width:600px;">
                <p class="section-title bg-white text-center text-primary px-3">
                    My Applications
                </p>
                <h1 class="display-6">Your Insurance Applications</h1>
            </div>
            <div class="row g-4">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                        <!-- ✅ One Card Per Row -->
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mild-yellow-card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary mb-3">
                                        <?= $row['full_name']; ?>
                                    </h5>
                                    <div class="row">

                                        <!-- LEFT SIDE -->
                                        <div class="col-md-6">
                                            <p><strong>CNIC:</strong> <?= $row['cnic_number']; ?></p>
                                            <p><strong>Plan:</strong> <?= $row['plan_name']; ?></p>
                                            <p><strong>Crop:</strong> <?= $row['crop_insured']; ?></p>
                                            <p><strong>Area:</strong> <?= $row['insured_area']; ?></p>
                                        </div>

                                        <!-- RIGHT SIDE -->
                                        <div class="col-md-6">
                                            <p><strong>Season:</strong> <?= $row['policy_duration']; ?></p>

                                            <!-- STATUS -->
                                            <p>
                                                <strong>Status:</strong>
                                                <?php
                                                $st = $row['status'];
                                                $ps = $row['payment_status'] ?? 'Pending';
                                                if ($st === 'Active') {
                                                    echo "<span class='badge bg-success'>Active - Policy Active</span>";
                                                } elseif ($st === 'Payment Submitted') {
                                                    echo "<span class='badge bg-info'>Payment Under Review</span>";
                                                } elseif ($st === 'Pending Payment') {
                                                    if ($ps === 'Rejected') {
                                                        echo "<span class='badge bg-danger'>Payment Rejected - Resubmit</span>";
                                                    } else {
                                                        echo "<span class='badge bg-warning text-dark'>Plan Not Activated - Premium Not Paid</span>";
                                                    }
                                                } elseif ($st === 'Rejected') {
                                                    echo "<span class='badge bg-danger'>Application Rejected</span>";
                                                } else {
                                                    echo "<span class='badge bg-secondary'>{$st}</span>";
                                                }
                                                ?>
                                            </p>

                                            <!-- ACTION -->
                                            <p>
                                                <strong>Action:</strong><br>
                                                <?php
                                                if (in_array($row['status'], ['Pending Payment', 'Payment Submitted', 'Active'])) {
                                                ?>
                                                    <a href="view_application_invoice.php?id=<?= $row['application_id']; ?>"
                                                       class="btn btn-sm btn-primary mt-1">
                                                        <i class="fa fa-file-invoice me-1"></i>
                                                        <?= $row['status'] === 'Pending Payment' ? 'View Invoice & Pay' : 'View Invoice'; ?>
                                                    </a>
                                                <?php
                                                } elseif ($row['status'] === 'Rejected') {
                                                    echo '<span class="text-danger fw-bold">Rejected by Admin</span>';
                                                }
                                                ?>
                                            </p>

                                        </div>

                                    </div>

                                </div>

                                <!-- FOOTER -->
                                <div class="card-footer text-muted small">
                                    Applied On: <?= $row['application_date']; ?>
                                </div>

                            </div>
                        </div>

                <?php
                    }
                } else {
                    echo "
                    <div class='col-12'>
                        <div class='text-center py-5'>
                            <div class='d-inline-flex align-items-center justify-content-center rounded-circle mb-4' style='width:90px;height:90px;background:#e8f5e9;'>
                                <i class='fas fa-file-contract fa-2x text-success'></i>
                            </div>
                            <h5 class='fw-semibold mb-2'>No Applications Yet</h5>
                            <p class='text-muted small mb-4'>You haven't applied for any insurance plan yet.<br>Browse our plans and protect your crops today.</p>
                            <a href='insurance_plans.php' class='btn btn-success px-4'>
                                <i class='fas fa-leaf me-2'></i>Browse Plans
                            </a>
                        </div>
                    </div>";
                }
                ?>

            </div>

        </div>
    </div>
<?php
include("./include/footer.php");
?>