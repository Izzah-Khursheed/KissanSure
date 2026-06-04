<?php
session_start();
include("./include/connection.php");

/* CHECK PLAN ID — redirect before any HTML output */
if (!isset($_GET['plan_id'])) {
    header("Location: insurance_plans.php");
    exit();
}

$selected_plan_id = (int)$_GET['plan_id'];

/* FETCH SELECTED PLAN — redirect before any HTML output */
$plan_query = mysqli_query($conn, "SELECT * FROM insurance_plan WHERE plan_id = $selected_plan_id");
$plan_data  = mysqli_fetch_assoc($plan_query);

if (!$plan_data) {
    header("Location: insurance_plans.php");
    exit();
}

/* HANDLE FORM SUBMISSION — must run BEFORE any HTML output */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_application']) && isset($_SESSION['farmer_id'])) {
    $farmer_id = $_SESSION['farmer_id'];

    $plan_id          = (int)$_POST['plan_id'];
    $policy_duration  = mysqli_real_escape_string($conn, $_POST["policy_duration"]);
    $cnic_number      = mysqli_real_escape_string($conn, $_POST["cnic_number"]);
    $mobile_number    = mysqli_real_escape_string($conn, $_POST["mobile_number"]);
    $full_name        = mysqli_real_escape_string($conn, $_POST["full_name"]);
    $father_name      = mysqli_real_escape_string($conn, $_POST["father_name"]);
    $district         = mysqli_real_escape_string($conn, $_POST["district"]);
    $bank_account     = mysqli_real_escape_string($conn, $_POST["bank_account"]);
    $insured_area     = mysqli_real_escape_string($conn, $_POST["insured_area"]);
    $crop_insured     = mysqli_real_escape_string($conn, $_POST["crop_insured"]);
    $sowing_date      = mysqli_real_escape_string($conn, $_POST["sowing_date"]);
    $irrigation_type  = mysqli_real_escape_string($conn, $_POST["irrigation_type"]);
    $expected_yield   = mysqli_real_escape_string($conn, $_POST["expected_yield"]);
    $historical_yield = mysqli_real_escape_string($conn, $_POST["historical_yield"]);
    $seed_variety     = mysqli_real_escape_string($conn, $_POST["seed_variety"]);
    $certified_seed   = isset($_POST['certified_seed']) ? 1 : 0;

    // Duplicate plan check: same farmer + crop + season
    $dup = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS cnt FROM farmer_applications
         WHERE farmer_id = '$farmer_id' AND crop_insured = '$crop_insured'
           AND policy_duration = '$policy_duration' AND status NOT IN ('Rejected')"
    ));
    if ($dup['cnt'] > 0) {
        $form_error = "You already have an active or pending application for {$crop_insured} ({$policy_duration} season). You cannot apply again for the same crop and season.";
    }

    // Total land area cap check
    if (empty($form_error)) {
        $farmer_field_size = (float)(mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT field_size FROM register_farmer WHERE farmerid = '$farmer_id'"
        ))['field_size'] ?? 0);
        $used_now = (float)mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COALESCE(SUM(insured_area), 0) AS used
             FROM farmer_applications WHERE farmer_id = '$farmer_id' AND status NOT IN ('Rejected')"
        ))['used'];
        $remaining_now = $farmer_field_size - $used_now;
        if ((float)$insured_area > $remaining_now) {
            $form_error = "You registered " . $farmer_field_size . " acres total. You have already insured " . number_format($used_now, 1) . " acres. You can only insure up to " . number_format(max($remaining_now, 0), 1) . " more acres.";
        }
    }

    if (empty($form_error)) :

    $insert_sql = "INSERT INTO farmer_applications (
        farmer_id, plan_id, policy_duration,
        cnic_number, full_name, father_name, mobile_number, district, bank_account,
        insured_area, irrigation_type,
        crop_insured, expected_yield, historical_yield, seed_variety, sowing_date,
        certified_seed, status
    ) VALUES (
        '$farmer_id', '$plan_id', '$policy_duration',
        '$cnic_number', '$full_name', '$father_name', '$mobile_number', '$district', '$bank_account',
        '$insured_area', '$irrigation_type',
        '$crop_insured', '$expected_yield', '$historical_yield', '$seed_variety', '$sowing_date',
        '$certified_seed', 'Pending Payment'
    )";

    if (mysqli_query($conn, $insert_sql)) {
        $new_app_id = mysqli_insert_id($conn);

        // Auto-calculate premium using dynamic market rate
        $plan_row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT base_premium_rate, coverage_level FROM insurance_plan WHERE plan_id = '$plan_id'"
        ));
        $rate_row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT price_per_maund FROM crop_market_rates WHERE crop_name = '$crop_insured' LIMIT 1"
        ));
        $market_rate       = $rate_row ? (float)$rate_row['price_per_maund'] : 3200.00;
        $base_premium_rate = (float)$plan_row['base_premium_rate'];
        $coverage_level    = (float)$plan_row['coverage_level'];

        // expected_yield is now TOTAL maunds (not per acre)
        $sum_insured           = round((float)$expected_yield * $market_rate, 2);
        $coverage_amount       = round($sum_insured * ($coverage_level / 100), 2);
        $final_premium         = round($coverage_amount * ($base_premium_rate / 100), 2);
        $guaranteed_production = round((float)$expected_yield * ($coverage_level / 100), 2);

        mysqli_query($conn, "
            UPDATE farmer_applications SET
                sum_insured           = $sum_insured,
                coverage_amount       = $coverage_amount,
                final_premium         = $final_premium,
                guaranteed_production = $guaranteed_production
            WHERE application_id = $new_app_id
        ");

        // Notify farmer to pay their premium
        include_once __DIR__ . '/include/notifications_helper.php';
        add_notification($conn, $farmer_id, 'premium_reminder',
            'Premium Payment Required',
            'Application submitted! Pay PKR ' . number_format($final_premium, 2) . ' to activate your insurance plan.',
            BASE_URL . '/user/view_application_invoice.php?id=' . $new_app_id
        );

        // Redirect straight to invoice so farmer sees premium immediately
        header("Location: view_application_invoice.php?id=$new_app_id&success=1");
        exit();
    } else {
        $form_error = "Database error: " . mysqli_error($conn);
    }

    endif; // end empty($form_error)
}

