<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

include("./include/navbar.php");

$farmer_id = $_SESSION['farmer_id'];
    // ✅ Fetch claims with joins + total claim count per application
    $sql = "SELECT ic.*, fa.full_name, fa.cnic_number, ip.plan_name,
                   (SELECT COUNT(*) FROM insurance_claims ic2
                    WHERE ic2.application_id = ic.application_id) AS total_claims_for_app
            FROM insurance_claims ic
            JOIN farmer_applications fa ON ic.application_id = fa.application_id
            JOIN insurance_plan ip ON ic.plan_id = ip.plan_id
            WHERE fa.farmer_id = '$farmer_id'
            ORDER BY ic.claim_id DESC";

    $result = mysqli_query($conn,$sql);
?>

<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:600px;">
            <p class="section-title bg-white text-center text-primary px-3">
                My Claims
            </p>
            <h1 class="display-6">Your Insurance Claims</h1>
        </div>
        <div class="row g-4">
        <?php
        if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
        ?>
        <!-- ✅ Full width card -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="row g-0">
                    <!-- Image Left (first of 6) -->
                    <div class="col-md-4">
                        <?php $firstImg = trim(explode(',', $row['evidence_image'])[0]); ?>
                        <img src="../admin/images/<?= htmlspecialchars($firstImg) ?>"
                             class="img-fluid h-100 w-100"
                             style="object-fit:cover;">
                    </div>
                    <!-- Content Right -->
                    <div class="col-md-8">
                        <div class="card-body">
                            <h5 class="card-title text-primary">
                                <?= $row['full_name']; ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>CNIC:</strong> <?= $row['cnic_number']; ?></p>
                                    <p><strong>Plan:</strong> <?= $row['plan_name']; ?></p>
                                    <p><strong>Loss Date:</strong> <?= $row['loss_date']; ?></p>
                                    <p><strong>Damage Area:</strong> <?= $row['damaged_area']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Reason:</strong> <?= $row['reason']; ?></p>
                                    <p><strong>Estimated Loss:</strong> 
                                        <span class="text-danger">Rs. <?= $row['estimated_loss']; ?></span>
                                    </p>

                                    <?php
                                    $dc = (int)($row['damaged_count'] ?? 0);
                                    // Use combined damage_percentage (area × AI), not raw ai_confidence
                                    $dp = round((float)($row['damage_percentage'] ?? $row['ai_confidence'] ?? 0), 1);
                                    if ($dc === 0 && $dp > 0) {
                                        $dc = (int)round($dp / 100 * 6);
                                    }
                                    $dpLabel = $dc <= 2 ? 'Low' : ($dc <= 4 ? 'Moderate' : 'Severe');
                                    $dpBadge = $dc <= 2 ? 'bg-warning text-dark' : ($dc <= 4 ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <p><strong>Damage Level:</strong>
                                        <span class="badge <?= $dpBadge ?>"><?= $dpLabel ?> (<?= $dp ?>%)</span>
                                    </p>
                                    <?php if (!empty($row['final_payout'])): ?>
                                    <p><strong>Est. Payout:</strong>
                                        <span class="text-success fw-bold">PKR <?= number_format($row['final_payout'], 2) ?></span>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Status -->
                            <p class="mt-2">
                                <strong>Status:</strong>
                                <?php
                                $cs = $row['claim_status'];
                                $badge = match($cs) {
                                    'AI Analyzed'  => 'bg-info text-dark',
                                    'Under Review' => 'bg-warning text-dark',
                                    'Approved'     => 'bg-success',
                                    'Rejected'     => 'bg-danger',
                                    'Payout Sent'  => 'bg-primary',
                                    'Completed'    => 'bg-dark',
                                    default        => 'bg-secondary',
                                };
                                echo "<span class='badge $badge'>$cs</span>";
                                ?>
                                <?php
                                $ps = $row['payout_status'] ?? 'Pending';
                                if (in_array($ps, ['Sent', 'Completed'])):
                                ?>
                                    &nbsp;<span class="badge bg-info">Payout Ready</span>
                                <?php endif; ?>
                            </p>

                            <div class="d-flex gap-2 flex-wrap mt-2">
                                <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                                   class="btn btn-sm btn-dark">View AI Report</a>

                                <?php if (in_array($row['payout_status'] ?? '', ['Sent', 'Completed'])): ?>
                                    <a href="view_claim_payout.php?claim_id=<?= $row['claim_id']; ?>"
                                       class="btn btn-sm btn-success">
                                       View Payout Receipt
                                    </a>
                                <?php endif; ?>

                                <?php if ($row['claim_status'] === 'Rejected' && (int)$row['total_claims_for_app'] < 2): ?>
                                    <a href="apply_claim.php?app_id=<?= $row['application_id']; ?>"
                                       class="btn btn-sm btn-warning">
                                        Reapply
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="card-footer text-muted small">
                            Submitted: <?= $row['submitted_at']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        }else{
            echo "
            <div class='col-12'>
                <div class='text-center py-5'>
                    <div class='d-inline-flex align-items-center justify-content-center rounded-circle mb-4' style='width:90px;height:90px;background:#e8f5e9;'>
                        <i class='fas fa-shield-halved fa-2x text-success'></i>
                    </div>
                    <h5 class='fw-semibold mb-2'>No Claims Submitted Yet</h5>
                    <p class='text-muted small mb-4'>You haven't filed any damage claims.<br>If your crops are damaged, submit a claim to get assessed.</p>
                    <a href='apply_claim.php' class='btn btn-success px-4'>
                        <i class='fas fa-plus-circle me-2'></i>Submit a Claim
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

