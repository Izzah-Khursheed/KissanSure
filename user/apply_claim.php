<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

include("./include/navbar.php");

$farmer_id        = $_SESSION['farmer_id'];
$preselect_app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

$sql = "SELECT fa.application_id, fa.plan_id, fa.crop_insured, fa.insured_area, fa.coverage_amount, ip.plan_name, ip.deductible_rate
        FROM farmer_applications fa
        JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
        WHERE fa.status = 'Active'
        AND fa.farmer_id = '$farmer_id'";
$result   = mysqli_query($conn, $sql);
$policies = [];
while ($row = mysqli_fetch_assoc($result)) {
    $policies[] = $row;
}

// Handle Submission
if (isset($_POST['submit_claim'])) {

    $application_id = mysqli_real_escape_string($conn, $_POST['application_id']);
    $plan_id        = mysqli_real_escape_string($conn, $_POST['plan_id']);
    $plan_name      = mysqli_real_escape_string($conn, $_POST['plan_name']);
    $loss_date      = mysqli_real_escape_string($conn, $_POST['loss_date']);
    $reason         = mysqli_real_escape_string($conn, $_POST['reason']);
    $estimated_loss = (float)$_POST['estimated_loss'];
    $damaged_area   = (float)$_POST['damaged_area'];

    // Server-side: area and loss validation
    $app_check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT insured_area, coverage_amount FROM farmer_applications WHERE application_id = '$application_id' AND farmer_id = '$farmer_id'"
    ));
    if (!$app_check || $damaged_area > (float)$app_check['insured_area']) {
        echo "<script>showFlash('Damaged area (" . $damaged_area . " acres) cannot exceed your insured area (" . ($app_check['insured_area'] ?? 0) . " acres).', 'warning');</script>";
        goto end_submit;
    }
    $insured_area    = (float)$app_check['insured_area'];
    $coverage_amount = (float)$app_check['coverage_amount'];
    $max_loss        = $insured_area > 0 ? ($damaged_area / $insured_area) * $coverage_amount : $coverage_amount;
    if ($coverage_amount > 0 && $estimated_loss > $max_loss * 1.05) {
        $max_fmt = number_format($max_loss, 0);
        echo "<script>showFlash('Estimated loss is too high. For " . $damaged_area . " damaged acres, maximum realistic loss is PKR " . $max_fmt . ".', 'warning');</script>";
        goto end_submit;
    }

    // Claim attempt check: max 2 per policy; blocked if approved or still pending
    $claim_rows  = mysqli_query($conn,
        "SELECT claim_status FROM insurance_claims
         WHERE application_id = '$application_id' ORDER BY claim_id ASC"
    );
    $all_claims  = mysqli_fetch_all($claim_rows, MYSQLI_ASSOC);
    $claim_count = count($all_claims);
    $last_status = $claim_count > 0 ? $all_claims[$claim_count - 1]['claim_status'] : null;
    $has_approved = false;
    foreach ($all_claims as $cr) {
        if (in_array($cr['claim_status'], ['Approved', 'Payout Sent', 'Completed'])) {
            $has_approved = true; break;
        }
    }

    if ($has_approved) {
        echo "<script>showFlash('This policy already has an approved claim. You cannot file another.', 'warning');</script>";
        goto end_submit;
    } elseif ($claim_count >= 2) {
        echo "<script>showFlash('You have already used your reapply opportunity for this policy. No further claims are allowed.', 'warning');</script>";
        goto end_submit;
    } elseif ($claim_count === 1 && in_array($last_status, ['AI Analyzed', 'Under Review'])) {
        echo "<script>showFlash('Your previous claim is still under review. Please wait for a decision before reapplying.', 'warning');</script>";
        goto end_submit;
    }

    $ai_result     = mysqli_real_escape_string($conn, $_POST['ai_result_hidden']);
    $ai_conf       = (float)$_POST['ai_conf_hidden'];
    $damaged_count = (int)($_POST['damaged_count_hidden'] ?? 0);

    // Fetch coverage and deductible
    $payout_data  = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT fa.coverage_amount, ip.deductible_rate
         FROM farmer_applications fa
         JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
         WHERE fa.application_id = '$application_id'"
    ));
    $cov_amt    = $payout_data ? round((float)$payout_data['coverage_amount'], 2) : 0;
    $deductible = $payout_data ? (float)$payout_data['deductible_rate'] : 0;

    // damage_pct = (damaged_area / insured_area) × (damaged_count / 6) × 100
    $area_ratio   = ($insured_area > 0) ? min($damaged_area / $insured_area, 1.0) : 1.0;
    $ai_pct       = $damaged_count / 6 * 100;
    $damage_pct   = round($area_ratio * $ai_pct, 2);
    $damage_loss  = round($cov_amt * ($damage_pct / 100), 2);
    $final_payout = round($damage_loss * (1 - $deductible / 100), 2);

    if (!isset($_FILES['evidence_images']) || count($_FILES['evidence_images']['name']) !== 6) {
        echo "<script>showFlash('Please upload exactly 6 crop images.', 'warning');</script>";
    } else {
        $ts          = time();
        $savedImages = [];
        $uploadOk    = true;

        for ($i = 0; $i < 6; $i++) {
            $ext     = strtolower(pathinfo($_FILES['evidence_images']['name'][$i], PATHINFO_EXTENSION));
            $newName = $ts . '_' . ($i + 1) . '.' . $ext;
            $dest    = "../admin/images/" . $newName;

            if (move_uploaded_file($_FILES['evidence_images']['tmp_name'][$i], $dest)) {
                $savedImages[] = $newName;
            } else {
                echo "<script>showFlash('Image upload failed for image " . ($i + 1) . ". Please try again.', 'danger');</script>";
                $uploadOk = false;
                break;
            }
        }

        if ($uploadOk) {
            $imagesCsv = implode(',', $savedImages);

            $insert = "INSERT INTO insurance_claims
                (application_id, plan_id, plan_name, loss_date, reason, evidence_image,
                 damaged_area, estimated_loss, ai_status, ai_result, ai_confidence,
                 damaged_count, damage_percentage, damage_loss, final_payout, claim_status)
                VALUES
                ('$application_id','$plan_id','$plan_name','$loss_date','$reason','$imagesCsv',
                 $damaged_area, $estimated_loss, 'Analyzed', '$ai_result', $ai_conf,
                 $damaged_count, $damage_pct, $damage_loss, $final_payout, 'AI Analyzed')";

            if (mysqli_query($conn, $insert)) {
                $new_claim_id = mysqli_insert_id($conn);
                echo "<script>showFlash('Claim submitted successfully. AI analysis complete — pending admin review.', 'success'); setTimeout(function(){ window.location='view_application_claim.php'; }, 2000);</script>";
            } else {
                $db_err = addslashes(mysqli_error($conn));
                echo "<script>showFlash('Database error: $db_err', 'danger');</script>";
            }
        }
    }
    end_submit:;
}
?>

