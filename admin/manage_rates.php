<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

$success = '';
$error   = '';

// Handle rate update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate_id'], $_POST['price_per_maund'])) {
    $rate_id        = (int)$_POST['rate_id'];
    $price_per_maund = (float)$_POST['price_per_maund'];

    if ($price_per_maund <= 0) {
        $error = 'Price must be greater than zero.';
    } else {
        $sql = "UPDATE crop_market_rates SET price_per_maund = $price_per_maund WHERE rate_id = $rate_id";
        if (mysqli_query($conn, $sql)) {
            $success = 'Market rate updated successfully.';
        } else {
            $error = 'Update failed: ' . mysqli_error($conn);
        }
    }
}

$rates = mysqli_query($conn, "SELECT * FROM crop_market_rates ORDER BY crop_name ASC");
?>

<div class="container mt-5">
    <h2 class="mb-1">Crop Market Rates</h2>
    <p class="text-muted mb-4">These rates are used to calculate the Sum Insured for farmer applications. Update them to reflect current market prices.</p>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-4">
    <?php while ($row = mysqli_fetch_assoc($rates)): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
                    <i class="fa fa-seedling"></i>
                    <strong><?php echo htmlspecialchars($row['crop_name']); ?></strong>
                </div>
                <div class="card-body">
                    <p class="mb-1 text-muted small">Current Rate</p>
                    <h4 class="text-success fw-bold mb-3">PKR <?php echo number_format($row['price_per_maund'], 2); ?> / Maund</h4>
                    <p class="text-muted small mb-3">Last updated: <?php echo date('d M Y, h:i A', strtotime($row['updated_at'])); ?></p>

                    <form method="POST" action="">
                        <input type="hidden" name="rate_id" value="<?php echo $row['rate_id']; ?>">
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input type="number" name="price_per_maund" class="form-control"
                                   value="<?php echo $row['price_per_maund']; ?>"
                                   step="0.01" min="1" required>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                        <small class="text-muted">Price per Maund in PKR</small>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

    <div class="card mt-4 border-0 bg-light">
        <div class="card-body">
            <h6 class="fw-bold mb-2"><i class="fa fa-info-circle text-primary me-2"></i>How Rates Are Used</h6>
            <p class="small text-muted mb-0">
                <strong>Sum Insured (SI)</strong> = Insured Area &times; Expected Yield &times; Market Rate per Maund<br>
                <strong>Coverage Amount</strong> = SI &times; Plan Coverage %<br>
                <strong>Premium Due</strong> = Coverage Amount &times; Plan Premium Rate %
            </p>
        </div>
    </div>
</div>

<?php include("./include/footer.php"); ?>
