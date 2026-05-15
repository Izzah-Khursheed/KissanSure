<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

$sql = "
SELECT ic.*, fa.full_name, fa.crop_insured
FROM insurance_claims ic
JOIN farmer_applications fa 
ON ic.application_id = fa.application_id
ORDER BY ic.claim_id DESC
";

$result = mysqli_query($conn, $sql);
?>

<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4">Insurance Claims</h2>

    <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Farmer</th>
                <th>Crop</th>
                <th>Loss Date</th>
                <th>Est. Loss</th>
                <th>Reason</th>
                <th>Photo</th>
                <th>Claim Status</th>
                <th>Payout Status</th>
                <th>AI Verdict</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= $row['claim_id']; ?></td>
                <td><?= htmlspecialchars($row['full_name']); ?></td>
                <td><?= htmlspecialchars($row['crop_insured']); ?></td>
                <td><?= $row['loss_date']; ?></td>
                <td>Rs. <?= number_format($row['estimated_loss'], 0); ?></td>
                <td><?= htmlspecialchars($row['reason']); ?></td>
                <td>
                    <?php
                    $firstImg = trim(explode(',', $row['evidence_image'])[0]);
                    ?>
                    <img src="images/<?= htmlspecialchars($firstImg); ?>"
                         width="80" height="60" style="object-fit:cover;border-radius:4px;">
                </td>

                <td>
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
                </td>

                <td>
                    <?php
                    $ps = $row['payout_status'] ?? 'Pending';
                    $pbadge = match($ps) {
                        'Sent'      => 'bg-primary',
                        'Completed' => 'bg-success',
                        'Approved'  => 'bg-info',
                        default     => 'bg-secondary',
                    };
                    echo "<span class='badge $pbadge'>$ps</span>";
                    ?>
                </td>

                <td>
                    <?php if ($row['ai_status'] === 'Analyzed'): ?>
                        <span class="badge bg-info"><?= $row['ai_result']; ?></span>
                        <?php if ($row['ai_confidence']): ?>
                            <br><small class="text-muted"><?= round($row['ai_confidence']); ?>%</small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-secondary">Not Analyzed</span>
                    <?php endif; ?>
                </td>

                <td>
                <?php $cs = $row['claim_status']; $ps = $row['payout_status'] ?? 'Pending'; ?>

                <?php if ($cs === 'AI Analyzed'): ?>
                    <a href="under_review_claim.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-secondary btn-sm mb-1 d-block">Mark Under Review</a>
                    <a href="approve_claim.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-success btn-sm mb-1 d-block">Approve</a>
                    <a href="reject_claim.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-danger btn-sm mb-1 d-block">Reject</a>
                    <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-info btn-sm d-block">AI Report</a>

                <?php elseif ($cs === 'Under Review'): ?>
                    <a href="approve_claim.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-success btn-sm mb-1 d-block">Approve</a>
                    <a href="reject_claim.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-danger btn-sm mb-1 d-block">Reject</a>
                    <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-info btn-sm d-block">AI Report</a>

                <?php elseif ($cs === 'Approved' && !in_array($ps, ['Sent', 'Completed'])): ?>
                    <a href="claim_payout.php?claim_id=<?= $row['claim_id']; ?>"
                       class="btn btn-warning btn-sm mb-1 d-block fw-bold">Issue Payout</a>
                    <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-outline-info btn-sm d-block">AI Report</a>

                <?php elseif (in_array($ps, ['Sent', 'Completed'])): ?>
                    <a href="claim_payout.php?claim_id=<?= $row['claim_id']; ?>"
                       class="btn btn-outline-primary btn-sm mb-1 d-block">View Payout</a>
                    <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-outline-info btn-sm d-block">AI Report</a>

                <?php elseif ($cs === 'Rejected'): ?>
                    <span class="badge bg-danger">Rejected</span><br>
                    <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-outline-info btn-sm mt-1">AI Report</a>

                <?php else: ?>
                    <a href="ai_report.php?id=<?= $row['claim_id']; ?>"
                       class="btn btn-outline-info btn-sm">AI Report</a>
                <?php endif; ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>
</div>

<?php include("./include/footer.php"); ?>