/* HTML output starts here */
include("./include/navbar.php");

/* LOGIN CHECK — show inline UI instead of redirect */
if (!isset($_SESSION['farmer_id'])): ?>
<div class="container-fluid py-5">
    <div class="container text-center">
        <p class="section-title bg-white text-center text-primary px-3">Crop Insurance Application</p>
        <h1 class="display-6 mb-4">Apply for <?= htmlspecialchars($plan_data['plan_name']) ?></h1>
        <div class="alert alert-warning d-inline-block px-5 py-3">
            <i class="fa fa-lock me-2"></i> Please login first to apply for this insurance plan.
        </div>
        <br>
        <a href="login.php" class="btn btn-primary px-4 py-2 mt-3">Login Now</a>
    </div>
</div>
<?php
include("./include/footer.php");
exit();
endif;

/* Logged in — fetch farmer-specific data */
$farmer_id = $_SESSION['farmer_id'];

$farmer_query = mysqli_query($conn, "SELECT * FROM register_farmer WHERE farmerid = $farmer_id");
$farmer_data  = mysqli_fetch_assoc($farmer_query);

$used_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(insured_area), 0) AS used
     FROM farmer_applications WHERE farmer_id = '$farmer_id' AND status NOT IN ('Rejected')"
));
$farmer_field_size = (float)($farmer_data['field_size'] ?? 0);
$farmer_used_area  = (float)$used_row['used'];
$farmer_remaining  = $farmer_field_size - $farmer_used_area;
?>

<style>
    input::placeholder {
        color: #9ca3af;
        opacity: 1;
    }
    select.select-placeholder {
        color: #9ca3af;
    }
    select.select-placeholder option {
        color: #212529;
    }
    input[type="date"].date-empty {
        color: #9ca3af;
    }
</style>

