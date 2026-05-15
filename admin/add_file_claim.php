<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

$farmers_sql    = "SELECT * FROM farmer_applications fa WHERE fa.status = 'Active' ORDER BY fa.full_name ASC";
$farmers_result = mysqli_query($conn, $farmers_sql);
$farmers        = [];
while ($f = mysqli_fetch_assoc($farmers_result)) {
    $farmers[] = $f;
}

$policies_sql    = "SELECT fa.application_id, fa.full_name, fa.plan_id,
                    fa.crop_insured, fa.insured_area, fa.coverage_amount, ip.plan_name
                    FROM farmer_applications fa
                    JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
                    WHERE fa.status = 'Active'";
$policies_result = mysqli_query($conn, $policies_sql);
$policies        = [];
while ($p = mysqli_fetch_assoc($policies_result)) {
    $policies[] = $p;
}

if (isset($_POST['submit_claim'])) {
    $application_id = mysqli_real_escape_string($conn, $_POST['application_id']);
    $plan_id        = mysqli_real_escape_string($conn, $_POST['plan_id']);
    $plan_name      = mysqli_real_escape_string($conn, $_POST['plan_name']);
    $loss_date      = mysqli_real_escape_string($conn, $_POST['loss_date']);
    $reason         = mysqli_real_escape_string($conn, $_POST['reason']);
    $desc           = mysqli_real_escape_string($conn, $_POST['description']);
    $estimated_loss = (float)$_POST['estimated_loss'];
    $damaged_area   = (float)$_POST['damaged_area'];

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
        echo "<script>alert('This policy already has an approved claim. Cannot file another claim.');</script>";
        goto end_submit;
    } elseif ($claim_count >= 2) {
        echo "<script>alert('This policy has already used the maximum reapply opportunity. No further claims allowed.');</script>";
        goto end_submit;
    } elseif ($claim_count === 1 && in_array($last_status, ['AI Analyzed', 'Under Review'])) {
        echo "<script>alert('The previous claim for this policy is still under review. Cannot submit another until a decision is made.');</script>";
        goto end_submit;
    }

    $ai_result     = mysqli_real_escape_string($conn, $_POST['ai_result_hidden']);
    $ai_conf       = (float)$_POST['ai_conf_hidden'];
    $damaged_count = (int)($_POST['damaged_count_hidden'] ?? 0);
    $damage_pct    = (int)round($damaged_count / 6 * 100);

    // Pre-calculate payout at submission
    $payout_data  = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT fa.coverage_amount, ip.deductible_rate
         FROM farmer_applications fa
         JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
         WHERE fa.application_id = '$application_id'"
    ));
    $cov_amt      = $payout_data ? round((float)$payout_data['coverage_amount'], 2) : 0;
    $deductible   = $payout_data ? (float)$payout_data['deductible_rate'] : 0;
    $damage_loss  = round($cov_amt * ($damage_pct / 100), 2);
    $final_payout = round($damage_loss * (1 - $deductible / 100), 2);

    $area_check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT insured_area, coverage_amount FROM farmer_applications WHERE application_id = '$application_id'"
    ));
    $insured_area    = $area_check ? (float)$area_check['insured_area']    : 0;
    $coverage_amount = $area_check ? (float)$area_check['coverage_amount'] : 0;
    $max_loss        = ($insured_area > 0 && $coverage_amount > 0)
                       ? ($damaged_area / $insured_area) * $coverage_amount
                       : 0;

    if ($insured_area > 0 && $damaged_area > $insured_area) {
        echo "<script>alert('Damaged area ($damaged_area acres) cannot exceed the insured area ($insured_area acres).');</script>";
    } elseif ($max_loss > 0 && $estimated_loss > $max_loss * 1.05) {
        $max_fmt = number_format($max_loss, 0);
        echo "<script>alert('Estimated loss is too high. For $damaged_area damaged acres, maximum realistic loss is PKR $max_fmt.');</script>";
    } elseif (!isset($_FILES['evidence_images']) || count($_FILES['evidence_images']['name']) !== 6) {
        echo "<script>alert('Please upload exactly 6 crop images.');</script>";
    } else {
        $ts          = time();
        $savedImages = [];
        $uploadOk    = true;

        for ($i = 0; $i < 6; $i++) {
            $ext     = strtolower(pathinfo($_FILES['evidence_images']['name'][$i], PATHINFO_EXTENSION));
            $newName = $ts . '_' . ($i + 1) . '.' . $ext;
            $dest    = "images/" . $newName;

            if (move_uploaded_file($_FILES['evidence_images']['tmp_name'][$i], $dest)) {
                $savedImages[] = $newName;
            } else {
                echo "<script>alert('Image upload failed for image " . ($i + 1) . ".');</script>";
                $uploadOk = false;
                break;
            }
        }

        if ($uploadOk) {
            $imagesCsv = implode(',', $savedImages);

            $sql = "INSERT INTO insurance_claims
                (application_id, plan_id, plan_name, loss_date, reason, description,
                 evidence_image, damaged_area, estimated_loss, ai_status, ai_result, ai_confidence,
                 damage_percentage, damage_loss, final_payout, claim_status)
                VALUES
                ('$application_id','$plan_id','$plan_name','$loss_date','$reason','$desc',
                 '$imagesCsv', $damaged_area, $estimated_loss, 'Analyzed', '$ai_result', $ai_conf,
                 $damage_pct, $damage_loss, $final_payout, 'AI Analyzed')";

            if (mysqli_query($conn, $sql)) {
                $new_claim_id = mysqli_insert_id($conn);
                echo "<script>alert('Claim submitted. AI analysis complete — pending admin review.'); window.location='ai_report.php?id=$new_claim_id';</script>";
            } else {
                echo "Database Error: " . mysqli_error($conn);
            }
        }
    }
    end_submit:;
}
?>