<div class="container-fluid page-header py-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="container text-center py-4">
        <h1 class="display-3 animated slideInDown">Claim</h1>
        <nav aria-label="breadcrumb animated slideInDown">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="#!">Home</a></li>
                <li class="breadcrumb-item"><a href="#!">Pages</a></li>
                <li class="breadcrumb-item active" aria-current="page">Claim</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="text-center mx-auto wow fadeIn mb-5" data-wow-delay="0.1s" style="max-width:600px;">
        <p class="section-title bg-white text-center text-primary px-3">Claim Application</p>
        <h1 class="display-6">Apply for Damaged Claim</h1>
        <p class="text-muted">Upload 6 images of your crop from different angles for AI analysis.</p>
    </div>

    <?php if (empty($policies)): ?>
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="alert alert-warning text-center py-4">
                <h5 class="mb-2"><i class="fa fa-lock me-2"></i>No Active Policy Found</h5>
                <p class="mb-3">You cannot submit a claim because you do not have any active insurance policy.<br>
                Your policy must be <strong>Active</strong> (premium payment verified by admin) before you can file a claim.</p>
                <a href="view_application_plan.php" class="btn btn-primary">View My Applications</a>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="card shadow-sm mx-auto" style="max-width:700px;">
        <div class="card-header bg-warning text-white">
            <h4 class="mb-0 mt-1">Submit Insurance Claim</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" id="claimForm">

                <script>
                    const policies        = <?= json_encode($policies); ?>;
                    const preselectAppId  = <?= $preselect_app_id ?>;
                </script>

                <!-- POLICY SELECT -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Your Policy</label>
                    <select id="policy_select" name="plan_id" class="form-select" required>
                        <option value="">-- Select Policy --</option>
                    </select>
                </div>

                <input type="hidden" name="application_id" id="application_id">
                <input type="hidden" name="plan_name"       id="plan_name">

                <!-- CROP -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Crop Type</label>
                    <select id="crop_select" name="type" class="form-select" required>
                        <option value="">-- Select Policy First --</option>
                    </select>
                </div>

                <!-- DATE -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Date of Loss</label>
                    <input type="date" name="loss_date" class="form-control" required>
                </div>

                <!-- AREA -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Damaged Area (Acres)</label>
                    <input type="number" step="0.01" name="damaged_area" id="damaged_area"
                           class="form-control" required min="0.01">
                    <div id="area_hint" class="form-text text-muted"></div>
                    <div id="area_error" class="text-danger small mt-1" style="display:none;"></div>
                </div>

                <!-- LOSS -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Estimated Loss (PKR)</label>
                    <input type="number" step="1" name="estimated_loss" id="estimated_loss"
                           class="form-control" required min="1">
                    <div id="loss_hint" class="form-text text-muted"></div>
                    <div id="loss_error" class="text-danger small mt-1" style="display:none;"></div>
                </div>

                <!-- REASON -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Reason</label>
                    <select name="reason" class="form-select" required>
                        <option value="">Select</option>
                        <option>Disease Outbreak</option>
                        <!-- <option>Flood</option>
                        <option>Drought</option>
                        <option>Pest Attack</option> -->
                    </select>
                </div>

                <!-- 6-IMAGE UPLOAD -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Upload 6 Crop Images (from different angles)
                    </label>
                    <input type="file" id="imageInputs" name="evidence_images[]"
                           class="form-control" accept="image/*" multiple required>
                    <small class="text-muted">Select exactly 6 images. Uploading the same image more than once is not allowed.</small>
                </div>

                <!-- Image previews -->
                <div id="previewGrid" class="row g-2 mb-3" style="display:none;"></div>
                <div id="uploadStatus" class="mb-3"></div>

                <!-- Run AI Button -->
                <div class="mb-3">
                    <button type="button" id="runAI" class="btn btn-primary w-100" disabled>
                        Run AI Analysis
                    </button>
                </div>

                <!-- Loading -->
                <div id="loading" style="display:none;" class="text-center text-primary mb-3">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    AI is analyzing images... please wait
                </div>

                <!-- AI Verdict -->
                <div id="ai_verdict_box" class="mb-3" style="display:none;">
                    <div class="alert" id="ai_verdict_alert">
                        <h5 id="ai_verdict_title" class="mb-1"></h5>
                        <p id="ai_verdict_text" class="mb-2"></p>
                        <small id="ai_image_summary"></small>
                    </div>
                    <!-- Per-image results -->
                    <div id="ai_image_results" class="row g-2 mt-1"></div>
                    <!-- Calculation breakdown -->
                    <div id="ai_breakdown" class="mt-3"></div>
                </div>

                <!-- Hidden AI fields -->
                <input type="hidden" name="ai_result_hidden"      id="ai_result_hidden">
                <input type="hidden" name="ai_conf_hidden"        id="ai_conf_hidden">
                <input type="hidden" name="damaged_count_hidden"  id="damaged_count_hidden" value="0">
                <input type="hidden" name="is_eligible_hidden"    id="is_eligible_hidden" value="false">

                <button type="submit" name="submit_claim" id="submitBtn"
                        class="btn btn-danger w-100" disabled>
                    Submit Claim
                </button>

            </form>
        </div>
    </div>