<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 600px;">
            <p class="section-title bg-white text-center text-primary px-3">Crop Insurance Application</p>
            <h1 class="display-6">Apply for <?php echo htmlspecialchars($plan_data['plan_name']); ?></h1>
        </div>

        <?php if (!empty($form_error)): ?>
            <div class="alert alert-danger"><?php echo $form_error; ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="bg-light p-5 rounded shadow-sm">
                    <form method="post">
                        <input type="hidden" name="plan_id" value="<?php echo $selected_plan_id; ?>">

                        <h4 class="text-primary border-bottom pb-2 mb-4">1. Selected Insurance Plan</h4>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Plan Details</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($plan_data['plan_name']); ?> (Coverage: <?php echo $plan_data['coverage_level']; ?>%)" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Policy Duration</label>
                                <select name="policy_duration" class="form-select" required>
                                    <option value="">-- Select Season --</option>
                                    <option value="Kharif">Kharif Season (Summer Crops)</option>
                                    <option value="Rabi">Rabi Season (Winter Crops)</option>
                                </select>
                            </div>
                        </div>

                        <h4 class="text-primary border-bottom pb-2 mb-4">2. Farmer Personal Details</h4>

                        <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
                            <i class="fa fa-info-circle me-2"></i>
                            <small>Your registered profile details are auto-filled and locked. Only District and Bank Account need to be entered.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">CNIC Number</label>
                                <input type="text" name="cnic_number" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($farmer_data['cnic']); ?>"
                                    readonly maxlength="13">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Mobile Number</label>
                                <input type="text" name="mobile_number" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($farmer_data['phone']); ?>"
                                    readonly maxlength="11">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" name="full_name" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars(ucwords($farmer_data['name'])); ?>"
                                    readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Father Name</label>
                                <input type="text" name="father_name" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars(ucwords($farmer_data['father_name'])); ?>"
                                    readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Complete Address</label>
                            <input type="text" name="address" class="form-control bg-light"
                                value="<?php echo htmlspecialchars($farmer_data['address'] . ', ' . $farmer_data['city']); ?>"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label  fw-bold">District / Tehsil</label>
                            <input type="text" name="district" class="form-control" required
                                value="<?php echo htmlspecialchars($farmer_data['city']); ?>"
                                placeholder="e.g., Faisalabad, Multan, Kasur">
                        </div>
                        <div class="mb-5">
                            <label class="form-label  fw-bold">Bank Account Number (For Payout)</label>
                            <input type="text" name="bank_account" class="form-control" required placeholder="Required for Claim Indemnity">
                        </div>

                        <h4 class="text-primary border-bottom pb-2 mb-4">3. Land & Crop Information</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label  fw-bold">Area to be Insured (Acres/Kanals)</label>
                                <input type="number" step="0.1" name="insured_area" id="insured_area" class="form-control"
                                    value="<?php echo $farmer_remaining > 0 ? round($farmer_remaining, 1) : ''; ?>"
                                    placeholder="e.g., 12.5" required min="1">
                                <div class="form-text text-<?= $farmer_remaining > 0 ? 'muted' : 'danger' ?>">
                                    Registered land: <strong><?= $farmer_field_size ?> acres</strong> —
                                    Already insured: <strong><?= number_format($farmer_used_area, 1) ?> acres</strong> —
                                    Remaining: <strong><?= number_format(max($farmer_remaining, 0), 1) ?> acres</strong>
                                </div>
                                <div id="area_cap_error" class="text-danger small mt-1" style="display:none;"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label  fw-bold">Primary Crop Insured</label>
                                <select name="crop_insured" class="form-select" required>
                                    <option value="">-- Select Crop --</option>
                                    <option value="Wheat">Wheat</option>
                                    <option value="Rice">Rice</option>
                                    <!-- <option value="Cotton">Cotton</option>
                                    <option value="Sugarcane">Sugarcane</option> -->
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label  fw-bold">Sowing Date</label>
                                <input type="date" name="sowing_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label  fw-bold">Irrigation Type</label>
                            <select name="irrigation_type" class="form-select" required>
                                <option value="">-- Select Irrigation Type --</option>
                                <option value="Canal/River">Canal/River Irrigated</option>
                                <option value="Tube-well">Tube-well Irrigated</option>
                                <option value="Rainfed (Barani)">Rainfed (Barani) - High Risk</option>
                            </select>
                        </div>

                        <h4 class="text-primary border-bottom pb-2 mb-4">4. Yield & Risk Factors</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Total Expected Yield (Maunds)</label>
                                <input type="number" step="1" name="expected_yield" id="expected_yield"
                                       class="form-control" placeholder="e.g. 80 for 2 acres of wheat" required min="1">
                                <div id="yield_hint" class="form-text text-muted"></div>
                                <div id="yield_error" class="text-danger small mt-1" style="display:none;"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label  fw-bold">Historical Yield Data (Last 3 Years)</label>
                                <input type="text" name="historical_yield" class="form-control" placeholder="e.g., 40, 45, 38" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label  fw-bold">Seed Type/Variety</label>
                                <input type="text" name="seed_variety" class="form-control" placeholder="e.g., Sawera 24, Super Basmati">
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="certified_seed" value="1" id="cert">
                            <label class="form-check-label" for="cert">Using Certified Seed Variety</label>
                        </div>

                        <button type="submit" name="submit_application" class="btn btn-primary w-100 py-3">Submit Application</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateSelectColor(sel) {
    if (sel.value === '') {
        sel.classList.add('select-placeholder');
    } else {
        sel.classList.remove('select-placeholder');
    }
}