<div class="container mt-5">
<div class="card shadow-sm mx-auto" style="max-width:680px;">

    <div class="card-header bg-danger text-white">
        <h4 class="mb-0">New Insurance Claim</h4>
    </div>

    <div class="card-body">
    <form method="POST" enctype="multipart/form-data" id="adminClaimForm">

        <script>const allPolicies = <?= json_encode($policies); ?>;</script>

        <!-- STEP 1: Farmer -->
        <div class="mb-3">
            <label class="form-label fw-bold">Select Farmer</label>
            <select id="farmer_select" class="form-select" required>
                <option value="">-- Select Farmer --</option>
                <?php foreach ($farmers as $f): ?>
                    <option value="<?= $f['application_id'] ?>">
                        <?= $f['full_name'] ?> (App ID: <?= $f['application_id'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- STEP 2: Policy -->
        <div class="mb-3">
            <label class="form-label fw-bold">Select Active Policy</label>
            <select id="policy_select" name="plan_id" class="form-select" required disabled>
                <option value="">-- Select Farmer First --</option>
            </select>
            <input type="hidden" name="application_id" id="application_id">
            <input type="hidden" name="plan_name"       id="plan_name">
        </div>

        <!-- STEP 3: Crop -->
        <div class="mb-3">
            <label class="form-label fw-bold">Crop Type</label>
            <select id="crop_select" name="type" class="form-select" required disabled>
                <option value="">-- Select Policy First --</option>
            </select>
        </div>

        <!-- Date of Loss -->
        <div class="mb-3">
            <label class="form-label fw-bold">Date of Loss</label>
            <input type="date" name="loss_date" class="form-control" required>
        </div>

        <!-- Damaged Area -->
        <div class="mb-3">
            <label class="form-label fw-bold">Damaged Field Area (Acres)</label>
            <input type="number" step="0.01" name="damaged_area" id="damaged_area" class="form-control" required min="0.01">
            <div id="area_hint" class="form-text text-muted"></div>
            <div id="area_error" class="text-danger small mt-1" style="display:none;"></div>
        </div>

        <!-- Estimated Loss -->
        <div class="mb-3">
            <label class="form-label fw-bold">Estimated Loss Amount (PKR)</label>
            <input type="number" step="1" name="estimated_loss" id="estimated_loss"
                   class="form-control" required min="1">
            <div id="loss_hint" class="form-text text-muted"></div>
            <div id="loss_error" class="text-danger small mt-1" style="display:none;"></div>
        </div>

        <!-- Reason -->
        <div class="mb-3">
            <label class="form-label fw-bold">Reason</label>
            <select name="reason" class="form-select" required>
                <option value="">-- Select Reason --</option>
                <option value="Disease Outbreak">Disease Outbreak</option>
                <!-- <option value="Hailstorm">Hailstorm</option>
                <option value="Flood">Flood</option>
                <option value="Drought">Drought</option>
                <option value="Pest Attack">Pest Attack</option> -->
            </select>
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label class="form-label fw-bold">Short Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <!-- 6-IMAGE UPLOAD -->
        <div class="mb-3">
            <label class="form-label fw-bold">
                Upload 6 Crop Images (from different angles)
            </label>
            <input type="file" id="imageInputs" name="evidence_images[]"
                   class="form-control" accept="image/*" multiple required>
            <small class="text-muted">Select exactly 6 images. Duplicate images are not allowed.</small>
        </div>

        <!-- Previews -->
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
                <p  id="ai_verdict_text"  class="mb-2"></p>
                <small id="ai_image_summary"></small>
            </div>
            <div id="ai_image_results" class="row g-2 mt-1"></div>
        </div>

        <!-- Hidden Fields -->
        <input type="hidden" name="ai_result_hidden"     id="ai_result_hidden">
        <input type="hidden" name="ai_conf_hidden"       id="ai_conf_hidden">
        <input type="hidden" name="damaged_count_hidden" id="damaged_count_hidden" value="0">
        <input type="hidden" name="is_eligible_hidden"   id="is_eligible_hidden" value="false">

        <!-- Submit -->
        <button type="submit" id="submitBtn" name="submit_claim"
                class="btn btn-danger w-100" disabled>
            Submit Claim
        </button>

    </form>
    </div>
</div>
</div>

<script>
// ---- Farmer → Policy cascade ----
document.getElementById("farmer_select").addEventListener("change", function () {
    const selectedAppId = this.value;
    const policySelect  = document.getElementById("policy_select");
    const cropSelect    = document.getElementById("crop_select");

    policySelect.innerHTML = '<option value="">-- Select Policy --</option>';
    cropSelect.innerHTML   = '<option value="">-- Select Policy First --</option>';
    cropSelect.disabled    = true;
    policySelect.disabled  = true;
    document.getElementById("application_id").value = "";
    document.getElementById("plan_name").value       = "";

    if (!selectedAppId) return;

    allPolicies.filter(p => p.application_id == selectedAppId).forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.plan_id;
        opt.textContent = p.plan_name;
        opt.setAttribute("data-crop",             p.crop_insured);
        opt.setAttribute("data-app-id",           p.application_id);
        opt.setAttribute("data-plan-name",        p.plan_name);
        opt.setAttribute("data-insured-area",     p.insured_area);
        opt.setAttribute("data-coverage-amount",  p.coverage_amount);
        policySelect.appendChild(opt);
    });
    policySelect.disabled = false;
});