<script>
// ---- Policy dropdown ----
const policySelect = document.getElementById("policy_select");
const cropSelect   = document.getElementById("crop_select");

policies.forEach(p => {
    let opt = document.createElement("option");
    opt.value = p.plan_id;
    opt.text  = p.plan_name;
    opt.dataset.app             = p.application_id;
    opt.dataset.planName        = p.plan_name;
    opt.dataset.crop            = p.crop_insured;
    opt.dataset.insured_area    = p.insured_area;
    opt.dataset.coverage_amount = p.coverage_amount;
    opt.dataset.deductible_rate = p.deductible_rate;
    policySelect.appendChild(opt);
});

const damagedAreaInput = document.getElementById("damaged_area");
const areaHint         = document.getElementById("area_hint");
const areaError        = document.getElementById("area_error");
const lossInput        = document.getElementById("estimated_loss");
const lossHint         = document.getElementById("loss_hint");
const lossError        = document.getElementById("loss_error");
let maxInsuredArea     = null;
let coverageAmount     = null;
let deductibleRate     = null;

function getMaxLoss() {
    const area    = parseFloat(damagedAreaInput.value) || 0;
    if (!maxInsuredArea || !coverageAmount || area <= 0) return null;
    return (area / maxInsuredArea) * coverageAmount;
}

