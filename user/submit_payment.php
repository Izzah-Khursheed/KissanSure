<?php
session_start();
include('./include/connection.php');

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit();
}

include('./include/navbar.php');

if (!isset($_GET['app_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid request.</div></div>";
    include('./include/footer.php');
    exit();
}

$app_id = (int)$_GET['app_id'];

// Fetch application to verify it belongs to the logged-in farmer and is payable
$sql = "
SELECT fa.*, ip.plan_name, ip.base_premium_rate, ip.coverage_level
FROM farmer_applications fa
JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
WHERE fa.application_id = $app_id
";
$result = mysqli_query($conn, $sql);
$app    = mysqli_fetch_assoc($result);

if (!$app) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Application not found.</div></div>";
    include('./include/footer.php');
    exit();
}

// Only allow payment if premium has been calculated and payment is Pending or Rejected
$payable = in_array($app['status'], ['Pending Payment']) &&
           in_array($app['payment_status'], ['Pending', 'Rejected']);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $payable) {
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $payment_date   = trim($_POST['payment_date'] ?? '');

    if (empty($transaction_id) || empty($payment_date)) {
        $error = 'Please fill in all fields.';
    } elseif (empty($_FILES['payment_proof']['name'])) {
        $error = 'Please upload your payment screenshot.';
    } else {
        $file      = $_FILES['payment_proof'];
        $allowed   = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
        $max_size  = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file['type'], $allowed)) {
            $error = 'Only JPG, PNG, WEBP, or PDF files are accepted.';
        } elseif ($file['size'] > $max_size) {
            $error = 'File size must be under 5 MB.';
        } else {
            $upload_dir = __DIR__ . '/uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'pay_' . $app_id . '_' . time() . '.' . $ext;
            $dest     = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $tid   = mysqli_real_escape_string($conn, $transaction_id);
                $pdate = mysqli_real_escape_string($conn, $payment_date);
                $fname = mysqli_real_escape_string($conn, $filename);

                mysqli_query($conn, "
                    UPDATE farmer_applications SET
                        payment_proof  = '$fname',
                        transaction_id = '$tid',
                        payment_date   = '$pdate',
                        payment_status = 'Submitted',
                        status         = 'Payment Submitted'
                    WHERE application_id = $app_id
                ");
                $success = 'Payment proof submitted successfully. Admin will verify it shortly.';
                $payable = false;
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fa fa-upload me-2"></i>Upload Premium Payment Proof</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                        <a href="view_application_plan.php" class="btn btn-primary w-100">
                            Back to My Applications
                        </a>
                    <?php elseif (!$payable): ?>
                        <div class="alert alert-warning">
                            Payment cannot be submitted for this application at this stage.
                        </div>
                        <a href="view_application_plan.php" class="btn btn-secondary w-100">
                            Back to My Applications
                        </a>
                    <?php else: ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <!-- Invoice summary -->
                        <div class="bg-light rounded p-3 mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Application</span>
                                <strong>#CROP-<?= str_pad($app['application_id'], 5, '0', STR_PAD_LEFT); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Plan</span>
                                <strong><?= htmlspecialchars($app['plan_name']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted fw-bold">Premium Due</span>
                                <strong class="text-success fs-5">PKR <?= number_format($app['final_premium'], 2); ?></strong>
                            </div>
                        </div>

                        <div class="alert alert-info small mb-4">
                            <strong>Payment Accounts:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Bank (HBL): <strong>0123-4567890-001</strong></li>
                                <li>JazzCash: <strong>0301-1234567</strong></li>
                                <li>EasyPaisa: <strong>0315-7654321</strong></li>
                            </ul>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Transaction / Reference ID</label>
                                <input type="text" name="transaction_id" class="form-control"
                                       placeholder="e.g. TXN-1234567" required
                                       value="<?= htmlspecialchars($_POST['transaction_id'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control"
                                       max="<?= date('Y-m-d'); ?>" required
                                       value="<?= htmlspecialchars($_POST['payment_date'] ?? ''); ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Payment Screenshot / Receipt</label>
                                <input type="file" name="payment_proof" class="form-control"
                                       accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                <div class="form-text">Accepted: JPG, PNG, WEBP, PDF — Max 5 MB</div>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="fa fa-paper-plane me-2"></i> Submit Payment Proof
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="view_application_invoice.php?id=<?= $app_id; ?>" class="text-muted small">
                    &larr; Back to Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<?php include('./include/footer.php'); ?>
