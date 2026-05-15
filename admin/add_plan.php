<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

if (isset($_POST["submit"])) {

    // ---  Plan Details ---
    $plan_name = mysqli_real_escape_string($conn, $_POST["plan_name"]);
    $plan_status = mysqli_real_escape_string($conn, $_POST["plan_status"]);
    $applicable_crops = mysqli_real_escape_string($conn, implode(",", $_POST["applicable_crops"])); // Convert array to string
    $description = mysqli_real_escape_string($conn, $_POST["description"]);

    // --- Financial & Risk Details ---
    $base_premium_rate = $_POST["base_premium_rate"];
    $coverage_level = $_POST["coverage_level"];
    $deductible_rate = $_POST["deductible_rate"];
    $coverage_type = mysqli_real_escape_string($conn, $_POST["coverage_type"]);
    $unit_structure = mysqli_real_escape_string($conn, $_POST["unit_structure"]);


    $sql = "INSERT INTO insurance_plan 
            (plan_name, plan_status, applicable_crops, description, base_premium_rate, coverage_level, deductible_rate, coverage_type, unit_structure)
            VALUES (
                '$plan_name', 
                '$plan_status', 
                '$applicable_crops', 
                '$description', 
                '$base_premium_rate', 
                '$coverage_level', 
                '$deductible_rate', 
                '$coverage_type', 
                '$unit_structure'
            )";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Insurance Plan Added Successfully');</script>";
    } else {
        
        echo "<script>alert('Error Adding Plan: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h2>Add Crop Insurance Plan</h2>
        </div>
        <div class="card-body">
            
            <form method="post">

                <h4 class="mt-4 mb-3 text-primary">1. General Plan Details</h4>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Plan Name</label>
                        <input type="text" name="plan_name" class="form-control" placeholder="e.g., Standard Shield" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Plan Status</label>
                        <select name="plan_status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Draft">Draft</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Applicable Crop Type(s)</label>
                    <small class="text-muted d-block"> (Select all that apply for this plan)</small>
                    <select name="applicable_crops[]" class="form-select" multiple required>
                        <option value="Wheat">Wheat</option>
                        <option value="Rice">Rice</option>
                        <option value="Maize">Maize (Corn)</option>
                        <option value="Cotton">Cotton</option>
                        <option value="Soybean">Soybean</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Plan Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief summary of coverage and key benefits."></textarea>
                </div>

                <h4 class="mt-5 mb-3 text-primary">2. Financial & Risk Parameters</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Base Premium Rate (%)</label>
                        <small class="text-muted d-block"> (% of Sum Insured before subsidies)</small>
                        <input type="number" step="0.01" name="base_premium_rate" class="form-control" placeholder="1.50" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Coverage Level (%)</label>
                        <small class="text-muted d-block"> (Guaranteed Production/Revenue level)</small>
                        <input type="number" name="coverage_level" class="form-control" placeholder="50 / 75 / 85" required min="1" max="100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Deductible Rate (%)</label>
                        <small class="text-muted d-block"> (% loss farmer bears)</small>
                        <input type="number" name="deductible_rate" class="form-control" placeholder="20 / 10 / 5" required min="0" max="100">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Type of Coverage</label>
                        <select name="coverage_type" class="form-select" required>
                            <option value="">Select Coverage Type</option>
                            <option value="Yield Loss Only">Yield Loss Only</option>
                            <option value="Yield + Revenue Loss">Yield + Revenue Loss</option>
                            <option value="Yield + Revenue + Quality Loss">Yield + Revenue + Quality Loss</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Unit Structure</label>
                        <select name="unit_structure" class="form-select" required>
                            <option value="">Select Unit Structure</option>
                            <option value="Basic Unit">Basic Unit (Higher Risk/Premium)</option>
                            <option value="Optional Unit">Optional Unit</option>
                            <option value="Enterprise Unit">Enterprise Unit (Lower Risk/Premium)</option>
                        </select>
                    </div>
                </div>

                
                <hr class="my-4">
                <button type="submit" name="submit" class="btn btn-success w-100 btn-lg">
                     Finalize and Add Insurance Plan
                </button>

            </form>

        </div>
    </div>
</div>

<?php include("./include/footer.php"); ?>