<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

$sql = "
SELECT 
    fa.*, 
    ip.plan_name, 
    ip.coverage_level, 
    ip.base_premium_rate
FROM farmer_applications fa
JOIN insurance_plan ip 
ON fa.plan_id = ip.plan_id
ORDER BY fa.application_id DESC
";

$result = mysqli_query($conn, $sql);
?>

<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4">Farmer Insurance Applications</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>Farmer</th>
                    <th>CNIC</th>
                    <th>Mobile</th>
                    <th>Plan</th>
                    <th>Season</th>
                    <th>Crop</th>
                    <th>Area (Acres)</th>
                    <th>Total Yield (Maunds)</th>
                    <th>Irrigation</th>
                    <th>Certified Seed</th>
                    <th>Applied On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $column_count = 14; 
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                            <td><?= $row['application_id']; ?></td>
                            <td>
                                <strong><?= $row['full_name']; ?></strong><br>
                                <small><?= $row['father_name']; ?></small><br>
                                <small><?= $row['district']; ?></small>
                            </td>
                            <td><?= $row['cnic_number']; ?></td>
                            <td><?= $row['mobile_number']; ?></td>

                            <td>
                                <?= $row['plan_name']; ?><br>
                                <small>
                                    Coverage: <?= $row['coverage_level']; ?>% <br>
                                    Rate: <?= $row['base_premium_rate']; ?>%
                                </small>
                            </td>

                            <td><?= $row['policy_duration']; ?></td>
                            <td><?= $row['crop_insured']; ?></td>
                            <td><?= $row['insured_area']; ?></td>
                            <td><?= $row['expected_yield']; ?></td>
                            <td><?= $row['irrigation_type']; ?></td>

                            <td class="text-center">
                                <?= $row['certified_seed'] ? 'Yes' : 'No'; ?>
                            </td>

                            <td>
                                <?= date("d-M-Y", strtotime($row['application_date'])); ?>
                            </td>
                            
                            <td class="text-center">
                                <?php
                                $st = $row['status'];
                                $ps = $row['payment_status'] ?? 'Pending';
                                if ($st === 'Active') {
                                    echo '<span class="badge bg-success">Active</span>';
                                } elseif ($st === 'Payment Submitted') {
                                    echo '<span class="badge bg-info">Payment Submitted</span>';
                                } elseif ($st === 'Pending Payment') {
                                    echo '<span class="badge bg-warning text-dark">Pending Payment</span>';
                                } elseif ($st === 'Rejected') {
                                    echo '<span class="badge bg-danger">Rejected</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">' . htmlspecialchars($st) . '</span>';
                                }
                                if ($ps === 'Rejected') {
                                    echo '<br><span class="badge bg-danger mt-1">Payment Rejected</span>';
                                }
                                ?>
                            </td>

                            <td class="text-center">
                            <?php
                            $st = $row['status'];
                            if ($st === 'Payment Submitted') {
                            ?>
                                <a href="verify_payment.php?app_id=<?= $row['application_id']; ?>"
                                   class="btn btn-sm btn-warning w-100 mb-1">
                                   <i class="fa fa-check-circle me-1"></i> Verify Payment
                                </a>
                                <a href="add_farmer_invoice.php?id=<?= $row['application_id']; ?>"
                                   class="btn btn-sm btn-outline-primary w-100">View Invoice</a>
                            <?php
                            } elseif (in_array($st, ['Pending Payment', 'Active'])) {
                            ?>
                                <a href="add_farmer_invoice.php?id=<?= $row['application_id']; ?>"
                                   class="btn btn-sm btn-primary w-100">
                                   <i class="fa fa-file-invoice me-1"></i> View Invoice
                                </a>
                            <?php
                            } elseif ($st === 'Rejected') {
                                echo '<span class="text-danger fw-bold">Rejected</span>';
                            } else {
                                echo '<span class="text-muted">—</span>';
                            }
                            ?>
                            </td>

                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='{$column_count}' class='text-center text-danger'>No Applications Found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("./include/footer.php"); ?>