function updateLossHint() {
    const maxLoss = getMaxLoss();
    if (maxLoss !== null) {
        lossHint.textContent =
            `For ${parseFloat(damagedAreaInput.value)} damaged acres out of ${maxInsuredArea} insured acres, ` +
            `max realistic loss is PKR ${Math.round(maxLoss).toLocaleString()}.`;
    } else {
        lossHint.textContent = coverageAmount
            ? "Enter damaged area first to see estimated loss range."
            : "Select a policy first.";
    }
    validateLoss();
}

function validateLoss() {
    const maxLoss = getMaxLoss();
    const val = parseFloat(lossInput.value) || 0;
    if (maxLoss !== null && val > maxLoss * 1.05) {
        lossError.textContent =
            `PKR ${val.toLocaleString()} is too high. Max for ${parseFloat(damagedAreaInput.value)} damaged acres is PKR ${Math.round(maxLoss).toLocaleString()}.`;
        lossError.style.display = "block";
        lossInput.setCustomValidity("Estimated loss is unrealistically high.");
    } else {
        lossError.style.display = "none";
        lossInput.setCustomValidity("");
    }
}

policySelect.addEventListener("change", function () {
    const sel = this.options[this.selectedIndex];
    document.getElementById("application_id").value = sel.dataset.app  || "";
    document.getElementById("plan_name").value       = sel.dataset.planName || "";
    cropSelect.innerHTML = sel.dataset.crop
        ? `<option value="${sel.dataset.crop}">${sel.dataset.crop}</option>`
        : `<option value="">-- Select Policy First --</option>`;

    maxInsuredArea = sel.dataset.insured_area    ? parseFloat(sel.dataset.insured_area)    : null;
    coverageAmount = sel.dataset.coverage_amount ? parseFloat(sel.dataset.coverage_amount) : null;
    deductibleRate = sel.dataset.deductible_rate ? parseFloat(sel.dataset.deductible_rate) : null;

    if (maxInsuredArea) {
        areaHint.textContent = `You insured ${maxInsuredArea} acres. Damaged area cannot exceed this.`;
    } else {
        areaHint.textContent = "";
    }
    areaError.style.display = "none";
    damagedAreaInput.value  = "";
    lossInput.value         = "";
    updateLossHint();
    resetAI();
});

// Pre-select policy if redirected from Reapply button (must be after listener is registered)
if (preselectAppId) {
    Array.from(policySelect.options).forEach(opt => {
        if (parseInt(opt.dataset.app) === preselectAppId) {
            policySelect.value = opt.value;
        }
    });
    if (policySelect.value) {
        policySelect.dispatchEvent(new Event('change'));
    }
}

damagedAreaInput.addEventListener("input", function () {
    if (maxInsuredArea !== null) {
        const val = parseFloat(this.value);
        if (val > maxInsuredArea) {
            areaError.textContent = `Cannot exceed insured area of ${maxInsuredArea} acres. You entered ${val} acres.`;
            areaError.style.display = "block";
            this.setCustomValidity("Damaged area exceeds insured area.");
        } else {
            areaError.style.display = "none";
            this.setCustomValidity("");
        }
    }
    updateLossHint();
});

lossInput.addEventListener("input", validateLoss);

// ---- SHA-256 hash of a File using SubtleCrypto ----
async function hashFile(file) {
    const buf  = await file.arrayBuffer();
    const hash = await crypto.subtle.digest("SHA-256", buf);
    return Array.from(new Uint8Array(hash)).map(b => b.toString(16).padStart(2,"0")).join("");
}

// ---- Image input handler ----
const imageInput   = document.getElementById("imageInputs");
const previewGrid  = document.getElementById("previewGrid");
const uploadStatus = document.getElementById("uploadStatus");
const runAIBtn     = document.getElementById("runAI");

