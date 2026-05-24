<?php

include("./include/connection.php"); 
include("./include/header.php"); 
include("./include/sidebar.php"); 

if (isset($_POST["submit_application"])) {
    
    // Farmer and Contact Details
    $cnic_number = mysqli_real_escape_string($conn, $_POST["cnic_number"]);
    $full_name = mysqli_real_escape_string($conn, $_POST["full_name"]);
    $father_name = mysqli_real_escape_string($conn, $_POST["father_name"]);
    $mobile_number = mysqli_real_escape_string($conn, $_POST["mobile_number"]);
    $district = mysqli_real_escape_string($conn, $_POST["district"]);
    $bank_account = mysqli_real_escape_string($conn, $_POST["bank_account"]);

    // Insurance Plan Selection
    $plan_id = (int)$_POST["plan_id"]; // Foreign Key to the plan details
    $farmer_id = (int)$_POST["farmer_id"];
    $policy_duration = mysqli_real_escape_string($conn, $_POST["policy_duration"]);

    // Land and Geographic Details
    $insured_area = (float)$_POST["insured_area"]; // Acres/Kanals
    $irrigation_type = mysqli_real_escape_string($conn, $_POST["irrigation_type"]);

    //  Crop and Yield History Details
    $crop_insured = mysqli_real_escape_string($conn, $_POST["crop_insured"]);
    $expected_yield = (float)$_POST["expected_yield"];
    $historical_yield = mysqli_real_escape_string($conn, $_POST["historical_yield"]);
    $seed_variety = mysqli_real_escape_string($conn, $_POST["seed_variety"]);
    $sowing_date = mysqli_real_escape_string($conn, $_POST["sowing_date"]);

    //  AI & Risk Mitigation Factors 
    $certified_seed = isset($_POST['certified_seed']) ? 1 : 0;
    
    // Total land area cap check
    $farmer_info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT field_size FROM register_farmer WHERE farmerid = '$farmer_id'"
    ));
    $field_size  = $farmer_info ? (float)$farmer_info['field_size'] : 0;
    $used_now    = (float)mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(insured_area), 0) AS used
         FROM farmer_applications WHERE farmer_id = '$farmer_id' AND status NOT IN ('Rejected')"
    ))['used'];
    $remaining_now = $field_size - $used_now;
    if ($field_size > 0 && $insured_area > $remaining_now) {
        echo "<script>showFlash('Cannot insure {$insured_area} acres. This farmer has {$field_size} total acres, {$used_now} already insured. Only " . number_format(max($remaining_now, 0), 1) . " acres remaining.', 'warning');</script>";
        goto end_submit;
    }

    // Server-side yield validation
    $max_per_acre = ['Wheat' => 60, 'Rice' => 90];
    if (isset($max_per_acre[$crop_insured]) && $insured_area > 0) {
        $max_yield = $insured_area * $max_per_acre[$crop_insured];
        if ($expected_yield > $max_yield) {
            echo "<script>showFlash('Expected yield ({$expected_yield} maunds) is unrealistic for {$insured_area} acres of {$crop_insured}. Max is {$max_yield} maunds.', 'warning');</script>";
            goto end_submit;
        }
    }

    // Duplicate plan check: same farmer + crop + season
    $dup = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS cnt FROM farmer_applications
         WHERE farmer_id = '$farmer_id' AND crop_insured = '$crop_insured'
           AND policy_duration = '$policy_duration' AND status NOT IN ('Rejected')"
    ));
    if ($dup['cnt'] > 0) {
        echo "<script>showFlash('This farmer already has an active or pending application for {$crop_insured} ({$policy_duration} season).', 'warning');</script>";
        goto end_submit;
    }

    $insert_sql = "INSERT INTO farmer_applications (
        plan_id, policy_duration,
        cnic_number, full_name, father_name, mobile_number, district, bank_account,
        insured_area, irrigation_type,
        crop_insured, expected_yield, historical_yield, seed_variety, sowing_date,
        certified_seed, farmer_id, status
    ) VALUES (
        $plan_id, '$policy_duration',
        '$cnic_number', '$full_name', '$father_name', '$mobile_number', '$district', '$bank_account',
        $insured_area, '$irrigation_type',
        '$crop_insured', $expected_yield, '$historical_yield', '$seed_variety', '$sowing_date',
        $certified_seed, $farmer_id, 'Pending Payment'
    )";
    $runn = mysqli_query($conn, $insert_sql);
    if ($runn) {
        $new_app_id = mysqli_insert_id($conn);

        // Auto-calculate premium
        $plan_row  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT base_premium_rate, coverage_level FROM insurance_plan WHERE plan_id = '$plan_id'"
        ));
        $rate_row  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT price_per_maund FROM crop_market_rates WHERE crop_name = '$crop_insured' LIMIT 1"
        ));
        $market_rate           = $rate_row ? (float)$rate_row['price_per_maund'] : 3200.00;
        $base_premium_rate     = (float)$plan_row['base_premium_rate'];
        $coverage_level        = (float)$plan_row['coverage_level'];
        $sum_insured           = round($expected_yield * $market_rate, 2);
        $coverage_amount       = round($sum_insured * ($coverage_level / 100), 2);
        $final_premium         = round($coverage_amount * ($base_premium_rate / 100), 2);
        $guaranteed_production = round($expected_yield * ($coverage_level / 100), 2);

        mysqli_query($conn, "
            UPDATE farmer_applications SET
                sum_insured           = $sum_insured,
                coverage_amount       = $coverage_amount,
                final_premium         = $final_premium,
                guaranteed_production = $guaranteed_production
            WHERE application_id = $new_app_id
        ");

        echo "<script>showFlash('Application submitted and premium calculated successfully.', 'success');</script>";
    } else {
        echo "<script>showFlash('Error submitting application: " . addslashes(mysqli_error($conn)) . "', 'danger');</script>";
    }
    end_submit:;
    
}


