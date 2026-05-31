<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

include("./include/navbar.php");

if (!isset($_GET['id'])) {
    echo "Invalid Request";
    exit();
}

$id  = (int)$_GET['id'];
$sql = "SELECT ic.*, fa.full_name, fa.crop_insured, fa.coverage_amount, fa.insured_area,
               ip.plan_name, ip.deductible_rate
        FROM insurance_claims ic
        JOIN farmer_applications fa ON ic.application_id = fa.application_id
        JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
        WHERE ic.claim_id = $id";

$result = mysqli_query($conn, $sql);
$data   = mysqli_fetch_assoc($result);

if (!$data) {
    echo "Claim not found.";
    exit();
}

// Parse evidence_image: may be comma-separated (new) or single filename (legacy)
$evidenceImages = array_filter(array_map('trim', explode(',', $data['evidence_image'])));

// Exact values from AI analysis
$exact_count = (int)($data['damaged_count'] ?? 0);
$exact_pct   = round((float)($data['ai_confidence'] ?? 0), 1);
// Fallback for legacy claims where damaged_count was not stored
if ($exact_count === 0 && $exact_pct > 0) {
    $exact_count = (int)round($exact_pct / 100 * 6);
}
$dmg_label = $exact_count <= 2 ? 'Low Damage' : ($exact_count <= 4 ? 'Moderate Damage' : 'Severe Damage');
$dmg_badge = $exact_count <= 2 ? 'bg-warning text-dark' : ($exact_count <= 4 ? 'bg-warning' : 'bg-danger');
$isDamaged = ($data['ai_result'] === 'damaged');

// Breakdown values
$ai_pct       = round((float)($data['ai_confidence'] ?? 0), 1);   // raw AI image score (%)
$insured_area = (float)($data['insured_area'] ?? 0);
$damaged_area = (float)($data['damaged_area'] ?? 0);
$area_ratio   = $insured_area > 0 ? round(($damaged_area / $insured_area) * 100, 2) : 100;
$damage_pct   = round((float)($data['damage_percentage'] ?? $ai_pct), 2); // combined (area × AI)

// Calculate payout from stored values or compute on the fly
$coverage_amount = (float)($data['coverage_amount'] ?? 0);
$deductible_rate = (float)($data['deductible_rate'] ?? 0);
$damage_loss     = $data['damage_loss']  ? (float)$data['damage_loss']  : round($coverage_amount * ($damage_pct / 100), 2);
$final_payout    = $data['final_payout'] ? (float)$data['final_payout'] : round($damage_loss * (1 - $deductible_rate / 100), 2);
?>

<style>
@media print {
    @page { margin: 1cm; size: A4 portrait; }
    #spinner, .top-bar, .nav-bar, .container-fluid.footer,
    .back-to-top, .card-footer, .text-center.mx-auto { display: none !important; }
    body { margin: 0; }
    .container-fluid.py-5 { padding: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; }
    #printCard { box-shadow: none !important; }
}
</style>