document.getElementById("policy_select").addEventListener("change", function () {
    const sel        = this.options[this.selectedIndex];
    const cropSelect = document.getElementById("crop_select");
    const areaInput    = document.getElementById("damaged_area");
    const areaHint     = document.getElementById("area_hint");
    const areaError    = document.getElementById("area_error");
    const lossInput    = document.getElementById("estimated_loss");
    const lossHint     = document.getElementById("loss_hint");
    const lossError    = document.getElementById("loss_error");

    cropSelect.innerHTML = '<option value="">-- Select Crop --</option>';
    cropSelect.disabled  = true;
    document.getElementById("application_id").value = "";
    document.getElementById("plan_name").value       = "";
    areaHint.textContent    = "";
    areaError.style.display = "none";
    areaInput.setCustomValidity("");
    lossHint.textContent    = "Select a policy first.";
    lossError.style.display = "none";
    lossInput.setCustomValidity("");

    if (!this.value) return;

    document.getElementById("application_id").value = sel.getAttribute("data-app-id");
    document.getElementById("plan_name").value       = sel.getAttribute("data-plan-name");

    const insuredArea    = parseFloat(sel.getAttribute("data-insured-area"))    || 0;
    const coverageAmount = parseFloat(sel.getAttribute("data-coverage-amount")) || 0;

    if (insuredArea > 0) {
        areaHint.textContent = `Insured area for this policy: ${insuredArea} acres. Damaged area cannot exceed this.`;
    }
    lossHint.textContent = coverageAmount > 0
        ? `Enter damaged area first to see max realistic loss (coverage: PKR ${Math.round(coverageAmount).toLocaleString()}).`
        : "Enter damaged area to calculate max realistic loss.";

    areaInput.addEventListener("input", function () {
        const area = parseFloat(this.value) || 0;
        if (insuredArea > 0 && area > insuredArea) {
            areaError.textContent = `Cannot exceed insured area of ${insuredArea} acres.`;
            areaError.style.display = "block";
            this.setCustomValidity("Damaged area exceeds insured area.");
        } else {
            areaError.style.display = "none";
            this.setCustomValidity("");
        }
        if (area > 0 && insuredArea > 0 && coverageAmount > 0) {
            const maxLoss = (area / insuredArea) * coverageAmount;
            lossHint.textContent = `For ${area} damaged acres: max realistic loss is PKR ${Math.round(maxLoss).toLocaleString()}.`;
        } else {
            lossHint.textContent = "Enter damaged area to calculate max realistic loss.";
        }
        validateLoss(insuredArea, coverageAmount);
    }, { once: false });

    lossInput.addEventListener("input", function () {
        validateLoss(insuredArea, coverageAmount);
    });

    function validateLoss(insArea, covAmt) {
        const area = parseFloat(areaInput.value) || 0;
        const val  = parseFloat(lossInput.value) || 0;
        if (area > 0 && insArea > 0 && covAmt > 0) {
            const maxLoss = (area / insArea) * covAmt;
            if (val > maxLoss * 1.05) {
                lossError.textContent = `PKR ${val.toLocaleString()} exceeds max realistic loss of PKR ${Math.round(maxLoss).toLocaleString()} for ${area} damaged acres.`;
                lossError.style.display = "block";
                lossInput.setCustomValidity("Estimated loss is unrealistically high.");
                return;
            }
        }
        lossError.style.display = "none";
        lossInput.setCustomValidity("");
    }

    const opt = document.createElement("option");
    opt.value = sel.getAttribute("data-crop");
    opt.textContent = sel.getAttribute("data-crop");
    cropSelect.appendChild(opt);
    cropSelect.disabled = false;
    cropSelect.value    = sel.getAttribute("data-crop");
    resetAI();
});

