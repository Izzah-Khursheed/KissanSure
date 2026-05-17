 <!-- Footer Start -->
    <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container">
            <div class="row g-5 py-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Our Office</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>University of Agriculture, Faisalabad, Pakistan</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>kissansure@gmail.com</p>
                    <!-- <div class="d-flex pt-3">
                        <a class="btn btn-square btn-primary me-2" href="#!"><i class="fab fa-x-twitter"></i></a>
                        <a class="btn btn-square btn-primary me-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-primary me-2" href="#!"><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-square btn-primary me-2" href="#!"><i class="fab fa-linkedin-in"></i></a>
                    </div> -->
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Quick Links</h4>
                    <a class="btn btn-link" href="farmer_register.php">Register Now</a>
                    <a class="btn btn-link" href="index.php#about">About Us</a>
                    <a class="btn btn-link" href="insurance_plans.php">Insurance Plans</a>
                    <a class="btn btn-link" href="index.php#team">Our Team</a>
                    <a class="btn btn-link" href="index.php#contact">Contact Us</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Insurance Plans</h4>
                    <?php
                    $footer_plans = [
                        ['label' => 'Apply Basic',    'keyword' => 'basic'],
                        ['label' => 'Apply Standard', 'keyword' => 'standard'],
                        ['label' => 'Apply Premium',  'keyword' => 'premium'],
                    ];
                    foreach ($footer_plans as $fp):
                        $fp_result = mysqli_query($conn, "SELECT plan_id FROM insurance_plan WHERE LOWER(plan_name) LIKE '%" . $fp['keyword'] . "%' LIMIT 1");
                        $fp_row = $fp_result ? mysqli_fetch_assoc($fp_result) : null;
                        $fp_href = $fp_row ? "apply_plan.php?plan_id=" . $fp_row['plan_id'] : "insurance_plans.php";
                    ?>
                    <a class="btn btn-link" href="<?php echo $fp_href; ?>"><?php echo $fp['label']; ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-3 col-md-6 text-center">
                    <h1 class="display-5 text-primary m-0 mb-4">KissanSure</h1>
                    <img src="/KissanSure/logo.png" alt="KissanSure Logo" class="img-fluid" style="max-width: 150px;">
                </div>
            </div>
            <div class="copyright pt-5">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="fw-semi-bold" href="index.php">KissanSure 2026</a>, All Right Reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#!" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>