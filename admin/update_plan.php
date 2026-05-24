<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

$pid = $_GET['pid'];

$sql = "SELECT * FROM insurance_plan WHERE plan_id='$pid'";
$result = mysqli_query($conn, $sql);
$plan = mysqli_fetch_assoc($result);

if (!$plan) {
    echo "<script>showFlash('Plan not found.', 'danger'); setTimeout(function(){ window.location='view_plan.php'; }, 1500);</script>";
}

// Convert CSV crops to array
$selected_crops = explode(",", $plan["applicable_crops"]);

if (isset($_POST["update"])) {

    $plan_name = $_POST["plan_name"];
    $plan_status = $_POST["plan_status"];
    $applicable_crops = implode(",", $_POST["applicable_crops"]);
    $description = $_POST["description"];

    $base_premium_rate = $_POST["base_premium_rate"];
    $coverage_level = $_POST["coverage_level"];
    $deductible_rate = $_POST["deductible_rate"];
    $coverage_type = $_POST["coverage_type"];
    $unit_structure = $_POST["unit_structure"];

    $update_sql = "UPDATE insurance_plan SET
        plan_name='$plan_name',
        plan_status='$plan_status',
        applicable_crops='$applicable_crops',
        description='$description',
        base_premium_rate='$base_premium_rate',
        coverage_level='$coverage_level',
        deductible_rate='$deductible_rate',
        coverage_type='$coverage_type',
        unit_structure='$unit_structure'
        WHERE plan_id='$pid'";

    if (mysqli_query($conn, $update_sql)) {
        echo "<script>showFlash('Plan updated successfully!', 'success'); setTimeout(function(){ window.location='view_plan.php'; }, 1500);</script>";
    } else {
        echo "<script>showFlash('Update failed. Please try again.', 'danger');</script>";
    }
}
?>

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-success text-white text-center">
            <h2>Update Insurance Plan</h2>
        </div>

        <div class="card-body">
            <form method="post">

                <h4 class="mt-4 mb-3 text-primary">1. General Plan Details</h4>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Plan Name</label>
                        <input type="text" name="plan_name" class="form-control"
                               value="<?= $plan['plan_name'] ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="plan_status" class="form-select">
                            <option <?= $plan['plan_status']=='Active'?'selected':'' ?>>Active</option>
                            <option <?= $plan['plan_status']=='Draft'?'selected':'' ?>>Draft</option>
                            <option <?= $plan['plan_status']=='Inactive'?'selected':'' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <label class="form-label fw-bold">Applicable Crops</label>
                <select name="applicable_crops[]" class="form-select" multiple>

                    <?php
                    $all_crops = ["Wheat", "Rice", "Maize", "Cotton", "Soybean", "Other"];

                    foreach ($all_crops as $crop) {
                        $selected = in_array($crop, $selected_crops) ? "selected" : "";
                        echo "<option value='$crop' $selected>$crop</option>";
                    }
                    ?>
                </select>

                <div class="mb-3 mt-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control"><?= $plan['description'] ?></textarea>
                </div>

                <h4 class="mt-5 mb-3 text-primary">2. Financial Details</h4>

                <div class="row">
                    <div class="col-md-4">
                        <label>Premium Rate (%)</label>
                        <input type="number" name="base_premium_rate" class="form-control"
                               value="<?= $plan['base_premium_rate'] ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label>Coverage Level (%)</label>
                        <input type="number" name="coverage_level" class="form-control"
                               value="<?= $plan['coverage_level'] ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label>Deductible Rate (%)</label>
                        <input type="number" name="deductible_rate" class="form-control"
                               value="<?= $plan['deductible_rate'] ?>" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label>Coverage Type</label>
                        <select name="coverage_type" class="form-select">
                            <option <?= $plan['coverage_type']=='Yield Loss Only'?'selected':'' ?>>Yield Loss Only</option>
                            <option <?= $plan['coverage_type']=='Yield + Revenue Loss'?'selected':'' ?>>Yield + Revenue Loss</option>
                            <option <?= $plan['coverage_type']=='Yield + Revenue + Quality Loss'?'selected':'' ?>>Yield + Revenue + Quality Loss</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>Unit Structure</label>
                        <select name="unit_structure" class="form-select">
                            <option <?= $plan['unit_structure']=='Basic Unit'?'selected':'' ?>>Basic Unit</option>
                            <option <?= $plan['unit_structure']=='Optional Unit'?'selected':'' ?>>Optional Unit</option>
                            <option <?= $plan['unit_structure']=='Enterprise Unit'?'selected':'' ?>>Enterprise Unit</option>
                        </select>
                    </div>
                </div>

                <button class="btn btn-success w-100 mt-4" name="update">
                    Update Insurance Plan
                </button>

            </form>
        </div>
    </div>
</div>

<?php include("./include/footer.php"); ?>