// ---- SHA-256 duplicate detection ----
async function hashFile(file) {
    const buf  = await file.arrayBuffer();
    const hash = await crypto.subtle.digest("SHA-256", buf);
    return Array.from(new Uint8Array(hash)).map(b => b.toString(16).padStart(2,"0")).join("");
}

const imageInput   = document.getElementById("imageInputs");
const previewGrid  = document.getElementById("previewGrid");
const uploadStatus = document.getElementById("uploadStatus");
const runAIBtn     = document.getElementById("runAI");

imageInput.addEventListener("change", async function () {
    previewGrid.innerHTML         = "";
    previewGrid.style.display     = "none";
    uploadStatus.innerHTML        = "";
    runAIBtn.disabled             = true;
    resetAI();

    const files = Array.from(this.files);

    if (files.length !== 6) {
        uploadStatus.innerHTML =
            `<div class="alert alert-warning">Please select exactly 6 images. You selected ${files.length}.</div>`;
        return;
    }

    uploadStatus.innerHTML = `<div class="text-muted small">Checking for duplicates...</div>`;

    const hashes = [];
    for (let i = 0; i < files.length; i++) {
        const h = await hashFile(files[i]);
        if (hashes.includes(h)) {
            uploadStatus.innerHTML =
                `<div class="alert alert-danger">
                    Duplicate image detected at position ${i + 1}!
                    Please upload images from 6 different angles.
                </div>`;
            imageInput.value = "";
            return;
        }
        hashes.push(h);
    }

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


function resetAI() {
    document.getElementById("ai_verdict_box").style.display   = "none";
    document.getElementById("ai_result_hidden").value         = "";
    document.getElementById("ai_conf_hidden").value           = "";
    document.getElementById("is_eligible_hidden").value       = "false";
    document.getElementById("submitBtn").disabled             = true;
}

// ---- Run AI Analysis ----
document.getElementById("runAI").addEventListener("click", async function () {
    const files    = Array.from(imageInput.files);
    const cropType = document.getElementById("crop_select").value;

    if (files.length !== 6) { alert("Please select exactly 6 images first."); return; }
    if (!cropType)           { alert("Please select a policy first."); return; }

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

        if (data.error) { alert("AI Error: " + data.error); return; }

        const verdictBox   = document.getElementById("ai_verdict_box");
        const verdictAlert = document.getElementById("ai_verdict_alert");
        const verdictTitle = document.getElementById("ai_verdict_title");
        const verdictText  = document.getElementById("ai_verdict_text");
        const imgSummary   = document.getElementById("ai_image_summary");
        const imgResults   = document.getElementById("ai_image_results");

        verdictBox.style.display = "block";

        const dc = data.damaged_count || 0;
        const dmgPct   = Math.round(dc / 6 * 100);
        const dmgLabel = dc <= 2 ? 'Low Damage' : (dc <= 4 ? 'Moderate Damage' : 'Severe Damage');
        verdictAlert.className   = dc <= 2 ? 'alert alert-warning' : (dc <= 4 ? 'alert alert-warning' : 'alert alert-danger');
        verdictTitle.textContent = `AI Analysis Complete — ${dmgLabel} (${dmgPct}%)`;
        verdictText.textContent  = `${dc} out of 6 images show crop damage. Estimated damage: ${dmgPct}%. Admin will review this claim and make the final decision.`;

        document.getElementById("damaged_count_hidden").value = dc;

        imgSummary.textContent =
            `Damaged images: ${dc} out of 6 | ${dmgLabel} — ${dmgPct}%`;

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

        document.getElementById("ai_result_hidden").value   = data.ai_result;
        document.getElementById("ai_conf_hidden").value     = data.ai_confidence;
        document.getElementById("is_eligible_hidden").value = "true";
        document.getElementById("submitBtn").disabled       = false;

    } catch (err) {
        document.getElementById("loading").style.display = "none";
        runAIBtn.disabled = false;
        alert("AI analysis failed: " + err.message);
        console.error(err);
    }
});
</script>

<?php include("./include/footer.php"); ?>