$plans_result = mysqli_query($conn, "SELECT plan_id, plan_name, coverage_level, base_premium_rate FROM insurance_plan WHERE plan_status = 'Active'");
$farmers_result = mysqli_query($conn, "SELECT farmerid, name, father_name, phone, cnic, address, city, field_size FROM register_farmer ORDER BY name ASC");
$farmers_array = [];
while ($f = mysqli_fetch_assoc($farmers_result)) {
    $used_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(insured_area), 0) AS used
         FROM farmer_applications WHERE farmer_id = '" . $f['farmerid'] . "' AND status NOT IN ('Rejected')"
    ));
    $f['field_size']      = (float)$f['field_size'];
    $f['used_area']       = (float)$used_row['used'];
    $f['remaining_area']  = max($f['field_size'] - $f['used_area'], 0);
    $farmers_array[$f['farmerid']] = $f;
}
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

<div class="container mt-5 mb-5">
    <div class="card shadow-lg">
        <div class="card-header bg-success text-white text-center">
            <h2> Crop Insurance Application </h2>
            <p class="mb-0">Please fill out the details accurately to calculate your premium.</p>
        </div>
        <div class="card-body p-4">
            
            <form method="post" enctype="multipart/form-data"> 

            <h4 class="mt-2 mb-3 text-success border-bottom pb-2">Select Farmer</h4>
            <div class="mb-4">
            <label class="form-label fw-bold">Registered Farmer</label>

            <select name="farmer_id" id="farmer_select" class="form-select" required>
            <option value="">-- Select Registered Farmer --</option>
            <?php foreach ($farmers_array as $fid => $f): ?>
            <option value="<?= $fid ?>">
                <?= htmlspecialchars(ucwords($f['name'])) ?> (CNIC: <?= htmlspecialchars($f['cnic']) ?>)
            </option>
            <?php endforeach; ?>
            </select>
            </div>
                <h4 class="mt-2 mb-3 text-success border-bottom pb-2">1. Select Insurance Plan</h4>
                <div class="mb-4">
                    <label class="form-label fw-bold">Choose Your Protection Plan</label>
                    <select name="plan_id" class="form-select" required>
                        <option value="">-- Select Basic, Standard, or Premium --</option>
                        <?php 
                        if (mysqli_num_rows($plans_result) > 0) {
                            while ($plan = mysqli_fetch_assoc($plans_result)) {
                                echo "<option value='{$plan['plan_id']}'>
                                        {$plan['plan_name']} 
                                        (Coverage: {$plan['coverage_level']}%, Rate: {$plan['base_premium_rate']}%)
                                      </option>";
                            }
                        } else {
                             echo "<option value=''>No Active Plans Available (Admin needs to create plans)</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Policy Duration (Season)</label>
                    <select name="policy_duration" class="form-select" required>
                        <option value="">-- Select Season --</option>
                        <option value="Kharif">Kharif Season (Summer Crops)</option>
                        <option value="Rabi">Rabi Season (Winter Crops)</option>
                    </select>
                </div>


                <h4 class="mt-5 mb-3 text-success border-bottom pb-2">2. Kissan Personal Details</h4>

                <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <small>Select a registered farmer above — CNIC, name, father name, and mobile will be auto-filled from their registration record.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label  fw-bold">CNIC Number (13 Digits with dashes)</label>
                        <input type="text" id="cnic_number" name="cnic_number" class="form-control" placeholder="e.g., 12345-1234567-1"
                        required maxlength="15" pattern="\d{5}-\d{7}-\d{1}" title="Enter 13 digit CNIC number with dashes">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label  fw-bold">Mobile Number (11 digit phone number with Dashes)</label>
                        <input type="text" id="mobile_number" name="mobile_number" class="form-control" placeholder="e.g., 03XX-XXXXXXX"
                           required maxlength="12" pattern="\d{4}-\d{7}" title="Enter 11 digit phone number">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label  fw-bold">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label  fw-bold">Father Name</label>
                        <input type="text" id="father_name" name="father_name" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Complete Address</label>
                    <input type="text" id="address_field" name="address" class="form-control bg-light"
                        placeholder="Auto-filled from farmer registration" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label  fw-bold">District / Tehsil</label>
                    <input type="text" id="district_field" name="district" class="form-control" placeholder="e.g., Faisalabad, Multan, Kasur" required>
                </div>

                <div class="mb-4">
                    <label class="form-label  fw-bold">Bank Account Number (For Payout)</label>
                    <input type="text" name="bank_account" class="form-control" placeholder="Required for Claim Indemnity" required>
                </div>

                <h4 class="mt-5 mb-3 text-success border-bottom pb-2">3. Land & Crop Information</h4>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label  fw-bold">Area to be Insured (Acres/Kanals)</label>
                        <input type="number" step="0.1" name="insured_area" id="insured_area"
                               class="form-control" placeholder="e.g., 12.5" required min="1">
                        <div id="area_cap_hint" class="form-text text-muted"></div>
                        <div id="area_cap_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label  fw-bold">Primary Crop Insured</label>
                        <select name="crop_insured" id="crop_insured" class="form-select" required>
                            <option value="">-- Select Crop --</option>
                            <option value="Wheat">Wheat</option>
                            <option value="Rice">Rice</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label  fw-bold">Sowing Date (Approximate)</label>
                        <input type="date" name="sowing_date" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label  fw-bold">Irrigation Type</label>
                        <select name="irrigation_type" class="form-select" required>
                            <option value="">-- Select Irrigation Type --</option>
                            <option value="Canal/River">Canal/River Irrigated</option>
                            <option value="Tube-well">Tube-well Irrigated</option>
                            <option value="Rainfed (Barani)">Rainfed (Barani) - High Risk</option>
                        </select>
                    </div>
                </div>

                <h4 class="mt-5 mb-3 text-success border-bottom pb-2">4. Yield History & Risk Factors</h4>
                
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
                        <input type="text" name="historical_yield" class="form-control" placeholder="e.g., 40, 45, 38 (separated by commas)" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label  fw-bold">Seed Type/Variety</label>
                        <input type="text" name="seed_variety" class="form-control" placeholder="e.g., Sawera 24, Super Basmati">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label  fw-bold">Risk Mitigation Discounts (Check if applicable)</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="certified_seed" value="1" id="certified_seed">
                        <label class="form-check-label" for="certified_seed">
                            Using Certified Seed Variety
                        </label>
                    </div>
                  
                </div>

                <hr class="my-5">
                <button type="submit" name="submit_application" class="btn btn-success w-100 btn-lg">
                    Submit Application
                </button>

            </form>

        </div>
    </div>
</div>
<script>
const farmersData = <?= json_encode($farmers_array, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const autoFields = ['cnic_number', 'full_name', 'father_name', 'mobile_number'];

function toTitleCase(str) {
    return str.replace(/\b\w/g, function(c) { return c.toUpperCase(); });
}

function formatCnic(raw) {
    var d = raw.replace(/\D/g, '');
    if (d.length === 13) return d.substr(0, 5) + '-' + d.substr(5, 7) + '-' + d.substr(12, 1);
    return raw;
}

function formatPhone(raw) {
    var d = raw.replace(/\D/g, '');
    if (d.length === 11) return d.substr(0, 4) + '-' + d.substr(4, 7);
    return raw;
}

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

// ---- Yield validation (same as user apply_plan.php) ----
const MAX_PER_ACRE = { Wheat: 60, Rice: 90 };

const areaInput  = document.querySelector('input[name="insured_area"]');
const cropSelect = document.getElementById('crop_insured');
const yieldInput = document.getElementById('expected_yield');
const yieldHint  = document.getElementById('yield_hint');
const yieldError = document.getElementById('yield_error');

function updateYieldGuide() {
    const area = parseFloat(areaInput?.value) || 0;
    const crop = cropSelect?.value || '';
    const maxPerAcre = MAX_PER_ACRE[crop] || null;
    if (area > 0 && maxPerAcre) {
        const maxTotal  = area * maxPerAcre;
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

document.getElementById('farmer_select').addEventListener('change', function () {
    var id = this.value;
    if (!id || !farmersData[id]) {
        autoFields.forEach(function (fid) {
            var el = document.getElementById(fid);
            el.value = '';
            el.removeAttribute('readonly');
            el.classList.remove('bg-light');
        });
        document.getElementById('district_field').value = '';
        document.getElementById('address_field').value = '';
        return;
    }
    var f = farmersData[id];
    document.getElementById('cnic_number').value    = formatCnic(f.cnic);
    document.getElementById('full_name').value      = toTitleCase(f.name);
    document.getElementById('father_name').value    = toTitleCase(f.father_name);
    document.getElementById('mobile_number').value  = formatPhone(f.phone);
    document.getElementById('address_field').value  = f.address + ', ' + toTitleCase(f.city);
    document.getElementById('district_field').value = toTitleCase(f.city);

    // Lock auto-filled fields to prevent accidental edits
    autoFields.forEach(function (fid) {
        var el = document.getElementById(fid);
        el.setAttribute('readonly', true);
        el.classList.add('bg-light');
    });

    // Show remaining insurable area
    const areaCapHint  = document.getElementById('area_cap_hint');
    const areaCapError = document.getElementById('area_cap_error');
    const areaInput2   = document.getElementById('insured_area');
    const remaining    = parseFloat(f.remaining_area) || 0;
    if (f.field_size > 0) {
        areaCapHint.innerHTML =
            `Registered land: <strong>${f.field_size} acres</strong> &mdash;
             Already insured: <strong>${parseFloat(f.used_area).toFixed(1)} acres</strong> &mdash;
             Remaining: <strong>${remaining.toFixed(1)} acres</strong>`;
        areaCapHint.className = remaining > 0 ? 'form-text text-muted' : 'form-text text-danger';
    } else {
        areaCapHint.textContent = '';
    }
    areaCapError.style.display = 'none';
    areaInput2.setCustomValidity('');

    // Live validation on area input
    areaInput2.oninput = function () {
        const val = parseFloat(this.value) || 0;
        if (remaining > 0 && val > remaining) {
            areaCapError.textContent = `Only ${remaining.toFixed(1)} acres available. Please enter ${remaining.toFixed(1)} or less.`;
            areaCapError.style.display = 'block';
            this.setCustomValidity('Exceeds available land area.');
        } else {
            areaCapError.style.display = 'none';
            this.setCustomValidity('');
        }
    };
});
</script>

<?php
include("./include/footer.php");
 ?>