<div class="container-fluid py-5">
    <div class="container">

        <div class="text-center mx-auto mb-5" style="max-width:600px;">
            <p class="section-title bg-white text-center text-primary px-3">Your Report</p>
            <h1 class="display-6">Your Insurance Claim Report</h1>
        </div>

        <div class="card shadow-lg" id="printCard">
            <div class="card-header bg-warning text-white text-center">
                <h3>AI Crop Damage Assessment Report</h3>
            </div>

            <div class="card-body" id="printArea">

                <!-- Farmer & Loss Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-primary">Farmer Information</h5>
                        <table class="table table-bordered">
                            <tr><th>Farmer Name</th>      <td><?= htmlspecialchars($data['full_name']) ?></td></tr>
                            <tr><th>Crop Type</th>        <td><?= htmlspecialchars($data['crop_insured']) ?></td></tr>
                            <tr><th>Insurance Policy</th> <td><?= htmlspecialchars($data['plan_name']) ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary">Loss Details</h5>
                        <table class="table table-bordered">
                            <tr><th>Date of Loss</th>       <td><?= htmlspecialchars($data['loss_date']) ?></td></tr>
                            <tr><th>Damaged Field Area</th> <td><?= htmlspecialchars($data['damaged_area']) ?> Acres</td></tr>
                            <tr><th>Estimated Loss</th>     <td>Rs. <?= htmlspecialchars($data['estimated_loss']) ?></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Claim Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-primary">Claim Information</h5>
                        <table class="table table-bordered">
                            <tr><th>Reason</th>           <td><?= htmlspecialchars($data['reason']) ?></td></tr>
                            <?php /* <tr><th>Short Description</th><td><?= htmlspecialchars($data['description'] ?? '') ?></td></tr> */ ?>
                            <tr>
                                <th>Claim Status</th>
                                <td>
                                    <?php
                                    $cs = $data['claim_status'];
                                    $cb = match($cs) {
                                        'AI Analyzed'  => 'bg-info text-dark',
                                        'Under Review' => 'bg-warning text-dark',
                                        'Approved'     => 'bg-success',
                                        'Rejected'     => 'bg-danger',
                                        'Payout Sent'  => 'bg-primary',
                                        'Completed'    => 'bg-dark',
                                        default        => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $cb ?>"><?= htmlspecialchars($cs) ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <!-- empty or future use -->
                    </div>
                </div>

                <!-- Uploaded Images (all 6) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="text-primary">Uploaded Crop Images</h5>
                        <div class="row g-2">
                            <?php foreach ($evidenceImages as $idx => $imgFile): ?>
                                <div class="col-6 col-md-2 text-center">
                                    <img src="../admin/images/<?= htmlspecialchars($imgFile) ?>"
                                         class="img-thumbnail"
                                         style="height:100px;object-fit:cover;width:100%;"
                                         alt="Crop Image <?= $idx + 1 ?>">
                                    <small class="text-muted d-block">Image <?= $idx + 1 ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- AI Result -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="text-primary">AI Analysis Result</h5>

                        <div class="alert <?= $exact_count >= 5 ? 'alert-danger' : 'alert-warning' ?>">
                            <h5 class="alert-heading">
                                AI Analysis Complete —
                                <span class="badge <?= $dmg_badge ?> fs-6"><?= $dmg_label ?> (<?= $damage_pct ?>% Combined Damage)</span>
                            </h5>
                            <p class="mb-1"><?= $exact_count ?> out of 6 submitted images classified as damaged (AI score: <?= $ai_pct ?>%).
                               Combined with field area (<?= $damaged_area ?>/<?= $insured_area ?> acres), final damage is <strong><?= $damage_pct ?>%</strong>.</p>
                            <hr>
                            <p class="mb-0">Your claim is pending admin review. Compensation is calculated below.</p>
                        </div>

                        <table class="table table-bordered">
                            <tr>
                                <th>AI Image Classification</th>
                                <td>
                                    <span class="badge <?= $isDamaged ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $isDamaged ? 'Damage Detected' : 'Minimal/No Damage' ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Damaged Images (AI Score)</th>
                                <td><strong><?= $exact_count ?> / 6 = <?= $ai_pct ?>%</strong></td>
                            </tr>
                            <tr>
                                <th>Damaged Field Area (Area Ratio)</th>
                                <td><strong><?= $damaged_area ?> / <?= $insured_area ?> acres = <?= $area_ratio ?>%</strong></td>
                            </tr>
                            <tr class="table-warning">
                                <th>Combined Damage %
                                    <small class="text-muted d-block fw-normal">AI Score × Area Ratio</small>
                                </th>
                                <td>
                                    <span class="badge <?= $dmg_badge ?> fs-6"><?= $dmg_label ?> — <?= $damage_pct ?>%</span>
                                    <small class="text-muted d-block"><?= $ai_pct ?>% × <?= $area_ratio ?>% = <?= $damage_pct ?>%</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Coverage Amount</th>
                                <td>PKR <?= number_format($coverage_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <th>Damage Loss
                                    <small class="text-muted d-block fw-normal">Coverage × <?= $damage_pct ?>%</small>
                                </th>
                                <td>PKR <?= number_format($damage_loss, 2) ?></td>
                            </tr>
                            <tr>
                                <th>Deductible (<?= $deductible_rate ?>%)</th>
                                <td class="text-danger">− PKR <?= number_format($damage_loss * $deductible_rate / 100, 2) ?></td>
                            </tr>
                            <tr class="table-success">
                                <th>Estimated Payout</th>
                                <td><strong class="text-success fs-5">PKR <?= number_format($final_payout, 2) ?></strong></td>
                            </tr>
                            <tr>
                                <th>Admin Decision</th>
                                <td>
                                    <?php
                                    $cs = $data['claim_status'];
                                    if ($cs === 'Approved' || $cs === 'Payout Sent' || $cs === 'Completed') {
                                        echo '<span class="badge bg-success">Approved</span>';
                                    } elseif ($cs === 'Rejected') {
                                        echo '<span class="badge bg-danger">Rejected</span>';
                                    } else {
                                        echo '<span class="badge bg-warning text-dark">Pending Admin Review</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <p><strong>Generated by AI Crop Damage Detection System &mdash; KissanSure</strong></p>
                </div>

            </div><!-- /card-body -->

            <div class="card-footer text-center">
                <button onclick="window.print()" class="btn btn-primary">Print Report</button>
                <button onclick="downloadPDF()" class="btn btn-success ms-2">
                    <i class="fa fa-file-pdf me-1"></i> Download PDF
                </button>
                <a href="view_application_claim.php" class="btn btn-secondary ms-2">Back</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function downloadPDF() {
    var footer = document.querySelector('#printCard .card-footer');
    if (footer) footer.style.display = 'none';

    html2canvas(document.getElementById('printCard'), {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
    }).then(function(canvas) {
        if (footer) footer.style.display = '';

        var { jsPDF } = window.jspdf;
        var pdf     = new jsPDF('p', 'mm', 'a4');
        var pageW   = pdf.internal.pageSize.getWidth();
        var pageH   = pdf.internal.pageSize.getHeight();
        var margin  = 10;
        var imgW    = pageW - margin * 2;
        var imgH    = canvas.height * imgW / canvas.width;
        var imgData = canvas.toDataURL('image/png');

        var y         = margin;
        var remaining = imgH;

        pdf.addImage(imgData, 'PNG', margin, y, imgW, imgH);
        remaining -= (pageH - margin * 2);

        while (remaining > 0) {
            pdf.addPage();
            y = margin - (imgH - remaining) - margin;
            pdf.addImage(imgData, 'PNG', margin, y, imgW, imgH);
            remaining -= (pageH - margin * 2);
        }

        pdf.save('ai-claim-report.pdf');
    });
}
</script>

<?php include('./include/footer.php'); ?>
