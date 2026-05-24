<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

$farmer_id = (int)$_SESSION['farmer_id'];

$result = mysqli_query($conn, "
    SELECT fa.application_id, fa.crop_insured, fa.final_premium,
           fa.transaction_id, fa.payment_date, fa.payment_status,
           fa.payment_notes, fa.status, ip.plan_name
    FROM farmer_applications fa
    JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
    WHERE fa.farmer_id = $farmer_id
    ORDER BY fa.application_id DESC
");

include('./include/navbar.php');
?>

<div class="container-fluid py-5">
    <div class="container">

        <div class="text-center mx-auto mb-5" style="max-width:600px;">
            <p class="section-title bg-white text-center text-primary px-3">My Payments</p>
            <h1 class="display-6">Payment History</h1>
            <p class="text-muted">A record of all premium payments for your insurance applications.</p>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-success">
                            <tr>
                                <th class="ps-4">Plan</th>
                                <th>Crop</th>
                                <th>Premium</th>
                                <th>Transaction ID</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $ps = $row['payment_status'] ?? 'Pending';
                            $badge = match($ps) {
                                'Verified'  => ['bg-success',     'Verified'],
                                'Submitted' => ['bg-info text-dark', 'Under Review'],
                                'Rejected'  => ['bg-danger',      'Rejected'],
                                default     => ['bg-warning text-dark', 'Not Paid'],
                            };
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['plan_name']) ?></td>
                                <td><?= htmlspecialchars($row['crop_insured']) ?></td>
                                <td class="fw-bold text-success">PKR <?= number_format($row['final_premium'], 0) ?></td>
                                <td class="text-muted small">
                                    <?= $row['transaction_id'] ? htmlspecialchars($row['transaction_id']) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $row['payment_date'] ? date('d M Y', strtotime($row['payment_date'])) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                                    <?php if ($ps === 'Rejected' && !empty($row['payment_notes'])): ?>
                                    <div class="text-danger small mt-1">
                                        <i class="fas fa-info-circle me-1"></i><?= htmlspecialchars($row['payment_notes']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="view_application_invoice.php?id=<?= $row['application_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-invoice me-1"></i>Invoice
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="text-center py-5">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                 style="width:90px;height:90px;background:#e8f5e9;">
                <i class="fas fa-receipt fa-2x text-success"></i>
            </div>
            <h5 class="fw-semibold mb-2">No Payment Records Yet</h5>
            <p class="text-muted small mb-4">You haven't applied for any insurance plan yet.<br>Browse our plans to get started.</p>
            <a href="insurance_plans.php" class="btn btn-success px-4">
                <i class="fas fa-leaf me-2"></i>Browse Plans
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include('./include/footer.php'); ?>