imageInput.addEventListener("change", async function () {
    previewGrid.innerHTML = "";
    previewGrid.style.display = "none";
    uploadStatus.innerHTML    = "";
    runAIBtn.disabled         = true;
    resetAI();

    const files = Array.from(this.files);

    if (files.length !== 6) {
        uploadStatus.innerHTML =
            `<div class="alert alert-warning">Please select exactly 6 images. You selected ${files.length}.</div>`;
        return;
    }

    // Duplicate detection via SHA-256
    uploadStatus.innerHTML =
        `<div class="text-muted small">Checking images for duplicates...</div>`;

    const hashes = [];
    for (let i = 0; i < files.length; i++) {
        const h = await hashFile(files[i]);
        if (hashes.includes(h)) {
            uploadStatus.innerHTML =
                `<div class="alert alert-danger">
                    Duplicate image detected! Image ${i + 1} is the same as a previously selected image.
                    Please upload images from 6 different angles.
                </div>`;
            imageInput.value = "";
            return;
        }
        hashes.push(h);
    }

    // Show previews
    previewGrid.style.display = "flex";
    files.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement("div");
            col.className = "col-4 col-md-2";
            col.innerHTML = `
                <div class="text-center">
                    <img src="${e.target.result}" class="img-thumbnail" style="height:80px;object-fit:cover;width:100%;">
                    <small class="text-muted d-block">Img ${idx + 1}</small>
                </div>`;
            previewGrid.appendChild(col);
        };
        reader.readAsDataURL(file);
    });

    uploadStatus.innerHTML =
        `<div class="alert alert-success py-2">6 unique images selected. Click "Run AI Analysis" to proceed.</div>`;
    runAIBtn.disabled = false;
});

// ---- Reset AI state ----
function resetAI() {
    document.getElementById("ai_verdict_box").style.display = "none";
    document.getElementById("ai_result_hidden").value   = "";
    document.getElementById("ai_conf_hidden").value     = "";
    document.getElementById("is_eligible_hidden").value = "false";
    document.getElementById("submitBtn").disabled        = true;
    document.getElementById("ai_breakdown").innerHTML   = "";
}

