<?php
include("./include/connection.php");

$reg_error   = '';
$reg_success = '';

if (isset($_POST["submit"])) {

    $name         = strtolower(mysqli_real_escape_string($conn, $_POST["name"]));
    $father_name  = mysqli_real_escape_string($conn, $_POST["father_name"]);
    $phone        = mysqli_real_escape_string($conn, $_POST["phone"]);
    $cnic         = mysqli_real_escape_string($conn, $_POST["cnic"]);
    $address      = mysqli_real_escape_string($conn, $_POST["address"]);
    $city         = mysqli_real_escape_string($conn, $_POST["city"]);
    $field_size   = (float)$_POST["field_size"];
    $email        = mysqli_real_escape_string($conn, $_POST["email"]);
    $password     = $_POST["password"];
    $confirm      = $_POST["confirm"];

    if ($password !== $confirm) {
        $reg_error = "Passwords do not match!";
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
              !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*()\-_=+\[\]{};:\'",.<>?\/\\\\|]/', $password)) {
        $reg_error = "Password must be at least 8 characters and include an uppercase letter, a number, and a special character.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $checkrunn = mysqli_query($conn, "SELECT farmerid FROM register_farmer WHERE phone='$phone' OR cnic='$cnic'");

        if (mysqli_num_rows($checkrunn) > 0) {
            $reg_error = "Phone or CNIC already registered!";
        } else {
            $sql = "INSERT INTO register_farmer
                (name, father_name, phone, cnic, address, city, field_size, email, password)
                VALUES
                ('$name','$father_name','$phone','$cnic','$address','$city','$field_size','$email','$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $reg_error = "Registration failed. Please try again.";
            }
        }
    }
}

include('./include/navbar.php');
?>
  
<!-- Farmer Registration Start -->
<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5 align-items-center">

            <!-- Left Side Image -->
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.2s">
                <div class="about-img">
                    <img class="img-fluid w-100 rounded" src="../2.jfif" alt="Farmer Registration">
                </div>
            </div>

            <!-- Right Side Form -->
            <div class="col-lg-6">
                <p class="section-title bg-white text-start text-primary pe-3">
                    Farmer Registration
                </p>
                <h1 class="display-6 mb-4 wow fadeIn" data-wow-delay="0.2s">
                    Create Your Account
                </h1>
                <p class="mb-4 wow fadeIn" data-wow-delay="0.3s">
                    Join our agricultural insurance system and protect your crops with secure and reliable coverage.
                </p>

               <?php if (!empty($reg_error)): ?>
                   <div class="alert alert-danger"><?= htmlspecialchars($reg_error) ?></div>
               <?php endif; ?>

               <form method="post" class="wow fadeIn" data-wow-delay="0.4s">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control"
                            placeholder="e.g., Muhammad Ali"
                            required pattern="[A-Za-z\s]+">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Father Name</label>
                        <input type="text" name="father_name" class="form-control"
                            placeholder="e.g., Muhammad Akbar"
                            required pattern="[A-Za-z\s]+">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                            placeholder="03001234567"
                            required maxlength="11" pattern="\d{11}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">CNIC</label>
                        <input type="text" name="cnic" class="form-control"
                            placeholder="1234512345671"
                            required maxlength="13" pattern="\d{13}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email (Optional)</label>
                        <input type="email" name="email" class="form-control"
                            placeholder="example@email.com">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Address</label>
                        <input type="text" name="address" class="form-control"
                            placeholder="Street, Area, Village"
                            required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">City</label>
                        <input type="text" name="city" class="form-control"
                            placeholder="e.g., Lahore"
                            required pattern="[A-Za-z\s]+">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Total Field Size (Acres)</label>
                        <input type="number" name="field_size" class="form-control"
                            placeholder="e.g., 12.5"
                            required min="0.1" step="0.1">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="regPassword"
                                class="form-control"
                                placeholder="Enter Password"
                                required minlength="8">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('regPassword', this)" tabindex="-1">
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

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm" id="regConfirm"
                                class="form-control"
                                placeholder="Re-enter Password"
                                required minlength="8">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('regConfirm', this)" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="matchText" class="fw-semibold"></small>
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" name="submit"
                                class="btn btn-primary py-3 px-5 w-100">
                            Register Now
                        </button>
                    </div>

                </div>

            </form>
            </div>

        </div>
    </div>
</div>
<!-- Farmer Registration End -->

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
    const pw      = document.getElementById('regPassword');
    const bar     = document.getElementById('strengthBar');
    const txt     = document.getElementById('strengthText');
    const confirm = document.getElementById('regConfirm');
    const matchTxt= document.getElementById('matchText');

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

<?php
include ('./include/footer.php');
?>