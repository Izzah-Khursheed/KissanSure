<?php
session_start();
include("./include/connection.php");

if (!isset($_SESSION['reset_farmer_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$farmer_id   = (int)$_SESSION['reset_farmer_id'];
$farmer_name = htmlspecialchars(ucwords($_SESSION['reset_farmer_name'] ?? ''));
$error       = '';

if (isset($_POST['reset'])) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
              !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*()\-_=+\[\]{};:\'",.<>?\/\\\\|]/', $password)) {
        $error = "Password does not meet the requirements below.";
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE register_farmer SET password='$hashed' WHERE farmerid=$farmer_id");
        unset($_SESSION['reset_farmer_id'], $_SESSION['reset_farmer_name']);
        header("Location: login.php?reset=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" href="../logo.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — KissanSure</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body {
    background: linear-gradient(135deg, #28a745, #198754);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.card {
    width: 100%;
    max-width: 430px;
    border: none;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}
.card-body { padding: 40px; }
.form-control { height: 45px; border-radius: 8px; }
.btn-reset    { height: 45px; border-radius: 8px; font-weight: 600; }
</style>
</head>
<body>

<div class="card">
    <div class="card-body">

        <div class="text-center mb-4">
            <i class="fas fa-key fa-3x text-success mb-3"></i>
            <h3 class="fw-bold">Reset Password</h3>
            <p class="text-muted small">Hello <strong><?= $farmer_name ?></strong>, set your new password below.</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <div class="small"><?= htmlspecialchars($error) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="resetPassword"
                           class="form-control" placeholder="Enter new password" required minlength="8">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('resetPassword', this)" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <!-- Strength Bar -->
                <div class="mt-2" style="height:6px; border-radius:4px; background:#e9ecef; overflow:hidden;">
                    <div id="strengthBar" style="height:100%; width:0%; border-radius:4px; transition:all 0.3s;"></div>
                </div>
                <small id="strengthText" class="fw-semibold"></small>

                <!-- Requirements -->
                <div class="mt-2 row g-1" style="font-size:12px;">
                    <div class="col-6"><span id="req-len"   class="text-danger"><i class="fas fa-times-circle me-1"></i>8+ characters</span></div>
                    <div class="col-6"><span id="req-upper" class="text-danger"><i class="fas fa-times-circle me-1"></i>Uppercase letter</span></div>
                    <div class="col-6"><span id="req-num"   class="text-danger"><i class="fas fa-times-circle me-1"></i>One number</span></div>
                    <div class="col-6"><span id="req-spec"  class="text-danger"><i class="fas fa-times-circle me-1"></i>Special character</span></div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Confirm New Password</label>
                <div class="input-group">
                    <input type="password" name="confirm" id="resetConfirm"
                           class="form-control" placeholder="Re-enter new password" required minlength="8">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('resetConfirm', this)" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small id="matchText" class="fw-semibold"></small>
            </div>

            <button type="submit" name="reset" class="btn btn-success w-100 btn-reset">
                <i class="fas fa-save me-1"></i> Save New Password
            </button>
        </form>

        <div class="text-center mt-3">
            <small><a href="login.php" class="text-success fw-semibold">Back to Login</a></small>
        </div>

    </div>
</div>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

(function () {
    const pw       = document.getElementById('resetPassword');
    const bar      = document.getElementById('strengthBar');
    const txt      = document.getElementById('strengthText');
    const confirm  = document.getElementById('resetConfirm');
    const matchTxt = document.getElementById('matchText');

    const reqs = [
        { id: 'req-len',   label: '8+ characters',    fn: v => v.length >= 8 },
        { id: 'req-upper', label: 'Uppercase letter',  fn: v => /[A-Z]/.test(v) },
        { id: 'req-num',   label: 'One number',        fn: v => /[0-9]/.test(v) },
        { id: 'req-spec',  label: 'Special character', fn: v => /[!@#$%^&*()\-_=+\[\]{};:'",.<>?\/\\|]/.test(v) }
    ];

    const colors = ['#e9ecef', '#dc3545', '#fd7e14', '#ffc107', '#20c997'];
    const labels = ['',        'Weak',    'Fair',    'Good',    'Strong'];

    pw.addEventListener('input', function () {
        const v = this.value;
        let score = 0;
        reqs.forEach(r => {
            const met = r.fn(v);
            if (met) score++;
            const el = document.getElementById(r.id);
            el.className = met ? 'text-success' : 'text-danger';
            el.innerHTML = `<i class="fas ${met ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i>${r.label}`;
        });
        bar.style.width      = (score * 25) + '%';
        bar.style.background = colors[score];
        txt.textContent      = v.length ? labels[score] : '';
        txt.style.color      = colors[score];
        checkMatch();
    });

    function checkMatch() {
        if (!confirm.value) { matchTxt.textContent = ''; return; }
        const ok = pw.value === confirm.value;
        matchTxt.textContent = ok ? '✓ Passwords match' : '✗ Passwords do not match';
        matchTxt.style.color = ok ? '#20c997' : '#dc3545';
    }
    confirm.addEventListener('input', checkMatch);
})();
</script>

</body>
</html>