// ---- Run AI Analysis ----
document.getElementById("runAI").addEventListener("click", async function () {
    const files    = Array.from(imageInput.files);
    const cropType = cropSelect.value;

    if (files.length !== 6) { showFlash("Please select exactly 6 images first.", "warning"); return; }
    if (!cropType)           { showFlash("Please select a policy first.", "warning"); return; }

    document.getElementById("loading").style.display = "block";
    runAIBtn.disabled = true;
    resetAI();

    const formData = new FormData();
    formData.append("crop_type", cropType);
    files.forEach(f => formData.append("images[]", f));

    try {
        const res  = await fetch("analyze_ai.php", { method: "POST", body: formData });
        const data = await res.json();
        document.getElementById("loading").style.display = "none";
        runAIBtn.disabled = false;

        if (data.error) { showFlash("AI Error: " + data.error, "danger"); return; }

        // Show verdict
        const verdictBox   = document.getElementById("ai_verdict_box");
        const verdictAlert = document.getElementById("ai_verdict_alert");
        const verdictTitle = document.getElementById("ai_verdict_title");
        const verdictText  = document.getElementById("ai_verdict_text");
        const imgSummary   = document.getElementById("ai_image_summary");
        const imgResults   = document.getElementById("ai_image_results");

        verdictBox.style.display = "block";

        const dc        = data.damaged_count || 0;
        const area      = parseFloat(damagedAreaInput.value) || 0;
        const areaRatio = (maxInsuredArea > 0) ? Math.min(area / maxInsuredArea, 1.0) : 1.0;
        const aiPct     = Math.round(dc / 6 * 100);
        const dmgPct    = Math.round(areaRatio * aiPct * 100) / 100;   // keep 2 decimals
        const dmgLabel  = dc <= 2 ? 'Low Damage' : (dc <= 4 ? 'Moderate Damage' : 'Severe Damage');
        verdictAlert.className   = dc <= 2 ? 'alert alert-warning' : (dc <= 4 ? 'alert alert-warning' : 'alert alert-danger');
        verdictTitle.textContent = `AI Analysis Complete — ${dmgLabel} (${dmgPct}% Combined Damage)`;
        verdictText.textContent  = `${dc}/6 images classified as damaged. See breakdown below for how the combined damage percentage and estimated payout are calculated.`;

        document.getElementById("damaged_count_hidden").value = dc;

        imgSummary.textContent =
            `Damaged images: ${dc} out of 6 | ${dmgLabel} — ${dmgPct}% combined damage`;

        // ---- Calculation Breakdown ----
        const damageLoss  = coverageAmount !== null ? Math.round(coverageAmount * dmgPct / 100) : null;
        const finalPayout = (damageLoss !== null && deductibleRate !== null)
            ? Math.round(damageLoss * (1 - deductibleRate / 100)) : null;
        const deductAmt   = (damageLoss !== null && deductibleRate !== null)
            ? Math.round(damageLoss * deductibleRate / 100) : null;

        let breakdownHtml = `
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white py-2 fw-bold small">
                    Damage Calculation Breakdown
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr><th>Parameter</th><th>Value</th><th>Formula</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>AI Image Score</td>
                                <td><strong>${aiPct}%</strong></td>
                                <td class="text-muted">${dc} damaged / 6 total × 100</td>
                            </tr>
                            <tr>
                                <td>Area Ratio</td>
                                <td><strong>${(areaRatio * 100).toFixed(2)}%</strong></td>
                                <td class="text-muted">${area} damaged / ${maxInsuredArea} insured acres</td>
                            </tr>
                            <tr class="table-warning">
                                <td><strong>Combined Damage %</strong></td>
                                <td><strong>${dmgPct}%</strong></td>
                                <td class="text-muted">AI ${aiPct}% × Area ${(areaRatio * 100).toFixed(2)}%</td>
                            </tr>`;

        if (coverageAmount !== null && damageLoss !== null) {
            breakdownHtml += `
                            <tr>
                                <td>Coverage Amount</td>
                                <td><strong>PKR ${Math.round(coverageAmount).toLocaleString()}</strong></td>
                                <td class="text-muted">From your plan</td>
                            </tr>
                            <tr>
                                <td>Damage Loss</td>
                                <td><strong>PKR ${damageLoss.toLocaleString()}</strong></td>
                                <td class="text-muted">Coverage × ${dmgPct}%</td>
                            </tr>`;
        }
        if (deductibleRate !== null && deductAmt !== null && finalPayout !== null) {
            breakdownHtml += `
                            <tr>
                                <td>Deductible (${deductibleRate}%)</td>
                                <td><strong>− PKR ${deductAmt.toLocaleString()}</strong></td>
                                <td class="text-muted">Damage loss × ${deductibleRate}%</td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>Estimated Payout</strong></td>
                                <td><strong class="text-success">PKR ${finalPayout.toLocaleString()}</strong></td>
                                <td class="text-muted">Damage loss − deductible</td>
                            </tr>`;
        }

        breakdownHtml += `
                        </tbody>
                    </table>
                </div>
            </div>`;

        document.getElementById("ai_breakdown").innerHTML = breakdownHtml;

        // Per-image mini results — show classifier verdict + confidence (NOT damage %)
        imgResults.innerHTML = "";
        (data.image_results || []).forEach(r => {
            const col = document.createElement("div");
            col.className = "col-4 col-md-2 text-center";
            const badge = r.class === "damaged"
                ? `<span class="badge bg-danger">Damaged</span>`
                : `<span class="badge bg-success">Healthy</span>`;
            col.innerHTML = `<small class="d-block">Img ${r.image}</small>${badge}<br><small class="text-muted">Conf: ${r.confidence}%</small>`;
            imgResults.appendChild(col);
        });

        // Store hidden values
        document.getElementById("ai_result_hidden").value   = data.ai_result;
        document.getElementById("ai_conf_hidden").value     = data.ai_confidence;
        document.getElementById("is_eligible_hidden").value = "true";

        // Enable submit — admin will review and approve/reject
        document.getElementById("submitBtn").disabled = false;

    } catch (err) {
        document.getElementById("loading").style.display = "none";
        runAIBtn.disabled = false;
        showFlash("AI analysis failed: " + err.message, "danger");
        console.error(err);
    }
});
</script>

    <?php endif; // end active policy check ?>
</div>

<?php include("./include/footer.php"); ?>