function updateDateColor(inp) {
    if (!inp.value) {
        inp.classList.add('date-empty');
    } else {
        inp.classList.remove('date-empty');
    }
}

document.querySelectorAll('select').forEach(function(sel) {
    updateSelectColor(sel);
    sel.addEventListener('change', function() { updateSelectColor(this); });
});

document.querySelectorAll('input[type="date"]').forEach(function(inp) {
    updateDateColor(inp);
    inp.addEventListener('change', function() { updateDateColor(this); });
});

// ---- Yield validation ----
const MAX_PER_ACRE = { Wheat: 60, Rice: 90 };

const areaInput   = document.querySelector('input[name="insured_area"]');
const cropSelect  = document.querySelector('select[name="crop_insured"]');
const yieldInput  = document.getElementById('expected_yield');
const yieldHint   = document.getElementById('yield_hint');
const yieldError  = document.getElementById('yield_error');

function updateYieldGuide() {
    const area = parseFloat(areaInput?.value) || 0;
    const crop = cropSelect?.value || '';
    const maxPerAcre = MAX_PER_ACRE[crop] || null;

    if (area > 0 && maxPerAcre) {
        const maxTotal = area * maxPerAcre;
        const typicalMin = area * (crop === 'Wheat' ? 30 : 50);
        yieldHint.textContent =
            `For ${area} acres of ${crop}: typical ${typicalMin}–${maxTotal} maunds total. Max realistic: ${maxTotal} maunds.`;
        validateYield();
    } else {
        yieldHint.textContent = 'Select crop and area first to see expected yield range.';
        yieldError.style.display = 'none';
        yieldInput.setCustomValidity('');
    }
}

function validateYield() {
    const area = parseFloat(areaInput?.value) || 0;
    const crop = cropSelect?.value || '';
    const val  = parseFloat(yieldInput.value) || 0;
    const maxPerAcre = MAX_PER_ACRE[crop] || null;

    if (!maxPerAcre || area <= 0 || val <= 0) {
        yieldError.style.display = 'none';
        yieldInput.setCustomValidity('');
        return;
    }

    const maxTotal = area * maxPerAcre;
    if (val > maxTotal) {
        yieldError.textContent =
            `${val} maunds is unrealistic for ${area} acres of ${crop}. Max is ${maxTotal} maunds (${maxPerAcre} maunds/acre).`;
        yieldError.style.display = 'block';
        yieldInput.setCustomValidity('Unrealistic yield entered.');
    } else {
        yieldError.style.display = 'none';
        yieldInput.setCustomValidity('');
    }
}

areaInput?.addEventListener('input', updateYieldGuide);
cropSelect?.addEventListener('change', updateYieldGuide);
yieldInput?.addEventListener('input', validateYield);

// ---- Land area cap validation ----
const areaCapInput = document.getElementById('insured_area');
const areaCapError = document.getElementById('area_cap_error');
const remainingAcres = <?= max($farmer_remaining, 0) ?>;

if (areaCapInput) {
    areaCapInput.addEventListener('input', function () {
        const val = parseFloat(this.value) || 0;
        if (remainingAcres > 0 && val > remainingAcres) {
            areaCapError.textContent = `You only have ${remainingAcres.toFixed(1)} acres available to insure. Please enter ${remainingAcres.toFixed(1)} or less.`;
            areaCapError.style.display = 'block';
            this.setCustomValidity('Exceeds available land area.');
        } else {
            areaCapError.style.display = 'none';
            this.setCustomValidity('');
        }
    });
}

// ---- Season → Crop filter ----
const seasonSelect = document.querySelector('select[name="policy_duration"]');
const SEASON_CROPS = { 'Rabi': ['Wheat'], 'Kharif': ['Rice'] };

function filterCrops() {
    const season  = seasonSelect ? seasonSelect.value : '';
    const allowed = SEASON_CROPS[season] || [];
    Array.from(cropSelect.options).forEach(opt => {
        if (!opt.value) return;
        opt.hidden   = allowed.length > 0 && !allowed.includes(opt.value);
        opt.disabled = allowed.length > 0 && !allowed.includes(opt.value);
    });
    if (allowed.length === 1) {
        cropSelect.value = allowed[0];
        cropSelect.dispatchEvent(new Event('change'));
    } else if (season === '') {
        cropSelect.value = '';
    }
}
seasonSelect?.addEventListener('change', filterCrops);
filterCrops();
</script>

<?php include("./include/footer.php"); ?>